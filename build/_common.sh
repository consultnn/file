#!/usr/bin/env bash

readonly DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
readonly CONTAINER_PREFIX=$(echo $(basename ${DIR}) | sed 's/[^a-zA-Z0-9_]//g');
readonly CONTAINER_PHP="${CONTAINER_PREFIX}_php_1";

# Форматирование текста
readonly RED=`tput setaf 1`
readonly GREEN=`tput setaf 2`
readonly YELLOW=`tput setaf 3`
readonly BLUE=`tput setaf 4`
readonly MAGENTA=`tput setaf 5`
readonly CYAN=`tput setaf 6`
readonly RESET=`tput sgr0`
readonly BOLD=`tput bold`

function section()
{
    echo "${BOLD}${GREEN}"
    echo "---------------------------------------"
    echo $1
    echo "---------------------------------------"
    echo "${RESET}"
}