#!/bin/sh
# Append a ground-truth marker to logs/exercise-markers.jsonl. This is a SEPARATE
# file; markers are never written into access.jsonl or app-events.jsonl, so they
# cannot contaminate the data you analyze. Use the markers afterwards to measure
# how well your detections found the activity between start and end.
#
#   scripts/mark-exercise.sh "sqli sweep" start
#   scripts/mark-exercise.sh "sqli sweep" end
set -eu

if [ $# -lt 2 ] || { [ "$2" != "start" ] && [ "$2" != "end" ]; }; then
    echo "usage: $0 \"<exercise name>\" start|end" >&2
    exit 2
fi

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
LOG_DIR="${VF_HOST_LOG_DIR:-$SCRIPT_DIR/../logs}"
mkdir -p "$LOG_DIR"

# JSON-escape the name (backslash and double-quote).
NAME=$(printf '%s' "$1" | sed 's/\\/\\\\/g; s/"/\\"/g')
TS=$(date -u +%Y-%m-%dT%H:%M:%S.000Z)

printf '{"@timestamp":"%s","marker":"%s","exercise":"%s"}\n' "$TS" "$2" "$NAME" \
    >> "$LOG_DIR/exercise-markers.jsonl"
echo "marked '$1' $2 at $TS -> $LOG_DIR/exercise-markers.jsonl"
