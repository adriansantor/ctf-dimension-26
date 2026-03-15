<?php
declare(strict_types=1);

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

        $nameB64 = (string) ($data[$idxName] ?? '');
        if (trim($nameB64) === '') {
            continue;
        }

        $difficultyId = (int) ($data[$idxDiff] ?? 0);
        $points = (int) ($data[$idxPoints] ?? 0);

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

        return strcmp((string) $a['team'], (string) $b['team']);
    });

    return $rows;
}

function loadFirstSolves(string $logPath): array
{
    if (!is_file($logPath)) {
        return [];
    }

    $handle = fopen($logPath, 'rb');
    if ($handle === false) {
        return [];
    }

    if (!flock($handle, LOCK_SH)) {
        fclose($handle);
        return [];
    }

    $firstSolvesByChallenge = [];

    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $entry = json_decode($line, true);
        if (!is_array($entry)) {
            continue;
        }

        if (($entry['event'] ?? '') !== 'submit_flag') {
            continue;
        }

        $context = $entry['context'] ?? null;
        if (!is_array($context)) {
            continue;
        }

        if (($context['result'] ?? '') !== 'accepted') {
            continue;
        }

        $challenge = trim((string) ($context['reto'] ?? ''));
        $teamB64 = trim((string) ($context['team_b64'] ?? ''));
        $timestamp = trim((string) ($entry['timestamp'] ?? ''));

        if ($challenge === '' || $teamB64 === '' || $timestamp === '') {
            continue;
        }

        $timestampUnix = strtotime($timestamp);
        if ($timestampUnix === false) {
            continue;
        }

        $existing = $firstSolvesByChallenge[$challenge] ?? null;
        if ($existing === null || $timestampUnix < $existing['timestamp_unix']) {
            $firstSolvesByChallenge[$challenge] = [
                'reto' => $challenge,
                'team' => decodeTeamName($teamB64),
                'timestamp' => $timestamp,
                'timestamp_unix' => $timestampUnix,
            ];
        }
    }

    flock($handle, LOCK_UN);
    fclose($handle);

    $firstSolves = array_values($firstSolvesByChallenge);
    usort($firstSolves, static function (array $a, array $b): int {
        $byTime = $a['timestamp_unix'] <=> $b['timestamp_unix'];
        if ($byTime !== 0) {
            return $byTime;
        }

        return strcmp((string) $a['reto'], (string) $b['reto']);
    });

    $madridTimezone = new DateTimeZone('Europe/Madrid');

    return array_map(static function (array $row) use ($madridTimezone): array {
        try {
            $madridDate = new DateTimeImmutable($row['timestamp']);
            $row['timestamp'] = $madridDate
                ->setTimezone($madridTimezone)
                ->format('d/m/Y H:i:s') . ' (Madrid)';
        } catch (Throwable) {
            // Keep original timestamp if parsing fails.
        }

        unset($row['timestamp_unix']);
        return $row;
    }, $firstSolves);
}

$csvPath = __DIR__ . '/retos.csv';
$logPath = __DIR__ . '/logs/backend.log';

if (isset($_GET['data'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode([
        'ok' => true,
        'generated_at' => date('c'),
        'rows' => loadScoreRows($csvPath),
        'first_solves' => loadFirstSolves($logPath),
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
    <title>CTF - Scoreboard</title>
</head>
<body class="body-bg ">
<div class="container align-items-stretch">
    <div class="row align-center g-4 mb-4">
        <br>
        <h1 class="text-center" style="font-family: 'VT323', monospace; font-size: 6rem;">Scoreboard CTF</h1>
        <br>
    </div>

    <div class="row g-3">
        <div class="col-12 gx-5 container">
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

            <div class="row mb-4">
                <h2 class="h3">Primeras resoluciones</h2>
                <table class="table-dark ">
                    <thead>
                    <tr class="fw-bold">
                        <th>Reto</th>
                        <th>Primer equipo</th>
                        <th>Fecha</th>
                    </tr>
                    </thead>
                    <tbody id="first-solves-body" class="table-bordered "></tbody>
                </table>
                <p id="first-solves-empty-msg" class="empty" hidden>No hay resoluciones registradas.</p>
            </div>
        </div>
    </div>
</div>

<script>
    const chart = document.getElementById('chart');
    const tableBody = document.getElementById('table-body');
    const emptyMsg = document.getElementById('empty-msg');
    const firstSolvesBody = document.getElementById('first-solves-body');
    const firstSolvesEmptyMsg = document.getElementById('first-solves-empty-msg');

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
            barRow.innerHTML =
                `<div class="col-4 fw-bold text-white">${row.team} <span class="badge bg-secondary">${row.difficulty}</span></div>` +
                `<div class="col">` +
                `<div class="progress" style="height:22px">` +
                `<div class="progress-bar bg-success" role="progressbar" style="width:${width}%">${points} pts</div>` +
                `</div>` +
                `</div>`;
            chart.appendChild(barRow);

            const tr = document.createElement('tr');
            tr.className = 'align-middle';
            tr.innerHTML =
                `<td class="fw-bold text-white mb-1">${row.team}</td>` +
                `<td><span class="badge bg-secondary mb-1">${row.difficulty} (${row.difficulty_id})</span></td>` +
                `<td><span class="badge bg-success mb-1">${points} pts</span></td>`;
            tableBody.appendChild(tr);
        }
    }

    function renderFirstSolves(firstSolves) {
        firstSolvesBody.innerHTML = '';

        if (!firstSolves.length) {
            firstSolvesEmptyMsg.hidden = false;
            return;
        }

        firstSolvesEmptyMsg.hidden = true;

        for (const solve of firstSolves) {
            const tr = document.createElement('tr');
            tr.className = 'align-middle';
            tr.innerHTML =
                `<td class="fw-bold text-white mb-1">${solve.reto}</td>` +
                `<td><span class="badge bg-primary mb-1">${solve.team}</span></td>` +
                `<td><span class="badge bg-secondary mb-1">${solve.timestamp}</span></td>`;
            firstSolvesBody.appendChild(tr);
        }
    }

    async function refreshScoreboard() {
        try {
            const response = await fetch('scoreboard.php?data=1&t=' + Date.now(), {
                cache: 'no-store',
            });
            const data = await response.json();
            if (!response.ok || !data.ok || !Array.isArray(data.rows) || !Array.isArray(data.first_solves)) {
                throw new Error('Formato invalido de datos');
            }

            renderRows(data.rows);
            renderFirstSolves(data.first_solves);
        } catch (error) {
            console.error('No se pudo refrescar el scoreboard', error);
        }
    }

    refreshScoreboard();
    setInterval(refreshScoreboard, 5000);
</script>
</body>
</html>
