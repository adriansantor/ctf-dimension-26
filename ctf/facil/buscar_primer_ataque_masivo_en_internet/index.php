<?php
declare(strict_types=1);

// Este ID debe coincidir con flags.json y retos.csv
$challengeId = 'buscar_primer_ataque_masivo_en_internet';
$backendUrl = '/backend.php';
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
		<p>Para conseguir la flag de este reto, tendrás que tirar de historia de la ciberseguridad.</p>

		<section>
			<h2>Pregunta</h2>
			<p>¿Cuál es el nombre del que se considera el primer ciber-ataque masivo en la historia de Internet?</p>
			
			<label for="answer-input">Tu respuesta:</label><br>
			<input id="answer-input" type="text" placeholder="Ejemplo: SQ" autocomplete="off" />
			<button onclick="comprobarRespuesta()">Comprobar</button>
			
			<div id="feedback" class="feedback"></div>
		</section>

		<section id="flag-section" style="display: none;">
			<h2>¡Conseguido! Envia flag</h2>
			<form id="flag-form">
				<label for="flag-input">Flag</label><br>
				<input id="flag-input" name="flag" type="text" required autocomplete="off" style="width: 300px;" />
				<button type="submit">Enviar flag</button>
			</form>
		</section>
	</main>

	<script>
		function comprobarRespuesta() {
			const respuesta = document.getElementById('answer-input').value.toLowerCase().trim();
			const feedback = document.getElementById('feedback');
			const flagSection = document.getElementById('flag-section');
			const flagInput = document.getElementById('flag-input');

			// Comprobamos si escriben "morris"
			if (respuesta.includes('morris')) {
				feedback.style.color = 'green';
				feedback.innerText = '¡Correcto! El Gusano Morris colapsó gran parte de la joven red en 1988. Aquí tienes tu flag.';
				
				flagSection.style.display = 'block';
				flagInput.value = 'core{buscar_primer_ataque_masivo_en_internet}';
			} else {
				feedback.style.color = '#b00020';
				feedback.innerText = 'Respuesta incorrecta. Sigue buscando...';
				flagSection.style.display = 'none';
			}
		}

		// Comunicación con tu backend usando tu template.php
		const BACKEND_URL = <?php echo json_encode($backendUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
		const CHALLENGE_ID = <?php echo json_encode($challengeId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

		async function postBackend(payload) {
			const response = await fetch(BACKEND_URL, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				credentials: 'include',
				body: JSON.stringify(payload),
			});
			let data = await response.json();
			if (!response.ok || !data.ok) throw new Error(data.error || 'Error del backend');
			return data;
		}

		document.getElementById('flag-form').addEventListener('submit', async (event) => {
			event.preventDefault();
			const flagValue = document.getElementById('flag-input').value.trim();

			try {
				const result = await postBackend({
					action: 'submit_flag',
					reto: CHALLENGE_ID,
					flag: flagValue,
				});
				if (result.correcta) {
					alert('¡Flag correcta! Puntos sumados.');
				} else {
					alert('Flag incorrecta');
				}
			} catch (error) {
				alert('Error: Asegúrate de haber creado un usuario en la landing primero.');
			}
		});
	</script>
</body>
</html>
