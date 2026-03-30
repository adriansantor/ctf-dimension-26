<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'mensaje_cifrado';
$backendUrl = '/backend.php';
$csrfToken = getCtfCsrfToken();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reto: Cifrado césar</title>
</head>
<body>
<main>
    <h1>Mensaje cifrado</h1>
    <p style="font-size: 20px">
        Nos ha llegado este mensaje, ayudanos a descifrarlo!
    </p>
    <div style="width: 60%; margin: 0 auto;">
        <p style="font-family: monospace; white-space: pre-wrap; font-size: 300%">
       CpchQbspvJlzhy
        </p>
    </div>

    <h1>Responder pregunta</h1>
    <form id="respuesta-form">
        <label for="respuesta-input">Cuál es el contenido del mensaje?</label>
        <input id="respuesta-input" name="respuesta" type="text" required autocomplete="off" />
        <button type="submit">Submit</button>
    </form>

    <h2>Enviar flag</h2>
    <form id="flag-form">
        <label for="flag-input">Flag</label>
        <input id="flag-input" name="flag" type="text" required autocomplete="off" />
        <button type="submit">Comprobar flag</button>
    </form>
</main>

<script>
    const BACKEND_URL = <?php echo json_encode($backendUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const CHALLENGE_ID = <?php echo json_encode($challengeId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
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

    async function validarCookieUsuario() {
        await postBackend({ action: 'get_puntos' });
    }

    async function comprobarRespuesta(respuestaValue) {
        return postBackend({
            action: 'comprobar_respuesta',
            id: CHALLENGE_ID,
            respuesta: respuestaValue,
        });
    }

    async function getFlag() {
        return postBackend({
            action: 'get_flag',
            id: CHALLENGE_ID,
        });
    }

    async function submitFlag(flagValue) {
        return postBackend({
            action: 'submit_flag',
            reto: CHALLENGE_ID,
            flag: flagValue,
        });
    }

    document.getElementById('respuesta-form').addEventListener('submit', async (event) => {
        event.preventDefault();

        const input = document.getElementById('respuesta-input');
        const respuestaValue = input.value.trim();

        if (!respuestaValue) {
            alert('Introduce una respuesta.');
            return;
        }

        try {
            await validarCookieUsuario();
        } catch (error) {
            alert('cookien\'t');
            return;
        }

        try {
            const result = await comprobarRespuesta(respuestaValue);
            if (result.correcta) {
                const flagResult = await getFlag();
                alert('Respuesta correcta! Flag: ' + flagResult.flag);
                return;
            }

            alert('Respuesta incorrecta. Prueba otra vez');
        } catch (error) {
            alert('Respuesta incorrecta. Prueba otra vez');
        }
    });

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
            if (result.ya_hecha){
                alert('Ya has conseguido esta flag, no se añadiran puntos');
                return;
            }
            if (result.correcta) {
                alert('Flag correcta! +' + result.puntos_sumados + ' puntos conseguidos');
                return;
            }

            alert('Flag incorrecta');
        } catch (error) {
            alert('Flag incorrecta');
        }
    });
</script>
</body>
</html>