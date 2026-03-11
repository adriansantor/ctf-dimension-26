<?php
declare(strict_types=1);

require __DIR__ . '/csrf.php';

const COOKIE_USUARIO_B64 = 'usuario_b64';

startCtfSession();
applyCorsHeaders();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !validateCtfCsrfToken()) {
    respond(403, ['ok' => false, 'error' => 'CSRF token inválido.']);
}

header('Content-Type: application/json; charset=utf-8');

function applyCorsHeaders(): void
{
    $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin !== '') {
        header('Vary: Origin');
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    }

    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
}

function isHttpsRequest(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

final class CtfBackend
{
    private string $csvPath;
    private string $flagsPath;
    private string $respuestasPath;
    private string $logsDir;
    private array $header = [];
    private array $challengeColumns = [];

    public function __construct(string $csvPath, string $flagsPath, string $respuestasPath, string $logsDir)
    {
        $this->csvPath = $csvPath;
        $this->flagsPath = $flagsPath;
        $this->respuestasPath = $respuestasPath;
        $this->logsDir = $logsDir;
        $this->ensureLogsDir();
        $this->loadHeader();
    }

    public function comprobarNombreExiste(string $nombre): bool
    {
        [$header, $rows] = $this->readAll();
        $nombreB64 = $this->isBase64($nombre) ? $nombre : $this->encodeName($nombre);

        foreach ($rows as $row) {
            if (($row['nombre_b64'] ?? '') === $nombreB64) {
                return true;
            }
        }

        return false;
    }

    public function existeUsuario(string $nombre): bool
    {
        return $this->comprobarNombreExiste($nombre);
    }

    public function saveName(string $nombre): void
    {
        $nombreB64 = $this->encodeName($nombre);

        if (!$this->comprobarNombreExiste($nombreB64)) {
            throw new RuntimeException('El usuario/equipo no existe.');
        }

        $this->setUsuarioCookieValue($nombreB64);
    }

    public function getName(string $nombreB64): string
    {
        return $this->decodeName($nombreB64);
    }

    public function getPuntos(string $nombre): int
    {
        $nombreB64 = $this->isBase64($nombre) ? $nombre : $this->encodeName($nombre);
        $row = $this->findRowByNameB64($nombreB64);

        return (int) ($row['puntos'] ?? 0);
    }

    public function setPuntos(string $nombre, int $dificultadId): void
    {
        $nombreB64 = $this->isBase64($nombre) ? $nombre : $this->encodeName($nombre);

        $this->updateRows(function (array $row) use ($nombreB64, $dificultadId): array {
            if (($row['nombre_b64'] ?? '') !== $nombreB64) {
                return $row;
            }

            $actuales = (int) ($row['puntos'] ?? 0);
            $row['puntos'] = (string) $this->sumarPuntosPorDificultad($actuales, $dificultadId);

            return $row;
        });
    }

    public function marcarHecho(string $nombre, string $reto): void
    {
        $nombreB64 = $this->isBase64($nombre) ? $nombre : $this->encodeName($nombre);
        $reto = $this->normalizeChallengeColumn($reto);

        $this->updateRows(function (array $row) use ($nombreB64, $reto): array {
            if (($row['nombre_b64'] ?? '') !== $nombreB64) {
                return $row;
            }

            $row[$reto] = '1';

            return $row;
        });
    }

    public function comprobarHecho(string $nombre, string $reto): bool
    {
        $nombreB64 = $this->isBase64($nombre) ? $nombre : $this->encodeName($nombre);
        $reto = $this->normalizeChallengeColumn($reto);
        $row = $this->findRowByNameB64($nombreB64);

        return ($row[$reto] ?? '0') === '1';
    }

    public function crearEquipo(string $nombre, int $dificultadId): void
    {
        $dificultadId = $this->normalizeDificultadId($dificultadId);
        $nombreB64 = $this->encodeName($nombre);

        if ($this->comprobarNombreExiste($nombreB64)) {
            throw new RuntimeException('El equipo ya existe.');
        }

        [$header, $rows] = $this->readAll();

        $newRow = [];
        foreach ($header as $column) {
            if ($column === 'nombre_b64') {
                $newRow[$column] = $nombreB64;
            } elseif ($column === 'dif') {
                $newRow[$column] = (string) $dificultadId;
            } elseif ($column === 'puntos') {
                $newRow[$column] = '0';
            } else {
                $newRow[$column] = '0';
            }
        }

        $rows[] = $newRow;
        $this->writeAll($header, $rows);
    }

    public function anadirAEquipo(string $nombreEquipoB64): void
    {
        if (!$this->comprobarNombreExiste($nombreEquipoB64)) {
            throw new RuntimeException('El equipo no existe.');
        }

        $this->setUsuarioCookieValue($nombreEquipoB64);
    }

    public function submitFlag(string $nombre, string $reto, string $flag): array
    {
        $nombreB64 = $this->isBase64($nombre) ? $nombre : $this->encodeName($nombre);
        $reto = $this->normalizeChallengeColumn($reto);
        $flag = trim($flag);

        if ($flag === '') {
            throw new InvalidArgumentException('La flag no puede estar vacía.');
        }

        if ($this->comprobarHecho($nombreB64, $reto)) {
            $this->logEvent('submit_flag', [
                'team_b64' => $nombreB64,
                'reto' => $reto,
                'result' => 'already_done',
            ]);

            return [
                'correcta' => true,
                'ya_hecha' => true,
                'puntos_sumados' => 0,
            ];
        }

        $flags = $this->loadFlags();
        if (!isset($flags[$reto])) {
            throw new RuntimeException('No hay configuración de flag para el reto indicado.');
        }

        $expectedFlag = (string) ($flags[$reto]['flag'] ?? '');
        if ($expectedFlag === '') {
            throw new RuntimeException('Flag no configurada para el reto indicado.');
        }

        $correcta = hash_equals($expectedFlag, $flag);
        if (!$correcta) {
            $this->logEvent('submit_flag', [
                'team_b64' => $nombreB64,
                'reto' => $reto,
                'result' => 'wrong_flag',
            ]);

            return [
                'correcta' => false,
                'ya_hecha' => false,
                'puntos_sumados' => 0,
            ];
        }

        $dificultadId = (int) ($flags[$reto]['dificultad_id'] ?? 0);
        $puntosSumados = $this->puntosPorDificultad($dificultadId);

        $this->marcarHecho($nombreB64, $reto);
        $this->setPuntos($nombreB64, $dificultadId);

        $this->logEvent('submit_flag', [
            'team_b64' => $nombreB64,
            'reto' => $reto,
            'result' => 'accepted',
            'dificultad_id' => $dificultadId,
            'puntos_sumados' => $puntosSumados,
        ]);

        return [
            'correcta' => true,
            'ya_hecha' => false,
            'puntos_sumados' => $puntosSumados,
        ];
    }

    public function getFlag(string $idPrueba): string
    {
        $idPrueba = trim($idPrueba);
        if ($idPrueba === '') {
            throw new InvalidArgumentException('El id de la prueba no puede estar vacío.');
        }

        $flags = $this->loadFlags();
        if (!isset($flags[$idPrueba])) {
            throw new RuntimeException('No hay flag para la prueba indicada.');
        }

        $flag = (string) ($flags[$idPrueba]['flag'] ?? '');
        if ($flag === '') {
            throw new RuntimeException('Errorin');
        }

        return $flag;
    }

    public function comprobarRespuesta(string $idPrueba, string $texto): bool
    {
        $idPrueba = trim($idPrueba);
        if ($idPrueba === '') {
            throw new InvalidArgumentException('El id de la prueba no puede estar vacío.');
        }

        $respuestaEsperada = $this->loadRespuestas()[$idPrueba] ?? null;
        if ($respuestaEsperada === null) {
            throw new RuntimeException('No hay respuesta para la prueba indicada.');
        }

        $respuestaEsperada = trim($respuestaEsperada);
        if ($respuestaEsperada === '') {
            throw new RuntimeException('Errorin'); 
        }

        return stripos($texto, $respuestaEsperada) !== false;
    }

    public function encodeName(string $nombre): string
    {
        return base64_encode(trim($nombre));
    }

    public function decodeName(string $nombreB64): string
    {
        $decoded = base64_decode($nombreB64, true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Nombre en base64 inválido.');
        }

        return $decoded;
    }

    private function sumarPuntosPorDificultad(int $puntosActuales, int $dificultadId): int
    {
        return $puntosActuales + $this->puntosPorDificultad($dificultadId);
    }

    private function puntosPorDificultad(int $dificultadId): int
    {
        return match ($dificultadId) {
            1 => 100,
            2 => 200,
            3 => 300,
            default => throw new InvalidArgumentException('dificultad_id debe ser 1 (fácil),2 (medio) o 3 (difícil).')
        };
    }

    private function normalizeDificultadId(int $dificultadId): int
    {
        if (!in_array($dificultadId, [1, 2, 3], true)) {
            throw new InvalidArgumentException('dificultad_id debe ser 1 (facil), 2 (medio) o 3 (dificil).');
        }

        return $dificultadId;
    }

    private function normalizeChallengeColumn(string $reto): string
    {
        $reto = trim($reto);
        if (!in_array($reto, $this->challengeColumns, true)) {
            throw new InvalidArgumentException('Reto no válido: ' . $reto);
        }

        return $reto;
    }

    private function findRowByNameB64(string $nombreB64): array
    {
        [, $rows] = $this->readAll();

        foreach ($rows as $row) {
            if (($row['nombre_b64'] ?? '') === $nombreB64) {
                return $row;
            }
        }

        throw new RuntimeException('Usuario/equipo no encontrado.');
    }

    private function updateRows(callable $transform): void
    {
        [$header, $rows] = $this->readAll();
        $found = false;

        foreach ($rows as $index => $row) {
            $updated = $transform($row);
            if ($updated !== $row) {
                $found = true;
            }
            $rows[$index] = $updated;
        }

        if (!$found) {
            throw new RuntimeException('No se encontró el usuario/equipo.');
        }

        $this->writeAll($header, $rows);
    }

    private function readAll(): array
    {
        if (!is_file($this->csvPath)) {
            throw new RuntimeException('No existe el CSV de retos.');
        }

        $handle = fopen($this->csvPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el CSV.');
        }

        if (!flock($handle, LOCK_SH)) {
            fclose($handle);
            throw new RuntimeException('No se pudo bloquear el CSV para lectura.');
        }

        $header = fgetcsv($handle, 0, ',', '"', '');
        if ($header === false) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw new RuntimeException('CSV vacío o inválido.');
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($data === [null] || $data === []) {
                continue;
            }

            $assoc = [];
            foreach ($header as $index => $column) {
                $assoc[$column] = $data[$index] ?? '';
            }
            $rows[] = $assoc;
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        return [$header, $rows];
    }

    private function writeAll(array $header, array $rows): void
    {
        $handle = fopen($this->csvPath, 'cb+');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el CSV para escritura.');
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new RuntimeException('No se pudo bloquear el CSV para escritura.');
        }

        ftruncate($handle, 0);
        rewind($handle);

        fputcsv($handle, $header, ',', '"', '');
        foreach ($rows as $row) {
            $line = [];
            foreach ($header as $column) {
                $line[] = $row[$column] ?? '';
            }
            fputcsv($handle, $line, ',', '"', '');
        }

        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function loadHeader(): void
    {
        [$header] = $this->readAll();
        $this->header = $header;
        $this->challengeColumns = array_values(array_filter(
            $header,
            static fn (string $column): bool => !in_array($column, ['nombre_b64', 'dif', 'puntos'], true)
        ));
    }

    private function isBase64(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === $value;
    }

    private function setUsuarioCookieValue(string $value): void
    {
        setcookie(COOKIE_USUARIO_B64, $value, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function ensureLogsDir(): void
    {
        if (is_dir($this->logsDir)) {
            return;
        }

        if (!mkdir($this->logsDir, 0775, true) && !is_dir($this->logsDir)) {
            throw new RuntimeException('No se pudo crear el directorio de logs.');
        }
    }

    private function logEvent(string $event, array $context = []): void
    {
        $line = [
            'timestamp' => date('c'),
            'event' => $event,
            'context' => $context,
        ];

        $logPath = $this->logsDir . '/backend.log';
        $encoded = json_encode($line, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return;
        }

        file_put_contents($logPath, $encoded . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function loadFlags(): array
    {
        if (!is_file($this->flagsPath)) {
            throw new RuntimeException('No existe el fichero de flags.');
        }

        $raw = file_get_contents($this->flagsPath);
        if ($raw === false || trim($raw) === '') {
            throw new RuntimeException('El fichero de flags está vacío o no se puede leer.');
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('El fichero de flags no tiene un JSON válido.');
        }

        return $decoded;
    }

    private function loadRespuestas(): array
    {
        if (!is_file($this->respuestasPath)) {
            throw new RuntimeException('No existe el fichero de respuestas.');
        }

        $raw = file_get_contents($this->respuestasPath);
        if ($raw === false || trim($raw) === '') {
            throw new RuntimeException('El fichero de respuestas está vacío o no se puede leer.');
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('El fichero de respuestas no tiene un JSON válido.');
        }

        $respuestasById = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $id = trim((string) ($entry['id_reto'] ?? ''));
            if ($id === '') {
                continue;
            }

            $respuestasById[$id] = (string) ($entry['respuesta'] ?? '');
        }

        return $respuestasById;
    }
}

function requestPayload(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }

    return $_POST;
}

function getNombreFromPayloadOrCookie(array $payload): string
{
    $fromPayload = (string) ($payload['nombre'] ?? ($payload['nombre_b64'] ?? ''));
    if ($fromPayload !== '') {
        return $fromPayload;
    }

    $fromCookie = (string) ($_COOKIE[COOKIE_USUARIO_B64] ?? '');
    return $fromCookie;
}

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$backend = new CtfBackend(
    __DIR__ . '/retos.csv',
    __DIR__ . '/private/flags.json',
    __DIR__ . '/private/respuestas.json',
    __DIR__ . '/logs'
);
$payload = requestPayload();
$action = $payload['action'] ?? '';

try {
    switch ($action) {
        case 'comprobar_nombre_existe':
            $nombre = (string) ($payload['nombre'] ?? '');
            respond(200, ['ok' => true, 'existe' => $backend->existeUsuario($nombre)]);

        case 'existe_usuario':
            $nombre = (string) ($payload['nombre'] ?? '');
            respond(200, ['ok' => true, 'existe' => $backend->existeUsuario($nombre)]);

        case 'save_name':
            $nombre = (string) ($payload['nombre'] ?? '');
            if ($nombre === '') {
                throw new InvalidArgumentException('Falta nombre.');
            }
            $backend->saveName($nombre);
            respond(200, ['ok' => true]);

        case 'get_name':
            $nombreB64 = (string) ($payload['nombre_b64'] ?? '');
            if ($nombreB64 === '') {
                throw new InvalidArgumentException('Falta nombre_b64.');
            }
            respond(200, ['ok' => true, 'nombre' => $backend->getName($nombreB64)]);

        case 'get_puntos':
            $nombre = getNombreFromPayloadOrCookie($payload);
            if ($nombre === '') {
                throw new InvalidArgumentException('Falta nombre/nombre_b64 o cookie usuario_b64.');
            }
            respond(200, ['ok' => true, 'puntos' => $backend->getPuntos($nombre)]);

        case 'marcar_hecho':
            $nombre = getNombreFromPayloadOrCookie($payload);
            $reto = (string) ($payload['reto'] ?? '');
            if ($nombre === '' || $reto === '') {
                throw new InvalidArgumentException('Faltan nombre/nombre_b64/cookie usuario_b64 o reto.');
            }
            $backend->marcarHecho($nombre, $reto);
            respond(200, ['ok' => true]);

        case 'comprobar_hecho':
            $nombre = getNombreFromPayloadOrCookie($payload);
            $reto = (string) ($payload['reto'] ?? '');
            if ($nombre === '' || $reto === '') {
                throw new InvalidArgumentException('Faltan nombre/nombre_b64/cookie usuario_b64 o reto.');
            }
            respond(200, ['ok' => true, 'hecho' => $backend->comprobarHecho($nombre, $reto)]);

        case 'crear_equipo':
            $nombre = (string) ($payload['nombre'] ?? '');
            $dificultadId = (int) ($payload['dificultad_id'] ?? 0);
            if ($nombre === '') {
                throw new InvalidArgumentException('Falta nombre.');
            }
            $backend->crearEquipo($nombre, $dificultadId);
            respond(200, ['ok' => true]);

        case 'anadir_a_equipo':
            $nombreB64 = (string) ($payload['nombre_equipo_b64'] ?? ($payload['nombre_b64'] ?? ''));
            if ($nombreB64 === '') {
                throw new InvalidArgumentException('Falta nombre_equipo_b64 o nombre_b64.');
            }
            $backend->anadirAEquipo($nombreB64);
            respond(200, ['ok' => true]);

        case 'submit_flag':
            $nombre = getNombreFromPayloadOrCookie($payload);
            $reto = (string) ($payload['reto'] ?? '');
            $flag = (string) ($payload['flag'] ?? '');
            if ($nombre === '' || $reto === '' || $flag === '') {
                throw new InvalidArgumentException('Faltan nombre/nombre_b64/cookie usuario_b64, reto o flag.');
            }

            $result = $backend->submitFlag($nombre, $reto, $flag);
            respond(200, [
                'ok' => true,
                'correcta' => $result['correcta'],
                'ya_hecha' => $result['ya_hecha'],
                'puntos_sumados' => $result['puntos_sumados'],
            ]);

        case 'get_flag':
            $idPrueba = (string) ($payload['id'] ?? ($payload['reto'] ?? ''));
            if ($idPrueba === '') {
                throw new InvalidArgumentException('Falta id o reto.');
            }

            respond(200, [
                'ok' => true,
                'flag' => $backend->getFlag($idPrueba),
            ]);

        case 'comprobar_respuesta':
            $idPrueba = (string) ($payload['id'] ?? ($payload['reto'] ?? ''));
            $respuesta = (string) ($payload['respuesta'] ?? '');
            if ($idPrueba === '') {
                throw new InvalidArgumentException('Falta id o reto.');
            }

            respond(200, [
                'ok' => true,
                'correcta' => $backend->comprobarRespuesta($idPrueba, $respuesta),
            ]);

        default:
            respond(400, ['ok' => false, 'error' => 'Acción no soportada.']);
    }
} catch (Throwable $e) {
    respond(400, ['ok' => false, 'error' => $e->getMessage()]);
}
