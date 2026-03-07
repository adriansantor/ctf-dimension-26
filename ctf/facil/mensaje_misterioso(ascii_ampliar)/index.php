<?php
declare(strict_types=1);

$challengeId = 'ascii_ampliar';
$backendUrl = '/backend.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mensaje misterioso</title>
</head>
<body>
<main>
    <h1>Mensaje misterioso</h1>
    <p style="font-size: 20px">
        Hemos recibido el siguiente mensaje. Sospechamos que contiene la flag para este desafio, pero no sabemos cuál es. Nos ayudas a descubrirlo?
    </p>
    <p style="font-family: monospace; white-space: pre-wrap; font-size: 40px">
                              __             _ _                          _ _          __
          ___ ___  _ __ ___  / /_ _ ___  ___(_|_)    __ _ _ __ ___  _ __ | (_) __ _ _ _\ \
         / __/ _ \| '__/ _ \| / _` / __|/ __| | |   / _` | '_ ` _ \| '_ \| | |/ _` | '__| |
        | (_| (_) | | |  __< < (_| \__ \ (__| | |  | (_| | | | | | | |_) | | | (_| | |   > >
         \___\___/|_|  \___|| \__,_|___/\___|_|_|___\__,_|_| |_| |_| .__/|_|_|\__,_|_|  | |
                             \_\               |_____|             |_|                 /_/
    </p>
    <form id="flag-form">
        <label for="flag-input">Flag</label>
        <input id="flag-input" name="flag" type="text" required autocomplete="off" />
        <button type="submit">Submit</button>
    </form>
</main>

<script>
    const BACKEND_URL = <?php echo json_encode($backendUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const CHALLENGE_ID = <?php echo json_encode($challengeId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

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

    async function validarCookieUsuario() {
        await postBackend({ action: 'get_puntos' });
    }

    async function submitFlag(flagValue) {
        return postBackend({
            action: 'submit_flag',
            reto: CHALLENGE_ID,
            flag: flagValue,
        });
    }

    document.getElementById('flag-form').addEventListener('submit', async (event) => {
        event.preventDefault();

        const input = document.getElementById('flag-input');
        const flagValue = input.value.trim();

        if (!flagValue) {
            alert('Introduce una flag.');
            return;
        }

        try {
            await validarCookieUsuario();
        } catch (error) {
            alert('cookien\'t');
            return;
        }

        try {
            const result = await submitFlag(flagValue);
            if (result.correcta) {
                alert('Flag correcta. Ctrl -!');
                return;
            }

            alert('Flag incorrecta, prueba otra vez');
        } catch (error) {
            alert('Flag incorrecta, prueba otra vez');
        }
    });
</script>
</body>
</html>
