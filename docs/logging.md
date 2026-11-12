# Logging for blue-team practice

VulnForge produces **high-fidelity logs in a standard format — ECS (Elastic
Common Schema) JSON Lines** — so you can attack the app and then analyze its logs
in any SIEM-style tool (Elastic, Wazuh, Splunk, or anything that reads ECS). The
logging is not tied to any one consumer; it just writes standard JSON.

Logging is **observational only**. It never changes a response, alters a lab, or
labels traffic as malicious. There are **no `is_attack` flags, lab names, or
difficulty levels** in the logs — detection has to be earned from the evidence,
exactly as on a real target.

## What gets written

Everything lands in the `logs/` directory, which is bind-mounted to your host so
you can read it directly (no `docker exec` needed):

| File | What it is |
|------|------------|
| `logs/access.jsonl` | One rich ECS record per HTTP request (the main log). Captures every PHP request, **including uploaded web-shell requests**. |
| `logs/app-events.jsonl` | Security-relevant application events the access log cannot show: logins, command execution, file reads, outbound requests, DB errors, and more. Joins to the access log by `http.request.id`. |
| `logs/server-access.log` | The edge proxy's raw nginx **combined** access log, for comparison with the richer app log. |
| `logs/exercise-markers.jsonl` | Ground-truth markers you write with `mark-exercise.sh` (kept separate from the data you analyze). |
| `logs/archive/*.tar.gz` | Snapshots created by `rotate-lab-logs.sh`. |

## Reading the logs from the host

```bash
# follow requests as they happen
tail -f logs/access.jsonl

# pretty-print with jq
tail -n 20 logs/access.jsonl | jq .

# every request that touched the users table
jq -c 'select(.url.query // "" | test("users"))' logs/access.jsonl

# all failed logins
jq -c 'select(.event.action=="user-login" and .event.outcome=="failure")' logs/app-events.jsonl

# join: find the access record for a given app-event
RID=$(jq -r 'select(.event.action=="command-exec").http.request.id' logs/app-events.jsonl | head -1)
jq -c --arg rid "$RID" 'select(.http.request.id==$rid)' logs/access.jsonl
```

## Access log fields (`vulnforge.access`)

| Field | Type | Example | ECS |
|-------|------|---------|-----|
| `@timestamp` | date | `2026-11-12T09:42:37.555Z` | core |
| `ecs.version` | keyword | `8.11.0` | ecs |
| `event.kind` / `event.category` / `event.type` | keyword[] | `event` / `["web"]` / `["access"]` | event |
| `event.dataset` | keyword | `vulnforge.access` | event |
| `event.duration` | long (ns) | `14366734` | event |
| `event.outcome` | keyword | `success` (≥500 → `failure`) | event |
| `http.request.id` | keyword | `95d67162-…` (join key) | http |
| `http.request.method` | keyword | `GET` | http |
| `http.version` | keyword | `1.1` | http |
| `url.original` / `url.path` / `url.query` | keyword | `/labs/sqli-search/run?q=…` | url |
| `http.request.referrer` | keyword | `null` | http |
| `http.request.mime_type` | keyword | `application/x-www-form-urlencoded` | http |
| `http.request.body.bytes` | long | `31` | http |
| `http.request.body.content` | keyword | `username=admin&password=hunter2` | http |
| `http.response.status_code` | long | `200` | http |
| `http.response.body.bytes` | long | `2170` | http |
| `http.response.mime_type` | keyword | `text/html` | http |
| `source.ip` / `source.port` | ip / long | `172.22.0.1` / `56552` | source |
| `user_agent.original` | keyword | `curl/8.20.0` | user_agent |
| `user.name` / `user.id` | keyword | present when a feature authenticates a user | user |
| `file.name` / `file.extension` / `file.size` / `file.mime_type` / `file.hash.sha256` | — | upload metadata (never contents) | file |
| `vulnforge.session_hash` | keyword | sha256 of the session cookie (never the raw token) | custom |
| `vulnforge.http.request.headers` | object | allowlisted headers (see below) | custom |
| `vulnforge.body_truncated` | boolean | `true` when the body exceeded the cap | custom |

