<?php
declare(strict_types=1);

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

    $header = fgetcsv($handle);
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
    while (($row = fgetcsv($handle)) !== false) {
        if (!is_array($row) || $row === [] || $row === [null]) {
            continue;
        }

        if ((string) ($row[$idxNombre] ?? '') !== $nombreB64) {
            continue;
        }

        foreach ($header as $index => $column) {
            if (!is_string($column) || $column === '' || in_array($column, ['nombre_b64', 'dif', 'puntos'], true)) {
                continue;
            }

            $state[$column] = (string) ($row[$index] ?? '0');
        }

        break;
    }

    flock($handle, LOCK_UN);
    fclose($handle);

    return $state;
}

$usuarioCookie = (string) ($_COOKIE['usuario_b64'] ?? '');
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
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CTF - Landing</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 2rem;
            max-width: 720px;
        }

        h1 {
            margin-bottom: 1.5rem;
        }

        section {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        form {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        input, button {
            padding: 0.5rem 0.75rem;
        }

        .status {
            margin-top: 1rem;
            font-weight: 600;
        }

        .retos-menu h2 {
            margin-top: 0;
        }

        .retos-grid {
            display: grid;
            gap: 1rem;
        }

        .retos-grid h3 {
            margin: 0 0 0.5rem;
        }

        .retos-grid ul {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .retos-grid li {
            margin: 0;
        }

        .challenge-link {
            display: inline-block;
            text-decoration: none;
            font-weight: 600;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            border: 1px solid #1d4f8f;
            color: #16365e;
            background: #eef5ff;
        }

        .challenge-link:hover {
            background: #ddeaff;
        }

        .challenge-link.is-done {
            background: #2f9e44;
            border-color: #2b8a3e;
            color: #fff;
        }
    </style>
</head>
<body>
    <main>
        <h1>Landing CTF</h1>

        <section>
            <h2>Scoreboard</h2>
            <p>visualiza los puntos en tiempo real</p>
            <a href="/scoreboard.php">
                <button type="button">Ver scoreboard</button>
            </a>
        </section>

        <section>
            <h2>Introducir usuario</h2>
            <p>asigna cookie <code>usuario_b64</code></p>
            <form id="introducir-form">
                <label for="introducir-usuario">Usuario</label>
                <input id="introducir-usuario" name="usuario" type="text" required autocomplete="off">
                <button type="submit">Introducir usuario</button>
            </form>
        </section>

        <section>
            <h2>Crear usuario</h2>
            <p>inicializa fila en <code>retos.csv</code> y asigna cookie</p>
            <form id="crear-form">
                <label for="crear-usuario">Usuario</label>
                <input id="crear-usuario" name="usuario" type="text" required autocomplete="off">

                <label for="crear-dificultad">Dificultad</label>
                <select id="crear-dificultad" name="dificultad" required>
                    <option value="1">facil</option>
                    <option value="2">medio</option>
                    <option value="3">dificil</option>
                </select>

                <button type="submit">Crear usuario</button>
            </form>
        </section>

        <?php if ($tieneUsuario): ?>
        <section class="retos-menu">
            <h2>Menú de retos</h2>
            <p>solo visible cuando tienes cookies nwn</p>

            <div class="retos-grid">
                <?php foreach ($retosPorDificultad as $dificultad => $config): ?>
                    <div>
                        <h3><?php echo htmlspecialchars($dificultad, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <?php if ($config['slugs'] === []): ?>
                            <p>No hay retos disponibles.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($config['slugs'] as $slug): ?>
                                    <?php
                                        $retoKey = $slug;
                                        if (!array_key_exists($retoKey, $estadoRetosUsuario)) {
                                            $retoKey = preg_replace('/\s*\(.*\)$/', '', $slug) ?? $slug;
                                        }
                                        $retoHecho = ($estadoRetosUsuario[$retoKey] ?? '0') === '1';
                                    ?>
                                    <li>
                                        <a class="challenge-link<?php echo $retoHecho ? ' is-done' : ''; ?>" href="<?php echo htmlspecialchars($config['basePath'] . '/' . $slug, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <p id="status" class="status" aria-live="polite"></p>
    </main>

    <script>
        const BACKEND_URL = '/backend.php';

        async function postBackend(payload) {
            const response = await fetch(BACKEND_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
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

            if (!username) {
                setStatus('Introduce un usuario válido.', true);
                return;
            }

            try {
                await postBackend({ action: 'save_name', nombre: username });
                setStatus('cookie wena :)');
                window.location.reload();
            } catch (error) {
                setStatus(error.message, true);
            }
        });

        document.getElementById('crear-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const username = document.getElementById('crear-usuario').value.trim();
            const dificultadId = Number.parseInt(document.getElementById('crear-dificultad').value, 10);

            if (!username) {
                setStatus('Introduce un usuario válido.', true);
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
                });

                await postBackend({ action: 'save_name', nombre: username });
                setStatus('usuario y cookie wenos :)');
                window.location.reload();
            } catch (error) {
                setStatus(error.message, true);
            }
        });
    </script>
</body>
</html>
