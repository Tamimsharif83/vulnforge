<?php
$title = 'Learn - VulnForge';
// Group labs by category for the mapping table.
$byCat = [];
foreach ($labs as $lab) { $byCat[$lab['category']][] = $lab; }
?>
<section class="intro prose">
  <h1>From lab to real targets</h1>
  <p>VulnForge teaches mechanics you transfer to authorized testing: bug bounty programs and your own code review. This page maps each lab to the wider landscape and gives you a method, a report template, and the ethics that keep this legal.</p>
</section>

<section class="prose">
  <h2>Coverage map</h2>
  <p>Every lab is a real feature with a Low to Secure progression. Open one, solve it in the console, read What Happened, then read Fix.</p>
  <table class="map">
    <tr><th>Category</th><th>Labs</th><th>OWASP Top 10 (2021)</th><th>CWE</th></tr>
    <?php
    $owasp = [
        'SQL Injection' => 'A03 Injection', 'Cross-Site Scripting' => 'A03 Injection',
        'Command Injection' => 'A03 Injection', 'Server-Side Template Injection' => 'A03 Injection',
        'Path Traversal' => 'A01 Broken Access Control', 'File Upload' => 'A04 Insecure Design',
        'SSRF' => 'A10 SSRF', 'XXE' => 'A05 Security Misconfiguration',
        'Insecure Deserialization' => 'A08 Integrity Failures', 'Authentication' => 'A07 Auth Failures',
        'Broken Access Control' => 'A01 Broken Access Control', 'CSRF' => 'A01 Broken Access Control',
        'Business Logic' => 'A04 Insecure Design',
    ];
    foreach ($byCat as $cat => $items):
        $links = implode(', ', array_map(fn ($l) => '<a href="/labs/' . e($l['slug']) . '">' . e($l['title']) . '</a>', $items));
        $cwes = implode(', ', array_unique(array_map(fn ($l) => $l['cwe'], $items)));
    ?>
      <tr><td><?= e($cat) ?></td><td><?= $links ?></td><td><?= e($owasp[$cat] ?? '-') ?></td><td><?= e($cwes) ?></td></tr>
    <?php endforeach; ?>
  </table>
</section>

<section class="prose">
  <h2>A method with Burp Suite</h2>
  <ol>
    <li><strong>Map</strong> the app through the Proxy. Visit every feature; let the sitemap fill. Note every parameter, header, and cookie.</li>
    <li><strong>Probe</strong> one input at a time in Repeater. Send a canary (a quote, a brace, a marker string) and read how the response changes. Change one thing per request.</li>
    <li><strong>Confirm</strong> the class: reflected marker (XSS), SQL error or timing shift (SQLi), file contents (LFI/XXE), a redirect or reflected Origin (business logic).</li>
    <li><strong>Automate</strong> with Intruder for enumeration (IDOR ids, blind SQLi extraction) once you have a working single request.</li>
    <li><strong>Scope</strong> the impact: what can you read, change, or run, and as whom. That is your report.</li>
  </ol>
  <p>Where each class hides: injection in search, filters, sort, and import; access control in ids and admin paths; SSRF in URL fetchers; deserialization in cookies and caches; template injection in anything with {{ }}.</p>
</section>

<section class="prose">
  <h2>Report template</h2>
  <p>Every lab's Report tab is a filled example of this structure.</p>
  <pre class="code wrap">Title: [class] in [location]
Severity: [Critical/High/Medium/Low] (CVSS 3.1 [score], [vector])
Weakness: [CWE-xx]

Summary: One or two sentences: what the bug is and why it matters.

Steps to reproduce:
1. [exact request / action]
2. [observed result that proves the bug]

Impact: What an attacker can read, change, or run, and as whom.

Remediation: The specific fix (parameterized query, output encoding,
allowlist, authz check), not just "sanitize input".</pre>
  <p><strong>CVSS basics:</strong> score Attack Vector, Complexity, Privileges, User interaction, and the Confidentiality/Integrity/Availability impact. Unauthenticated remote code execution trends to 9.8; a reflected XSS needing a click trends to 6.1.</p>
</section>

<section class="prose">
  <h2>Ethics and scope</h2>
  <ul>
    <li>Test only systems you own or are explicitly authorized to test. A public bug bounty <em>scope</em> page is that authorization; stay inside it.</li>
    <li>Do not access more data than needed to prove the bug. Stop at proof; never pivot, exfiltrate, or persist.</li>
    <li>Report privately through the program. Do not disclose publicly before a fix and agreed timeline.</li>
    <li>VulnForge itself is local-only. Never point these techniques at a system without permission.</li>
  </ul>
</section>

<section class="prose">
  <h2>Where to go next</h2>
  <ul>
    <li><strong>Structured learning:</strong> PortSwigger Web Security Academy (free, hands-on labs that mirror these classes).</li>
    <li><strong>Programs:</strong> HackerOne, Bugcrowd, Intigriti. Read scope and rules first; start with programs that welcome beginners.</li>
    <li><strong>WordPress and plugins:</strong> Patchstack and Wordfence publish advisories. Many plugin bugs are the exact classes here (unsanitized $wpdb queries, missing nonce/capability checks, unrestricted uploads). A reproducible finding, reported to the vendor or via Patchstack, can become a CVE.</li>
    <li><strong>Tooling:</strong> Burp Suite, sqlmap, ffuf, jwt_tool, and phpggc for gadget chains.</li>
  </ul>
</section>
