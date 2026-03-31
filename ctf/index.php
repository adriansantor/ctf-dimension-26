<?php
declare(strict_types=1);


$theme = [
    'primary'              => '#00ff99',
    'secondary'            => '#6c757d',
    'success'              => '#20c997',
    'warning'              => '#ffc107',
    'danger'               => '#ff4444',
    'dark'                 => '#0d0d0d',
    'body-bg'              => '#0a0f1e',
    'body-color'           => '#FFFFFF',
    'link-color'           => '#00ff99',
    'font-family-base'     => 'Fira Code',
    'font-size-base'       => '1.2rem',
    'headings-font-weight' => '700',
    'headings-color'       => '#ffffff',
    'border-radius'        => '0.25rem',
    'border-color'         => '#333333',
];

$cssOut    = __DIR__ . '/css/style.css';
$indexFile = __FILE__;

if (!file_exists($cssOut) || filemtime($indexFile) > filemtime($cssOut)) {
    require __DIR__ . '/../vendor/autoload.php';

    $scss = new \ScssPhp\ScssPhp\Compiler();
    $scss->setImportPaths([
        __DIR__ . '/scss',
        __DIR__ . '/../bootstrap-scss',
    ]);
    $scss->setOutputStyle(\ScssPhp\ScssPhp\OutputStyle::COMPRESSED);

    $vars = '';
    foreach ($theme as $k => $v) {
        $vars .= "\${$k}: {$v};\n";
    }

    $source = $vars . "\n" . file_get_contents(__DIR__ . '/scss/style.scss');

    if (!is_dir(__DIR__ . '/css')) mkdir(__DIR__ . '/css', 0755, true);
    file_put_contents($cssOut, $scss->compileString($source)->getCss());
}
require __DIR__ . '/landing.php';
