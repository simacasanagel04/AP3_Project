#!/bin/bash

# Export environment variables explicitly
export DB_HOST="shinkansen.proxy.rlwy.net"
export DB_PORT="30981"
export DB_NAME="railway"
export DB_USER="root"
export DB_PASSWORD="BJscjrBkAzQTWQlFnMNuuWjHxYUirDeh"

# Start PHP server
php -S 0.0.0.0:${PORT:-8080} -t .