**Header allowlist.** Only `Host`, `Content-Type`, `Origin`, and
`X-Forwarded-For` are kept with full values; cookies are recorded as
**names only** (`cookie_names`); `Authorization` is recorded as its
**scheme only** (`authorization_scheme`). Response headers are never logged.

## Application event fields (`vulnforge.app`)

Every event has the shared envelope (`@timestamp`, `ecs.version`,
`event.dataset: vulnforge.app`, `event.kind: event`, `service.*`, `host.name`,
and the `http.request.id` join key) plus:

| Event | `event.category` / `event.action` | Key fields |
|-------|-----------------------------------|------------|
| Login / token | `authentication` / `user-login` | `event.outcome`, `user.name` (attempted), `event.reason` |
| Account change | `iam` / `role-changed`, `user-updated` | `user.target.name`, `vulnforge.account.*` |
| File upload | `file` / `file-upload` | `file.path`, `file.name`, `file.size`, `file.hash.sha256` |
| File read | `file` / `file-read` | `file.path` (resolved), `vulnforge.file.requested` |
| DB error | `database` / `query-error` | `error.type`, `error.message` (truncated) |
| Outbound request | `network` / `outbound-request` | `url.original`, `destination.domain`, `destination.ip`, `http.response.status_code` |
| Command execution | `process` / `command-exec` | `process.command_line`, `process.exit_code` (when known) |
| App error | `web` / `app-error` | `error.type`, `error.message`, `error.stack_trace` |

## Example lines

**A normal page view** (`access.jsonl`):

```json
{"@timestamp":"2026-11-12T09:42:37.512Z","event":{"dataset":"vulnforge.access","kind":"event","category":["web"],"type":["access"],"duration":9862248,"outcome":"success"},"http":{"request":{"id":"f5b65c1a-…","method":"GET","body":{"bytes":0,"content":null}},"version":"1.1","response":{"status_code":200,"body":{"bytes":8841},"mime_type":"text/html"}},"url":{"original":"/labs/sqli-search","path":"/labs/sqli-search","query":null},"source":{"ip":"172.22.0.1","port":56550},"user_agent":{"original":"Mozilla/5.0"},"vulnforge":{"session_hash":null,"http":{"request":{"headers":{"Host":"127.0.0.1:8088","X-Forwarded-For":"172.22.0.1","cookie_names":["vf_student"]}}}}}
```

**A SQL-injection attempt** — nothing flags it as an attack; the payload is just
there in `url.query`, to be found:

```json
{"…":"…","http":{"request":{"id":"95d67162-…","method":"GET"},"response":{"status_code":200}},"url":{"path":"/labs/sqli-search/run","query":"q=x%27+UNION+SELECT+1%2cusername%2cpassword_md5%2c4+FROM+users+--+-"},"source":{"ip":"172.22.0.1"},"user_agent":{"original":"curl/8.20.0"}}
```

**A failed login** (`app-events.jsonl`) — note the password is **not** in this
event; the attempted username is:

```json
{"@timestamp":"2026-11-12T09:42:37.579Z","event":{"dataset":"vulnforge.app","kind":"event","category":["authentication"],"type":["start"],"action":"user-login","outcome":"failure","reason":"invalid-credentials"},"http":{"request":{"id":"5f526941-…"}},"user":{"name":"admin"}}
```

**A file upload** (`app-events.jsonl`) — metadata and a SHA-256, never contents:

```json
{"event":{"dataset":"vulnforge.app","category":["file"],"action":"file-upload"},"http":{"request":{"id":"65bca0b7-…"}},"file":{"path":"…/public/uploads/shell.phtml","name":"shell.phtml","extension":"phtml","size":21,"hash":{"sha256":"345eae0154e438a475fd08e9a66228f96636ab36cf2826cb0fca1523684f1ba1"}}}
```

**Command execution** (`app-events.jsonl`) — the shell command as run:

```json
{"event":{"dataset":"vulnforge.app","category":["process"],"action":"command-exec"},"http":{"request":{"id":"75d199df-…"}},"process":{"command_line":"ping -c 1 127.0.0.1; id 2>&1"}}
```

