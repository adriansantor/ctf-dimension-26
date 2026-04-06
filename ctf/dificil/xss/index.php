<?php
declare(strict_types=1);

require __DIR__ . '/../../csrf.php';

$challengeId = 'xss';
$backendUrl = '/backend.php';
$csrfToken = getCtfCsrfToken();

// Cookie del profesor que el XSS debe robar. httponly:false para que document.cookie la exponga.
// El valor real se inyecta desde JS via getFlag() al cargar la página.
setcookie('profesor_sesion', '', [
    'expires'  => time() + 86400,
    'path'     => '/dificil/Xss',
    'httponly' => false,
    'samesite' => 'Lax',
]);

$secciones = ['horarios', 'profesores', 'normativa', 'eventos', 'laboratorios'];
$seccion   = (string) ($_GET['seccion'] ?? 'horarios');

// Filtro: bloquea <script> pero no otros vectores HTML con eventos
$seccionFiltrada = str_ireplace(['<script', '</script>'], ['', ''], $seccion);

$seccionActiva  = in_array($seccion, $secciones, true) ? $seccion : null;
$esProyectoAura = ($seccion === 'proyecto-aura');

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
            &nbsp; el portal interno de la ETSISI guarda información clasificada
            sobre el <strong style="color:#fff">Proyecto AURA</strong>. El sistema filtra a los intrusos
            por cookie de sesión de profesor. Encuentra la manera de robar esa cookie y obtén la flag.
        </p>
    </div>
</div>

