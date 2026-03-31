<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'sopadeletras';
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
	<title>Sopa de Letras</title>
</head>
<body class="body-bg">
	<main class="container py-4">
		<div class="row align-center g-4 mb-4">
			<h1 class="text-center" style="font-family: 'VT323', monospace; font-size: 6rem;">Sopa de letras</h1>
		</div>

		<div class="row justify-content-center g-4">
			<div class="col-12 col-lg-10">
				<div class="border border-secondary rounded-3 p-4 h-100 mb-4">
					<p>Queremos poder acceder al panel de administrador de la Universidad Patatuda de Madrid para editar las notas y que aprueben todos.</p>
					<p>Con este objetivo hemos accedido a su base de datos y hemos encontrado esto:</p>

					<div class="table-responsive">
						<table class="table table-dark table-bordered mb-3 font-monospace">
							<thead>
								<tr>
									<th>Username</th>
									<th>Password</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>admin</td>
									<td>$2a$12$dFQgN1b2ivWo3K8CZ8x36ePJF6nSIGVS8yuNvu6cdIrvV35vyJw4y</td>
								</tr>
							</tbody>
						</table>
					</div>

					<p>No conseguimos acceder a la cuenta del administrador usando esa contraseña, ¿nos ayudas?</p>
				</div>
			</div>
		</div>

		<div class="row g-4 justify-content-center">
			<div class="col-12 col-lg-5">
				<section id="login-section" class="border border-secondary rounded-3 p-4 h-100">
					<h2 class="h3 fw-bold mb-3">Inicio de sesion de administrador</h2>
					<form id="respuesta-form" class="d-flex flex-column gap-2">
						<label for="respuesta-user" class="form-label mb-0">Usuario</label>
						<input id="respuesta-user" name="respuesta-user" type="text" class="form-control bg-dark text-white border-secondary" required autocomplete="off" />
						<label for="respuesta-password" class="form-label mb-0">Contraseña</label>
						<input id="respuesta-password" name="respuesta-password" type="text" class="form-control bg-dark text-white border-secondary" required autocomplete="off" />
						<button type="submit" class="btn btn-outline-light mt-2">Iniciar sesion</button>
					</form>
					<p id="mensaje-login" class="status mt-3" style="display: none">Bienvenido administrador! Aqui tienes tu codigo ultrasecreto:</p>
				</section>
			</div>

			<div class="col-12 col-lg-5">
				<section id="flag-section" class="border border-secondary rounded-3 p-4 h-100" style="display:none">
					<h2 class="h3 fw-bold mb-3">Panel de edicion de notas</h2>
					<form id="flag-form" class="d-flex flex-column gap-2">
						<label for="flag-input" class="form-label mb-0">Introduce codigo ultrasecreto para acceder</label>
						<input id="flag-input" name="flag" type="text" class="form-control bg-dark text-white border-secondary" required autocomplete="off" />
						<button type="submit" class="btn btn-outline-light mt-2">Acceder</button>
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
				id:CHALLENGE_ID,
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

            const userValue = document.getElementById('respuesta-user').value.trim();
            const passwordValue = document.getElementById('respuesta-password').value.trim();
            const flagSection = document.getElementById('flag-section');

			if (!userValue || !passwordValue) {
				alert('Introduce un usuario y una contraseña');
				return;
			}
            if (userValue !== 'admin'){
                alert('Este usuario no tiene privilegios de administrador');
                return;
            }

			try {
				await validarCookieUsuario();
			} catch (error) {
				alert('cookien\'t');
				return;
			}

			try {
				const result = await comprobarRespuesta(passwordValue);
                alert(result.correcta)
				if (result.correcta) {
                    flagSection.style.display = 'block';
					const flagResult = await getFlag();
                    const loginMessage = document.getElementById('mensaje-login');
                    loginMessage.innerText = loginMessage.innerText + flagResult.flag;
                    loginMessage.style.display = 'block';
					return;
				}

				alert('Respuesta incorrecta');
			} catch (error) {
				alert('Respuesta incorrecta');
			}
		});

		document.getElementById('flag-form').addEventListener('submit', async (event) => {
			event.preventDefault();

			const input = document.getElementById('flag-input');
			const flagValue = input.value.trim();

			if (!flagValue) {
				alert('Introduce una flag.');
				return;
			}

			try {
				await validarCookieUsuario();
			} catch (error) {
				alert('cookien\'t');
				return;
			}

			try {
				const result = await submitFlag(flagValue);
                if (result.ya_hecha) {
                    alert('Ya has conseguido esta flag, no se añadiran puntos');
                    return;
                }

				if (result.correcta) {
					alert('Felicidades! Has salvado a los alumnos de TLP!. ' + result.puntos_sumados + ' puntos conseguidos');
					return;
				}

				alert('Flag incorrecta');
			} catch (error) {
				alert('Flag incorrecta');
			}
		});
	</script>
</body>
</html>
