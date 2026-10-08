#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

PHP_BASE_IMAGE="php:7.1.33-cli"
PHP_TEST_IMAGE="productcomments-php-test:7.1.33"
MYSQL_IMAGE="mysql:5.7.44"
COMPOSER_VERSION="2.2.24"
NETWORK_NAME="pc-test"
MYSQL_CONTAINER="pc-mysql"
MYSQL_ROOT_PASSWORD="productcomments"
MYSQL_DATABASE="productcomments"
READINESS_TIMEOUT_SECONDS=120
RANDOM_SEED="20261008"

DIGEST_FILE="$ROOT_DIR/tests/docker-images.txt"

cleanup() {
  docker rm -f "$MYSQL_CONTAINER" >/dev/null 2>&1 || true
  docker network rm "$NETWORK_NAME" >/dev/null 2>&1 || true
}
trap cleanup EXIT

if ! docker image inspect "$PHP_BASE_IMAGE" >/dev/null 2>&1; then
  docker pull "$PHP_BASE_IMAGE"
fi
if ! docker image inspect "$MYSQL_IMAGE" >/dev/null 2>&1; then
  docker pull "$MYSQL_IMAGE"
fi

PHP_BASE_DIGEST="$(docker image inspect --format='{{index .RepoDigests 0}}' "$PHP_BASE_IMAGE")"
MYSQL_DIGEST="$(docker image inspect --format='{{index .RepoDigests 0}}' "$MYSQL_IMAGE")"
if [[ -z "$PHP_BASE_DIGEST" || -z "$MYSQL_DIGEST" ]]; then
  echo "Failed to resolve image digests for $PHP_BASE_IMAGE or $MYSQL_IMAGE" >&2
  exit 1
fi

docker build --network=host -t "$PHP_TEST_IMAGE" "$ROOT_DIR/tests"
PHP_DIGEST="$(docker image inspect --format='{{.Id}}' "$PHP_TEST_IMAGE")"

cat >"$DIGEST_FILE" <<EOF
PHP_BASE_IMAGE=$PHP_BASE_DIGEST
PHP_TEST_IMAGE=$PHP_DIGEST
MYSQL_IMAGE=$MYSQL_DIGEST
EOF

docker network inspect "$NETWORK_NAME" >/dev/null 2>&1 || docker network create "$NETWORK_NAME"
docker rm -f "$MYSQL_CONTAINER" >/dev/null 2>&1 || true
docker run -d --name "$MYSQL_CONTAINER" --network "$NETWORK_NAME" \
  -e MYSQL_ROOT_PASSWORD="$MYSQL_ROOT_PASSWORD" \
  -e MYSQL_DATABASE="$MYSQL_DATABASE" \
  "$MYSQL_DIGEST" \
  --character-set-server=utf8mb4 \
  --collation-server=utf8mb4_general_ci \
  --sql-mode=STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION,ONLY_FULL_GROUP_BY

elapsed=0
until docker exec "$MYSQL_CONTAINER" mysqladmin ping -h 127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD" --silent; do
  sleep 2
  elapsed=$((elapsed + 2))
  if [[ "$elapsed" -ge "$READINESS_TIMEOUT_SECONDS" ]]; then
    echo "MySQL did not become ready within ${READINESS_TIMEOUT_SECONDS}s" >&2
    exit 1
  fi
done

run_php_host() {
  docker run --rm --network host \
    -v "$ROOT_DIR":/app \
    -w /app/tests \
    "$PHP_TEST_IMAGE" \
    bash -lc "$1"
}

run_php_test() {
  docker run --rm --network "container:${MYSQL_CONTAINER}" \
    -v "$ROOT_DIR":/app \
    -w /app/tests \
    -e PC_DB_HOST="127.0.0.1" \
    -e PC_DB_PASSWORD="$MYSQL_ROOT_PASSWORD" \
    "$PHP_TEST_IMAGE" \
    bash -lc "$1"
}

INSTALL_DEPS="set -e
if [ ! -f composer.phar ]; then
  curl -fsSL -o composer-setup.php https://getcomposer.org/installer
  EXPECTED_SIG=\"\$(curl -fsSL https://composer.github.io/installer.sig)\"
  ACTUAL_SIG=\"\$(php -r \"echo hash_file('sha384', 'composer-setup.php');\")\"
  if [ \"\$EXPECTED_SIG\" != \"\$ACTUAL_SIG\" ]; then
    echo \"Invalid composer installer signature\" >&2
    exit 1
  fi
  php composer-setup.php --version=${COMPOSER_VERSION} --filename=composer.phar
  rm composer-setup.php
fi
php -d date.timezone=UTC composer.phar install --no-interaction
php -d date.timezone=UTC vendor/bin/phpunit --help | grep -q -- \"--order-by\""

run_php_host "$INSTALL_DEPS"

RUN_PHPUNIT='php -d date.timezone=UTC vendor/bin/phpunit -c phpunit.xml.dist'
RESULTS_FILE="/opt/cursor/artifacts/suite-results.md"
mkdir -p /opt/cursor/artifacts

append_run() {
  local label="$1"
  local extra_args="$2"
  local output
  if ! output="$(run_php_test "$RUN_PHPUNIT $extra_args" 2>&1)"; then
    echo "$output"
    exit 1
  fi
  echo "$output"
  {
    echo ""
    echo "### $label"
    echo '```'
    echo "$output"
    echo '```'
  } >>"$RESULTS_FILE"
}

COMMIT_SHA="$(git rev-parse HEAD)"
PHP_VERSION="$(run_php_test 'php -r "echo PHP_VERSION;"')"

cat >"$RESULTS_FILE" <<EOF
# productcomments test suite results

- Repository: Training-Datasmith/productcomments
- Date: $(date -u +%Y-%m-%dT%H:%M:%SZ)
- Branch: $(git rev-parse --abbrev-ref HEAD)
- Commit: $COMMIT_SHA
- PHP: $PHP_VERSION ($PHP_DIGEST)
- MySQL: $MYSQL_DIGEST
- Command base: $RUN_PHPUNIT
EOF

append_run "default order run 1" ""
append_run "default order run 2" ""
append_run "random order run 1" "--order-by=random --random-order-seed=$RANDOM_SEED"
append_run "random order run 2" "--order-by=random --random-order-seed=$RANDOM_SEED"

echo "Suite completed. Results: $RESULTS_FILE"
