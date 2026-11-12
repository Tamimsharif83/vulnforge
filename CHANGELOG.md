# Changelog

All notable changes to VulnForge are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added — structured security logging (blue-team practice)

High-fidelity, standard-format logs (ECS JSON Lines) so VulnForge traffic can be
analyzed in any SIEM-style tool. Logging is observational only: it never changes
a response, alters a lab, or labels traffic as malicious.

- **Phase 1 — logging engine (`app/src/Logger.php`).** A self-contained,
  fail-safe ECS writer: safe JSON encoding (invalid UTF-8 substituted, control
  bytes incl. newline and NUL escaped so log lines cannot be forged), dotted
  ECS keys expanded to nested JSON, per-request `http.request.id`, body
  truncation, field redaction, size-based rotation with gzip, trusted-proxy
  `X-Forwarded-For` client-IP resolution, RFC 3339 UTC millisecond timestamps,
  and environment-variable configuration (enabled by default). Covered by
  `tests/LoggerTest.php` (plain PHP, no framework).
- **Phase 2 — HTTP access log (`logs/access.jsonl`).** One ECS record per
  request, written via a PHP `auto_prepend_file` so every PHP execution is
  captured — routed pages and directly-executed uploaded files (web shells)
  alike. Records carry the full ECS request/response/client/identity set,
  allowlisted request headers (cookie names only, Authorization scheme only),
  upload metadata with SHA-256 (never contents), body capture with truncation,
  and a session hash (never the raw token). The edge proxy now forwards
  `X-Forwarded-For`, and the in-browser Attack Console relay forwards the real
  client IP, so console-driven attacks keep their true source address. No
  response headers or lab/difficulty/solved signals are logged.
- **Phase 3 — application security events (`logs/app-events.jsonl`).** Events
  the access log cannot show, hooked where the app already performs each action
  (never changing it): authentication (login success/failure, token accept),
  account changes (role-changed, user-updated), file upload (path + SHA-256),
  file read (requested + resolved path), database query errors, server outbound
  requests (URL, resolved IP, status), OS command execution (command line), and
  app errors (fatals / HTTP 500). Every event carries the request's
  `http.request.id` so it joins the access log. Covered by
  `tests/AppEventsTest.php`.
- **Phase 4 — raw proxy log (`logs/server-access.log`).** The edge nginx also
  writes a standard combined access log into the shared logs directory, so the
  raw proxy view can be compared with the app's richer ECS log.
- **Phase 5 — storage, rotation & Docker.** The log directory is bind-mounted to
  `./logs` on the host (readable without `docker exec`). Logging is configured by
  environment variables, enabled by default in the compose setup. A small app
  entrypoint makes the bind-mounted log directory writable by the web server user
  and readable from the host. The app remains published on `127.0.0.1` only.
- **Phase 6 — lab hygiene helpers.** `scripts/rotate-lab-logs.sh` archives the
  current logs to `logs/archive/<UTC-timestamp>.tar.gz` and starts fresh files;
  `scripts/mark-exercise.sh "<name>" start|end` records ground-truth markers to a
  separate `logs/exercise-markers.jsonl` (never into the access or app-event
  logs). `logs/` is git-ignored except for a `.gitkeep`.
- **Phase 7 — documentation & tests.** `docs/logging.md` (field reference,
  example lines, environment variables, redaction trade-off, how to read the
  logs, and a five-minute walkthrough), a README "Logging for blue-team practice"
  section, and `make test` / `make rotate-logs` / `make loglive` targets. The
  plain-PHP suites (`tests/LoggerTest.php`, `tests/AppEventsTest.php`) cover
  required fields and types, body truncation and hostile-payload handling, JSON
  validity under newlines/quotes/unicode/NUL, fail-safe behavior, redaction,
  rotation, and overhead.
