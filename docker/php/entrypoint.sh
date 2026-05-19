#!/bin/sh
set -e

mkdir -p /app/storage/tasks
mkdir -p /app/storage/logs
mkdir -p /app/storage/shap_outputs

chown -R www-data:www-data /app/storage
chmod -R u+rwX,g+rwX /app/storage

exec "$@"