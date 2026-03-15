#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOST="${HOST:-0.0.0.0}"
PORT="${PORT:-8080}"

cd "$ROOT_DIR"

if [ ! -d "$ROOT_DIR/vendor" ]; then
    echo "Instalando dependencias PHP..."
    composer require scssphp/scssphp
fi

if [ ! -d "$ROOT_DIR/bootstrap-scss" ]; then
    echo "Descargando Bootstrap SCSS..."
    wget -q https://github.com/twbs/bootstrap/archive/refs/tags/v5.3.8.zip -O "$ROOT_DIR/bs.zip"
    unzip -q "$ROOT_DIR/bs.zip" -d "$ROOT_DIR"
    mv "$ROOT_DIR/bootstrap-5.3.8/scss" "$ROOT_DIR/bootstrap-scss"
    rm -rf "$ROOT_DIR/bs.zip" "$ROOT_DIR/bootstrap-5.3.8"
fi


echo "Iniciando servidor de pruebas en http://$HOST:$PORT"
echo "Root público: $ROOT_DIR/ctf"

php -S "$HOST:$PORT" -t ctf
