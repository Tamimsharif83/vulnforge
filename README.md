# VulnForge

A realistic, intentionally vulnerable web application for hands-on web
exploitation training. VulnForge runs a small e-commerce and blog app
("ForgeShop") whose features contain **real, exploitable** vulnerabilities —
genuine SQL injection against MySQL, command execution in the container,
arbitrary file read, SSRF to internal services, a working file-upload webshell,
and more. Each bug is wrapped in a teaching workspace that shows you exactly
what the server did and why your input worked.

It is in the lineage of DVWA, OWASP Juice Shop, and the PortSwigger Academy,
with two priorities: **realism** (drive it with the built-in console, Burp, or
curl against a real server) and **experience** (a workbench that explains cause
and effect at every step, with a Low → Medium → High → Secure progression per
lab).

> Intentionally vulnerable. Run only on your own machine, on localhost.
> See [DISCLAIMER.md](DISCLAIMER.md).

## Run it

Requirements: Docker with the Compose plugin.

```bash
docker compose up --build
# then open http://localhost:8080
```

Or with the Makefile:

```bash
make up            # build and start
make reset-db      # wipe and re-seed the database
make down          # stop
```

The app is published only on **127.0.0.1:8080**. To use a different port:

```bash
VULNFORGE_PORT=8088 docker compose up --build
```

### Demo credentials

ForgeShop is seeded with these accounts (used by the auth and access-control
labs). You rarely need to log in manually — most labs are solved by crafting
requests.

| Username | Password          | Role  |
|----------|-------------------|-------|
| admin    | `Forge!Admin#2026`| admin |
| alice    | `alice123`        | user  |
| bob      | `bob123`          | user  |

## Safety and containment

VulnForge is deliberately insecure. Treat it accordingly.

- **Local only.** Only the `edge` proxy publishes a port, bound to `127.0.0.1`.
  The `app`, `db`, `internal-api`, and `metadata` services sit on **internal**
  Docker networks with **no route to the internet**.
- **Never expose it** to the internet or a shared network, and never host a
  public running instance. Publishing the source (as this repo does) is fine.
- The dangerous capabilities (RCE, file read, SSRF) stay inside the container
  network. The app cannot make outbound internet connections.

If any service fails to bind because a port is taken, set `VULNFORGE_PORT`.

## How a lab works

Open any lab from the dashboard. The workspace is split in two:

- **Left:** the live ForgeShop feature, fully usable.
- **Right:** a tabbed panel.
  - **Attack Console** — a Repeater-like client. Edit the method, path,
    headers, and body, press Send, and read the raw response. It hits the real
    server, so Burp and curl behave identically.
  - **What Happened** — after each Send, the exact thing the server did: the
    final SQL query (injected part highlighted), the shell command, the
    resolved file path, the decoded token, or the deserialized data.
  - **Source** — the vulnerable PHP for the current level, with the sink
    highlighted. Toggle the diff to see what changes between levels.
  - **Hints** — progressive, opt-in. They never hand you the full payload first.
  - **Fix** — root cause, the correct fix, and how to hunt the class on real
    targets.
  - **Report** — a filled finding template for the lab.
  - **Solution** — locked until you have opened every hint. It then reveals, for
    the current level, the exact steps, why each one works and why the exploit
    succeeds, and a **Run the exploit** button that loads the real request into
    the console, sends it to the live server, and shows the result — so you can
    watch it work when the hints were not enough.

Switch the **security level** (Low / Medium / High / Secure) to see a weak
filter get bypassed and then closed. Solving a level is detected from real
effect (the secret file read, the admin token accepted), not from matching your
payload text.

The **Learn** page maps every lab to OWASP Top 10 and CWE, gives a Burp
methodology, a report template, CVSS basics, responsible-disclosure ethics, and
where to go next (HackerOne, Bugcrowd, Intigriti, PortSwigger, and WordPress
plugin CVEs via Patchstack and Wordfence).

## Logging for blue-team practice

VulnForge can produce **standard-format logs (ECS JSON Lines)** so you can attack
it, then analyze its logs in any SIEM-style tool (Elastic, Wazuh, Splunk). The
logs are observational and carry **no attack/lab/difficulty labels** — detection
is earned from the evidence. Logging is enabled by default and writes to the
host-readable `logs/` directory:

- `logs/access.jsonl` — rich ECS record per request (incl. web-shell requests),
- `logs/app-events.jsonl` — security events (logins, command exec, file reads,
  outbound requests, DB errors, …), joined by `http.request.id`,
- `logs/server-access.log` — the raw nginx combined proxy log.

```bash
tail -f logs/access.jsonl | jq .
scripts/mark-exercise.sh "sqli sweep" start   # ground-truth markers (separate file)
scripts/rotate-lab-logs.sh                    # archive + start fresh
```

See **[docs/logging.md](docs/logging.md)** for the full field reference, example
lines, environment variables, and a five-minute walkthrough.

## What's inside

Services (`docker-compose.yml`):

- `edge` — nginx reverse proxy; the only service with a published port.
- `app` — PHP 8.3 on Apache; the ForgeShop application and lab engine.
- `db` — MariaDB; real error-based, UNION, boolean, and time-based SQLi.
- `internal-api` — an internal-only HTTP service (SSRF target).
- `metadata` — a mock cloud metadata service at `169.254.169.254` (SSRF target).

Vulnerability catalog (each with Low → Secure levels):

SQL injection (UNION, auth bypass, blind/time-based) · Cross-site scripting
(reflected, stored, DOM) · OS command injection · Path traversal / LFI (with
`php://filter`) · File upload to webshell · SSRF (internal service and cloud
metadata) · Server-side template injection · XXE · Insecure deserialization ·
JWT forgery (`alg:none`, weak secret) · IDOR · Mass assignment · CSRF · Open
redirect · CORS misconfiguration.

## Adding a new lab

A lab is a directory under `app/labs/<slug>/`:

```
app/labs/<slug>/
  meta.php      # title, category, severity, cwe, scenario, hints, fix, report,
                # the default console request, and the payload palette
  low.php       # the vulnerable handler for each level; the one that runs is
  medium.php    # chosen by the security-level cookie. Mark the vulnerable line
  high.php      # with a trailing  // @sink  comment so the Source tab highlights it.
  secure.php
```

Each level handler renders the live feature and records what it did:

- `Trace::step($label, $text, $input)` shows a sink in **What Happened**, with
  `$input` highlighted inside `$text`.
- `Db::raw($sql, $input)` runs a string-built query (and highlights the injected
  part); `Db::prepared($sql, $params)` runs a parameterized one.
- `Lab::markSolved($slug, current_level())` records a solve. Call it only when
  the exploit truly succeeded (a secret read, an admin token accepted) so the
  solved state reflects real effect.

`meta.php` returns an array; see `app/labs/sqli-search/meta.php` for the full
shape. New labs appear on the dashboard and the Learn map automatically.

Solutions are in [SOLUTIONS.md](SOLUTIONS.md) (kept separate on purpose).

## License

MIT. See [LICENSE](LICENSE).
