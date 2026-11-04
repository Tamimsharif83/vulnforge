<?php
// A beacon from the executed payload marks the lab solved for this student.
if (isset($_GET['forge_solve'])) {
    Lab::markSolved('xss-dom', current_level());
    Trace::note('Beacon received: your injected script executed and called forgeSolve().');
    header('Content-Type: text/plain');
    echo 'ok';
    return true;
}
return false;
