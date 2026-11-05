<?php $title = $lab['title'] . ' - VulnForge'; $level = current_level(); ?>
<section class="lab-head">
  <div>
    <h1><?= e($lab['title']) ?></h1>
    <p><?= e($lab['scenario']) ?></p>
    <div class="chips">
      <span class="chip sev-<?= e(strtolower($lab['severity'])) ?>"><?= e($lab['severity']) ?></span>
      <span class="chip"><?= e($lab['cwe']) ?></span>
      <span class="chip"><?= e($lab['difficulty']) ?></span>
    </div>
  </div>
  <div class="levels" role="group" aria-label="Security level">
    <?php foreach (LEVELS as $option): ?>
      <button type="button" data-level="<?= $option ?>" class="<?= $option === $level ? 'active' : '' ?><?= in_array($option, $solved, true) ? ' solved' : '' ?>"><?= ucfirst($option) ?></button>
    <?php endforeach; ?>
  </div>
</section>
<div class="split" id="workspace" data-slug="<?= e($lab['slug']) ?>" data-level="<?= e($level) ?>">
  <section class="pane feature">
    <iframe title="Live feature" src="<?= e($lab['feature']) ?>"></iframe>
  </section>
  <section class="pane tools">
    <div class="tabs" role="tablist">
      <?php foreach (['console' => 'Attack Console', 'trace' => 'What Happened', 'source' => 'Source', 'hints' => 'Hints', 'fix' => 'Fix', 'report' => 'Report'] as $id => $label): ?>
        <button type="button" role="tab" data-tab="<?= $id ?>"><?= $label ?></button>
      <?php endforeach; ?>
      <?php if ($solution): ?>
        <button type="button" role="tab" data-tab="solution" id="solution-tab" class="locked" title="Open every hint to unlock">Solution</button>
      <?php endif; ?>
    </div>

    <div class="tab" data-panel="console">
      <div class="request-line">
        <select id="method" aria-label="Method"><?php foreach (['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'] as $m): ?><option<?= $m === $lab['request']['method'] ? ' selected' : '' ?>><?= $m ?></option><?php endforeach; ?></select>
        <input id="path" aria-label="Path" spellcheck="false" value="<?= e($lab['request']['path']) ?>">
        <button type="button" id="send" class="primary">Send</button>
      </div>
      <label>Headers<textarea id="headers" rows="2" spellcheck="false"><?= e($lab['request']['headers'] ?? '') ?></textarea></label>
      <label>Body<textarea id="body" rows="2" spellcheck="false"><?= e($lab['request']['body'] ?? '') ?></textarea></label>
      <div class="palette">
        <?php foreach ($lab['payloads'] as $payload): ?><button type="button" class="ghost" data-payload="<?= e($payload) ?>"><?= e($payload) ?></button><?php endforeach; ?>
      </div>
      <p class="status" id="status">Press Send, or Ctrl+Enter.</p>
      <pre class="code" id="response"></pre>
    </div>

    <div class="tab" data-panel="trace" hidden>
      <div id="debrief" class="debrief" hidden>
        <h3>Solved at this level</h3>
        <p><?= e($lab['debrief']) ?></p>
      </div>
      <div id="trace"><p class="muted">Send a request to see what the server did.</p></div>
    </div>

    <div class="tab" data-panel="source" hidden>
      <label class="inline"><input type="checkbox" id="diff"> Show changes from the previous level</label>
      <pre class="code" id="source"></pre>
    </div>

    <div class="tab" data-panel="hints" hidden>
      <?php if ($solution): ?><p class="muted">Open all <?= count($lab['hints']) ?> hints to unlock the Solution tab.</p><?php endif; ?>
      <?php foreach ($lab['hints'] as $i => $hint): ?>
        <details><summary>Hint <?= $i + 1 ?></summary><p><?= e($hint) ?></p></details>
      <?php endforeach; ?>
    </div>

    <div class="tab prose" data-panel="fix" hidden>
      <h3>Root cause</h3><p><?= e($lab['root_cause']) ?></p>
      <h3>Fix</h3><p><?= e($lab['fix']) ?></p>
      <h3>How to hunt this on real targets</h3><p><?= e($lab['hunting']) ?></p>
    </div>

    <div class="tab prose" data-panel="report" hidden>
      <pre class="code wrap"><?= e($lab['report']) ?></pre>
    </div>

    <?php if ($solution): ?>
    <div class="tab prose" data-panel="solution" hidden>
      <div id="solution-locked" class="debrief">
        <h3>Solution locked</h3>
        <p>Try the hints first. Open every hint in the Hints tab and the full solution for the <strong><?= e($level) ?></strong> level unlocks here: the exact steps, why each one works, and a button that runs the exploit live so you can watch it succeed.</p>
        <p class="muted">Hints opened: <span id="hint-progress">0</span> of <?= count($lab['hints']) ?>.</p>
        <p><button type="button" id="reveal-anyway" class="ghost">Reveal the solution now</button></p>
      </div>
      <div id="solution-body" hidden>
        <h3>Solving the <?= e($level) ?> level</h3>
        <p><?= e($solution['goal']) ?></p>
        <h3>Steps</h3>
        <ol class="sol-steps">
          <?php foreach ($solution['steps'] as $step): ?><li><?= e($step) ?></li><?php endforeach; ?>
        </ol>
        <h3>Why this works</h3>
        <p><?= e($solution['why']) ?></p>
        <?php if (!empty($solution['run'])): ?>
          <h3>See it happen</h3>
          <p><?= $level === 'secure'
              ? 'Run the same exploit against the Secure level and watch it fail. The What Happened tab shows why the server refused it.'
              : 'Run the exploit now. It loads the real request into the Attack Console, sends it to the live server, and shows what happened — the same effect that marks the level solved.' ?></p>
          <button type="button" id="run-solution" class="primary" data-run='<?= e(json_encode($solution['run'], JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>
            <?= $level === 'secure' ? 'Run it against Secure' : 'Run the exploit' ?>
          </button>
          <p class="status" id="run-status"></p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </section>
</div>
<script src="/assets/workspace.js"></script>
