# Servidor CTF

## Enfoque general

- Cada prueba va en un puerto distinto.
- Se usa el servidor de PHP (`php -S`) para no escribir 40 configs de nginx ♥️.
- El backend actúa como intermediario entre las pruebas y la base de datos SIEMPRE, como algún index haga cambios en la db os sacrifico.

## Detalles específicos del servidor

- **Arranque por puerto:** cada reto se va a levantar en su propia dirección, por ejemplo:
  - `php -S 0.0.0.0:8001 -t ctf/facil/cesar_decode`
  - `php -S 0.0.0.0:8002 -t ctf/medio/grep`
  Los retos fáciles en puertos 1000, los medios en 2000, y los difíciles en 3000
- **Backend central:** levantarlo en puerto aparte con router para no exponer `/private`, `/logs` ni `retos.csv`:
  - `php -S 0.0.0.0:8080 -t ctf ctf/router.php`
  - URL backend: `http://IP_DEL_SERVIDOR:8080/backend.php`
  - Desde los retos (otros puertos), hacer `fetch` a esa URL del backend con `credentials: 'include'` para enviar la cookie común `usuario_b64`.
- **Formato de datos:** retos completados en columnas booleanas (`1` = hecho, `0` = no hecho).
