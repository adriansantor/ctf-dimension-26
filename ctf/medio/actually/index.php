<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'actually';
$backendUrl = '/backend.php';
$csrfToken = getCtfCsrfToken();
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Reto: Metadata en una foto</title>
	<style>
		body { font-family: sans-serif; margin: 2rem; max-width: 720px; }
		section { border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
		input, button { padding: 0.5rem; margin-top: 0.5rem; cursor: pointer; }
		.feedback { margin-top: 1rem; margin-bottom: 1rem; font-weight: bold; }
	</style>
</head>
<body>
	<main>
		<h1>Búsqueda: Encontrar la metadata en la foto</h1>
		<h3>Para conseguir la flag de este reto tienes que tirar de Fundamentos de Seguridad.</h3>

		<section>
			<h2>Encuentra la flag en la metadata de la imagen</h2>
			
			<div style="text-align: center; margin-bottom: 1rem;">
				<img src="/medio/actually/actually.jpg" alt="Imagen sospechosa" style="max-width: 100%; max-height: 200px; width: auto; border: 1px solid #ccc;">
				<br>
				<a href="/medio/actually/actually.jpg" download>
					<button type="button" style="margin-top: 10px;">Descargar imagen</button>
				</a>
			</div>

			<div id="feedback-flag" class="feedback"></div>
			<form id="flag-form">
				<label for="flag-input">Flag:</label><br>
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
			try { data = await response.json(); } catch (error) { throw new Error('backend murió'); }
			if (!response.ok || !data.ok) { throw new Error(data.error || 'backend murió'); }
			return data;
		}

		async function validarCookieUsuario() { await postBackend({ action: 'get_puntos' }); }
		async function submitFlag(flagValue) { return postBackend({ action: 'submit_flag', reto: CHALLENGE_ID, flag: flagValue }); }

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
				if (result.correcta) {
					feedback.innerText = '¡Hackeo completado! Puntos sumados a tu equipo.';
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
