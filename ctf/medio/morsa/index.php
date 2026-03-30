<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'morsa';
$backendUrl = '/backend.php';
$csrfToken = getCtfCsrfToken();
?>
<!doctype html>
<html lang="es">
    //que pesado es adri
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Template Reto CTF</title>
</head>
<body>
	<main>
		<h1>Message in a Bottle</h1>
        <p>
            Nos ha llegado un pendrive en una botella misteriosamente a la puerta de Core Dumped,
            necesitamos que alguien nos ayude a descifrar que dice el audio que hemos encontrado.
        </p>

       <h1>Escucha nuestro código Morse</h1>
           <p>Haz clic abajo para bajar el archivo MP3 directamente a tu carpeta de descargas.</p>

         <a href="/medio/morsa/morse.mp3" download="mensaje_morse.mp3" style="
             display: inline-block;
             padding: 10px 20px;
             background-color: #27ae60;
             color: white;
             text-decoration: none;
             border-radius: 5px;
             font-family: sans-serif;
         ">
             📥 Descargar MP3
         </a>

		<form id="respuesta-form">
			<label for="respuesta-input">Pregunta</label>
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
					alert('Flag: ' + flagResult.flag);
					return;
				}

				alert('Respuesta incorrecta');
			} catch (error) {
				alert('Respuesta incorrecta');
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
				if (result.correcta) {
					alert('Flag correcta');
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