**An outbound (SSRF) request** (`app-events.jsonl`) — the server reaching cloud
metadata:

```json
{"event":{"dataset":"vulnforge.app","category":["network"],"action":"outbound-request"},"http":{"request":{"id":"78df9170-…"},"response":{"status_code":200}},"url":{"original":"http://169.254.169.254/latest/meta-data/"},"destination":{"domain":"169.254.169.254","ip":"169.254.169.254"}}
```

## Environment variables

Set these in `docker-compose.yml` (they are configured there, enabled by default).

| Variable | Default | Meaning |
|----------|---------|---------|
| `VF_LOG_ENABLED` | `true` | Master on/off switch. |
| `VF_LOG_DIR` | `/var/www/app/logs` | Where logs are written (bind-mounted to `./logs`). |
| `VF_LOG_MAX_BODY_BYTES` | `8192` | Max logged request-body size; longer bodies are truncated and marked `vulnforge.body_truncated`. |
| `VF_LOG_REDACT_FIELDS` | *(empty)* | Comma-separated form-field names to redact. **Empty on purpose** — see below. |
| `VF_TRUSTED_PROXIES` | `127.0.0.1,::1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16` | Peers whose `X-Forwarded-For` is trusted. |
| `VF_LOG_STDOUT` | `false` | Also mirror lines to stdout. |
| `VF_LOG_ROTATE_BYTES` | `20971520` (20 MB) | Rotate a file when it would exceed this size. |
| `VF_LOG_ROTATE_KEEP` | `5` | Number of gzipped backups to keep (`access.jsonl.1.gz` … `.5.gz`). |

## Redaction trade-off (why passwords are not redacted by default)

On a normal app you would redact `password` fields. VulnForge is a **training
target**, and injection payloads very often live in the password field (SQLi auth
bypass, NoSQL operators, etc.). If the password field were redacted, those
attacks would be invisible in the logs and you could not learn to detect them.

So `VF_LOG_REDACT_FIELDS` is **empty by default**: bodies are logged in full.
When a field *is* redacted, it is written as `"[REDACTED len=N]"` (the length is
kept so you can still see the shape). To redact, for example:

```yaml
VF_LOG_REDACT_FIELDS: "password,csrf_token"
```

## Source IP and the Attack Console

You can attack a lab two ways: directly (Burp, curl) or via the in-browser
**Attack Console**, which re-sends your request through a local relay. The relay
forwards your real client IP (`X-Forwarded-For`), and the edge proxy is trusted,
so **both paths keep the true `source.ip`** instead of showing the internal relay
or proxy. On a localhost-only lab these addresses are naturally private (the
Docker gateway); on a real target they would be the public client.

## Rotating and marking exercises

```bash
# mark the window you are about to run (written to a SEPARATE file)
scripts/mark-exercise.sh "sqli sweep" start
#   … run your attack …
scripts/mark-exercise.sh "sqli sweep" end

# archive the current logs and start fresh for the next exercise
scripts/rotate-lab-logs.sh      # -> logs/archive/<UTC-timestamp>.tar.gz
```

The markers in `logs/exercise-markers.jsonl` are your ground truth: compare what
your detections found against the window between `start` and `end`. They are
never written into `access.jsonl` or `app-events.jsonl`.

## Five-minute try it

```bash
# 1. start the lab (logs enabled by default)
VULNFORGE_PORT=8088 docker compose up --build -d

# 2. start fresh
scripts/rotate-lab-logs.sh

# 3. run one SQL injection …
curl "http://localhost:8088/labs/sqli-search/run?q=x%27+UNION+SELECT+1,username,password_md5,4+FROM+users+--+-" \
  -b vf_level=low

# 4. … and one failed login
curl "http://localhost:8088/labs/sqli-login/run" -b vf_level=secure \
  --data "username=admin&password=wrongpassword"

# 5. look at what the server recorded
tail -n 5 logs/access.jsonl | jq .
jq -c 'select(.event.action=="user-login")' logs/app-events.jsonl
```

You will see the injection payload sitting in `url.query` of an ordinary-looking
access record, and a `user-login` / `failure` event for `admin` — with no label
telling you either one was an attack. That is the point.
