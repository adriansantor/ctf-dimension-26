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
	<title>Sopa de Letras</title>
</head>
<body>
	<main>
		<h1>Sopa de Letras</h1>
        <p>
            Queremos poder acceder al panel de administrador de la Universidad Patatuda de Madrid
            para poder editar las notas de una cierta asignatura de manera que aprueben todos los alumnos.
            Con este objetivo hemos conseguido acceder a su base de datos y hemos encontrado esto:
        </p>

       <!-- Tablita to guapa que me ha hecho chatgpt -->
		<table style="border-collapse:collapse; width:100%; max-width:400px; font-family:monospace; border:1px solid #444;">
			<tr>
				<th style="border:1px solid #444; padding:8px; text-align:left; background:#f0f0f0;">Username</th>
				<th style="border:1px solid #444; padding:8px; text-align:left; background:#f0f0f0;">Password</th>
			</tr>
			<tr>
				<td style="border:1px solid #444; padding:8px;">admin</td>
				<td style="border:1px solid #444; padding:8px;">$2a$12$dFQgN1b2ivWo3K8CZ8x36ePJF6nSIGVS8yuNvu6cdIrvV35vyJw4y</td>
			</tr>
		</table>
        <br>
        <p>
            No conseguimos acceder a la cuenta del administrador usando esa contraseña, nos ayudas?
        </p>
        <br>
        <br>
        <br>

        <section id="login-section">
            <h2>Inicio de sesión de administrador</h2>
            <form id="respuesta-form" style="display:flex; flex-direction:column; gap:0.5rem; max-width:320px;">
                <div style="display:flex; flex-direction:column;">
                    <label for="respuesta-user">Usuario</label>
                    <input id="respuesta-user" name="respuesta-user" type="text" required autocomplete="off" />
                </div>
                <div style="display:flex; flex-direction:column;">
                    <label for="respuesta-password">Contraseña</label>
                    <input id="respuesta-password" name="respuesta-password" type="text" required autocomplete="off" />
                </div>
                <button type="submit">Iniciar sesión</button>
            </form>
            <p id="mensaje-login" style="display: none">
                Bienvenido administrador! Aquí tienes tu código ultrasecreto:
            </p>
        </section>
        <br>

        <section id="flag-section" style="display:none">
            <h2>Panel de edición de notas</h2>
            <form id="flag-form">
                <label for="flag-input">Introduce código ultrasecreto para acceder:</label>
                <input id="flag-input" name="flag" type="text" required autocomplete="off" />
                <button type="submit">Acceder</button>
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
