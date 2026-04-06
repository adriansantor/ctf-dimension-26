<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'xss';
$backendUrl = '/backend.php';
$csrfToken = getCtfCsrfToken();

$cookieProfesorActiva = 'ETSISI_AURA_PROFESOR';
$tieneCookieProfesor = isset($_COOKIE['profesor_sesion'])
    && is_string($_COOKIE['profesor_sesion'])
    && hash_equals($cookieProfesorActiva, $_COOKIE['profesor_sesion']);

$info = (string) ($_GET['info'] ?? 'asignatura');
$avisoInterno = (string) ($_GET['aviso'] ?? '');

$asignaturas = [
    ['slug' => 'redes-ii', 'nombre' => 'Redes II', 'grupo' => 'A1.01', 'horario' => 'Lunes y miércoles, 09:00', 'aula' => 'A1.01', 'profesor' => 'Dr. Marcos Vidal', 'correo' => 'm.vidal@etsisi.upm.es'],
    ['slug' => 'seguridad-informatica', 'nombre' => 'Seguridad Informática', 'grupo' => 'B2.12', 'horario' => 'Martes y jueves, 11:00', 'aula' => 'B2.12', 'profesor' => 'Dra. Lucía Herrero', 'correo' => 'l.herrero@etsisi.upm.es'],
    ['slug' => 'sistemas-distribuidos', 'nombre' => 'Sistemas Distribuidos', 'grupo' => 'A1.03', 'horario' => 'Lunes y martes, 13:00', 'aula' => 'A1.03', 'profesor' => 'D. Rafael Cano', 'correo' => 'r.cano@etsisi.upm.es'],
    ['slug' => 'criptografia', 'nombre' => 'Criptografía', 'grupo' => 'B1.08', 'horario' => 'Martes y jueves, 15:00', 'aula' => 'B1.08', 'profesor' => 'Dra. Elena Mora', 'correo' => 'e.mora@etsisi.upm.es'],
    ['slug' => 'laboratorio-seguridad', 'nombre' => 'Laboratorio de Seguridad', 'grupo' => 'L2.01', 'horario' => 'Lunes y miércoles, 17:00', 'aula' => 'L2.01', 'profesor' => 'D. Sergio Blanco', 'correo' => 's.blanco@etsisi.upm.es'],
];

$asignaturaActiva = null;
foreach ($asignaturas as $asignatura) {
    if ($info === $asignatura['slug']) {
        $asignaturaActiva = $asignatura;
        break;
    }
}

