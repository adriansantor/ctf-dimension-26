<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'historia_importante';
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
	<title>Reto: Primer ataque masivo</title>
</head>
<body class="body-bg">
	<main class="container py-4">
		<div class="row align-center g-4 mb-4">
			<h1 class="text-center" style="font-family: 'VT323', monospace; font-size: 6rem;">Historia importante</h1>
		</div>

		<div class="row justify-content-center g-4">
			<div class="col-12 col-lg-10">
				<div class="border border-secondary rounded-3 p-4 h-100 mb-4">
					<h2 class="h3 fw-bold">Pregunta</h2>
					<p class="mb-3">Para conseguir la flag de este reto tendras que tirar de historia de la ciberseguridad.</p>
					<p>¿Cual es el nombre del que se considera el primer ciber-ataque masivo en la historia de Internet?</p>
					<form id="respuesta-form" class="d-flex flex-column gap-2">
						<label for="respuesta-input" class="form-label mb-0">Tu respuesta</label>
						<input id="respuesta-input" name="respuesta" type="text" placeholder="Ej: Troyano" class="form-control bg-dark text-white border-secondary" required autocomplete="off" />
						<button type="submit" class="btn btn-outline-light mt-2">Comprobar</button>
					</form>
					<div id="feedback" class="status mt-3"></div>
				</div>
			</div>
		</div>

		<div class="row justify-content-center g-4">
			<div class="col-12 col-lg-10">
				<section id="flag-section" class="border border-secondary rounded-3 p-4 h-100" style="display: none;">
					<h2 class="h3 fw-bold">Conseguido</h2>
					<p>Introduce la flag que has obtenido para sumar los puntos:</p>
					<form id="flag-form" class="d-flex flex-column gap-2">
						<label for="flag-input" class="form-label mb-0">Flag</label>
						<input id="flag-input" name="flag" type="text" class="form-control bg-dark text-white border-secondary" placeholder="core{...}" required autocomplete="off" />
						<button type="submit" class="btn btn-outline-light mt-2">Enviar la flag</button>
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
			try {
				data = await response.json();
			} catch (error) {
				throw new Error('El backend no responde');
			}

			if (!response.ok || !data.ok) {
				throw new Error(data.error || 'Error del backend');
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
			const feedback = document.getElementById('feedback');
			const flagSection = document.getElementById('flag-section');
			const flagInput = document.getElementById('flag-input');

			if (!respuestaValue) return;

			try {
				await validarCookieUsuario();
			} catch (error) {
				feedback.style.color = '#b00020';
				feedback.innerText = 'No tienes la cookie. Vuelve a la landing.';
				return;
			}

			try {
				const result = await comprobarRespuesta(respuestaValue);
				
				if (result.correcta) {
					const flagResult = await getFlag();
					
					feedback.style.color = 'green';
					feedback.innerText = '¡Correcto! Aquí tienes tu flag secreta: ' + flagResult.flag;
					flagSection.style.display = 'block';
					flagInput.value = flagResult.flag;
				} else {
					feedback.style.color = '#b00020';
					feedback.innerText = 'Respuesta incorrecta. Sigue buscando...';
					flagSection.style.display = 'none';
				}
			} catch (error) {
				feedback.style.color = '#b00020';
				feedback.innerText = 'Respuesta incorrecta o error de conexión.';
			}
		});
		document.getElementById('flag-form').addEventListener('submit', async (event) => {
			event.preventDefault();

			const flagValue = document.getElementById('flag-input').value.trim();
			if (!flagValue) return;
			try {
				const result = await submitFlag(flagValue);
				if (result.correcta) {
					alert('¡Flag correcta! Puntos sumados a tu equipo.');
					document.getElementById('flag-section').style.display = 'none';
					document.getElementById('feedback').innerText = '¡Reto completado!';
				} else {
					alert('Flag incorrecta');
				}
			} catch (error) {
				alert('Flag incorrecta o ya habías hecho este reto.');
			}
		});
	</script>
</body>
</html>
