<?php
declare(strict_types=1);

/**
 * Server-side proxy for "this payload would execute in a browser". Two modes:
 *
 *  A. Tag injection: a script-capable tag from the input survives into the
 *     output as a real tag. The tag name must be followed by a tag terminator
 *     (whitespace, "/", or ">"), so "<scriptalert(...)" (what a one-pass filter
 *     can leave behind) is NOT a real <script> element and does not count.
 *     htmlspecialchars turns "<img" into "&lt;img", so encoded output misses too.
 *  B. Attribute breakout: the input's quote survives unencoded and is followed
 *     by an event-handler attribute, so it escaped the value and added a
 *     handler to a real element. Encoding the quote (&quot;) defeats this.
 *
 * A bare "onerror=" left as text (as htmlspecialchars leaves it) matches
 * neither, so the Secure level is correctly judged non-exploitable. Real
 * confirmation is always the payload firing in a browser; the debrief says so.
 */
function xss_executes(string $input, string $html): bool
{
    $tags = ['script', 'svg', 'img', 'iframe', 'body', 'video', 'audio',
        'object', 'embed', 'details', 'marquee', 'math', 'a'];
    foreach ($tags as $tag) {
        // The tag name must be followed by a space, "/", or ">" to be a real tag.
        $realTag = '/<' . $tag . '[\s\/>]/i';
        if (preg_match($realTag, $input) && preg_match($realTag, $html)) {
            return true; // mode A
        }
    }
    // mode B: a literal quote, then an event-handler attribute, in the output.
    if (preg_match('/["\'][\w\s]*\son\w+\s*=/i', $html)
        && (str_contains($input, '"') || str_contains($input, "'"))) {
        return true;
    }
    return false;
}