$mostrarAsignaturas = ($info === 'asignatura');
$mostrarDetalleAsignatura = is_array($asignaturaActiva);
$mostrarFlag = ($info === 'aura');

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=VT323&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css"/>
    <title>Portal Intranet ETSISI</title>
    <style>
        .intranet-header {
            background: #0d1117;
            border-bottom: 2px solid #00ff99;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .intranet-logo {
            font-family: 'VT323', monospace;
            font-size: 2rem;
            color: #00ff99;
            letter-spacing: 2px;
        }
        .intranet-subtitle {
            font-size: 0.75rem;
            color: #6c757d;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .nav-intranet .nav-link {
            color: #adb5bd;
            border-radius: 0;
            border-bottom: 2px solid transparent;
            padding: 0.5rem 1rem;
        }
        .nav-intranet .nav-link:hover {
            color: #00ff99;
            border-bottom-color: #00ff9966;
        }
        .nav-intranet .nav-link.active {
            color: #00ff99;
            border-bottom: 2px solid #00ff99;
            background: transparent;
        }
        .content-panel {
            background: #0d1117;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 2rem;
            min-height: 300px;
        }
        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 160px;
            padding: 0.65rem 1rem;
            border: 1px solid #555;
            border-radius: 4px;
            color: #e5e7eb;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.03);
            transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease;
        }
        .action-link:hover {
            color: #00ff99;
            border-color: #00ff99;
            background: rgba(0, 255, 153, 0.05);
        }
        .action-link.active {
            color: #00ff99;
            border-color: #00ff99;
            background: rgba(0, 255, 153, 0.08);
        }
        .table-intranet th { color: #00ff99; border-color: #333; }
        .table-intranet td { color: #ccc;    border-color: #222; }
        .restricted-badge {
            display: inline-block;
            background: #ff444422;
            border: 1px solid #ff4444;
            color: #ff4444;
            padding: 0.2rem 0.6rem;
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 2px;
        }
    </style>
</head>
<body class="body-bg">

<script>
    function activarCookie() {
        document.cookie = 'profesor_sesion=ETSISI_AURA_PROFESOR; path=/dificil/xss; SameSite=Lax';
    }
</script>

<div class="intranet-header">
    <div class="container">
        <div class="intranet-logo">[ ETSISI // INTRANET ]</div>
        <div class="intranet-subtitle">Escuela Técnica Superior de Ingeniería de Sistemas Informáticos &mdash; Campus Sur, UPM</div>
    </div>
</div>
<div style="background:#0d1117; border-bottom:1px solid #333;">
    <div class="container py-3">
        <p class="mb-0 small" style="color:#adb5bd;">
            <span style="color:#00ff99; font-family:'VT323',monospace; font-size:1.1rem;">// MISIÓN</span>
            &nbsp; el portal interno de la ETSISI expone el plan docente del departamento.
            El acceso a la parte clasificada depende de la cookie de sesión del profesor.
        </p>
    </div>
</div>

<main class="container pb-5">

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <div class="border border-secondary rounded-3 p-4 h-100">
                <h2 class="h5 fw-bold mb-2" style="color:#00ff99">Navegación interna</h2>
                <p class="text-muted small mb-3">Usa los botones para moverte por el portal.</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($asignaturas as $asignatura): ?>
                        <a class="action-link <?= ($info === $asignatura['slug']) ? 'active' : '' ?>" href="?info=<?= htmlspecialchars($asignatura['slug'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($asignatura['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="border border-secondary rounded-3 p-4 h-100">
                <h2 class="h6 fw-bold mb-2" style="color:#adb5bd">Aviso</h2>
                <p class="text-muted small mb-2">Nos hemos dejado la función activarCookie() disponible! Hay que corregirlo cuanto antes!</p>
                <p class="text-muted small mb-0">También hay que arreglar el botón de <span style="color:#ff9944">AURA</span>.</p>
            </div>
        </div>
    </div>

    <div class="content-panel mb-4">

        <?php if ($mostrarAsignaturas): ?>
            <h2 class="h4 fw-bold mb-3" style="color:#00ff99">Asignaturas disponibles</h2>
            <p class="text-muted small mb-0">Selecciona una asignatura en los botones superiores para ver su profesor y correo.</p>

            <?php if ($avisoInterno !== ''): ?>
                <div class="border border-secondary rounded p-3 mt-4">
                    <h3 class="h6 fw-bold mb-2" style="color:#adb5bd">Comunicado interno</h3>
                    <p class="text-muted small mb-0"><?= $avisoInterno ?></p>
                </div>
            <?php endif; ?>

        <?php elseif ($mostrarDetalleAsignatura): ?>
            <h2 class="h4 fw-bold mb-3" style="color:#00ff99"><?= htmlspecialchars($asignaturaActiva['nombre'], ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="table-responsive mb-4">
                <table class="table table-intranet table-sm">
                    <thead>
                        <tr><th>Grupo</th><th>Horario</th><th>Aula</th><th>Profesor</th><th>Correo</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= htmlspecialchars($asignaturaActiva['grupo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($asignaturaActiva['horario'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($asignaturaActiva['aula'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($asignaturaActiva['profesor'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($asignaturaActiva['correo'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0">Puedes cambiar de asignatura usando los botones superiores.</p>

        <?php elseif ($mostrarFlag): ?>
            <div class="text-center py-4">
                <h2 class="h4 fw-bold mt-3" style="color:#ff4444">ACCESO RESTRINGIDO</h2>
                <?php if ($tieneCookieProfesor): ?>
                    <p class="text-muted mt-3 mb-1">Esta sección contiene información clasificada.</p>
                    <p class="text-muted mb-3">Contenido interno del profesorado.</p>
                    <p class="text-muted mb-0">
                        La flag es: <strong id="aura-flag" style="color:#ccc">Cargando...</strong>
                    </p>
                <?php else: ?>
                    <p class="text-muted mt-3 mb-1">PROYECTO DE INVESTIGACIÓN AURA</p>
                    <p class="text-muted mb-0">Área reservada para personal autorizado.</p>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="text-center py-4">
                <h2 class="h4 fw-bold mt-3" style="color:#ff4444">ACCESO NO DISPONIBLE</h2>
                <p class="text-muted mt-3 mb-1">
                    La sección <strong style="color:#ff9944"><?= $info ?></strong>
                    no existe en este portal.
                </p>
                <p class="text-muted small">Comprueba la URL.</p>
            </div>
        <?php endif; ?>

    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="border border-secondary rounded-3 p-4">
                <h2 class="h5 fw-bold mb-1">Enviar flag</h2>
                <p class="text-muted small mb-3">Cuando hayas obtenido la flag, introdúcela aquí para sumar los puntos.</p>
                <form id="flag-form" class="d-flex flex-column gap-2">
                    <label for="flag-input" class="form-label mb-0">Flag</label>
                    <input id="flag-input" name="flag" type="text"
                           class="form-control bg-dark text-white border-secondary"
                           required autocomplete="off" placeholder="core{...}" />
                    <button type="submit" class="btn btn-outline-light mt-2">Enviar flag</button>
                </form>
            </div>
        </div>
    </div>

    <p id="status" class="status mt-3 text-center" aria-live="polite"></p>

</main>

<script>
    const BACKEND_URL  = <?php echo json_encode($backendUrl,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const CHALLENGE_ID = <?php echo json_encode($challengeId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const CSRF_TOKEN   = <?php echo json_encode($csrfToken,   JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const IS_AURA_VIEW = <?php echo json_encode($mostrarFlag, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const HAS_PROFESSOR_COOKIE = <?php echo json_encode($tieneCookieProfesor, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

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

    async function cargarFlagAura() {
        const flagNode = document.getElementById('aura-flag');
        if (!flagNode || !IS_AURA_VIEW || !HAS_PROFESSOR_COOKIE) {
            return;
        }

        try {
            const result = await postBackend({
                action: 'get_flag',
                reto: CHALLENGE_ID,
            });
            flagNode.textContent = String(result.flag || 'N/D');
        } catch {
            flagNode.textContent = 'No disponible';
        }
    }

    function setStatus(message, isError = false) {
        const status = document.getElementById('status');
        status.textContent = message;
        status.style.color = isError ? '#b00020' : '#0a6b0a';
    }

    document.getElementById('flag-form').addEventListener('submit', async (event) => {
        event.preventDefault();

        const flagValue = document.getElementById('flag-input').value.trim();
        if (!flagValue) {
            setStatus('Introduce una flag.', true);
            return;
        }

        try {
            await validarCookieUsuario();
        } catch {
            setStatus('cookien\'t', true);
            return;
        }

        try {
            const result = await submitFlag(flagValue);
            if (result.ya_hecha) {
                setStatus('Ya habías resuelto este reto anteriormente.');
                return;
            }
            if (result.correcta) {
                setStatus('¡Flag correcta! Puntos sumados al equipo.');
            } else {
                setStatus('Flag incorrecta.', true);
            }
        } catch {
            setStatus('Flag incorrecta.', true);
        }
    });

    cargarFlagAura();
</script>
</body>
</html>
