<?php
declare(strict_types=1);

$sourceDir = __DIR__ . '/epstein_files';
$realSourceDir = realpath($sourceDir);

if ($realSourceDir === false || !is_dir($realSourceDir)) {
	http_response_code(404);
	header('Content-Type: text/plain; charset=UTF-8');
	echo 'No se encontro la carpeta a comprimir.';
	exit;
}

if (!class_exists('ZipArchive')) {
	http_response_code(500);
	header('Content-Type: text/plain; charset=UTF-8');
	echo 'La extension ZipArchive no esta disponible en el servidor.';
	exit;
}

$tmpZipPath = tempnam(sys_get_temp_dir(), 'epstein_files_');
if ($tmpZipPath === false) {
	http_response_code(500);
	header('Content-Type: text/plain; charset=UTF-8');
	echo 'No se pudo crear el archivo temporal.';
	exit;
}

$zip = new ZipArchive();
if ($zip->open($tmpZipPath, ZipArchive::OVERWRITE) !== true) {
	@unlink($tmpZipPath);
	http_response_code(500);
	header('Content-Type: text/plain; charset=UTF-8');
	echo 'No se pudo generar el archivo ZIP.';
	exit;
}

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($realSourceDir, FilesystemIterator::SKIP_DOTS),
	RecursiveIteratorIterator::SELF_FIRST
);

$baseName = basename($realSourceDir);

foreach ($iterator as $item) {
	$itemPath = $item->getRealPath();
	if ($itemPath === false) {
		continue;
	}

	$relativePath = substr($itemPath, strlen($realSourceDir) + 1);
	$archivePath = $baseName . '/' . str_replace('\\', '/', $relativePath);

	if ($item->isDir()) {
		$zip->addEmptyDir($archivePath);
		continue;
	}

	$zip->addFile($itemPath, $archivePath);
}

$zip->close();

$downloadName = 'epstein_files.zip';
$fileSize = filesize($tmpZipPath);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
if ($fileSize !== false) {
	header('Content-Length: ' . (string) $fileSize);
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

readfile($tmpZipPath);
@unlink($tmpZipPath);
exit;
