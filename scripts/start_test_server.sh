#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOST="${HOST:-0.0.0.0}"
PORT="${PORT:-8080}"

cd "$ROOT_DIR"

echo "Iniciando servidor de pruebas en http://$HOST:$PORT"
echo "Root público: $ROOT_DIR/ctf"

php -S "$HOST:$PORT" -t ctf
