<?php
declare(strict_types=1);

require __DIR__ . '/csrf.php';

$csrfToken = getCtfCsrfToken();

function listChallengeSlugs(string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }

    $entries = scandir($directory);
    if ($entries === false) {
        return [];
    }

    $slugs = [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        if (is_dir($directory . DIRECTORY_SEPARATOR . $entry)) {
            $slugs[] = $entry;
        }
    }

    sort($slugs, SORT_NATURAL | SORT_FLAG_CASE);
    return $slugs;
}

function loadUserChallengeState(string $csvPath, string $nombreB64): array
{
    if ($nombreB64 === '' || !is_file($csvPath)) {
        return [];
    }

    $handle = fopen($csvPath, 'rb');
    if ($handle === false) {
        return [];
    }

    if (!flock($handle, LOCK_SH)) {
        fclose($handle);
        return [];
    }

    $header = fgetcsv($handle, 0, ',', '"', '');
    if (!is_array($header) || $header === []) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return [];
    }

    $idxNombre = array_search('nombre_b64', $header, true);
    if ($idxNombre === false) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return [];
    }

    $state = [];
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        if (!is_array($row) || $row === [] || $row === [null]) {
            continue;
        }

        if ((string)($row[$idxNombre] ?? '') !== $nombreB64) {
            continue;
        }

        foreach ($header as $index => $column) {
            if (!is_string($column) || $column === '' || in_array($column, ['nombre_b64', 'password_hash', 'dif', 'puntos'], true)) {
                continue;
            }

            $state[$column] = (string)($row[$index] ?? '0');
        }

        break;
    }

    flock($handle, LOCK_UN);
    fclose($handle);

    return $state;
}

$usuarioCookie = (string)($_COOKIE['usuario_b64'] ?? '');
$tieneUsuario = $usuarioCookie !== '';
$estadoRetosUsuario = loadUserChallengeState(__DIR__ . '/retos.csv', $usuarioCookie);

$retosPorDificultad = [
        'Fácil' => [
                'basePath' => '/facil',
                'slugs' => listChallengeSlugs(__DIR__ . '/facil'),
        ],
        'Medio' => [
                'basePath' => '/medio',
                'slugs' => listChallengeSlugs(__DIR__ . '/medio'),
        ],
        'Difícil' => [
                'basePath' => '/dificil',
                'slugs' => listChallengeSlugs(__DIR__ . '/dificil'),
        ],
];

// -------------------------- Scoreboard count -----------------------------
function decodeTeamName(string $nameB64): string
{
    $decoded = base64_decode($nameB64, true);
    if ($decoded === false || trim($decoded) === '') {
        return $nameB64;
    }

    return $decoded;
}

function difficultyLabel(int $difficultyId): string
{
    return match ($difficultyId) {
        1 => 'facil',
        2 => 'medio',
        3 => 'dificil',
        default => 'unknown',
    };
}

function loadScoreRows(string $csvPath): array
{
    if (!is_file($csvPath)) {
        return [];
    }

    $handle = fopen($csvPath, 'rb');
    if ($handle === false) {
        return [];
    }

    if (!flock($handle, LOCK_SH)) {
        fclose($handle);
        return [];
    }

    $header = fgetcsv($handle, 0, ',', '"', '');
    if (!is_array($header)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return [];
    }

    $idxName = array_search('nombre_b64', $header, true);
    $idxDiff = array_search('dif', $header, true);
    $idxPoints = array_search('puntos', $header, true);
    if ($idxName === false || $idxDiff === false || $idxPoints === false) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return [];
    }

    $rows = [];
    while (($data = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        if (!is_array($data) || $data === [] || $data === [null]) {
            continue;
        }

        $nameB64 = (string)($data[$idxName] ?? '');
        if (trim($nameB64) === '') {
            continue;
        }

        $difficultyId = (int)($data[$idxDiff] ?? 0);
        $points = (int)($data[$idxPoints] ?? 0);

        $rows[] = [
                'team' => decodeTeamName($nameB64),
                'difficulty_id' => $difficultyId,
                'difficulty' => difficultyLabel($difficultyId),
                'points' => $points,
        ];
    }

    flock($handle, LOCK_UN);
    fclose($handle);

    usort($rows, static function (array $a, array $b): int {
        $byPoints = $b['points'] <=> $a['points'];
        if ($byPoints !== 0) {
            return $byPoints;
        }

        return strcmp((string)$a['team'], (string)$b['team']);
    });

    return $rows;
}

$csvPath = __DIR__ . '/retos.csv';

