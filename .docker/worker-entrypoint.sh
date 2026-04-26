#!/bin/sh

set -eu

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_NAME:-crm_db}"
DB_USER="${DB_USER:-crm_user}"
DB_PASSWORD="${DB_PASSWORD:-crm_password}"
PROCESS_INTERVAL="${PROCESS_INTERVAL:-4}"

export PGPASSWORD="${DB_PASSWORD}"

until pg_isready -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d "${DB_NAME}" >/dev/null 2>&1; do
  sleep 2
done

psql \
  -v ON_ERROR_STOP=1 \
  -h "${DB_HOST}" \
  -p "${DB_PORT}" \
  -U "${DB_USER}" \
  -d "${DB_NAME}" >/dev/null <<'SQL'
SELECT pg_advisory_lock(hashtext('crm_init_sql'));
\i /var/www/symfony/init.sql
SELECT pg_advisory_unlock(hashtext('crm_init_sql'));
SQL

php bin/console app:seed-demo-data --if-empty --force

while true; do
  php bin/console app:process-email-campaigns --limit=10 || true
  sleep "${PROCESS_INTERVAL}"
done
