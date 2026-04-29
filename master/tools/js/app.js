/* =========================================================
   WebForge AI — Pipeline Orchestration
   ========================================================= */

'use strict';

/* ---- STATE ---- */
const STATE = {
apiKey: '',
userRequest: '',
running: false,
outputs: {
bot1: null, // JSON strategic plan (object)
bot2: null, // { html, css, js }
bot3: null, // { files: [{name, content}] }
bot4: null, // { files: [{name, content}], preview_html }
},
projectName: 'Website',
};

/* ---- DOM REFS ---- */
const $ = (id) => document.getElementById(id);

const DOM = {
settingsToggle: $('settings-toggle'),
settingsPanel: $('settings-panel'),
apiKeyInput: $('api-key-input'),
saveKeyBtn: $('save-key-btn'),
keyStatus: $('key-status'),
statusBadge: $('status-badge'),
userRequest: $('user-request'),
charCount: $('char-count'),
runPipelineBtn: $('run-pipeline-btn'),
pipelineProgress: $('pipeline-progress-fill'),
resultsSection: $('results-section'),
previewBtn: $('preview-btn'),
downloadBtn: $('download-btn'),
resetBtn: $('reset-btn'),
// Toast
toast: $('error-toast'),
toastMessage: $('toast-message'),
toastClose: $('toast-close'),
// Output modal
outputModal: $('output-modal'),
outputModalTitle: $('output-modal-title'),
outputModalTabs: $('output-modal-tabs'),
outputModalContent:$('output-modal-content'),
outputModalClose: $('output-modal-close'),
// Preview modal
previewModal: $('preview-modal'),
previewModalClose: $('preview-modal-close'),
previewIframe: $('preview-iframe'),
previewProjectName:$('preview-project-name'),
previewDesktop: $('preview-desktop'),
previewTablet: $('preview-tablet'),
previewMobile: $('preview-mobile'),
};

/* ---- INIT ---- */
document.addEventListener('DOMContentLoaded', () => {
loadApiKey();
bindEvents();
});

function loadApiKey() {
const saved = Core.getApiKey();
if (saved) {
STATE.apiKey = saved;
DOM.apiKeyInput.value = saved;
DOM.keyStatus.textContent = '✓ API key loaded from storage.';
DOM.keyStatus.className = 'field-hint ok';
// Migrate legacy key if present
const legacyKey = localStorage.getItem('wf_api_key');
if (legacyKey && legacyKey !== saved) {
localStorage.setItem('openaikey', legacyKey);
localStorage.removeItem('wf_api_key');
}
} else {
// Check for legacy key
const legacyKey = localStorage.getItem('wf_api_key');
if (legacyKey) {
localStorage.setItem('openaikey', legacyKey);
localStorage.removeItem('wf_api_key');
STATE.apiKey = legacyKey;
DOM.apiKeyInput.value = legacyKey;
DOM.keyStatus.textContent = '✓ API key migrated from legacy storage.';
DOM.keyStatus.className = 'field-hint ok';
} else {
DOM.keyStatus.textContent = 'No key stored.';
DOM.keyStatus.className = 'field-hint';
}
}
}

