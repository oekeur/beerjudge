#!/usr/bin/env bash
# Walks every user journey in docs/user-journeys.md against a throwaway server.
# Fresh SQLite database with BJCP styles and DemoSeeder, server on localhost:8010
# (override with JOURNEY_PORT), journeys in story order. Pass journey names to
# run a subset against the current database, e.g. `run-all.sh m4-judge`.
set -u
HERE="$(cd "$(dirname "$0")" && pwd)"
. "$HERE/env.sh"
OUT="$REPO/storage/logs/journeys"
JOURNEYS=(m1-sign-in a1-onboard-member a2-setup-competition m2-profile m3-submit-entry a3-manage-entries a4-run-judging m4-judge a5-close-and-publish m5-results a6-reopen a7-styles a8-guards)

if ss -ltn "sport = :$JOURNEY_PORT" | grep -q LISTEN; then
    echo "Port $JOURNEY_PORT is in use; stop that server or set JOURNEY_PORT." >&2
    exit 1
fi
[ -f "$REPO/public/build/manifest.json" ] || { echo "Run npm run build first." >&2; exit 1; }

if [ $# -eq 0 ]; then
    rm -rf "$DB_DATABASE" "$OUT"
    touch "$DB_DATABASE"
    ( cd "$REPO" && php artisan migrate --force -q && php artisan db:seed --force -q && php artisan db:seed --class=DemoSeeder --force -q ) || exit 1
else
    JOURNEYS=("$@")
fi

mkdir -p "$OUT"
( cd "$REPO" && exec php artisan serve --host=localhost --port="$JOURNEY_PORT" ) > "$OUT/serve.log" 2>&1 &
SERVER=$!
trap 'kill "$SERVER" 2>/dev/null; pkill -f "artisan serve --host=localhost --port=$JOURNEY_PORT" 2>/dev/null' EXIT
for _ in $(seq 1 20); do curl -fs -o /dev/null "$APP_URL/login" && break; sleep 0.5; done

for j in "${JOURNEYS[@]}"; do
    echo "=== $j"
    node "$HERE/$j.mjs" 2>&1 | grep -E "^(PASS|FAIL|FATAL)|^  [a-z0-9]|5xx|pageerror"
done
echo "Recordings and screenshots: $OUT"
