<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

// ¡Usamos 'actually' para TODO!
$challengeId = 'actually';
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
	<title>Reto: Metadata en una foto</title>
</head>
<body class="body-bg">
	<main class="container py-4">
		<div class="row align-center g-4 mb-4">
			<h1 class="text-center" style="font-family: 'VT323', monospace; font-size: 6rem;">Actually</h1>
		</div>

		<div class="row justify-content-center g-4">
			<div class="col-12 col-lg-10">
				<section class="border border-secondary rounded-3 p-4 h-100 mb-4">
					<h2 class="h3 fw-bold">Pregunta</h2>
					<p>Para conseguir la flag de este reto tienes que tirar de fundamentos de seguridad.</p>
					<p>¿Cual es la palabra escondida en la metadata de esta imagen?</p>

					<div class="text-center mb-3">
						<img src="/medio/actually/actually.jpg" alt="Imagen sospechosa" class="img-fluid border border-secondary rounded-2" style="max-height: 220px; width: auto;">
						<div class="mt-2">
							<a href="/medio/actually/actually.jpg" download class="btn btn-outline-light">Descargar imagen</a>
						</div>
					</div>

					<div id="feedback-respuesta" class="status mt-2 mb-3"></div>

					<form id="respuesta-form" class="d-flex flex-column gap-2">
						<label for="respuesta-input" class="form-label mb-0">Respuesta</label>
						<input id="respuesta-input" name="respuesta" type="text" class="form-control bg-dark text-white border-secondary" required autocomplete="off"/>
						<button type="submit" class="btn btn-outline-light mt-2">Comprobar</button>
					</form>
				</section>
			</div>
		</div>

		<div class="row justify-content-center g-4">
			<div class="col-12 col-lg-10">
				<section id="seccion-flag" class="border border-secondary rounded-3 p-4 h-100" style="display: none;">
					<h2 class="h3 fw-bold">Conseguido</h2>
					<p id="mensaje-exito" class="text-success fw-bold"></p>
					<p>Introdúcela aquí para validar el reto y sumar los puntos a tu equipo:</p>

					<form id="flag-form" class="d-flex flex-column gap-2">
						<label for="flag-input" class="form-label mb-0">Flag</label>
						<input id="flag-input" name="flag" type="text" class="form-control bg-dark text-white border-secondary" required autocomplete="off"/>
						<button type="submit" class="btn btn-outline-light mt-2">Enviar flag real</button>
					</form>
				</section>
			</div>
		</div>
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
			try { data = await response.json(); } catch (error) { throw new Error('backend murió'); }
			if (!response.ok || !data.ok) { throw new Error(data.error || 'backend murió'); }
			return data;
		}

		async function validarCookieUsuario() { await postBackend({ action: 'get_puntos' }); }
		async function comprobarRespuesta(respuestaValue) { return postBackend({ action: 'comprobar_respuesta', id: CHALLENGE_ID, respuesta: respuestaValue }); }
		async function getFlag() { return postBackend({ action: 'get_flag', id: CHALLENGE_ID }); }
		async function submitFlag(flagValue) { return postBackend({ action: 'submit_flag', reto: CHALLENGE_ID, flag: flagValue }); }

		document.getElementById('respuesta-form').addEventListener('submit', async (event) => {
			event.preventDefault();
			
			const respuestaValue = document.getElementById('respuesta-input').value.trim();
			const feedback = document.getElementById('feedback-respuesta');
			
			if (!respuestaValue) return;

			try { await validarCookieUsuario(); } catch (error) { feedback.innerText = 'Error de sesión.'; feedback.style.color = 'red'; return; }

			try {
				const result = await comprobarRespuesta(respuestaValue);
				if (result.correcta) {
					const flagResult = await getFlag();
					
					feedback.innerText = '¡Has acertado!';
					feedback.style.color = 'green';
					
					document.getElementById('mensaje-exito').innerText = 'Tu flag real es: ' + flagResult.flag;
					document.getElementById('seccion-flag').style.display = 'block';
					
					document.getElementById('flag-input').value = flagResult.flag;
					
					document.querySelector('#respuesta-form button').disabled = true;
					return;
				}
				feedback.innerText = 'Respuesta incorrecta.';
				feedback.style.color = 'red';
			} catch (error) {
				feedback.innerText = 'Respuesta incorrecta o error de conexión.';
				feedback.style.color = 'red';
			}
		});

		document.getElementById('flag-form').addEventListener('submit', async (event) => {
			event.preventDefault();
			
			const flagValue = document.getElementById('flag-input').value.trim();
			if (!flagValue) return;

			try { await validarCookieUsuario(); } catch (error) { alert('Error de sesión.'); return; }

			try {
				const result = await submitFlag(flagValue);
				if (result.correcta) {
					alert('¡Hackeo completado! Puntos sumados a tu equipo.');
					document.querySelector('#flag-form button').disabled = true;
					return;
				}
				alert('Flag incorrecta.');
			} catch (error) {
				alert('Flag incorrecta o ya has resuelto este reto.');
			}
		});
	</script>
</body>
</html>