function bindEvents() {
/* Settings toggle */
DOM.settingsToggle.addEventListener('click', () => {
DOM.settingsPanel.classList.toggle('open');
});

/* Save API key */
DOM.saveKeyBtn.addEventListener('click', () => {
const val = DOM.apiKeyInput.value.trim();
if (!val.startsWith('sk-') || val.length < 20) {
DOM.keyStatus.textContent = '✗ Invalid key format.';
DOM.keyStatus.className = 'field-hint err';
return;
}
STATE.apiKey = val;
Core.setApiKey(val);
// Clean legacy storage
localStorage.removeItem('wf_api_key');
DOM.keyStatus.textContent = '✓ Key saved.';
DOM.keyStatus.className = 'field-hint ok';
DOM.settingsPanel.classList.remove('open');
});

/* Char count */
DOM.userRequest.addEventListener('input', () => {
const len = DOM.userRequest.value.length;
DOM.charCount.textContent = `${len} chars`;
});

/* Run pipeline */
DOM.runPipelineBtn.addEventListener('click', startPipeline);

/* Results actions */
DOM.previewBtn.addEventListener('click', openPreviewModal);
DOM.downloadBtn.addEventListener('click', downloadZip);
DOM.resetBtn.addEventListener('click', resetPipeline);

/* Toast close */
DOM.toastClose.addEventListener('click', hideToast);

/* Output modal */
DOM.outputModalClose.addEventListener('click', () => closeModal(DOM.outputModal));
DOM.outputModal.addEventListener('click', (e) => {
if (e.target === DOM.outputModal) closeModal(DOM.outputModal);
});

/* Preview modal */
DOM.previewModalClose.addEventListener('click', () => closeModal(DOM.previewModal));
DOM.previewModal.addEventListener('click', (e) => {
if (e.target === DOM.previewModal) closeModal(DOM.previewModal);
});

/* View buttons */
$('view-btn-1').addEventListener('click', () => showBotOutput(1));
$('view-btn-2').addEventListener('click', () => showBotOutput(2));
$('view-btn-3').addEventListener('click', () => showBotOutput(3));
$('view-btn-4').addEventListener('click', () => showBotOutput(4));

/* Preview toolbar */
DOM.previewDesktop.addEventListener('click', () => setPreviewWidth('100%', DOM.previewDesktop));
DOM.previewTablet.addEventListener('click', () => setPreviewWidth('768px', DOM.previewTablet));
DOM.previewMobile.addEventListener('click', () => setPreviewWidth('390px', DOM.previewMobile));
}

/* ---- PIPELINE ENTRY ---- */
async function startPipeline() {
if (STATE.running) return;

const request = DOM.userRequest.value.trim();
if (!request) { showToast('Please describe your project first.'); return; }
if (!STATE.apiKey) {
showToast('No API key found. Click the settings icon to add your OpenAI key.');
DOM.settingsPanel.classList.add('open');
return;
}

/* Quick probe: confirm chat.php is reachable before starting */
try {
const username = localStorage.getItem('sync_username') || 'Anonymous';
const probe = await fetch('api/chat.php', {
method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify({ api_key: STATE.apiKey, model: 'gpt-4o-mini', messages: [], username: username }),
});
const probeData = await probe.json().catch(() => ({}));
/* 400 "Missing or invalid messages" = PHP reached OK */
/* anything else = surface the real problem */
if (!probe.ok && probe.status !== 400) {
const msg = probeData?.error?.message || probeData?.error || `Server returned HTTP ${probe.status}`;
showToast(`Cannot reach api/chat.php — ${msg}`);
return;
}
} catch (e) {
showToast(`Cannot reach api/chat.php: ${e.message}. Make sure the PHP server is running.`);
return;
}

STATE.running = true;
STATE.userRequest = request;
DOM.runPipelineBtn.disabled = true;
DOM.userRequest.disabled = true;
setStatusBadge('running', 'RUNNING');
DOM.resultsSection.style.display = 'none';
resetCards();

try {
await runBot1();
await runBot2();
await runBot3();
await runBot4();
showResults();
} catch (err) {
showToast(err.message || 'Pipeline failed. Check your API key and try again.');
setStatusBadge('error', 'ERROR');
} finally {
STATE.running = false;
DOM.runPipelineBtn.disabled = false;
DOM.userRequest.disabled = false;
}
}

