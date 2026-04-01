<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'mensaje_masivo';
$backendUrl = '/backend.php';
$csrfToken = getCtfCsrfToken();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=VT323&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css"/>
    <title>Mensaje misterioso</title>
</head>
<body class="body-bg">
<main class="container py-4">
    <div class="row align-center g-4 mb-4">
        <h1 class="text-center" style="font-family: 'VT323', monospace; font-size: 6rem;">Mensaje misterioso</h1>
    </div>

    <div class="row justify-content-center g-4">
        <div class="col-12 col-lg-10">
            <div class="border border-secondary rounded-3">
                <p class="mb-3 fs-5">Hemos recibido el siguiente mensaje. Sospechamos que contiene la flag para este desafio, pero no sabemos cual es.</p>
                <p class="font-monospace" style="font-family: monospace;white-space: pre-wrap; font-size: 95%; line-height: 1.25;">
             _   _            _                      _ _                                    _
            | | | |_ __   ___| |_ ___    __ _    ___(_) |__   ___ _ __    ___ ___  _ __ ___| |
            | | | | '_ \ / _ \ __/ _ \  / _` |  / __| | '_ \ / _ \ '__|  / __/ _ \| '__/ _ \ |
            | |_| | | | |  __/ ||  __/ | (_| | | (__| | |_) |  __/ |    | (_| (_) | | |  __/_|
             \___/|_| |_|\___|\__\___|  \__,_|  \___|_|_.__/ \___|_|     \___\___/|_|  \___(_)
        </p>
            </div>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-12 col-lg-5">
            <div class="border border-secondary rounded-3 p-4 h-100">
                <h2 class="h3 fw-bold mb-3">Responder pregunta</h2>
                <form id="respuesta-form" class="d-flex flex-column gap-2">
                    <label for="respuesta-input" class="form-label mb-0">Cual es el contenido del mensaje misterioso?</label>
                    <input id="respuesta-input" name="respuesta" type="text" class="form-control bg-dark text-white border-secondary" required autocomplete="off" />
                    <button type="submit" class="btn btn-outline-light mt-2">Comprobar respuesta</button>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="border border-secondary rounded-3 p-4 h-100">
                <h2 class="h3 fw-bold mb-3">Enviar flag</h2>
                <form id="flag-form" class="d-flex flex-column gap-2">
                    <label for="flag-input" class="form-label mb-0">Flag</label>
                    <input id="flag-input" name="flag" type="text" class="form-control bg-dark text-white border-secondary" required autocomplete="off" />
                    <button type="submit" class="btn btn-outline-light mt-2">Comprobar flag</button>
                </form>
            </div>
        </div>
    </div>

    <p id="status" class="status mt-3 text-center" aria-live="polite"></p>
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

    function setStatus(message, isError = false) {
        const status = document.getElementById('status');
        status.textContent = message;
        status.style.color = isError ? '#b00020' : '#0a6b0a';
    }

    document.getElementById('respuesta-form').addEventListener('submit', async (event) => {
        event.preventDefault();

        const input = document.getElementById('respuesta-input');
        const respuestaValue = input.value.trim();

        if (!respuestaValue) {
            setStatus('Introduce una respuesta.', true);
            return;
        }

        try {
            await validarCookieUsuario();
        } catch (error) {
            setStatus('cookien\'t', true);
            return;
        }

        try {
            const result = await comprobarRespuesta(respuestaValue);
            if (result.correcta) {
                const flagResult = await getFlag();
                setStatus('Respuesta correcta! Flag: ' + flagResult.flag);
                return;
            }

            setStatus('Respuesta incorrecta. Prueba otra vez', true);
        } catch (error) {
            setStatus('Respuesta incorrecta. Prueba otra vez', true);
        }
    });

    document.getElementById('flag-form').addEventListener('submit', async (event) => {
        event.preventDefault();

        const input = document.getElementById('flag-input');
        const flagValue = input.value.trim();

        if (!flagValue) {
            setStatus('Introduce una flag.', true);
            return;
        }

        try {
            await validarCookieUsuario();
        } catch (error) {
            setStatus('cookien\'t', true);
            return;
        }

        try {
            const result = await submitFlag(flagValue);
            if (result.ya_hecha){
                setStatus('Ya has conseguido esta flag, no se añadiran puntos', true);
                return;
            }
            if (result.correcta) {
                setStatus('Flag correcta! +' + result.puntos_sumados + ' puntos conseguidos');
                return;
            }

            setStatus('Flag incorrecta', true);
        } catch (error) {
            setStatus('Flag incorrecta', true);
        }
    });
</script>
</body>
</html>
