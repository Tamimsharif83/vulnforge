<?php
echo '<!DOCTYPE html><title>Account</title><link rel="stylesheet" href="/assets/shop.css">' . shop_header('Account') . '<h1>Change email</h1>';
echo '<p>Current email: <strong>' . e($email) . '</strong></p>';
if (!empty($changed)) { echo '<p>Email changed by the request.</p>'; }
echo '<form method="post" action="/labs/csrf-email/run"><input name="email" value="new@forgeshop.local">';
if (!empty($token)) { echo '<input type="hidden" name="csrf_token" value="' . e($token) . '">'; }
echo '<button>Update</button></form>';
