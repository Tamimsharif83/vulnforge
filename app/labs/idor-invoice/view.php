<?php
// $row is the fetched invoice or null. SESSION_UID is fixed: you are alice (2).
if (is_array($row) && str_contains($row['detail'], 'FORGE-INVOICE{')) {
    Lab::markSolved('idor-invoice', current_level());
}
Trace::step('Record lookup', 'invoices.id = ' . ($lookedUp ?? '?') . (isset($row['owner_uid']) ? ' (owner_uid ' . $row['owner_uid'] . ', you are uid 2)' : ''), '');
echo '<!DOCTYPE html><title>Invoice</title><link rel="stylesheet" href="/assets/shop.css?v=2">' . shop_header('Account') . '<h1>Invoice viewer</h1>';
echo '<p>Signed in as <strong>alice</strong> (uid 2).</p>';
echo is_array($row) ? '<p>Invoice #' . e((string) $row['id']) . ': ' . e($row['detail']) . '</p>' : '<p>No such invoice, or access denied.</p>';
