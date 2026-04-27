
function parseDateLocal(dateString) {
const [year, month, day] = dateString.split('-').map(Number);
return new Date(year, month - 1, day);
}

function formatDateItaly(dateObj) {
return new Intl.DateTimeFormat('it-IT', {
timeZone: 'Europe/Rome',
day: '2-digit',
month: '2-digit',
year: 'numeric'
}).format(dateObj);
}

function formatDateLocalForInput(date) {
const y = date.getFullYear();
const m = String(date.getMonth() + 1).padStart(2, '0');
const d = String(date.getDate()).padStart(2, '0');
return `${y}-${m}-${d}`;
}

// Configurazione globale
let currentDate = new Date();
let currentYear = currentDate.getFullYear();
let currentMonth = currentDate.getMonth();
let bookings = [];
let timeSlots = [];

// Configurazione fasce orarie
let config = {
startHour: 8,
startMinute: 0,
endHour: 20,
endMinute: 0,
intervalMinutes: 30
};

// Inizializzazione
document.addEventListener('DOMContentLoaded', function() {
initializeEventListeners();
loadBookings();
updateCalendar();
});

// Event Listeners
function initializeEventListeners() {
// Navigazione mese
document.getElementById('prevMonth').addEventListener('click', () => {
currentMonth--;
if (currentMonth < 0) {
currentMonth = 11;
currentYear--;
}
updateCalendar();
});

document.getElementById('nextMonth').addEventListener('click', () => {
currentMonth++;
if (currentMonth > 11) {
currentMonth = 0;
currentYear++;
}
updateCalendar();
});

document.getElementById('todayBtn').addEventListener('click', () => {
const today = new Date();
currentYear = today.getFullYear();
currentMonth = today.getMonth();
updateCalendar();
});

// Applica impostazioni orario
document.getElementById('applyTimeSettings').addEventListener('click', applyTimeSettings);

// Modal
document.querySelectorAll('.close').forEach(btn => {
btn.addEventListener('click', closeModal);
});

document.querySelectorAll('.cancel-btn').forEach(btn => {
btn.addEventListener('click', closeModal);
});

// Form prenotazione
document.getElementById('bookingForm').addEventListener('submit', saveBooking);
document.getElementById('deleteBtn').addEventListener('click', deleteBooking);
document.getElementById('editBookingBtn').addEventListener('click', editFromDetails);

// Chiudi modal cliccando fuori
window.addEventListener('click', (e) => {
if (e.target.classList.contains('modal')) {
closeModal();
}
});
}

// Applica configurazione orari
function applyTimeSettings() {
const startHour = document.getElementById('startHour').value;
const endHour = document.getElementById('endHour').value;
const intervalMinutes = parseInt(document.getElementById('intervalMinutes').value);

const [startH, startM] = startHour.split(':').map(Number);
const [endH, endM] = endHour.split(':').map(Number);

config.startHour = startH;
config.startMinute = startM;
config.endHour = endH;
config.endMinute = endM;
config.intervalMinutes = intervalMinutes;

updateCalendar();
}

// Genera fasce orarie
function generateTimeSlots() {
const slots = [];
let currentHour = config.startHour;
let currentMinute = config.startMinute;

while (currentHour < config.endHour || (currentHour === config.endHour && currentMinute < config.endMinute)) {
const timeString = `${String(currentHour).padStart(2, '0')}:${String(currentMinute).padStart(2, '0')}`;
slots.push(timeString);

currentMinute += config.intervalMinutes;
if (currentMinute >= 60) {
currentHour += Math.floor(currentMinute / 60);
currentMinute = currentMinute % 60;
}
}

return slots;
}

// Aggiorna calendario
function updateCalendar() {
timeSlots = generateTimeSlots();

// Aggiorna intestazione mese
const monthNames = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
document.getElementById('currentMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;

// Genera sidebar giorni
generateDaysSidebar();

// Genera header fasce orarie
generateTimeHeader();

// Genera griglia
generateGrid();

// Renderizza prenotazioni
renderBookings();
}

// Genera sidebar con giorni del mese
function generateDaysSidebar() {
const sidebar = document.getElementById('daysSidebar');
sidebar.innerHTML = '';

const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
const today = new Date();
const dayNames = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];

for (let day = 1; day <= daysInMonth; day++) {
const date = new Date(currentYear, currentMonth, day);
const dayOfWeek = date.getDay();

const dayCell = document.createElement('div');
dayCell.className = 'day-cell';

// Evidenzia oggi
if (date.toDateString() === today.toDateString()) {
dayCell.classList.add('today');
}

// Evidenzia weekend
if (dayOfWeek === 0 || dayOfWeek === 6) {
dayCell.classList.add('weekend');
}

dayCell.innerHTML = `
<span class="day-number">${day}</span>
<span class="day-name">${dayNames[dayOfWeek]}</span>
`;

sidebar.appendChild(dayCell);
}
}