/* ---- BOT 1: Idea Strategist ---- */
async function runBot1() {
setCardState(1, 'running', 'Analyzing project requirements...');
setProgress(1, 25);
activateConnector(0); // pre-line

const systemPrompt = `You are an expert web strategist and solution architect. Analyze the user's request and produce a comprehensive, detailed strategic plan.
Return ONLY a valid JSON object with this exact structure:
{
"projectName": "string",
"projectType": "string (website|PWA|SaaS|webapp)",
"description": "string",
"targetAudience": "string",
"keyFeatures": ["string"],
"techStack": {
"frontend": ["string"],
"backend": ["string"],
"dataStorage": ["string"]
},
"pages": [{"name":"string","slug":"string","description":"string"}],
"components": [{"name":"string","description":"string"}],
"dataModels": [{"name":"string","fields":[{"name":"string","type":"string"}]}],
"apiEndpoints": [{"method":"string","endpoint":"string","description":"string"}],
"colorScheme": {
"primary":"string (hex)",
"secondary":"string (hex)",
"accent":"string (hex)",
"background":"string (hex)",
"text":"string (hex)"
},
"typography": {"heading":"string (font name)","body":"string (font name)"},
"uiStyle": "string",
"specialRequirements": ["string"]
}
Return ONLY the JSON, no markdown fences, no explanation.`;

const userMessage = `Project request: ${STATE.userRequest}`;

setStatus(1, 'Generating strategic plan...');
setProgress(1, 60);

const raw = await callOpenAI([
{ role: 'system', content: systemPrompt },
{ role: 'user', content: userMessage },
]);

setProgress(1, 85);
setStatus(1, 'Parsing strategic plan...');

const parsed = parseJSON(raw, 'Bot 1 (Idea Strategist)');
STATE.outputs.bot1 = parsed;
STATE.projectName = parsed.projectName || 'Website';

setCardState(1, 'done', `Plan ready: ${parsed.keyFeatures?.length || 0} features identified.`);
setProgress(1, 100);
setChipsReady(1, ['chip-1-json']);
enableViewBtn(1);
activateConnector(1);
}

/* ---- BOT 2: Frontend DevBot ---- */
async function runBot2() {
setCardState(2, 'running', 'Reading strategic plan...');
setProgress(2, 15);

const systemPrompt = `You are an expert frontend developer. Using the provided strategic plan, create a complete, production-ready frontend.
Return ONLY a valid JSON object:
{
"html": "...complete index.html content...",
"css": "...complete styles.css content...",
"js": "...complete main.js content..."
}
Requirements:
- Write real, complete, functional code. No placeholder comments like "add code here".
- HTML must be a full document with meta SEO tags, Open Graph tags, structured content.
- CSS must be comprehensive with responsive design, animations, and the color scheme from the plan.
- JavaScript must include all interactive features, AJAX calls to backend API endpoints, form handling, DOM manipulation.
- Use vanilla JS only. No external frameworks except Google Fonts and optionally Font Awesome for icons (CDN links in HTML).
- All IDs and element references must match exactly what the PHP backend will expect.
- Return ONLY the JSON, no markdown fences.`;

const userMessage = `Strategic Plan:\n${JSON.stringify(STATE.outputs.bot1, null, 2)}`;

setStatus(2, 'Generating HTML structure...');
setProgress(2, 30);

const raw = await callOpenAI([
{ role: 'system', content: systemPrompt },
{ role: 'user', content: userMessage },
]);

setProgress(2, 80);
setStatus(2, 'Parsing frontend code...');

const parsed = parseJSON(raw, 'Bot 2 (Frontend DevBot)');
STATE.outputs.bot2 = parsed;

setCardState(2, 'done', 'Frontend code generated successfully.');
setProgress(2, 100);
setChipsReady(2, ['chip-2-html', 'chip-2-css', 'chip-2-js']);
enableViewBtn(2);
activateConnector(2);
}

