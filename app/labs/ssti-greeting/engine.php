<?php
// A deliberately unsafe template engine: {{ expr }} is evaluated as PHP. This
// mirrors how a real template engine (Twig, Jinja) becomes RCE when user input
// is used as the template itself rather than as data passed to a fixed template.
function ssti_render(string $template, array $blocklist): string
{
    return preg_replace_callback('/\{\{(.+?)\}\}/s', function ($m) use ($blocklist) {
        $expr = trim($m[1]);
        foreach ($blocklist as $bad) {
            if (stripos($expr, $bad) !== false) { return '[blocked: ' . e($bad) . ']'; }
        }
        try {
            // Buffer output so functions that print directly (system, echo) are
            // captured into the result instead of being sent to the page early.
            ob_start();
            $value = @eval("return $expr;"); // @sink
            $printed = ob_get_clean();
            return $printed . (is_scalar($value) ? (string) $value : '');
        } catch (Throwable $e) {
            ob_end_clean();
            return '[error]';
        }
    }, $template);
}
