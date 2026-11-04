<?php
// The gadget. Its magic method does something dangerous with its properties,
// which is what makes object injection exploitable: the attacker controls the
// property values that reach the dangerous sink.
class Preferences
{
    public string $theme = 'dark';
    public $debugLog = null; // path of a log to show when debug is on

    public function __wakeup(): void
    {
        if ($this->debugLog) {
            // @sink  reads an attacker-controlled path on unserialize
            $contents = (string) @file_get_contents($this->debugLog);
            Trace::note("debugLog is set, so __wakeup read: " . $this->debugLog);
            $GLOBALS['vf_deser_output'] = $contents;
        }
    }
}
