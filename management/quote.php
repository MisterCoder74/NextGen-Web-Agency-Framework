<?php
session_start();

// Verifica se l'admin è loggato
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
header('Location: ../index.php');
exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

function load_company_data() {
$file = 'setup.json';
if (!file_exists($file)) {
return null;
}
$content = file_get_contents($file);
return $content ? json_decode($content, true) : null;
}

$company_data = load_company_data();

// Parametri di default personalizzabili
$default_params = [
['label'=>'Ore di lavoro', 'key'=>'work_hours', 'unit'=>'h', 'unit_price' => 35],
['label'=>'Materiali e forniture', 'key'=>'materials', 'unit'=>'€', 'unit_price' => null],
['label'=>'Trasferta', 'key'=>'travel', 'unit'=>'km', 'unit_price' => 0.5],
['label'=>'Consulenza', 'key'=>'consulting', 'unit'=>'h', 'unit_price' => 50],
['label'=>'Installazione/Configurazione', 'key'=>'installation', 'unit'=>'h', 'unit_price' => 40]
];

// Prezzi per tipo di lavoro (costo orario principale)
$job_prices = [
'landing page' => 40,
'sito web' => 35,
'chatbot base' => 45,
'chatbot avanzato' => 65,
'chatbot terze parti' => 55,
'app desktop' => 60,
'mail campaign' => 35,
'consulenza' => 50,
'manutenzione' => 40,
'altro' => 35
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Generatore Preventivi</title>
<link rel="stylesheet" href="css/style.css" />
<style>
/* Stili CSS qui senza modifiche */
.param-box {
background-color: #f8f9fa;
border: 1px solid #dee2e6;
border-radius: 5px;
padding: 15px;
margin-bottom: 15px;
}
.param-box label {
display: block;
margin-bottom: 5px;
font-weight: bold;
color: #495057;
}
.param-box input {
width: 100%;
padding: 8px 12px;
border: 1px solid #ced4da;
border-radius: 4px;
font-size: 14px;
box-sizing: border-box;
}
.param-box input:focus {
outline: none;
border-color: #007bff;
box-shadow: 0 0 5px rgba(0,123,255,0.3);
}
.calc-btn {
background-color: #007bff;
color: white;
padding: 12px 24px;
border: none;
border-radius: 5px;
font-size: 16px;
cursor: pointer;
margin-top: 20px;
}
.calc-btn:hover {
background-color: #0056b3;
}
.result-section {
background-color: #e8f5e8;
border: 1px solid #28a745;
border-radius: 5px;
padding: 20px;
margin-top: 20px;
}
.action-buttons {
margin-top: 15px;
}
.action-buttons button {
background-color: #28a745;
color: white;
padding: 10px 20px;
border: none;
border-radius: 4px;
cursor: pointer;
margin-right: 10px;
font-size: 14px;
}
.action-buttons button:hover {
background-color: #1e7e34;
}
.iva-section {
background-color: #fff3cd;
border: 1px solid #ffeaa7;
border-radius: 5px;
padding: 15px;
margin-top: 15px;
}
.total-section {
background-color: #d1ecf1;
border: 1px solid #bee5eb;
border-radius: 5px;
padding: 15px;
margin-top: 10px;
font-size: 18px;
font-weight: bold;
}
</style>

<script>
const params = <?=json_encode($default_params)?>;
const jobPrices = <?=json_encode($job_prices)?>;
const companyData = <?=json_encode($company_data['company'])?>; // Aggiunto i dati aziendali

</script>

</head>
<body>
<div class="container">
<h1>Generatore Preventivi</h1>

<nav style="margin-bottom: 30px;">
<a href="../admin-dashboard.php">Dashboard Admin</a> |
<a href="logout.php">Disconnetti</a>
</nav>

<form id="quoteForm">
<h3>Inserisci i parametri del preventivo:</h3>

<div class="param-box">
<label for="jobtype">Tipo di lavoro *</label>
<select name="jobtype" id="jobtype" required onchange="updatePricesBasedOnJobType()" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
<option value="">--Seleziona tipo di lavoro--</option>
<option value="landing page">Landing Page (€40/h)</option>
<option value="sito web">Sito Web (€35/h)</option>
<option value="chatbot base">Chatbot Base (€45/h)</option>
<option value="chatbot avanzato">Chatbot Avanzato (€65/h)</option>
<option value="chatbot terze parti">Chatbot Terze Parti (€55/h)</option>
<option value="app desktop">App Desktop (€60/h)</option>
<option value="mail campaign">Mail Campaign (€35/h)</option>
<option value="consulenza">Consulenza (€50/h)</option>
<option value="manutenzione">Manutenzione (€40/h)</option>
<option value="altro">Altro (€35/h)</option>
</select>
<small style="color: #666; font-size: 0.9em;">Il prezzo orario si aggiorna automaticamente in base al tipo di lavoro</small>
</div>

<div id="paramsContainer">
<!-- I campi verranno inseriti dinamicamente da JavaScript -->
</div>

<button type="button" class="calc-btn" onclick="calculateQuote()">Calcola Preventivo</button>
</form>

<div id="result" style="display:none;">
<div class="result-section">
<h2>Preventivo Generato</h2>
<div id="quoteDetails"></div>

<div class="iva-section">
<h4>Calcolo IVA e Sconti</h4>

<div style="margin-bottom: 15px;">
<label for="discountType">Applica sconto:</label>
<select id="discountType" onchange="updateIvaCalculation()" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; margin-top: 5px;">
<option value="0">Nessuno sconto (0%)</option>
<option value="10_nuovo">Nuovo cliente (10%)</option>
<option value="15_grande">Grande commissione (15%)</option>
<option value="10_fedelta">Premio fedeltà (10%)</option>
</select>
</div>

<div style="margin-bottom: 15px;">
<label for="ivaRate">Aliquota IVA (%):</label>
<select id="ivaRate" onchange="updateIvaCalculation()" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; margin-top: 5px;">
<option value="22">22% (Aliquota ordinaria)</option>
<option value="10">10% (Aliquota ridotta)</option>
<option value="4">4% (Aliquota super ridotta)</option>
<option value="0">0% (Esente IVA)</option>
</select>
</div>

<div id="ivaCalculation" style="margin-top: 10px;"></div>
</div>

<div class="total-section">
<div id="finalTotal"></div>
</div>

<div class="action-buttons">
<button id="downloadPdfBtn">📄 Scarica PDF</button>
<button id="saveQuoteBtn">💾 Salva Preventivo</button>
</div>
<div id="saveStatus" style="margin-top: 10px; color: green; font-weight: bold;"></div>
</div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
// Funzione per aggiornare i prezzi in base al tipo di lavoro
function updatePricesBasedOnJobType() {
const jobtype = document.getElementById('jobtype').value;

if (jobtype && jobPrices[jobtype]) {
const hourlyRate = jobPrices[jobtype];

// Aggiorna i prezzi nei parametri
params.forEach(param => {
if (param.key === 'work_hours') {
param.unit_price = hourlyRate;
}
});

// Rigenera i campi con i nuovi prezzi
regenerateParamFields();

// Mostra notifica del cambio prezzo
showPriceUpdateNotification(hourlyRate);
}
}

function regenerateParamFields() {
const container = document.getElementById('paramsContainer');
if (!container) return;

// Salva i valori correnti
const currentValues = {};
container.querySelectorAll('input').forEach(input => {
currentValues[input.name] = input.value;
});

container.innerHTML = '';

params.forEach(p => {
const div = document.createElement('div');
div.className = 'param-box';

const unitPriceText = p.unit_price !== null ? ` (€${p.unit_price} per ${p.unit})` : '';

div.innerHTML = `
<label>${p.label}${unitPriceText}</label>
<input type="number"
min="0"
step="1"
name="${p.key}"
value="${currentValues[p.key] || '0'}"
placeholder="Inserisci ${p.label.toLowerCase()}" />
`;
container.appendChild(div);
});
}

function showPriceUpdateNotification(hourlyRate) {
// Rimuovi notifiche precedenti
const existingNotif = document.getElementById('priceNotification');
if (existingNotif) {
existingNotif.remove();
}

// Crea nuova notifica
const notification = document.createElement('div');
notification.id = 'priceNotification';
notification.style.cssText = `
background-color: #d4edda;
color: #155724;
border: 1px solid #c3e6cb;
padding: 10px;
margin: 10px 0;
border-radius: 4px;
font-size: 14px;
`;
notification.innerHTML = `💰 Prezzo orario aggiornato a €${hourlyRate}/h per questo tipo di lavoro`;

// Inserisci dopo il campo tipo di lavoro
const jobtypeField = document.getElementById('jobtype').closest('.param-box');
jobtypeField.parentNode.insertBefore(notification, jobtypeField.nextSibling);

// Rimuovi dopo 3 secondi
setTimeout(() => {
if (notification && notification.parentNode) {
notification.remove();
}
}, 3000);
}

// Funzione per filtrare e depurare il tipo di lavoro
function sanitizeJobType(jobtype) {
if (!jobtype) return '';

// Lista dei tipi di lavoro consentiti
const allowedJobTypes = [
'landing page',
'sito web',
'chatbot base',
'chatbot avanzato',
'chatbot terze parti',
'app desktop',
'mail campaign',
'consulenza',
'manutenzione',
'altro'
];

// Converte in minuscolo e rimuove spazi extra
const cleanJobType = jobtype.toString().toLowerCase().trim();

// Verifica se il tipo è nella lista consentita
if (allowedJobTypes.includes(cleanJobType)) {
return cleanJobType;
}

return '';
}

document.addEventListener('DOMContentLoaded', () => {
const container = document.getElementById('paramsContainer');
if (!container) return;

container.innerHTML = '';

params.forEach(p => {
const div = document.createElement('div');
div.className = 'param-box';

const unitPriceText = p.unit_price !== null ? ` (€${p.unit_price} per ${p.unit})` : '';

div.innerHTML = `
<label>${p.label}${unitPriceText}</label>
<input type="number"
min="0"
step="1"
name="${p.key}"
value="0"
placeholder="Inserisci ${p.label.toLowerCase()}" />
`;
container.appendChild(div);
});
});

let baseTotal = 0;

function calculateQuote() {
const form = document.getElementById('quoteForm');
if (!form) return alert("Form non trovato.");

const formData = new FormData(form);

// Valida e filtra il tipo di lavoro
const jobtype = sanitizeJobType(formData.get('jobtype'));
if (!jobtype) {
alert('Seleziona il tipo di lavoro.');
return;
}

let total = 0;
let breakdown = [];

params.forEach(p => {
let val = parseFloat(formData.get(p.key)) || 0;
if (val < 0) val = 0;

let item_cost = 0;
if (p.unit_price === null) {
item_cost = val;
} else {
item_cost = val * p.unit_price;
}

if (val > 0) {
breakdown.push({
label: p.label,
quantity: val,
unit: p.unit,
unit_price: p.unit_price,
cost: item_cost
});
}

total += item_cost;
});

if (breakdown.length === 0) {
alert('Inserisci almeno un parametro maggiore di zero.');
return;
}

baseTotal = total;

const result = document.getElementById('result');
const quoteDetails = document.getElementById('quoteDetails');

let detailsHtml = `<h4>Tipo di lavoro: <em>${jobtype}</em></h4>`;
detailsHtml += `<h4>Dettaglio servizi:</h4><ul>`;
breakdown.forEach(item => {
const unitPriceDisplay = item.unit_price !== null ? `€${item.unit_price}` : '';
const quantityDisplay = item.unit_price !== null ? `${item.quantity} ${item.unit} x ` : '';
detailsHtml += `<li><strong>${item.label}</strong>: ${quantityDisplay}${unitPriceDisplay} = €${item.cost.toFixed(2)}</li>`;
});
detailsHtml += `</ul>`;
detailsHtml += `<p><strong>Subtotale (imponibile): €${total.toFixed(2)}</strong></p>`;

quoteDetails.innerHTML = detailsHtml;
result.style.display = 'block';

// Calcola IVA iniziale
updateIvaCalculation();

// Salva dati globali per PDF e salvataggio
window.currentQuote = {
date: new Date().toISOString(),
jobtype: jobtype,
subtotal: total.toFixed(2),
breakdown,
company: companyData, // Aggiunto i dati aziendali
discountRate: 0,
discountLabel: '',
discountAmount: '0.00',
totalAfterDiscount: total.toFixed(2),
ivaRate: 22, // Default IVA 22%
ivaAmount: (total * 0.22).toFixed(2),
total: (total * 1.22).toFixed(2)
};
}

function updateIvaCalculation() {
if (baseTotal === 0) return;

// Calcola sconto
const discountSelect = document.getElementById('discountType');
const discountValue = discountSelect.value;

let discountRate = 0;
let discountLabel = '';

if (discountValue !== '0') {
const [rate, type] = discountValue.split('_');
discountRate = parseFloat(rate);

switch(type) {
case 'nuovo': discountLabel = 'Nuovo cliente'; break;
case 'grande': discountLabel = 'Grande commissione'; break;
case 'fedelta': discountLabel = 'Premio fedeltà'; break;
}
}

const discountAmount = baseTotal * (discountRate / 100);
const totalAfterDiscount = baseTotal - discountAmount;

// Calcola IVA sul totale scontato
const ivaRate = parseFloat(document.getElementById('ivaRate').value);
const ivaAmount = totalAfterDiscount * (ivaRate / 100);
const finalTotal = totalAfterDiscount + ivaAmount;

const ivaCalcDiv = document.getElementById('ivaCalculation');
const finalTotalDiv = document.getElementById('finalTotal');

let calculationHtml = `<p><strong>Riepilogo calcoli:</strong></p>`;
calculationHtml += `<p>Subtotale: €${baseTotal.toFixed(2)}</p>`;

if (discountRate > 0) {
calculationHtml += `<p style="color: #dc3545;">Sconto ${discountLabel} (-${discountRate}%): -€${discountAmount.toFixed(2)}</p>`;
calculationHtml += `<p>Totale dopo sconto: €${totalAfterDiscount.toFixed(2)}</p>`;
}

calculationHtml += `<p>IVA (${ivaRate}%): €${ivaAmount.toFixed(2)}</p>`;

ivaCalcDiv.innerHTML = calculationHtml;
finalTotalDiv.innerHTML = `TOTALE PREVENTIVO: €${finalTotal.toFixed(2)}`;

// Aggiorna il preventivo corrente con i dati completi
if (window.currentQuote) {
window.currentQuote.discountRate = discountRate;
window.currentQuote.discountLabel = discountLabel;
window.currentQuote.discountAmount = discountAmount.toFixed(2);
window.currentQuote.totalAfterDiscount = totalAfterDiscount.toFixed(2);
window.currentQuote.ivaRate = ivaRate;
window.currentQuote.ivaAmount = ivaAmount.toFixed(2);
window.currentQuote.total = finalTotal.toFixed(2);
}
}

function generatePdf(quote) {
const { jsPDF } = window.jspdf;
const doc = new jsPDF();

// Intestazione
doc.setFontSize(20);
doc.setTextColor(0, 0, 0);
doc.text('PREVENTIVO', 105, 20, { align: 'center' });

// Dati azienda
doc.setFontSize(12);
doc.text(`Data: ${new Date(quote.date).toLocaleDateString('it-IT')}`, 10, 40);
doc.text(`Tipo di lavoro: ${quote.jobtype || 'Non specificato'}`, 10, 48);
doc.text(`Azienda: ${quote.company.name}`, 10, 58);
doc.text(`Indirizzo: ${quote.company.address}`, 10, 66);
doc.text(`Telefono: ${quote.company.phone}`, 10, 74);
doc.text(`P.IVA: ${quote.company.iva}`, 10, 82);

// Linea separatrice
doc.line(10, 88, 200, 88);

// Dettaglio servizi
doc.setFontSize(14);
doc.text('Dettaglio servizi:', 10, 98);

doc.setFontSize(10);
let y = 108;
quote.breakdown.forEach(item => {
const unitPrice = item.unit_price !== null ? `€${item.unit_price}` : '';
const quantity = item.unit_price !== null ? `${item.quantity} ${item.unit} x ` : '';
const line = `${item.label}: ${quantity}${unitPrice} = €${item.cost}`;
doc.text(line, 12, y);
y += 6;
});

// Totali
y += 10;
doc.line(10, y, 200, y);
y += 8;

doc.setFontSize(12);
doc.text(`Imponibile: €${quote.subtotal || '0.00'}`, 10, y);
y += 8;
doc.text(`IVA (${quote.ivaRate || 22}%): €${quote.ivaAmount || '0.00'}`, 10, y);
y += 8;

doc.setFontSize(14);
doc.setTextColor(0, 100, 0);
doc.text(`TOTALE: €${quote.total || quote.subtotal || '0.00'}`, 10, y);

doc.save(`Preventivo_${quote.company.name}_${new Date().toISOString().slice(0,10)}.pdf`);
}

function saveQuote() {
if (!window.currentQuote) {
alert("Calcola prima il preventivo.");
return;
}

fetch('save_quote.php', {
method: 'POST',
headers: {'Content-Type':'application/json'},
body: JSON.stringify(window.currentQuote)
}).then(res => res.json())
.then(data => {
const statusDiv = document.getElementById('saveStatus');
if (data.success) {
statusDiv.textContent = "✅ Preventivo salvato correttamente!";
statusDiv.style.color = 'green';
} else {
statusDiv.textContent = "❌ Errore nel salvataggio: " + (data.error || 'Errore sconosciuto');
statusDiv.style.color = 'red';
}
})
.catch(err => {
const statusDiv = document.getElementById('saveStatus');
statusDiv.textContent = "❌ Errore di connessione.";
statusDiv.style.color = 'red';
});
}

// Eventi per i pulsanti
document.addEventListener('click', function(event) {
if(event.target.matches('button')) {
const id = event.target.id;

switch(id) {
case 'downloadPdfBtn':
if (!window.currentQuote) {
alert("Calcola prima il preventivo.");
return;
}
generatePdf(window.currentQuote);
break;
case 'saveQuoteBtn':
saveQuote();
break;
}
}
});

</script>
</body>
</html>
