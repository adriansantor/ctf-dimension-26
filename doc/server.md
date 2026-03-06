# Servidor CTF (nginx)

## Enfoque general

- Un único host/puerto para todo (`/facil/*`, `/medio/*`, `/dificil/*` y `/backend.php`).
- El backend ES EL ÚNICO punto de escritura de estado (`retos.csv`, flags y logs).

## URLs

- Las pruebas deben abrir como:
  - `http://192.168.1.100/facil/rsa`
  - `http://192.168.1.100/medio/grep`
  - `http://192.168.1.100/dificil/jwt`

## Configuración Nginx

Usa `ctf/` como `root` del server y rootea solo `backend.php` por PHP-FPM.

```nginx
server {
  listen 80;
  server_name 192.168.1.100;

  root /home/raspi/ctf-dimension-26/ctf;
  index index.html index.htm index.php;

  location = /retos.csv {
    deny all;
  }

  location ^~ /private/ {
    deny all;
  }

  location ^~ /logs/ {
    deny all;
  }

  location = /backend.php {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/backend.php;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
  }

  location ~ \.php$ {
    return 404;
  }

  location / {
    try_files $uri $uri/ $uri/index.html =404;
  }
}
```

## Cookies y sesiones

- Con host único (same-origin) la cookie `usuario_b64` se comparte automáticamente entre `/backend.php` y `/facil|medio|dificil/*` porq usa `path=/`.
- En frontend se debe mantener `credentials: 'include'` en `fetch` al backend. Porfiplis.
