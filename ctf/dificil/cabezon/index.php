<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'cabezon';
$backendUrl = '/backend.php';
$csrfToken = getCtfCsrfToken();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && (($_GET['oraculo'] ?? '') === '1')) {
	header('Content-Type: application/json; charset=utf-8');
	$headerValue = trim((string) ($_SERVER['HTTP_X_CABEZON_KEY'] ?? ''));
	if ($headerValue !== 'cabez0n_total') {
		http_response_code(403);
		echo json_encode(['ok' => false, 'error' => 'Cabecera incorrecta']);
		exit;
	}

	echo json_encode(['ok' => true, 'flag' => 'core{buena_cabeza}']);
	exit;
}
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link href="https://fonts.googleapis.com/css2?family=VT323&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="/css/style.css"/>
	<title>Reto CTF: Cabezón</title>
</head>
<body class="body-bg">
	<main class="container py-4">
		<div class="row align-center g-4 mb-4">
			<h1 class="text-center" style="font-family: 'VT323', monospace; font-size: 6rem;">Reto Cabezón</h1>
		</div>

		<div class="row justify-content-center mb-4">
			<div class="col-12 col-lg-8">
				<div class="border border-secondary rounded-3 p-4">
					<h2 class="h3 fw-bold mb-3">Instrucciones</h2>
					<p class="mb-2">Este reto no tiene pregunta directa. Tienes que hacer una peticion GET a esta misma ruta con <code>?oraculo=1</code>.</p>
					<p class="mb-2">Solo respondera si envias una cabecera concreta:</p>
					<p class="mb-2"><code>X-Cabezon-Key: cabez0n_total</code></p>
					<p class="mb-0">Si la cabecera es correcta, el oraculo te devolvera la flag en JSON.</p>
				</div>
			</div>
		</div>

		<div class="row g-4 justify-content-center">
			<div class="col-12 col-lg-6">
				<div class="border border-secondary rounded-3 p-4 h-100">
					<h2 class="h3 fw-bold mb-3">Enviar flag</h2>
					<form id="flag-form" class="d-flex flex-column gap-2">
						<label for="flag-input" class="form-label mb-0">Flag</label>
						<input id="flag-input" name="flag" type="text" class="form-control bg-dark text-white border-secondary" required autocomplete="off" />
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
				if (result.correcta) {
					setStatus('Flag correcta');
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