/* ---- BOT 3: Backend DevBot ---- */
async function runBot3() {
setCardState(3, 'running', 'Reading frontend architecture...');
setProgress(3, 15);

const systemPrompt = `You are an expert PHP backend developer. Using the strategic plan and frontend code, create a complete PHP backend.
Return ONLY a valid JSON object:
{
"files": [
{"name": "api/endpoint.php", "content": "...complete PHP file content..."},
{"name": "data/schema.json", "content": "...JSON schema/initial data..."}
]
}
Requirements:
- Write REAL, complete, functional PHP code. No placeholder comments.
- Use JSON files for data persistence (no database required).
- Each PHP endpoint must handle CORS headers properly.
- All $_POST/$_GET field names must match exactly the field names/IDs used in the frontend JS.
- Implement proper input validation, sanitization, and error handling.
- Include an index.php router or separate PHP files per endpoint.
- Include initial data JSON files (data/*.json) for any data models.
- Return ONLY the JSON, no markdown fences.`;

const userMessage = `Strategic Plan:\n${JSON.stringify(STATE.outputs.bot1, null, 2)}\n\nFrontend HTML:\n${STATE.outputs.bot2.html?.substring(0, 3000)}\n\nFrontend JS:\n${STATE.outputs.bot2.js?.substring(0, 3000)}`;

setStatus(3, 'Building API endpoints...');
setProgress(3, 40);

const raw = await callOpenAI([
{ role: 'system', content: systemPrompt },
{ role: 'user', content: userMessage },
]);

setProgress(3, 80);
setStatus(3, 'Parsing backend code...');

const parsed = parseJSON(raw, 'Bot 3 (Backend DevBot)');
STATE.outputs.bot3 = parsed;

const fileCount = parsed.files?.length || 0;
setCardState(3, 'done', `Backend ready: ${fileCount} files generated.`);
setProgress(3, 100);
setChipsReady(3, ['chip-3-php', 'chip-3-json']);
enableViewBtn(3);
activateConnector(3);
}

/* ---- BOT 4: Final Validator ---- */
async function runBot4() {
setCardState(4, 'running', 'Reviewing all components...');
setProgress(4, 10);

const systemPrompt = `You are an expert code reviewer and integration specialist. Review the strategic plan, frontend code, and backend code.

Your job:
1. Verify all parts align with the strategic plan.
2. Fix any inconsistencies between frontend and backend.
3. Complete any missing functionality.
4. Produce a final, production-ready integrated codebase.

Return the complete codebase as a series of files, each in the EXACT format below:

File: [filename.ext]
---- START OF FILE ----
[complete file content]
---- END OF FILE ----

Return ONLY the files in sequence, no JSON, no extra explanations, no markdown fences.
`;

setStatus(4, 'Cross-checking frontend & backend...');
setProgress(4, 30);

const frontendPart = `
Strategic Plan:
${JSON.stringify(STATE.outputs.bot1, null, 2)}

--- FRONTEND CODE ---
HTML:
${STATE.outputs.bot2.html || ''}

CSS:
${STATE.outputs.bot2.css || ''}

JavaScript:
${STATE.outputs.bot2.js || ''}
`.trim();

const backendPart = (STATE.outputs.bot3.files || []).map(f => `File: ${f.name}\n${f.content}`).join('\n\n---\n');

const raw = await callOpenAI([
{ role: 'system', content: systemPrompt },
{ role: 'user', content: frontendPart + '\n\n--- BACKEND CODE ---\n' + backendPart },
]);

setProgress(4, 85);
setStatus(4, 'Integrating final codebase...');

const parsed = parseJSON(raw, 'Bot 4 (Final Validator)');
STATE.outputs.bot4 = parsed;

// --- Ricostruzione preview_html
const htmlFile = parsed.files?.find(f => f.name.toLowerCase().endsWith('.html'));
const cssFile = parsed.files?.find(f => f.name.toLowerCase().endsWith('.css'));
const jsFile = parsed.files?.find(f => f.name.toLowerCase().endsWith('.js'));

if (htmlFile) {
let previewHtml = htmlFile.content;

// Inserimento CSS inline con fallback se non presente </head>
if (cssFile) {
if (previewHtml.includes('</head>')) {
previewHtml = previewHtml.replace(/<\/head>/i, `<style>${cssFile.content}</style>\n</head>`);
} else {
previewHtml = `<style>${cssFile.content}</style>\n` + previewHtml;
}
}

// Inserimento JS inline con fallback se non presente </body>
if (jsFile) {
if (previewHtml.includes('</body>')) {
previewHtml = previewHtml.replace(/<\/body>/i, `<script>${jsFile.content}</script>\n</body>`);
} else {
previewHtml = previewHtml + `\n<script>${jsFile.content}</script>`;
}
}
STATE.outputs.bot4.preview_html = previewHtml;
} else {
STATE.outputs.bot4.preview_html = null;
}

// --- Estrarre summary (esempio semplice, puoi personalizzare)
const summaryMatch = raw.match(/summary\s*:\s*(.+)/i);
if (summaryMatch) {
STATE.outputs.bot4.summary = summaryMatch[1].trim();
} else if (parsed.summary) {
STATE.outputs.bot4.summary = parsed.summary;
} else {
STATE.outputs.bot4.summary = 'No summary provided by Bot 4.';
}

setCardState(4, 'done', STATE.outputs.bot4.summary);
setProgress(4, 100);
setChipsReady(4, ['chip-4-final', 'chip-4-preview']);
enableViewBtn(4);
setPipelineProgress(100);
}