if (isset($_GET['data'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode([
            'ok' => true,
            'generated_at' => date('c'),
            'rows' => loadScoreRows($csvPath),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=VT323&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css"/>
    <title>CTF - Landing</title>
    <link rel="stylesheet" type="text/css" href="/css/style.css"
</head>
<body class="body-bg ">
<div class="container align-items-stretch">
    <div class="row align-center g-4 mb-4">
        <br>
        <h1 class="text-center" style="font-family: 'VT323', monospace; font-size: 6rem;">Landing CTF</h1>
        <br>
    </div>
    <div class="row g-3">
        <div class="col-12 col-md-6 gx-5 container">
            <div class="row">
                <h2 class="h3">Grafico de puntos</h2>
                <div id="chart"></div>
                <p id="empty-msg" class="empty" hidden>No hay equipos todavia.</p>
            </div>

            <div class="row mb-4">
                <h2 class="h3">Detalle</h2>
                <table class="table-dark ">
                    <thead>
                    <tr class="fw-bold">
                        <th>Equipo</th>
                        <th>Dificultad</th>
                        <th>Puntos</th>
                    </tr>
                    </thead>
                    <tbody id="table-body" class="table-bordered "></tbody>
                </table>
            </div>
        </div>

        <div class="col-12 col-md-6 gx-5">
            <div class="row mb-4 gy-4">
                <div class="border border-secondary rounded-3 p-4 h-100">
                    <h2 class="fw-bold mb-2">Introducir usuario</h2>
                    <form id="introducir-form" class="d-flex flex-column gap-2">
                        <label for="introducir-usuario" class="form-label mb-0">Usuario</label>
                        <input id="introducir-usuario" name="usuario" type="text"
                               class="form-control bg-dark text-white border-secondary"
                               required autocomplete="off">
                        <label for="introducir-password" class="form-label mb-0">Contraseña</label>
                        <input id="introducir-password" name="password" type="password"
                               class="form-control bg-dark text-white border-secondary"
                               required autocomplete="current-password">
                        <button type="submit" class="btn btn-outline-light mt-2">Introducir usuario</button>
                    </form>
                </div>
            </div>

            <div class="row mb-4 ">
                <div class="border border-secondary rounded-3 p-4 h-100">
                    <h2 class="fw-bold mb-2">Crear usuario</h2>
                    <form id="crear-form" class="d-flex flex-column gap-2">
                        <label for="crear-usuario" class="form-label mb-0">Usuario</label>
                        <input id="crear-usuario" name="usuario" type="text"
                               class="form-control bg-dark text-white border-secondary"
                               required autocomplete="off">
                        <label for="crear-password" class="form-label mb-0">Contraseña</label>
                        <input id="crear-password" name="password" type="password"
                               class="form-control bg-dark text-white border-secondary"
                               required autocomplete="new-password">
                        <label for="crear-dificultad" class="form-label mb-0">Dificultad</label>
                        <select id="crear-dificultad" name="dificultad"
                                class="form-select bg-dark text-white border-secondary" required>
                            <option value="1">Fácil</option>
                            <option value="2">Medio</option>
                            <option value="3">Difícil</option>
                        </select>
                        <button type="submit" class="btn btn-outline-light mt-2">Crear usuario</button>
                    </form>
                </div>
            </div>

            <div class="row mb-4">
                <div class="border border-secondary rounded-3 p-4 h-100">
                    <?php if ($tieneUsuario): ?>
                        <h2 class="fw-bold mb-2">Menú de retos</h2>
                        <div class="retos-grid">
                            <?php foreach ($retosPorDificultad as $dificultad => $config): ?>
                                <div>
                                    <h6 class="text-uppercase text-muted fw-bold mb-1">
                                        <?php echo htmlspecialchars($dificultad, ENT_QUOTES, 'UTF-8'); ?>
                                    </h6>
                                    <?php if ($config['slugs'] === []): ?>
                                        <p class="small text-muted">Sin retos.</p>
                                    <?php else: ?>
                                        <ul class="list-unstyled mb-2">
                                            <?php foreach ($config['slugs'] as $slug): ?>
                                                <?php
                                                $retoKey = $slug;
                                                if (!array_key_exists($retoKey, $estadoRetosUsuario)) {
                                                    $retoKey = preg_replace('/\s*\(.*\)$/', '', $slug) ?? $slug;
                                                }
                                                $retoHecho = ($estadoRetosUsuario[$retoKey] ?? '0') === '1';
                                                ?>
                                                <li>
                                                    <a class="<?php echo $retoHecho ? 'text-success' : 'text-danger'; ?> text-decoration-none"
                                                       href="<?php echo htmlspecialchars($config['basePath'] . '/' . $slug, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>
                                                        <?php echo $retoHecho ? ' ✓' : ''; ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted fst-italic">Introduce un usuario para ver los retos.</p>
                    <?php endif; ?>
                </div>
            </div>


            <p id="status" class="status mt-3 text-center" aria-live="polite"></p>
        </div>
    </div>
</div>
</body>
<script>
    const BACKEND_URL = '/backend.php';
    const CSRF_TOKEN = <?php echo json_encode($csrfToken, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    async function postBackend(payload) {
        const response = await fetch(BACKEND_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN,
            },
            credentials: 'include',
            body: JSON.stringify(payload),
        });

        let data = {};
        try {
            data = await response.json();
        } catch (error) {
            throw new Error('backend murió');
        }

        if (!response.ok || !data.ok) {
            throw new Error(data.error || 'backend murió');
        }

        return data;
    }

    function setStatus(message, isError = false) {
        const status = document.getElementById('status');
        status.textContent = message;
        status.style.color = isError ? '#b00020' : '#0a6b0a';
    }

    document.getElementById('introducir-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const username = document.getElementById('introducir-usuario').value.trim();
        const password = document.getElementById('introducir-password').value;

        if (!username) {
            setStatus('Introduce un usuario válido.', true);
            return;
        }

        if (!password) {
            setStatus('Introduce una contraseña válida.', true);
            return;
        }

        try {
            await postBackend({action: 'save_name', nombre: username, password});
            setStatus('cookie wena :)');
            window.location.reload();
        } catch (error) {
            setStatus(error.message, true);
        }
    });

    document.getElementById('crear-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const username = document.getElementById('crear-usuario').value.trim();
        const password = document.getElementById('crear-password').value;
        const dificultadId = Number.parseInt(document.getElementById('crear-dificultad').value, 10);

        if (!username) {
            setStatus('Introduce un usuario válido.', true);
            return;
        }

        if (!password) {
            setStatus('Introduce una contraseña válida.', true);
            return;
        }

        if (![1, 2, 3].includes(dificultadId)) {
            setStatus('Selecciona una dificultad válida.', true);
            return;
        }

        try {
            await postBackend({
                action: 'crear_equipo',
                nombre: username,
                dificultad_id: dificultadId,
                password,
            });

            await postBackend({action: 'save_name', nombre: username, password});
            setStatus('usuario y cookie wenos :)');
            window.location.reload();
        } catch (error) {
            setStatus(error.message, true);
        }
    });

    // ------------------------------------------- Scoreboard ------------------------------------------------------
    const chart = document.getElementById('chart');
    const tableBody = document.getElementById('table-body');
    const emptyMsg = document.getElementById('empty-msg');

    function renderRows(rows) {
        chart.innerHTML = '';
        tableBody.innerHTML = '';

        if (!rows.length) {
            emptyMsg.hidden = false;
            return;
        }

        emptyMsg.hidden = true;
        const maxPoints = Math.max(...rows.map((row) => Number(row.points) || 0), 1);

        for (const row of rows) {
            const points = Number(row.points) || 0;
            const width = Math.max((points / maxPoints) * 100, 1);

            const barRow = document.createElement('div');
            barRow.className = 'row align-items-center mb-2 p-2 border border-secondary rounded-3';

            const colLabel = document.createElement('div');
            colLabel.className = 'col-4 fw-bold text-white';
            colLabel.appendChild(document.createTextNode(row.team + ' '));
            const diffBadge = document.createElement('span');
            diffBadge.className = 'badge bg-secondary';
            diffBadge.textContent = row.difficulty;
            colLabel.appendChild(diffBadge);

            const colBar = document.createElement('div');
            colBar.className = 'col';
            const progressDiv = document.createElement('div');
            progressDiv.className = 'progress';
            progressDiv.style.height = '22px';
            const progressBar = document.createElement('div');
            progressBar.className = 'progress-bar bg-success';
            progressBar.setAttribute('role', 'progressbar');
            progressBar.style.width = width + '%';
            progressBar.textContent = points + ' pts';
            progressDiv.appendChild(progressBar);
            colBar.appendChild(progressDiv);

            barRow.appendChild(colLabel);
            barRow.appendChild(colBar);
            chart.appendChild(barRow);

            const tr = document.createElement('tr');
            tr.className = 'align-middle';

            const td1 = document.createElement('td');
            td1.className = 'fw-bold text-white mb-1';
            td1.textContent = row.team;

            const td2 = document.createElement('td');
            const badge2 = document.createElement('span');
            badge2.className = 'badge bg-secondary mb-1';
            badge2.textContent = row.difficulty + ' (' + String(row.difficulty_id) + ')';
            td2.appendChild(badge2);

            const td3 = document.createElement('td');
            const badge3 = document.createElement('span');
            badge3.className = 'badge bg-success mb-1';
            badge3.textContent = points + ' pts';
            td3.appendChild(badge3);

            tr.appendChild(td1);
            tr.appendChild(td2);
            tr.appendChild(td3);
            tableBody.appendChild(tr);
        }
    }

    async function refreshScoreboard() {
        try {
            const response = await fetch('scoreboard.php?data=1&t=' + Date.now(), {
                cache: 'no-store',
            });
            const data = await response.json();
            if (!response.ok || !data.ok || !Array.isArray(data.rows)) {
                throw new Error('Formato invalido de datos');
            }

            renderRows(data.rows);
        } catch (error) {
            console.error('No se pudo refrescar el scoreboard', error);
        }
    }

    refreshScoreboard();
    setInterval(refreshScoreboard, 5000);
</script>
</html>
