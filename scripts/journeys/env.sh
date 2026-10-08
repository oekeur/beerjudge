# Environment for the journey walkthrough server and its tinker probes.
# Exported vars override .env (Laravel loads it immutably), so .env, Postgres
# and Redis stay untouched. Same pattern as scripts/dev-lite.sh.
REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
export DB_CONNECTION=sqlite
export DB_DATABASE="$REPO/database/journeys.sqlite"
export SESSION_DRIVER=database
export SESSION_ENCRYPT=false
export QUEUE_CONNECTION=sync
export CACHE_STORE=database
export MAIL_MAILER=log
export JOURNEY_PORT="${JOURNEY_PORT:-8010}"
export APP_URL="http://localhost:$JOURNEY_PORT"
export JOURNEY_URL="$APP_URL"