/* ---- RESULTS ---- */
function showResults() {
DOM.resultsSection.style.display = 'flex';
DOM.resultsSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
setStatusBadge('done', 'DONE');
setPipelineProgress(100);
}

/* ---- PREVIEW ---- */
function openPreviewModal() {
const previewHtml = STATE.outputs.bot4?.preview_html;
if (!previewHtml) {
showToast('Preview HTML not available.');
return;
}

DOM.previewProjectName.textContent = STATE.projectName;
DOM.previewIframe.srcdoc = previewHtml;
DOM.previewIframe.style.width = '100%';
setActiveToolbarBtn(DOM.previewDesktop);
openModal(DOM.previewModal);
}

function setPreviewWidth(width, btn) {
DOM.previewIframe.style.width = width;
DOM.previewIframe.style.margin = width === '100%' ? '0' : '0 auto';
setActiveToolbarBtn(btn);
}

function setActiveToolbarBtn(active) {
[DOM.previewDesktop, DOM.previewTablet, DOM.previewMobile].forEach(b => b.classList.remove('active'));
active.classList.add('active');
}

/* ---- DOWNLOAD ZIP ---- */
async function downloadZip() {
const files = STATE.outputs.bot4?.files;
if (!files || !files.length) {
showToast('No files to download.');
return;
}

DOM.downloadBtn.textContent = '↓ Preparing ZIP...';
DOM.downloadBtn.disabled = true;

try {
const username = localStorage.getItem('sync_username') || 'Anonymous';
const formData = new FormData();
formData.append('files', JSON.stringify(files));
formData.append('project_name', STATE.projectName);
formData.append('username', username);

const response = await fetch('api/download.php', {
method: 'POST',
body: formData,
});

if (!response.ok) {
const errText = await response.text();
throw new Error('Download failed: ' + errText);
}

const blob = await response.blob();
const contentType = response.headers.get('Content-Type') || '';

if (contentType.includes('application/zip') || contentType.includes('octet-stream')) {
/* ZIP download */
const url = URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = `${sanitizeFilename(STATE.projectName)}.zip`;
document.body.appendChild(a);
a.click();
document.body.removeChild(a);
URL.revokeObjectURL(url);
} else {
/* Fallback: individual file links via JSON */
const data = JSON.parse(await blob.text());
downloadFilesIndividually(data.files || files);
}
} catch (err) {
showToast(err.message);
downloadFilesIndividually(files);
} finally {
DOM.downloadBtn.textContent = '↓ Download ZIP';
DOM.downloadBtn.disabled = false;
}
}

