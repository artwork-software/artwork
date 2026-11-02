#!/usr/bin/env bash
# PreToolUse hook: blocks any DB-purging command regardless of wrapping
# (ddev exec, bash -c "...", env prefixes, &&, pipes, etc.).
# Scans the FULL command string content, not just its prefix.
# Guard against accidents, not a security boundary: variable expansion,
# scripts written to disk etc. still get through.

input="$(cat)"

# Extract the command field from the tool input JSON.
if command -v jq >/dev/null 2>&1; then
  cmd="$(printf '%s' "$input" | jq -r '.tool_input.command // empty')"
else
  # Fallback: scan the whole payload if jq is unavailable.
  cmd="$input"
fi

# Join backslash line continuations first, so "db:w\<newline>ipe" becomes "db:wipe".
line_continuation=$'\\\n'
cmd="${cmd//"$line_continuation"/}"

# Strip quotes and backslashes the shell would join or drop ("migrate:fre""sh", db:w'ipe', db:w\ipe),
# then normalize whitespace so "migrate:fresh" can't be split across newlines/spaces.
scan="$(printf '%s' "$cmd" | tr -d "\"'\\\\" | tr '\n\t' '  ')"

# Forbidden destructive DB operations (artisan, raw SQL, query builder, ddev).
patterns='migrate:fresh|migrate:reset|migrate:refresh|migrate:rollback|db:wipe'
patterns+='|DROP[[:space:]]+(DATABASE|SCHEMA|TABLE)'
patterns+='|TRUNCATE[[:space:]]+TABLE'
# Bare "TRUNCATE users" only at statement start, so "grep truncate resources/js" still passes.
patterns+='|(^|[;(|]|(^|[[:space:]])(-e|--execute=?|echo|<<<))[[:space:]]*TRUNCATE[[:space:]]+[[:alnum:]_`]+'
patterns+='|(->|::)truncate[[:space:]]*\('
patterns+='|ddev[[:space:]]+(import-db|snapshot[[:space:]]+restore)'

if printf '%s' "$scan" | grep -iqE "$patterns"; then
  echo "BLOCKED: DB-purge ist verboten (migrate:fresh/reset/refresh/rollback, db:wipe, DROP DATABASE/SCHEMA/TABLE, TRUNCATE, ddev import-db/snapshot restore). Befehl abgelehnt — auch in ddev exec / bash -c / env-prefix / mit Quotes erkannt." >&2
  exit 2
fi

exit 0
