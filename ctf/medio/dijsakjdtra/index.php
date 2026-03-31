<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'dijsakjdtra';
$backendUrl = '/backend.php';
$csrfToken = getCtfCsrfToken();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if ($scriptDir === '/' || $scriptDir === '.') {
	$scriptDir = '';
}
$graphImageUrl = $scriptDir . '/grafo.jpeg';
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link href="https://fonts.googleapis.com/css2?family=VT323&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="/css/style.css"/>
	<title>dijsakjdtra</title>
</head>
<body class="body-bg">
	<main class="container py-4">
		<div class="row align-center g-4 mb-4">
			<h1 class="text-center" style="font-family: 'VT323', monospace; font-size: 6rem;">dijsakjdtra</h1>
		</div>

		<div class="row justify-content-center g-4">
			<div class="col-12 col-lg-10">
				<div class="border border-secondary rounded-3 p-4 h-100 mb-4">
					<p class="fs-5">Para este reto se te iba a dar la flag directamente como incentivo, pero un malvado estudiante de logica y matematicas discretas quiso complicar el reto.</p>
					<p>Aqui tienes un grafo con una pequena contraseña. Tienes que encontrar el camino mas corto de la f a la s. ¿Puedes encontrar el mensaje oculto?</p>
					<img src="<?php echo htmlspecialchars($graphImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Grafo del reto" class="img-fluid border border-secondary rounded-2" />
				</div>
			</div>
		</div>

		<div class="row g-4 justify-content-center">
			<div class="col-12 col-lg-5">
				<section class="border border-secondary rounded-3 p-4 h-100">
					<h2 class="h3 fw-bold mb-3">Responder reto</h2>
					<div id="feedback-respuesta" class="status mb-2"></div>
					<form id="respuesta-form" class="d-flex flex-column gap-2">
						<label for="respuesta-input" class="form-label mb-0">¿Cual es la respuesta?</label>
						<input id="respuesta-input" name="respuesta" type="text" class="form-control bg-dark text-white border-secondary" required autocomplete="off"/>
						<button type="submit" class="btn btn-outline-light mt-2">Comprobar respuesta</button>
					</form>
				</section>
			</div>

			<div class="col-12 col-lg-5">
				<section id="flag-section" class="border border-secondary rounded-3 p-4 h-100" style="display: none;">
					<h2 class="h3 fw-bold mb-3">Enviar flag</h2>
					<div id="feedback-flag" class="status mb-2"></div>
					<form id="flag-form" class="d-flex flex-column gap-2">
						<label for="flag-input" class="form-label mb-0">¿Cual es la flag?</label>
						<input id="flag-input" name="flag" type="text" class="form-control bg-dark text-white border-secondary" required autocomplete="off"/>
						<button type="submit" class="btn btn-outline-light mt-2">Enviar flag</button>
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

			const respuestaValue = document.getElementById('respuesta-input').value.trim();
			const feedback = document.getElementById('feedback-respuesta');
			const flagSection = document.getElementById('flag-section');
			const flagInput = document.getElementById('flag-input');
			if (!respuestaValue) return;

			try {
				await validarCookieUsuario();
			} catch (error) {
				feedback.innerText = 'Error de sesión.';
				feedback.style.color = 'red';
				return;
			}

			try {
				const result = await comprobarRespuesta(respuestaValue);
				if (result.correcta) {
					const flagResult = await getFlag();
					feedback.innerText = 'Respuesta correcta. Aquí tienes la flag: ' + flagResult.flag;
					feedback.style.color = 'green';
					flagSection.style.display = 'block';
					flagInput.value = flagResult.flag;
					document.querySelector('#respuesta-form button').disabled = true;
					return;
				}
				feedback.innerText = 'Respuesta incorrecta.';
				feedback.style.color = 'red';
				flagSection.style.display = 'none';
			} catch (error) {
				feedback.innerText = 'Respuesta incorrecta o error de conexión.';
				feedback.style.color = 'red';
			}
		});

		document.getElementById('flag-form').addEventListener('submit', async (event) => {
			event.preventDefault();

			const flagValue = document.getElementById('flag-input').value.trim();
			const feedback = document.getElementById('feedback-flag');
			if (!flagValue) return;

			try {
				await validarCookieUsuario();
			} catch (error) {
				feedback.innerText = 'Error de sesión.';
				feedback.style.color = 'red';
				return;
			}

			try {
				const result = await submitFlag(flagValue);
				if (result.ya_hecha) {
					feedback.innerText = 'Ya habías resuelto este reto.';
					feedback.style.color = 'green';
					return;
				}
				if (result.correcta) {
					feedback.innerText = '¡Flag correcta! Puntos sumados a tu equipo.';
					feedback.style.color = 'green';
					document.querySelector('#flag-form button').disabled = true;
					return;
				}
				feedback.innerText = 'Flag incorrecta.';
				feedback.style.color = 'red';
			} catch (error) {
				feedback.innerText = 'Flag incorrecta o ya has resuelto este reto.';
				feedback.style.color = 'red';
			}
		});
	</script>
</body>
</html>
