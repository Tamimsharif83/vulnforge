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
  </section>
</div>
<script src="/assets/workspace.js"></script>
