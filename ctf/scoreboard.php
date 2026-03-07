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
        default => 'desconocida',
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

    $header = fgetcsv($handle);
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
    while (($data = fgetcsv($handle)) !== false) {
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
    <title>Scoreboard CTF</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --ink: #122033;
            --muted: #6b7a90;
            --accent: #1f7a8c;
            --accent-2: #ff8a5b;
            --line: #d9e0ea;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: ui-sans-serif, -apple-system, Segoe UI, sans-serif;
            color: var(--ink);
            background: radial-gradient(circle at top right, #ecf6ff 0%, var(--bg) 55%);
        }

        main {
            max-width: 980px;
            margin: 2rem auto;
            padding: 0 1rem 2rem;
        }

        h1 { margin: 0 0 0.4rem; }

        .meta {
            color: var(--muted);
            margin-bottom: 1rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        #chart {
            display: grid;
            gap: 0.7rem;
        }

        .bar-row {
            display: grid;
            gap: 0.35rem;
        }

        .bar-label {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.95rem;
        }

        .bar-track {
            width: 100%;
            height: 22px;
            background: #eef2f8;
            border-radius: 999px;
            overflow: hidden;
            border: 1px solid #e1e7f0;
        }

        .bar-fill {
            height: 100%;
            min-width: 2px;
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            border-radius: 999px;
            transition: width 260ms ease;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        th, td {
            text-align: left;
            padding: 0.55rem;
            border-bottom: 1px solid var(--line);
        }

        th { color: var(--muted); font-weight: 600; }

        .empty {
            color: var(--muted);
            margin: 0;
        }
    </style>
</head>
<body>
    <main>
        <h1>Scoreboard</h1>
        <p class="meta">Datos en tiempo real desde <code>retos.csv</code>. Refresco automatico cada 5s.</p>

        <section class="card">
            <h2>Grafico de puntos</h2>
            <div id="chart"></div>
            <p id="empty-msg" class="empty" hidden>No hay equipos todavia.</p>
        </section>

        <section class="card">
            <h2>Detalle</h2>
            <table>
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th>Dificultad</th>
                        <th>Puntos</th>
                    </tr>
                </thead>
                <tbody id="table-body"></tbody>
            </table>
        </section>
    </main>

    <script>
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
                barRow.className = 'bar-row';
                barRow.innerHTML =
                    `<div class="bar-label"><span>${row.team} (${row.difficulty})</span><strong>${points} pts</strong></div>` +
                    `<div class="bar-track"><div class="bar-fill" style="width:${width}%"></div></div>`;
                chart.appendChild(barRow);

                const tr = document.createElement('tr');
                tr.innerHTML =
                    `<td>${row.team}</td>` +
                    `<td>${row.difficulty} (${row.difficulty_id})</td>` +
                    `<td>${points}</td>`;
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
</body>
</html>