// Genera header fasce orarie
function generateTimeHeader() {
const header = document.getElementById('timeHeader');
header.innerHTML = '';
header.style.gridTemplateColumns = `repeat(${timeSlots.length}, 1fr)`;

timeSlots.forEach(slot => {
const slotHeader = document.createElement('div');
slotHeader.className = 'time-slot-header';
slotHeader.textContent = slot;
header.appendChild(slotHeader);
});
}

// Genera griglia celle
function generateGrid() {
const grid = document.getElementById('bookingGrid');
grid.innerHTML = '';

const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

grid.style.gridTemplateColumns = `repeat(${timeSlots.length}, 1fr)`;
grid.style.gridTemplateRows = `repeat(${daysInMonth}, 60px)`;

for (let day = 1; day <= daysInMonth; day++) {
for (let slotIndex = 0; slotIndex < timeSlots.length; slotIndex++) {
const cell = document.createElement('div');
cell.className = 'grid-cell';
cell.dataset.day = day;
cell.dataset.slot = timeSlots[slotIndex];

cell.addEventListener('click', () => openNewBookingModal(day, timeSlots[slotIndex]));

grid.appendChild(cell);
}
}
}

// Carica prenotazioni
async function loadBookings() {
try {
const response = await fetch('load_bookings.php', {
cache: 'no-store'
});
const data = await response.json();
bookings = data.bookings || [];
renderBookings();
} catch (error) {
console.error('Errore caricamento prenotazioni:', error);
bookings = [];
}
}

// Renderizza prenotazioni sulla griglia
function renderBookings() {
// Rimuovi blocchi esistenti
document.querySelectorAll('.booking-block').forEach(block => block.remove());

bookings.forEach(booking => {
// Usa parseDateLocal invece di new Date diretto
const bookingDate = parseDateLocal(booking.date);

// Solo prenotazioni del mese corrente
if (bookingDate.getMonth() === currentMonth && bookingDate.getFullYear() === currentYear) {
const day = bookingDate.getDate();
const startTime = booking.startTime;

// Trova la cella corrispondente
const cell = document.querySelector(`.grid-cell[data-day="${day}"][data-slot="${startTime}"]`);

if (cell) {
const block = document.createElement('div');
block.className = `booking-block ${booking.priority}`;
block.innerHTML = `
<div class="booking-client">${booking.clientName}</div>
<div class="booking-service">${booking.serviceType}</div>
`;

block.addEventListener('click', (e) => {
e.stopPropagation();
showBookingDetails(booking);
});

cell.appendChild(block);
cell.classList.add('has-booking');
}
}
});
}

// Apri modal nuova prenotazione
function openNewBookingModal(day, timeSlot) {
const modal = document.getElementById('bookingModal');
const form = document.getElementById('bookingForm');

document.getElementById('modalTitle').textContent = 'Nuova Prenotazione' + day;
document.getElementById('deleteBtn').style.display = 'none';

form.reset();
document.getElementById('bookingId').value = '';

const date = new Date(currentYear, currentMonth, day);
document.getElementById('bookingDate').value = formatDateLocalForInput(date);
document.getElementById('bookingTime').value = timeSlot;
document.getElementById('startTime').value = timeSlot;

// Calcola ora fine (aggiungi intervallo)
const [hours, minutes] = timeSlot.split(':').map(Number);
const endMinutes = minutes + config.intervalMinutes;
const endHours = hours + Math.floor(endMinutes / 60);
const finalMinutes = endMinutes % 60;
document.getElementById('endTime').value =
`${String(endHours).padStart(2, '0')}:${String(finalMinutes).padStart(2, '0')}`;

modal.classList.add('show');
}

