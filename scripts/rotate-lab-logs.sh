#!/bin/sh
# Archive the current logs and start fresh, to separate one exercise from the
# next. Archives go to logs/archive/<UTC-timestamp>.tar.gz; the live files are
# then reset to empty.
#
# The app and the proxy write their logs as a different user (inside the
# containers), so those files are reset through the owning container when Docker
# is reachable, with a host-side fallback. The markers file is host-owned and is
# reset directly.
set -eu

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
REPO_DIR=$(cd "$SCRIPT_DIR/.." && pwd)
LOG_DIR="${VF_HOST_LOG_DIR:-$REPO_DIR/logs}"
cd "$LOG_DIR"

TS=$(date -u +%Y%m%dT%H%M%SZ)
mkdir -p archive

FILES=""
for f in access.jsonl app-events.jsonl server-access.log exercise-markers.jsonl; do
    [ -f "$f" ] && FILES="$FILES $f"
done

if [ -z "$FILES" ]; then
    echo "no logs to rotate in $LOG_DIR"
    exit 0
fi

# shellcheck disable=SC2086
tar -czf "archive/$TS.tar.gz" $FILES
echo "archived:$FILES -> $LOG_DIR/archive/$TS.tar.gz"

reset_app_file() {
    # Truncate an app-owned log via the app container (root), else try host.
    (cd "$REPO_DIR" && docker compose exec -T app sh -c ": > /var/www/app/logs/$1") >/dev/null 2>&1 \
        || : > "$1" 2>/dev/null \
        || echo "  note: could not reset $1 (reset it with the lab stopped, or via docker)"
}

reset_proxy_log() {
    # Truncate the proxy log in the edge container, then have nginx reopen it.
    (cd "$REPO_DIR" \
        && docker compose exec -T edge sh -c ': > /var/log/nginx/vf/server-access.log' \
        && docker compose exec -T edge nginx -s reopen) >/dev/null 2>&1 \
        || : > server-access.log 2>/dev/null \
        || echo "  note: could not reset server-access.log"
}

for f in $FILES; do
    case "$f" in
        server-access.log) reset_proxy_log ;;
        access.jsonl|app-events.jsonl) reset_app_file "$f" ;;
        *) : > "$f" 2>/dev/null || true ;;
    esac
done
echo "fresh logs started"
