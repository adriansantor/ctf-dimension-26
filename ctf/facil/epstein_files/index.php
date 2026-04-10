<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';
$challengeId = 'epstein_files';
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
	<title>Archivos de Epstein</title>
</head>
<body class="body-bg">
	<main class="container py-4">
		<div class="row align-center g-4 mb-4">
			<h1 class="text-center" style="font-family: 'VT323', monospace; font-size: 6rem;">Archivos de Epstein</h1>
			<p class="text-center" style="font-size: 1.5rem;">Te encuentras ante un volcado de datos de alta prioridad. Tu misión es analizar las entrañas de este sistema, conectar los puntos y desvelar el secreto que CORE ha intentado enterrar.<br><strong>Misión:</strong> Navega por los directorios, ignora los callejones sin salida y encuentra la forma de acceder al contenido restringido.</p>
			<div class="text-center mt-3">
				<a href="/facil/epstein_files/download.php" class="btn btn-outline-light btn-lg">Descargar Archivo ZIP</a>
			</div>
		</div>

		<div class="row g-4 justify-content-center">
			<div class="col-12 col-lg-5">
				<div class="border border-secondary rounded-3 p-4 h-100">
					<h2 class="h3 fw-bold mb-3">Enviar flag</h2>
					<form id="flag-form" class="d-flex flex-column gap-2">
						<label for="flag-input" class="form-label mb-0">Flag</label>
						<input id="flag-input" name="flag" type="text" class="form-control bg-dark text-white border-secondary" placeholder="core{...}" required autocomplete="off" />
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

		const respuestaForm = document.getElementById('respuesta-form');
		if (respuestaForm) {
			respuestaForm.addEventListener('submit', async (event) => {
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
						setStatus('Flag: ' + flagResult.flag);
						return;
					}

					setStatus('Respuesta incorrecta', true);
				} catch (error) {
					setStatus('Respuesta incorrecta', true);
				}
			});
		}

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
				if (result.ya_hecha) {
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
