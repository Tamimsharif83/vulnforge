<?php
$p = load_profile();
if (($p['role'] ?? 'user') === 'admin' || (int) ($p['balance'] ?? 0) > 100) {
    Lab::markSolved('massassign-profile', current_level());
    Trace::note('Privileged field changed: role=' . ($p['role'] ?? '?') . ', balance=' . ($p['balance'] ?? '?') . '.');
}
Trace::step('Record after update', json_encode($p, JSON_PRETTY_PRINT), '');
echo '<!DOCTYPE html><title>Profile</title><link rel="stylesheet" href="/assets/shop.css">' . shop_header('Account') . '<h1>Your profile</h1>';
echo '<p>role: <strong>' . e($p['role']) . '</strong> | balance: <strong>' . e((string) $p['balance']) . '</strong></p>';
echo '<form method="post" action="/labs/massassign-profile/run"><input name="email" value="' . e($p['email']) . '"><input name="bio" value="' . e($p['bio']) . '"><button>Save</button></form>';