function downloadFilesIndividually(files) {
files.forEach((file, i) => {
setTimeout(() => {
const blob = new Blob([file.content], { type: 'text/plain' });
const url = URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = file.name.replace(/\//g, '_');
document.body.appendChild(a);
a.click();
document.body.removeChild(a);
URL.revokeObjectURL(url);
}, i * 300);
});
}

/* ---- BOT OUTPUT MODAL ---- */
function showBotOutput(botNum) {
const outputMap = {
1: {
title: '01 — Idea Strategist',
tabs: [{ label: 'Strategic Plan (JSON)', content: () => JSON.stringify(STATE.outputs.bot1, null, 2) }]
},
2: {
title: '02 — Frontend DevBot',
tabs: [
{ label: 'HTML', content: () => STATE.outputs.bot2?.html || '' },
{ label: 'CSS', content: () => STATE.outputs.bot2?.css || '' },
{ label: 'JS', content: () => STATE.outputs.bot2?.js || '' },
]
},
3: {
title: '03 — Backend DevBot',
tabs: (STATE.outputs.bot3?.files || []).map(f => ({
label: f.name,
content: () => f.content,
}))
},
4: {
title: '04 — Final Validator',
tabs: [
...(STATE.outputs.bot4?.files || []).map(f => ({ label: f.name, content: () => f.content })),
{ label: 'Preview HTML', content: () => STATE.outputs.bot4?.preview_html || '' },
{ label: 'Summary', content: () => STATE.outputs.bot4?.summary || '' },
]
},
};

const def = outputMap[botNum];
if (!def || !def.tabs.length) {
showToast('No output available yet.');
return;
}

DOM.outputModalTitle.textContent = def.title;
DOM.outputModalTabs.innerHTML = '';
DOM.outputModalContent.textContent = '';

def.tabs.forEach((tab, idx) => {
const btn = document.createElement('button');
btn.className = 'modal-tab' + (idx === 0 ? ' active' : '');
btn.textContent = tab.label;
btn.addEventListener('click', () => {
DOM.outputModalTabs.querySelectorAll('.modal-tab').forEach(b => b.classList.remove('active'));
btn.classList.add('active');
DOM.outputModalContent.textContent = tab.content();
});
DOM.outputModalTabs.appendChild(btn);
});

DOM.outputModalContent.textContent = def.tabs[0].content();
openModal(DOM.outputModal);
}

/* ---- OPENAI API CALL ---- */
async function callOpenAI(messages) {
    const username = localStorage.getItem('sync_username') || 'Anonymous';
    const payload = {
    api_key: STATE.apiKey,
    model: 'gpt-4.1-nano',
    messages: messages,
    username: username,
    max_tokens: 28000,
    temperature: 0.3,
    };

const response = await fetch('api/chat.php', {
method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify(payload),
});

let data;
try {
data = await response.json();
} catch {
throw new Error(`Server error (HTTP ${response.status}). Make sure a PHP server is running.`);
}

if (!response.ok || data.error) {
const errMsg = data?.error?.message || (typeof data?.error === 'string' ? data.error : JSON.stringify(data?.error)) || `HTTP ${response.status}`;
throw new Error(`API Error (HTTP ${response.status}): ${errMsg}`);
}

const content = data?.choices?.[0]?.message?.content;
if (!content) throw new Error('Empty response from OpenAI API.');
// API response returned successfully - no logging of sensitive data
return content;
}

function parseJSON(raw, botName) {
const cleaned = raw.trim();

// Se inizia con ... togli code fences
let text = cleaned;
if (text.startsWith('')) {
text = text.replace(/^(?:json)?\s*/i, '').replace(/\s*\s*$/, '');
}

// Prova a parsare intero testo come JSON (per compatibilità con caso JSON unico)
try {
const singleJson = JSON.parse(text);
return singleJson;
} catch (e) {
// passa al parsing multi-file
}

// Parsing formato multi-file sequenziale
// Cerca tutte le occorrenze "File: nomefile" seguite da "---- START OF FILE ----" ... "---- END OF FILE ----"
const fileRegex = /File:\s*(.+?)\s*---- START OF FILE ----\s*([\s\S]*?)\s*---- END OF FILE ----/g;

const files = [];
let match;
while ((match = fileRegex.exec(text)) !== null) {
const name = match[1].trim();
const content = match[2];
files.push({ name, content });
}

if (files.length === 0) {
throw new Error(`${botName} returned invalid format (no files found). Raw (first 200 chars): ${raw.substring(0, 200)}`);
}

return { files };
}

/* ---- UI HELPERS ---- */
function setCardState(num, state, statusText) {
const card = $(`card-${num}`);
card.className = `agent-card${num === 4 ? ' agent-card--final' : ''} ${state}`;
setStatus(num, statusText);
updateGlobalProgress(num, state);
}

function setStatus(num, text) {
$(`status-${num}`).textContent = text;
}

function setProgress(num, pct) {
$(`progress-${num}`).style.width = pct + '%';
}

function enableViewBtn(num) {
$(`view-btn-${num}`).disabled = false;
$(`view-btn-${num}`).textContent = 'View Output →';
}

function setChipsReady(num, chipIds) {
chipIds.forEach(id => {
const el = $(id);
if (el) el.classList.add('ready');
});
}

function setPipelineProgress(pct) {
DOM.pipelineProgress.style.width = pct + '%';
}

function updateGlobalProgress(num, state) {
if (state === 'done') {
const pct = (num / 4) * 100;
setPipelineProgress(pct);
}
}

function activateConnector(lineNum) {
const line = $(`line-${lineNum}`);
const pulse = $(`pulse-${lineNum}`);
if (line) { line.classList.add('active'); }
if (pulse) { pulse.classList.add('animating'); }
}

function setStatusBadge(state, text) {
DOM.statusBadge.className = `badge ${state}`;
DOM.statusBadge.textContent = text;
}

function resetCards() {
for (let i = 1; i <= 4; i++) {
const card = $(`card-${i}`);
card.className = `agent-card${i === 4 ? ' agent-card--final' : ''}`;
setStatus(i, 'Waiting...');
setProgress(i, 0);
$(`view-btn-${i}`).disabled = true;
$(`view-btn-${i}`).textContent = 'View Output';
// reset chips
card.querySelectorAll('.output-chip').forEach(c => c.classList.remove('ready'));
// reset connectors
const line = $(`line-${i}`);
const pulse = $(`pulse-${i}`);
if (line) { line.classList.remove('active'); }
if (pulse) { pulse.classList.remove('animating'); }
}
setPipelineProgress(0);
// reset outputs
STATE.outputs = { bot1: null, bot2: null, bot3: null, bot4: null };
}

function resetPipeline() {
resetCards();
DOM.resultsSection.style.display = 'none';
DOM.userRequest.value = '';
DOM.charCount.textContent = '0 chars';
DOM.userRequest.disabled = false;
setStatusBadge('', 'IDLE');
STATE.running = false;
}

/* ---- MODALS ---- */
function openModal(el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeModal(el) { el.classList.remove('open'); document.body.style.overflow = ''; }

/* ---- TOAST ---- */
function showToast(msg) {
DOM.toastMessage.textContent = msg;
DOM.toast.classList.add('visible');
clearTimeout(showToast._t);
showToast._t = setTimeout(hideToast, 10000);
}

function hideToast() {
DOM.toast.classList.remove('visible');
}

/* ---- UTILS ---- */
function sanitizeFilename(name) {
return name.replace(/[^a-z0-9_\-]/gi, '_').toLowerCase() || 'project';
}