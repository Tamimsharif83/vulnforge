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
