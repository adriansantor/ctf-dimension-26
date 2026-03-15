<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'dijkstra';
$backendUrl = '/backend.php';
$csrfToken = getCtfCsrfToken();
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Una flag, no dos</title>

</head>
<body>
	<main>
		<h1>Reto: Una flag, no dos</h1>
		<p style="font-size: 20px">
			"Para este reto se te iba a dar la flag directamente como incentivo, pero un malvado estudiante de Lógica y Mátematicas Discretas quiso complicar el reto. Se te dará un archivo txt que representa un grafo de 10000 nodos. Cada línea representa una arista con el formato: nodo1 nodo2 peso char. Significa que nodo 1 tiene una arista que va a nodo 2 con un peso y un char asociado. Puedes encontrar el mensaje oculto?"
  	    </p>
		<a href="/medio/dijkstra/flag.txt" download>
			<button type="button" style="margin-top: 10px;">Descargar txt</button>
		</a>
		<section>
			<h1>Enviar flag</h1>
			<div id="feedback-flag" class="feedback"></div>
			<form id="flag-form">
				<label for="flag-input">Cuál es la flag?:</label><br>
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

		async function submitFlag(flagValue) {
			return postBackend({
				action: 'submit_flag',
				reto: CHALLENGE_ID,
				flag: flagValue,
			});
		}

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
