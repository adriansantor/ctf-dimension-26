# Setup de servidor (www-data)

## 1) Copiar ctf al servidor

```bash
sudo rm -rf /var/www/ctf
sudo mkdir -p /var/www
sudo cp -a /ruta/a/tu/proyecto/ctf /var/www/ctf
```

## 2) Propietario y permisos

```bash
# Directorios base legibles por nginx/php-fpm
sudo chown -R root:root /var/www/ctf
sudo find /var/www/ctf -type d -exec chmod 755 {} \;
sudo find /var/www/ctf -type f -exec chmod 644 {} \;

# El backend debe poder escribir estado y logs
sudo chown www-data:www-data /var/www/ctf/retos.csv
sudo chmod 664 /var/www/ctf/retos.csv

sudo chown -R www-data:www-data /var/www/ctf/logs
sudo find /var/www/ctf/logs -type d -exec chmod 775 {} \;
sudo find /var/www/ctf/logs -type f -exec chmod 664 {} \;
```

## 3) Aplicar config nginx y recargar

```bash
sudo cp /ruta/a/tu/proyecto/doc/nginx.conf.example /etc/nginx/sites-available/ctf
sudo ln -sf /etc/nginx/sites-available/ctf /etc/nginx/sites-enabled/ctf
sudo nginx -t
sudo systemctl reload nginx
```

## 4) Comprobaciones rapidas

```bash
# Debe responder landing
curl -i http://TU_HOST/

# Debe bloquear recursos sensibles
curl -i http://TU_HOST/retos.csv
curl -i http://TU_HOST/private/flags.json
curl -i http://TU_HOST/logs/backend.log
```

Si usas una distro donde PHP-FPM no corre como `www-data`, sustituye ese usuario en los comandos anteriores por el real del servicio.
