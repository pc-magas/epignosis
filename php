#!/bin/sh

SCRIPT_DIR=$(dirname "$0")

docker exec -u www-data epignosis_recruitment_php81 php "$@"

exit $?