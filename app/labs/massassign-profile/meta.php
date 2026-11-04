<?php
return [
    'order' => 111,
    'title' => 'Profile Update: Mass Assignment',
    'category' => 'Broken Access Control',
    'summary' => 'Saving your profile writes every field you submit.',
    'severity' => 'High',
    'cwe' => 'CWE-915',
    'difficulty' => 'Intermediate',
    'scenario' => 'The profile form saves the fields you send. It also binds fields the form never shows, like role and balance. POST extra fields to make yourself an admin (role=admin) or inflate your balance.',
    'feature' => '/labs/massassign-profile/run',
    'request' => ['method' => 'POST', 'path' => '/labs/massassign-profile/run', 'headers' => 'Content-Type: application/x-www-form-urlencoded', 'body' => 'email=alice@forgeshop.local&bio=hello&role=admin'],
    'payloads' => ['role=admin', 'ROLE=admin', 'balance=999999', 'bio=updated'],
    'hints' => [
        'The handler copies each submitted field onto your record. Add a field the form never shows: role=admin.',
        'At Medium the key role is dropped. Column names are case-insensitive; try ROLE=admin.',
        'At High role is dropped case-insensitively, but balance is still bound. Set balance=999999 to escalate a different way.',
    ],
    'debrief' => 'The update bound request fields straight onto the record, including privileged ones the UI never exposes. Blacklisting a field name misses case and sibling fields. The Secure level binds only an explicit allowlist (bio and email), so role and balance can never be set from a request.',
    'root_cause' => 'Request parameters are bound to model attributes without an allowlist, so attackers set fields the UI does not expose.',
    'fix' => 'Bind only an explicit allowlist of user-editable fields. Keep privileged fields (role, balance, is_admin) out of any request-to-model mapping.',
    'hunting' => 'Add guessable fields to update and signup requests: role, is_admin, admin, user_id, balance, verified, plan. Watch JSON bodies especially. Frameworks with mass binding (Rails, Laravel fill, Spring) are classic. Compare the form fields with the model columns.',
    'report' => "Title: Mass assignment allows privilege escalation\nSeverity: High (CVSS 3.1 8.1)\nWeakness: CWE-915\n\nSteps to reproduce:\n1. POST the profile update with an extra field role=admin.\n2. The account role changes to admin.\n\nImpact: Privilege escalation; balance/entitlement tampering.\n\nRemediation: Allowlist bindable fields; exclude privileged attributes.",
];
