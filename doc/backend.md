# Backend CTF (`backend.php`)

Este backend recibe peticiones desde los `index.php` de los retos y guarda el estado en `retos.csv`.

## Sitios

- API: `ctf/backend.php`
- DB: `ctf/retos.csv`

Con despliegue en nginx, la ruta del backend va a ser:

- `http://192.168.1.100/backend.php`

Y los retos cuelgan de la misma origin (`/facil/*`, `/medio/*`, `/dificil/*`).

## Formato de petición

`backend.php` acepta (d momento):

- `POST` con `application/json`
- `POST` con `application/x-www-form-urlencoded` (campos de `$_POST`)

SIEMPRE SIEMPRE SIEMPRE se debe enviar el campo `action`.

## Sesión y CSRF

- `backend.php` abre sesión PHP y exige token CSRF en todos los `POST`.
- El token se genera en páginas PHP como `landing.php` y los retos usando `ctf/csrf.php`.
- El frontend debe enviar SIEMPRE:
  - cookie de sesión (`credentials: 'include'`)
  - header `X-CSRF-Token`
- Un `POST` sin token válido responde `403` con `{"ok": false, "error": "CSRF token inválido."}`.

Ejemplo mínimo desde una página PHP:

```php
require __DIR__ . '/../ctf/csrf.php';

$csrfToken = getCtfCsrfToken();
```

```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({ action: 'get_puntos' })
});
```

## Respuesta

SIEMPRE responde JSON:

- Chill: `{"ok": true, ...}`
- Muerte, dolor y sufrimiento: `{"ok": false, "error": "..."}`

## Acciones disponibles

## 1) Comprobar si nombre existe (bool)

- `action`: `comprobar_nombre_existe`
- params:
  - `nombre` (puede ir en texto plano o en base64, mejor en b64 tho, q se cansa la raspi)
- response:
  - `existe` (`true|false`)

```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({
    action: 'comprobar_nombre_existe',
    nombre: btoa('team')
  })
});
```

## 2) Save name (void) + base64

- `action`: `save_name`
- params:
  - `nombre` (texto plano)
- action:
  - Encodea el nombre en base64
  - guarda cookie `usuario_b64`
```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({
    action: 'save_name',
    nombre: 'team'
  })
});
```

## 3) Get name (string) + decode base64

- `action`: `get_name`
- params:
  - `nombre_b64`
- response:
  - `nombre` (texto plano)

```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({
    action: 'get_name',
    nombre_b64: btoa('team')
  })
});
```

## 4) Get puntos (int)

- `action`: `get_puntos`
- params:
  - `nombre` o `nombre_b64`
- response:
  - `puntos` (int)

```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({
    action: 'get_puntos',
    nombre_b64: btoa('team')
  })
});
```

## 5) Marcar hecho (void)

- `action`: `marcar_hecho`
- params:
  - `nombre` o `nombre_b64`
  - `reto` (nombre de columna en `retos.csv`, ej. `cesar_decode`)
- action:
  - marca `1` en la columna del reto para ese equipo

```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({
    action: 'marcar_hecho',
    nombre_b64: btoa('team'),
    reto: 'jwt'
  })
});
```

## 6) Comprobar hecho (bool)

- `action`: `comprobar_hecho`
- params:
  - `nombre` o `nombre_b64`
  - `reto`
- response:
  - `hecho` (`true|false`)

```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({
    action: 'comprobar_hecho',
    nombre_b64: btoa('team'),
    reto: 'jwt'
  })
});
```

## 7) Crear equipo (void)

- `action`: `crear_equipo`
- params:
  - `nombre` (texto plano)
  - `dificultad_id` (int)
- action:
  - guarda `nombre_b64`
  - guarda `dif`
  - pone `puntos = 0`
  - inicializa todos los retos a `0`

```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({
    action: 'crear_equipo',
    nombre: 'team',
    dificultad_id: 2
  })
});
```

## 8) Añadir a equipo (cookie)

- `action`: `anadir_a_equipo`
- params:
  - `nombre_equipo_b64` o `nombre_b64`
- action:
  - valida que el equipo exista
  - crea cookie `usuario_b64`

```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({
    action: 'anadir_a_equipo',
    nombre_b64: btoa('team')
  })
});
```

## 9) Submit flag (void)

- `action`: `submit_flag`
- params:
  - `reto` (clave del reto en `flags.json`, ej. `jwt`)
  - `flag` (flag introducida por el usuario)
  - `nombre` o `nombre_b64` (opcional si ya existe cookie `usuario_b64`)
- response:
  - `correcta` (`true|false`)
  - `ya_hecha` (`true|false`)
  - `puntos_sumados` (int)
- action:
  - valida la flag contra `private/flags.json`
  - si es correcta y no estaba hecha, marca el reto y suma puntos

```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({
    action: 'submit_flag',
    reto: 'jwt',
    flag: 'core{jwt}'
  })
});
```

## 10) Get flag por id de prueba (string)

- `action`: `get_flag`
- params:
  - `id` (id del reto/prueba en `flags.json`, por ejemplo `jwt`)
  - también acepta `reto` como alias
- response:
  - `flag` (string)
- action:
  - usa la función `getFlag(string $idPrueba): string` del backend
  - devuelve la flag configurada para ese id

```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({
    action: 'get_flag',
    id: 'jwt'
  })
});
```

## 11) Comprobar respuesta por id de prueba (bool)

- `action`: `comprobar_respuesta`
- params:
  - `id` (id del reto/prueba en `respuestas.json`, por ejemplo `html`)
  - también acepta `reto` como alias
  - `respuesta` (texto introducido por el usuario)
- response:
  - `correcta` (`true|false`)
- action:
  - usa la función `comprobarRespuesta(string $idPrueba, string $texto): bool` del backend
  - comprueba si `respuesta` contiene el valor configurado en `respuestas.json` para ese id
  - la comprobación no distingue mayúsculas/minúsculas (case-insensitive)

```js
fetch('/backend.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
  },
  credentials: 'include',
  body: JSON.stringify({
    action: 'comprobar_respuesta',
    id: 'html',
    respuesta: 'la etiqueta es <A>'
  })
});
```
