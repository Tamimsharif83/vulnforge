<?php
function profile_path(): string
{
    $dir = '/tmp/vf-profiles';
    if (!is_dir($dir)) { mkdir($dir, 0700, true); }
    return "$dir/" . student_id() . '.json';
}
function load_profile(): array
{
    $file = profile_path();
    $default = ['username' => 'alice', 'email' => 'alice@forgeshop.local', 'bio' => 'Keeps buying keyboards', 'role' => 'user', 'balance' => 100];
    return is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: $default) : $default;
}
function save_profile(array $p): void { file_put_contents(profile_path(), json_encode($p)); }
