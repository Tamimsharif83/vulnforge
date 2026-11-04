<?php
// Per-student file storage so each learner sees only their own reviews.
function stored_reviews_path(): string
{
    $dir = '/tmp/vf-comments';
    if (!is_dir($dir)) { mkdir($dir, 0700, true); }
    return "$dir/" . student_id() . '.json';
}
function load_reviews(): array
{
    $file = stored_reviews_path();
    return is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
}
function save_review(string $body): void
{
    $file = stored_reviews_path();
    $reviews = load_reviews();
    $reviews[] = $body;
    file_put_contents($file, json_encode(array_slice($reviews, -20)));
}