<main class="container pb-5">

    <nav class="mb-4">
        <ul class="nav nav-intranet border-bottom border-secondary">
            <?php foreach ($secciones as $s): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $seccionActiva === $s ? 'active' : '' ?>"
                       href="?seccion=<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="content-panel mb-4">

        <?php if ($seccionActiva === 'horarios'): ?>
            <h2 class="h4 fw-bold mb-3" style="color:#00ff99">Horarios — Curso 2025/2026</h2>
            <p class="text-muted small mb-3">Semestre B &mdash; Actualizado: 03/02/2026</p>
            <table class="table table-intranet table-sm">
                <thead>
                    <tr><th>Hora</th><th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th><th>Viernes</th></tr>
                </thead>
                <tbody>
                    <tr><td>09:00</td><td>Redes II (A1.01)</td><td>&mdash;</td><td>Redes II (A1.01)</td><td>&mdash;</td><td>Lab. Redes (L3.04)</td></tr>
                    <tr><td>11:00</td><td>&mdash;</td><td>Seg. Informática (B2.12)</td><td>&mdash;</td><td>Seg. Informática (B2.12)</td><td>&mdash;</td></tr>
                    <tr><td>13:00</td><td>Sistemas Dist. (A1.03)</td><td>Sistemas Dist. (A1.03)</td><td>&mdash;</td><td>&mdash;</td><td>&mdash;</td></tr>
                    <tr><td>15:00</td><td>&mdash;</td><td>Criptografía (B1.08)</td><td>&mdash;</td><td>Criptografía (B1.08)</td><td>&mdash;</td></tr>
                    <tr><td>17:00</td><td>Lab. Seg. (L2.01)</td><td>&mdash;</td><td>Lab. Seg. (L2.01)</td><td>&mdash;</td><td>&mdash;</td></tr>
                </tbody>
            </table>

        <?php elseif ($seccionActiva === 'profesores'): ?>
            <h2 class="h4 fw-bold mb-3" style="color:#00ff99">Directorio de Profesorado</h2>
            <p class="text-muted small mb-3">Departamento de Sistemas Informáticos</p>
            <table class="table table-intranet table-sm">
                <thead>
                    <tr><th>Nombre</th><th>Área</th><th>Despacho</th><th>Correo</th></tr>
                </thead>
                <tbody>
                    <tr><td>Dr. Marcos Vidal</td><td>Seguridad de Redes</td><td>D2.14</td><td>m.vidal@etsisi.upm.es</td></tr>
                    <tr><td>Dra. Lucía Herrero</td><td>Criptografía Aplicada</td><td>D2.09</td><td>l.herrero@etsisi.upm.es</td></tr>
                    <tr><td>D. Rafael Cano</td><td>Sistemas Distribuidos</td><td>D1.22</td><td>r.cano@etsisi.upm.es</td></tr>
                    <tr><td>Dra. Elena Mora</td><td>Investigación — <span class="restricted-badge">AURA</span></td><td>D3.01</td><td>e.mora@etsisi.upm.es</td></tr>
                    <tr><td>D. Sergio Blanco</td><td>Redes de Área Local</td><td>D1.07</td><td>s.blanco@etsisi.upm.es</td></tr>
                </tbody>
            </table>

        <?php elseif ($seccionActiva === 'normativa'): ?>
            <h2 class="h4 fw-bold mb-3" style="color:#00ff99">Normativa del Portal</h2>
            <p class="text-muted small mb-3">Versión 4.1 &mdash; Aprobada por Junta de Escuela el 15/09/2025</p>
            <ul class="text-secondary" style="line-height:2">
                <li>El acceso es exclusivo para personal docente e investigador con sesión activa.</li>
                <li>Cada usuario dispone de una <strong style="color:#ccc">cookie de sesión</strong> única (<code>profesor_sesion</code>) que identifica su identidad en el sistema.</li>
                <li>Queda prohibida la divulgación de credenciales o tokens de sesión a terceros.</li>
                <li>Las secciones marcadas con <span class="restricted-badge">ACCESO RESTRINGIDO</span> requieren permisos adicionales.</li>
                <li>Cualquier acceso indebido será registrado y comunicado al responsable de seguridad del centro.</li>
            </ul>

        <?php elseif ($seccionActiva === 'eventos'): ?>
            <h2 class="h4 fw-bold mb-3" style="color:#00ff99">Eventos del Campus</h2>
            <p class="text-muted small mb-3">Próximas actividades &mdash; Febrero / Marzo 2026</p>
            <div class="d-flex flex-column gap-3">
                <div class="border border-secondary rounded p-3">
                    <div class="small text-muted mb-1">14 Feb 2026 &mdash; Aula Magna</div>
                    <strong>Jornada de Ciberseguridad ETSISI 2026</strong>
                    <p class="text-muted small mt-1 mb-0">Ponencias sobre amenazas actuales, CTF interno y talleres de pentesting.</p>
                </div>
                <div class="border border-secondary rounded p-3">
                    <div class="small text-muted mb-1">21 Feb 2026 &mdash; Laboratorio L3</div>
                    <strong>Taller: Introducción a Wireshark y análisis de tráfico</strong>
                    <p class="text-muted small mt-1 mb-0">Sesión práctica de 3 horas. Plazas limitadas a 20 asistentes.</p>
                </div>
                <div class="border border-secondary rounded p-3">
                    <div class="small text-muted mb-1">05 Mar 2026 &mdash; Sala de reuniones D1</div>
                    <strong>Reunión de coordinación de investigación — Departamento SI</strong>
                    <p class="text-muted small mt-1 mb-0">Presentación de avances en proyectos activos.</p>
                </div>
            </div>

        <?php elseif ($seccionActiva === 'laboratorios'): ?>
            <h2 class="h4 fw-bold mb-3" style="color:#00ff99">Laboratorios</h2>
            <p class="text-muted small mb-3">Planta 2 y 3 — Edificio A</p>
            <table class="table table-intranet table-sm mb-4">
                <thead>
                    <tr><th>Lab</th><th>Nombre</th><th>Capacidad</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <tr><td>L2.01</td><td>Lab. Seguridad</td><td>24 puestos</td><td><span class="text-success">Operativo</span></td></tr>
                    <tr><td>L2.04</td><td>Lab. Redes</td><td>30 puestos</td><td><span class="text-success">Operativo</span></td></tr>
                    <tr><td>L3.01</td><td>Lab. Sistemas Distribuidos</td><td>20 puestos</td><td><span class="text-success">Operativo</span></td></tr>
                    <tr><td>L3.04</td><td>Lab. Criptografía</td><td>16 puestos</td><td><span class="text-warning">Mantenimiento</span></td></tr>
                    <tr><td>L3.09</td><td>Lab. Investigación Avanzada</td><td>—</td><td><span class="restricted-badge">RESTRINGIDO</span></td></tr>
                </tbody>
            </table>
            <div class="border border-secondary rounded p-3">
                <h3 class="h6 fw-bold mb-2" style="color:#adb5bd">Proyectos de investigación activos</h3>
                <ul class="text-muted small mb-0" style="line-height:2">
                    <li><strong style="color:#ccc">ProtoSec</strong> — Análisis de vulnerabilidades en protocolos industriales.</li>
                    <li><strong style="color:#ccc">RedGuard</strong> — Detección de intrusiones basada en aprendizaje automático.</li>
                    <li><strong style="color:#ccc">Proyecto AURA</strong> — Clasificado. Acceso solo para personal autorizado del Departamento SI.</li>
                </ul>
            </div>

        <?php elseif ($esProyectoAura): ?>
            <div class="text-center py-4">
                <h2 class="h4 fw-bold mt-3" style="color:#ff4444">ACCESO RESTRINGIDO</h2>
                <p class="text-muted mt-3 mb-1">Esta sección contiene información clasificada.</p>
                <p class="text-muted mb-3">
                    El sistema verifica la identidad mediante la cookie de sesión
                    <code>profesor_sesion</code>. Solo el profesorado autorizado con
                    dicha cookie activa puede acceder al contenido del
                    <strong style="color:#ccc">Proyecto AURA</strong>.
                </p>
                <p class="text-muted small">
                    Si eres investigador del proyecto, asegúrate de tener la cookie correcta
                    en tu navegador antes de intentar acceder.
                </p>
            </div>

        <?php else: ?>
            <p class="text-muted mb-1">
                La sección <strong style="color:#ff9944"><?= $seccionFiltrada ?></strong>
                no existe en este portal.
            </p>
            <p class="text-muted small">Comprueba que la URL es correcta o usa la navegación superior.</p>
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
                           required autocomplete="off" placeholder="CTF{...}" />
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

    async function getFlag() {
        return postBackend({
            action: 'get_flag',
            id: CHALLENGE_ID,
        });
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

    // Establece la cookie del profesor con la flag real al cargar la página.
    getFlag().then(result => {
        document.cookie = `profesor_sesion=${result.flag}; path=/dificil/Xss; samesite=Lax`;
    }).catch(() => {});

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
            setStatus('No tienes cookie de equipo. Vuelve a la landing.', true);
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
</script>
</body>
</html>
