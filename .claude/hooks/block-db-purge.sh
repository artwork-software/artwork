#!/usr/bin/env bash
# PreToolUse hook: blocks any DB-purging command regardless of wrapping
# (ddev exec, bash -c "...", env prefixes, &&, pipes, etc.).
# Scans the FULL command string content, not just its prefix.

input="$(cat)"

# Extract the command field from the tool input JSON.
if command -v jq >/dev/null 2>&1; then
  cmd="$(printf '%s' "$input" | jq -r '.tool_input.command // empty')"
else
  # Fallback: scan the whole payload if jq is unavailable.
  cmd="$input"
fi

# Normalize whitespace so "migrate:fresh" can't be split across newlines/spaces.
scan="$(printf '%s' "$cmd" | tr '\n\t' '  ')"

# Forbidden destructive DB operations (artisan + raw SQL).
patterns='migrate:fresh|migrate:reset|migrate:refresh|db:wipe|DROP[[:space:]]+DATABASE|DROP[[:space:]]+SCHEMA'

if printf '%s' "$scan" | grep -iqE "$patterns"; then
  echo "BLOCKED: DB-purge ist verboten (migrate:fresh/reset/refresh, db:wipe, DROP DATABASE). Befehl abgelehnt — auch in ddev exec / bash -c / env-prefix erkannt." >&2
  exit 2
fi

exit 0