// Mostra dettagli prenotazione
function showBookingDetails(booking) {
const modal = document.getElementById('detailsModal');
const details = document.getElementById('bookingDetails');

// Usa parseDateLocal e formatDateItaly
const date = parseDateLocal(booking.date);
const formattedDate = formatDateItaly(date);

details.innerHTML = `
<div class="detail-row">
<span class="detail-label">Data:</span>
<span class="detail-value">${formattedDate}</span>
</div>
<div class="detail-row">
<span class="detail-label">Orario:</span>
<span class="detail-value">${booking.startTime} - ${booking.endTime}</span>
</div>
<div class="detail-row">
<span class="detail-label">Cliente:</span>
<span class="detail-value">${booking.clientName}</span>
</div>
<div class="detail-row">
<span class="detail-label">Telefono:</span>
<span class="detail-value">${booking.clientPhone || '-'}</span>
</div>
<div class="detail-row">
<span class="detail-label">Email:</span>
<span class="detail-value">${booking.clientEmail || '-'}</span>
</div>
<div class="detail-row">
<span class="detail-label">Servizio:</span>
<span class="detail-value">${booking.serviceType}</span>
</div>
<div class="detail-row">
<span class="detail-label">Priorità:</span>
<span class="detail-value">${booking.priority === 'urgente' ? 'Urgente' : 'Normale'}</span>
</div>
<div class="detail-row">
<span class="detail-label">Note:</span>
<span class="detail-value">${booking.notes || '-'}</span>
</div>
`;

document.getElementById('editBookingBtn').onclick = () => {
closeModal();
editBooking(booking);
};

modal.classList.add('show');
}

// Modifica prenotazione
function editBooking(booking) {
const modal = document.getElementById('bookingModal');
const form = document.getElementById('bookingForm');

document.getElementById('modalTitle').textContent = 'Modifica Prenotazione';
document.getElementById('deleteBtn').style.display = 'block';

document.getElementById('bookingId').value = booking.id;
document.getElementById('bookingDate').value = booking.date;
document.getElementById('clientName').value = booking.clientName;
document.getElementById('clientPhone').value = booking.clientPhone || '';
document.getElementById('clientEmail').value = booking.clientEmail || '';
document.getElementById('serviceType').value = booking.serviceType;
document.getElementById('startTime').value = booking.startTime;
document.getElementById('endTime').value = booking.endTime;
document.getElementById('priority').value = booking.priority;
document.getElementById('notes').value = booking.notes || '';

modal.classList.add('show');
}

function editFromDetails() {
const detailsModal = document.getElementById('detailsModal');
const detailsText = detailsModal.querySelector('#bookingDetails').textContent;

// Trova la prenotazione dall'ID memorizzato
const bookingId = detailsModal.dataset.currentBookingId;
const booking = bookings.find(b => b.id == bookingId);

if (booking) {
closeModal();
editBooking(booking);
}
}

// Salva prenotazione (crea o modifica)
async function saveBooking(e) {
e.preventDefault();

const bookingId = document.getElementById('bookingId').value;
const bookingData = {
id: bookingId || Date.now().toString(),
date: document.getElementById('bookingDate').value,
clientName: document.getElementById('clientName').value,
clientPhone: document.getElementById('clientPhone').value,
clientEmail: document.getElementById('clientEmail').value,
serviceType: document.getElementById('serviceType').value,
startTime: document.getElementById('startTime').value,
endTime: document.getElementById('endTime').value,
priority: document.getElementById('priority').value,
notes: document.getElementById('notes').value
};

try {
const url = bookingId ? 'update_booking.php' : 'save_booking.php';
const response = await fetch(url, {
method: 'POST',
headers: {
'Content-Type': 'application/json'
},
body: JSON.stringify(bookingData),
cache: 'no-store'
});

const result = await response.json();

if (result.success) {
closeModal();
loadBookings();
} else {
alert('Errore: ' + result.message);
}
} catch (error) {
console.error('Errore salvataggio:', error);
alert('Errore durante il salvataggio');
}
}

// Elimina prenotazione
async function deleteBooking() {
if (!confirm('Sei sicuro di voler eliminare questa prenotazione?')) {
return;
}

const bookingId = document.getElementById('bookingId').value;

try {
const response = await fetch('delete_booking.php', {
method: 'POST',
headers: {
'Content-Type': 'application/json'
},
body: JSON.stringify({ id: bookingId }),
cache: 'no-store'
});

const result = await response.json();

if (result.success) {
closeModal();
loadBookings();
} else {
alert('Errore: ' + result.message);
}
} catch (error) {
console.error('Errore eliminazione:', error);
alert('Errore durante l\'eliminazione');
}
}

// Chiudi modal
function closeModal() {
document.querySelectorAll('.modal').forEach(modal => {
modal.classList.remove('show');
});
}