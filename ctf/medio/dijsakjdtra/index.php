<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'dijsakjdtra';
$backendUrl = '/backend.php';
$csrfToken = getCtfCsrfToken();
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>dijsakjdtra</title>

</head>
<body>
	<main>
		<h1>Reto: dijsakjdtra</h1>
		<p style="font-size: 20px">
			"Para este reto se te iba a dar la flag directamente como incentivo, pero un malvado estudiante de Lógica y Mátematicas Discretas quiso complicar el reto. Aquí tienes un grafo con una pequeña contraseña. Puedes encontrar el mensaje oculto?"
  	    </p>
		<section>
			<h1>Responder reto</h1>
			<div id="feedback-respuesta" class="feedback"></div>
			<form id="respuesta-form">
				<label for="respuesta-input">Cuál es la respuesta?</label><br>
				<input id="respuesta-input" name="respuesta" type="text" required autocomplete="off" style="width: 300px;"/>
				<button type="submit">Comprobar respuesta</button>
			</form>
		</section>

		<section id="flag-section" style="display: none;">
			<h1>Enviar flag</h1>
			<div id="feedback-flag" class="feedback"></div>
			<form id="flag-form">
				<label for="flag-input">Cuál es la flag?</label><br>
				<input id="flag-input" name="flag" type="text" required autocomplete="off" style="width: 300px;"/>
				<button type="submit">Enviar flag</button>
			</form>
		</section>
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
