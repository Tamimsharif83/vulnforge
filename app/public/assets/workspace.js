const workspace = document.getElementById('workspace');
const { slug, level } = workspace.dataset;
const $ = (id) => document.getElementById(id);
const levels = ['low', 'medium', 'high', 'secure'];

function showTab(name) {
  document.querySelectorAll('[data-tab]').forEach((tab) => {
    tab.setAttribute('aria-selected', tab.dataset.tab === name);
  });
  document.querySelectorAll('[data-panel]').forEach((panel) => {
    panel.hidden = panel.dataset.panel !== name;
  });
}
document.querySelectorAll('[data-tab]').forEach((tab) => {
  tab.addEventListener('click', () => showTab(tab.dataset.tab));
});
showTab('console');

document.querySelectorAll('.levels [data-level]').forEach((button) => {
  button.addEventListener('click', async () => {
    await fetch('/level', { method: 'POST', body: new URLSearchParams({ level: button.dataset.level }) });
    location.reload();
  });
});

document.querySelectorAll('[data-payload]').forEach((button) => {
  button.addEventListener('click', () => {
    const path = $('path');
    const [base, query] = path.value.split('?');
    if (!query) return;
    const [name] = query.split('=');
    path.value = `${base}?${name}=${encodeURIComponent(button.dataset.payload)}`;
    path.focus();
  });
});

function renderTrace(trace) {
  const root = $('trace');
  root.replaceChildren();
  if (!trace.events.length) {
    root.textContent = 'The server recorded nothing for this request.';
    return;
  }
  for (const event of trace.events) {
    const block = document.createElement('div');
    block.className = 'trace-event';
    const code = document.createElement('pre');
    code.className = 'code';
    if (event.mark) {
      const bytes = new TextEncoder().encode(event.text);
      const decode = (a, b) => new TextDecoder().decode(bytes.slice(a, b));
      const mark = document.createElement('mark');
      mark.textContent = decode(event.mark[0], event.mark[1]);
      code.append(decode(0, event.mark[0]), mark, decode(event.mark[1]));
    } else {
      code.textContent = event.text;
    }
    block.append(code);
    if (event.type === 'sql') {
      const meta = document.createElement('p');
      meta.className = 'muted';
      const bound = event.params ? ` Bound values, sent separately from the query: ${JSON.stringify(event.params)}.` : '';
      meta.textContent = `${event.rows} rows in ${event.ms} ms.${bound}`;
      block.append(meta);
    }
    if (event.error) {
      const error = document.createElement('p');
      error.className = 'error';
      error.textContent = event.error;
      block.append(error);
    }
    root.append(block);
  }
}

async function send() {
  $('status').textContent = 'Sending...';
  const result = await fetch('/console/send', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      method: $('method').value,
      path: $('path').value,
      headers: $('headers').value,
      body: $('body').value,
    }),
  }).then((response) => response.json());

  if (result.error) {
    $('status').textContent = result.error;
    return;
  }
  $('response').textContent = result.response;
  const [head] = result.response.split('\r\n\r\n');
  const status = head.split('\r\n')[0];
  $('status').textContent = `${status} in ${result.ms} ms`;

  const traceId = /^x-vf-trace:\s*([a-f0-9]{24})/im.exec(head);
  if (traceId) {
    renderTrace(await fetch(`/trace/${traceId[1]}`).then((response) => response.json()));
  }
  if (/^x-vf-solved:/im.test(head)) {
    $('debrief').hidden = false;
    document.querySelector(`.levels [data-level="${level}"]`).classList.add('solved');
    showTab('trace');
  }
}
$('send').addEventListener('click', send);
document.addEventListener('keydown', (event) => {
  if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) send();
});

// Line diff by longest common subsequence: sources are short enough for the table.
function diffLines(before, after) {
  const a = before.split('\n');
  const b = after.split('\n');
  const table = Array.from({ length: a.length + 1 }, () => new Array(b.length + 1).fill(0));
  for (let i = a.length - 1; i >= 0; i--) {
    for (let j = b.length - 1; j >= 0; j--) {
      table[i][j] = a[i] === b[j] ? table[i + 1][j + 1] + 1 : Math.max(table[i + 1][j], table[i][j + 1]);
    }
  }
  const lines = [];
  let i = 0;
  let j = 0;
  while (i < a.length || j < b.length) {
    if (i < a.length && j < b.length && a[i] === b[j]) {
      lines.push(['same', a[i]]); i++; j++;
    } else if (j < b.length && (i === a.length || table[i][j + 1] >= table[i + 1][j])) {
      lines.push(['added', b[j]]); j++;
    } else {
      lines.push(['removed', a[i]]); i++;
    }
  }
  return lines;
}

let sources = null;
function renderSource() {
  const root = $('source');
  root.replaceChildren();
  const previous = levels[levels.indexOf(level) - 1];
  const lines = $('diff').checked && previous
    ? diffLines(sources[previous], sources[level])
    : sources[level].split('\n').map((line) => ['same', line]);
  for (const [kind, text] of lines) {
    const line = document.createElement('span');
    line.className = `line ${kind}${text.includes('@sink') ? ' sink' : ''}`;
    line.textContent = `${text}\n`;
    root.append(line);
  }
}
$('diff').addEventListener('change', renderSource);
fetch(`/labs/${slug}/source`).then((response) => response.json()).then((data) => {
  sources = data;
  renderSource();
});
