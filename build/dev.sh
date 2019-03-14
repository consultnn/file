#!/usr/bin/env bash

{
    source _common.sh
} 2>> /dev/null
source $(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/_common.sh

section "Used environment"
echo "Development"

section "Install composer dependencies"
docker exec ${CONTAINER_PHP} composer install --prefer-dist --optimize-autoloader