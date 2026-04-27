document.addEventListener('DOMContentLoaded', () => {

// === ELEMENTI PRINCIPALI ===
window.chatMemory = [];
const taskOutput = document.getElementById('taskOutput');
const codeOutput = document.getElementById('codeOutput');
const artifactFrame = document.getElementById('artifactFrame');
const leftPanel = document.getElementById('leftPanel');
const decomposeBtn = document.getElementById('decomposeBtn');
const executeBtn = document.getElementById('executeBtn');
executeBtn.disabled = true; // disattivato all’avvio
const memSaved = localStorage.getItem('vivacity_chatMemory');
if (memSaved) chatMemory = JSON.parse(memSaved);
const delay = ms => new Promise(r => setTimeout(r, ms));

// === gestione modale editing tasks ===
const editTasksIcon = document.getElementById('editTasksIcon');
const editTasksModal = document.getElementById('editTasksModal');
const tasksEditor = document.getElementById('tasksEditor');
const saveTasksBtn = document.getElementById('saveTasks');
const cancelTasksBtn = document.getElementById('cancelTasks');

let totalTokensSession = 0; // 👉 nuovo contatore cumulativo

// === Funzione generica per chiamate API ===
async function callAPI(action, data = {}) {
try {
const apiKey = localStorage.getItem('vivacity_apiKey') || '';
const model = localStorage.getItem('vivacity_model') || '';

if (!apiKey) {
alert('⚠️ Nessuna API key impostata. Vai su "Setup" nella sidebar.');
return {};
}

const res = await fetch('viber_api.php', {
method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify({ action, apiKey, model, ...data })
});
const json = await res.json();

// 🔢 aggiorna totale se presente campo tokens
if (json.tokens) totalTokensSession += Number(json.tokens || 0);

return json;
} catch (err) {
console.error('API error:', err);
return {};
}
}

/* === 1️⃣ DECOMPOSE === */
decomposeBtn.addEventListener('click', async () => {
const prompt = document.getElementById('userPrompt').value.trim();
if (!prompt) return alert('Please enter a request!');

chatMemory.push({
prompt,
fragments: [],
htmlFinal: "",
timestamp: Date.now()
});

// Determina contesto precedente (ultimo html generato, se esiste)
const previousContext = (chatMemory.length > 1)
? chatMemory[chatMemory.length - 2].htmlFinal || ""
: "";

taskOutput.textContent = '⏳ Decomposing prompt...';

const data = await callAPI('decompose', { prompt, previousContext });

if (data.tasks?.length) {
taskOutput.textContent =
`✅ ${data.tasks.length} tasks found (tokens used: ${data.tokens || 0})\n` +
JSON.stringify(data.tasks, null, 2);
codeOutput.textContent = '';
artifactFrame.srcdoc = '';

// 🔘 dopo decomposizione: gestisco autoExecute
const autoExecute = (localStorage.getItem('vivacity_autoExecute') === 'true');
if (autoExecute) {
executeBtn.disabled = false;
executeBtn.click(); // esegue automaticamente
} else {
executeBtn.disabled = false; // abilita manuale
}
} else {
taskOutput.textContent = '❌ No tasks found.';
}
});

/* === 2️⃣ EXECUTE PROGRESSIVELY === */
executeBtn.addEventListener('click', async () => {
const bundle = await callAPI('get_tasks');
if (!bundle?.tasks) {
taskOutput.textContent = '❌ No tasks. Decompose first.';
return;
}

const steps = bundle.tasks;
const fragments = [];
taskOutput.textContent = `🚀 Executing ${steps.length} tasks...\n`;

for (let i = 0; i < steps.length; i++) {
const t = steps[i];
taskOutput.textContent += `\n🧩 Step ${i + 1}/${steps.length}: ${t.task}`;

const res = await callAPI('execute_single_task', {
taskData: t,
taskId: t.id,
previousFragments: fragments
});

let snippet = (res.html || '').trim();
// 👉 visualizza i token per ogni task
taskOutput.textContent += `\n ↳ tokens used: ${res.tokens || 0} (partial total: ${totalTokensSession})`;

// clean Markdown fences
snippet = snippet.replace(/^[\w]*\n?([\s\S]*?)$/i, '$1').trim();

// estrai document completo se presente
const m = snippet.match(/<!DOCTYPE html[\s\S]*<\/html>/i);
if (m) snippet = m[0];

fragments.push({ id: t.id, task: t.task, code: snippet });
// Aggiorna anche l'ultima entry in chatMemory
if (chatMemory.length) {
chatMemory[chatMemory.length - 1].fragments = [...fragments];
}

codeOutput.textContent = snippet;
artifactFrame.srcdoc = snippet;
await delay(600);
}

// === ASSEMBLY ===
taskOutput.textContent += '\n🧩 Assembling final HTML...';
const bodyParts = fragments.map(f => {
let code = f.code;
code = code.replace(/<!DOCTYPE[^>]*>/gi, '');
code = code.replace(/<\/?html[^>]*>/gi, '');
code = code.replace(/<\/?head[^>]*>[\s\S]*?<\/head>/gi, '');
code = code.replace(/<\/?body[^>]*>/gi, '');
return `<!-- Task ${f.id}: ${f.task} -->\n${code.trim()}`;
}).join('\n\n');

const assembleRes = await callAPI('assemble_final', {
context: bundle.prompt,
fragments,
assembledBody: bodyParts
});

if (assembleRes.html) {
artifactFrame.srcdoc = assembleRes.html;
codeOutput.textContent = assembleRes.html;

// ✅ salva l’HTML finale nella chatMemory
if (chatMemory.length) {
chatMemory[chatMemory.length - 1].htmlFinal = assembleRes.html;
}

// ❓salva anche su localStorage per persistenza tra sessioni
localStorage.setItem('vivacity_chatMemory', JSON.stringify(chatMemory));

taskOutput.textContent +=
`\n✅ Final page ready. (tokens this step: ${assembleRes.tokens || 0})` +
`\n\n🔢 TOTAL TOKENS USED IN SESSION: ${totalTokensSession}`;
createDownloadButton(assembleRes.html);
} else {
taskOutput.textContent += '\n❌ Assembly error.';
}
});

/* === 3️⃣ DOWNLOAD BUTTON === */
function createDownloadButton(html) {
document.getElementById('downloadBtn')?.remove();
const b = document.createElement('button');
b.id = 'downloadBtn';
b.textContent = 'Download Final HTML';
b.onclick = () => {
const blob = new Blob([html], { type: 'text/html' });
const a = document.createElement('a');
a.href = URL.createObjectURL(blob);
a.download = `generated_${Date.now()}.html`;
a.click();
URL.revokeObjectURL(a.href);
};
leftPanel.appendChild(b);
}

// === 4️⃣ MODALE EDITING TASKS ===

// apri modale
if (editTasksIcon) {
editTasksIcon.onclick = async () => {
const res = await callAPI('get_tasks');
if (!res?.tasks) return alert('No tasks file found.');
tasksEditor.value = JSON.stringify(res.tasks, null, 2);
editTasksModal.style.display = 'flex';
};
}

// chiudi modale
if (cancelTasksBtn) {
cancelTasksBtn.onclick = () => (editTasksModal.style.display = 'none');
}

window.addEventListener('click', e => {
if (e.target === editTasksModal) editTasksModal.style.display = 'none';
});

// salva tasks modificati
if (saveTasksBtn) {
saveTasksBtn.onclick = async () => {
let newTasks;
try {
newTasks = JSON.parse(tasksEditor.value);
} catch (err) {
return alert('JSON non valido!');
}

const res = await callAPI('save_tasks', { tasks: newTasks });
if (res.success) {
alert('Tasks aggiornati con successo.');
editTasksModal.style.display = 'none';
taskOutput.textContent =
`✅ ${newTasks.length} tasks updated:\n` +
JSON.stringify(newTasks, null, 2);
} else {
alert('Errore durante il salvataggio.');
}
};
}

});