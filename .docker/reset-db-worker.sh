#!/bin/sh

set -eu

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_NAME:-crm_db}"
DB_USER="${DB_USER:-crm_user}"
DB_PASSWORD="${DB_PASSWORD:-crm_password}"

export PGPASSWORD="${DB_PASSWORD}"

echo "Waiting for database..."
until pg_isready -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d "${DB_NAME}" >/dev/null 2>&1; do
  sleep 2
done

echo "Starting DB reset loop (every 30 minutes)..."

while true; do
    echo "[$(date)] Resetting database with demo data..."
    
    psql \
      -v ON_ERROR_STOP=1 \
      -h "${DB_HOST}" \
      -p "${DB_PORT}" \
      -U "${DB_USER}" \
      -d "${DB_NAME}" <<'SQL'
    SELECT pg_advisory_lock(hashtext('crm_reset_db_lock'));
    
    DROP SCHEMA public CASCADE;
    CREATE SCHEMA public;
    GRANT ALL ON SCHEMA public TO public;
    
    \i /var/www/symfony/init.sql
    
    SELECT pg_advisory_unlock(hashtext('crm_reset_db_lock'));
SQL

    echo "[$(date)] Schema reset complete. Seeding demo data..."
    php bin/console app:seed-demo-data --force || true
    
    echo "[$(date)] Reset complete. Sleeping for 30 minutes..."
    sleep 1800
done
