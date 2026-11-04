<?php
// $transform(string): string is the level's sanitizer applied at render time.
require __DIR__ . '/store.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    save_review((string) $_POST['comment']);
    Trace::note('Stored review: ' . $_POST['comment']);
}
$reviews = load_reviews();
$html = '';
foreach ($reviews as $review) {
    $rendered = $transform($review);
    $html .= "<li>$rendered</li>";
    if (xss_executes($review, $rendered)) {
        Lab::markSolved('xss-stored', current_level());
    }
}
Trace::step('Rendered review HTML (sink)', $html !== '' ? $html : '(no reviews yet)', '', 'This HTML is sent to every viewer of the product.');
?>
<!DOCTYPE html>
<title>ForgeShop Reviews</title>
<link rel="stylesheet" href="/assets/shop.css">
<h1>Anvil Mechanical Keyboard</h1>
<h2>Reviews</h2>
<ul><?= $html ?></ul>
<form method="post" action="/labs/xss-stored/run">
  <input name="comment" placeholder="Write a review" style="flex:1">
  <button>Post</button>
</form>
