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
	<title>Reto: Primer ataque masivo</title>
	<style>
		body { font-family: sans-serif; margin: 2rem; max-width: 720px; }
		section { border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
		input, button { padding: 0.5rem; margin-top: 0.5rem; }
		.feedback { margin-top: 1rem; font-weight: bold; }
	</style>
</head>
<body>
	<main>
		<h1>Búsqueda: El primer ataque masivo</h1>
		<p>Para conseguir la flag de este reto tendrás que tirar de historia de la ciberseguridad.</p>
		<section>
			<h2>Pregunta</h2>
			<p>¿Cuál es el nombre del que se considera el primer ciber-ataque masivo en la historia de Internet?</p>
			<form id="respuesta-form">
				<label for="respuesta-input">Tu respuesta:</label><br>
				<input id="respuesta-input" name="respuesta" type="text" placeholder="Ej:Troyano" required autocomplete="off" />
				<button type="submit">Comprobar</button>
			</form>
			
			<div id="feedback" class="feedback"></div>
		</section>

		<section id="flag-section" style="display: none;">
			<h2>¡Conseguido!</h2>
			<p>Introduce la flag que has obtenido para sumar los puntos:</p>
			<form id="flag-form">
				<label for="flag-input">Flag:</label><br>
				<input id="flag-input" name="flag" type="text" required autocomplete="off" style="width: 300px;" />
				<button type="submit">Enviar la flag</button>
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
