
function parseDateLocal(dateString) {
    if (!dateString) return null;
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
let projects = [];
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
document.addEventListener('DOMContentLoaded', async function() {
    initializeEventListeners();
    await loadProjects();
    await loadBookings();
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

    // All Day checkbox logic
    document.getElementById('allDayCheckbox').addEventListener('change', function() {
        const timeFields = document.getElementById('timeFields');
        if (this.checked) {
            timeFields.style.display = 'none';
            document.getElementById('startTime').required = false;
            document.getElementById('endTime').required = false;
        } else {
            timeFields.style.display = 'grid';
            document.getElementById('startTime').required = true;
            document.getElementById('endTime').required = true;
        }
    });

    // Chiudi modal cliccando fuori
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeModal();
        }
    });
}

// Carica progetti
async function loadProjects() {
    try {
        const response = await fetch('projects.json', { cache: 'no-store' });
        projects = await response.json();
        populateProjectSelect();
    } catch (error) {
        console.error('Errore caricamento progetti:', error);
    }
}

function populateProjectSelect() {
    const select = document.getElementById('projectSelect');
    if (!select) return;
    // Keep first option
    select.innerHTML = '<option value="">Select Project...</option>';
    projects.forEach(project => {
        const option = document.createElement('option');
        option.value = project.id;
        option.textContent = project.nome_progetto;
        select.appendChild(option);
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

    const grid = document.getElementById('plannerGrid');
    grid.innerHTML = '';

    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    
    // Configura le colonne della griglia: 80px per sidebar, 100px per All Day, e poi le fasce orarie
    grid.style.gridTemplateColumns = `80px 100px repeat(${timeSlots.length}, 120px)`;
    
    // Corner cell
    const corner = document.createElement('div');
    corner.className = 'corner-cell';
    corner.textContent = 'Day / Time';
    corner.style.gridRow = '1';
    corner.style.gridColumn = '1';
    grid.appendChild(corner);

    // All Day Header
    const allDayHeader = document.createElement('div');
    allDayHeader.className = 'time-slot-header';
    allDayHeader.textContent = 'All Day';
    allDayHeader.style.gridRow = '1';
    allDayHeader.style.gridColumn = '2';
    grid.appendChild(allDayHeader);

    // Time Headers
    timeSlots.forEach((slot, index) => {
        const header = document.createElement('div');
        header.className = 'time-slot-header';
        header.textContent = slot;
        header.style.gridRow = '1';
        header.style.gridColumn = (index + 3).toString();
        grid.appendChild(header);
    });

    const today = new Date();
    const dayNames = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];

    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(currentYear, currentMonth, day);
        const dayOfWeek = date.getDay();
        const row = day + 1;

        // Day Sidebar Cell
        const dayCell = document.createElement('div');
        dayCell.className = 'day-sidebar-cell';
        if (date.toDateString() === today.toDateString()) dayCell.classList.add('today');
        if (dayOfWeek === 0 || dayOfWeek === 6) dayCell.classList.add('weekend');
        dayCell.style.gridRow = row.toString();
        dayCell.style.gridColumn = '1';
        dayCell.innerHTML = `
            <span class="day-number">${day}</span>
            <span class="day-name">${dayNames[dayOfWeek]}</span>
        `;
        grid.appendChild(dayCell);

        // All Day Grid Cell
        const allDayCell = document.createElement('div');
        allDayCell.className = 'grid-cell';
        allDayCell.dataset.day = day;
        allDayCell.dataset.slot = 'all-day';
        allDayCell.style.gridRow = row.toString();
        allDayCell.style.gridColumn = '2';
        allDayCell.addEventListener('click', () => openNewBookingModal(day, 'all-day'));
        grid.appendChild(allDayCell);

        // Time Grid Cells
        timeSlots.forEach((slot, index) => {
            const cell = document.createElement('div');
            cell.className = 'grid-cell';
            cell.dataset.day = day;
            cell.dataset.slot = slot;
            cell.style.gridRow = row.toString();
            cell.style.gridColumn = (index + 3).toString();
            cell.addEventListener('click', () => openNewBookingModal(day, slot));
            grid.appendChild(cell);
        });
    }

    renderBookings();
}

// Carica prenotazioni
async function loadBookings() {
    try {
        const response = await fetch('load_bookings.php', {
            cache: 'no-store'
        });
        const data = await response.json();
        bookings = data.bookings || [];
        
        // Add virtual bookings from projects milestones
        addProjectMilestones();
        
        renderBookings();
    } catch (error) {
        console.error('Errore caricamento prenotazioni:', error);
        bookings = [];
    }
}

function addProjectMilestones() {
    projects.forEach(project => {
        if (project.data_inizio) {
            bookings.push({
                id: `proj_start_${project.id}`,
                date: project.data_inizio,
                taskName: `🚀 Project Start: ${project.nome_progetto}`,
                projectName: project.nome_progetto,
                projectId: project.id,
                description: `Official start of ${project.nome_progetto}`,
                isAllDay: true,
                priority: 'normale',
                creator: 'System',
                isVirtual: true
            });
        }
        if (project.data_fine) {
            bookings.push({
                id: `proj_end_${project.id}`,
                date: project.data_fine,
                taskName: `🏁 Project Deadline: ${project.nome_progetto}`,
                projectName: project.nome_progetto,
                projectId: project.id,
                description: `Deadline for ${project.nome_progetto}`,
                isAllDay: true,
                priority: 'urgente',
                creator: 'System',
                isVirtual: true
            });
        }
    });
}

function getTimeSlotIndex(timeStr, roundUp = false) {
    const [h, m] = timeStr.split(':').map(Number);
    const totalMinutes = h * 60 + m;
    
    let index = -1;
    
    if (roundUp) {
        // Find the first slot that is >= totalMinutes
        for (let i = 0; i < timeSlots.length; i++) {
            const [sh, sm] = timeSlots[i].split(':').map(Number);
            const slotMinutes = sh * 60 + sm;
            if (slotMinutes >= totalMinutes) {
                return i;
            }
        }
        return timeSlots.length; // Beyond last slot
    } else {
        // Find the first slot that is <= totalMinutes
        for (let i = timeSlots.length - 1; i >= 0; i--) {
            const [sh, sm] = timeSlots[i].split(':').map(Number);
            const slotMinutes = sh * 60 + sm;
            if (slotMinutes <= totalMinutes) {
                return i;
            }
        }
        return 0;
    }
}

// Renderizza prenotazioni sulla griglia
function renderBookings() {
    // Rimuovi blocchi esistenti
    document.querySelectorAll('.booking-block').forEach(block => block.remove());

    const grid = document.getElementById('plannerGrid');
    if (!grid) return;

    bookings.forEach(booking => {
        const bookingDate = parseDateLocal(booking.date);
        if (!bookingDate) return;

        if (bookingDate.getMonth() === currentMonth && bookingDate.getFullYear() === currentYear) {
            const day = bookingDate.getDate();
            const row = day + 1;
            
            const block = document.createElement('div');
            block.className = `booking-block ${booking.priority}`;
            
            if (booking.isAllDay) {
                block.classList.add('all-day');
                block.style.gridRow = row.toString();
                block.style.gridColumn = '2';
            } else {
                const startIndex = getTimeSlotIndex(booking.startTime, false);
                let endIndex = getTimeSlotIndex(booking.endTime, true);
                
                // Ensure at least one slot
                if (endIndex <= startIndex) endIndex = startIndex + 1;
                
                const startCol = startIndex + 3;
                const endCol = endIndex + 3;
                
                block.style.gridRow = row.toString();
                block.style.gridColumn = `${startCol} / ${endCol}`;
            }
            
            block.innerHTML = `
                <div class="booking-client">${booking.taskName || booking.clientName}</div>
                <div class="booking-service">${booking.projectName || 'No Project'}</div>
            `;

            if (!booking.isVirtual) {
                block.addEventListener('click', (e) => {
                    e.stopPropagation();
                    showBookingDetails(booking);
                });
            } else {
                block.style.cursor = 'default';
                block.style.opacity = '0.8';
            }

            grid.appendChild(block);
        }
    });
}

// Apri modal nuova prenotazione
function openNewBookingModal(day, slot) {
    const modal = document.getElementById('bookingModal');
    const form = document.getElementById('bookingForm');

    document.getElementById('modalTitle').textContent = 'Nuova Task - ' + day + '/' + (currentMonth + 1);
    document.getElementById('deleteBtn').style.display = 'none';

    form.reset();
    document.getElementById('bookingId').value = '';
    
    // Identity auto-fill
    const username = localStorage.getItem('sync_username') || 'Anonymous';
    document.getElementById('creator').value = username;

    const date = new Date(currentYear, currentMonth, day);
    document.getElementById('bookingDate').value = formatDateLocalForInput(date);
    
    const allDayCheckbox = document.getElementById('allDayCheckbox');
    const timeFields = document.getElementById('timeFields');

    if (slot === 'all-day') {
        allDayCheckbox.checked = true;
        timeFields.style.display = 'none';
        document.getElementById('startTime').required = false;
        document.getElementById('endTime').required = false;
        document.getElementById('bookingTime').value = '';
    } else {
        allDayCheckbox.checked = false;
        timeFields.style.display = 'grid';
        document.getElementById('startTime').required = true;
        document.getElementById('endTime').required = true;
        
        document.getElementById('bookingTime').value = slot;
        document.getElementById('startTime').value = slot;

        // Calcola ora fine (aggiungi intervallo)
        const [hours, minutes] = slot.split(':').map(Number);
        const totalMinutes = hours * 60 + minutes + config.intervalMinutes;
        const endHours = Math.floor(totalMinutes / 60);
        const finalMinutes = totalMinutes % 60;
        document.getElementById('endTime').value =
            `${String(endHours).padStart(2, '0')}:${String(finalMinutes).padStart(2, '0')}`;
    }

    modal.classList.add('show');
}

// Mostra dettagli prenotazione
function showBookingDetails(booking) {
    const modal = document.getElementById('detailsModal');
    const details = document.getElementById('bookingDetails');

    const date = parseDateLocal(booking.date);
    const formattedDate = formatDateItaly(date);

    details.innerHTML = `
        <div class="detail-row">
            <span class="detail-label">Task:</span>
            <span class="detail-value">${booking.taskName || booking.clientName}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Project:</span>
            <span class="detail-value">${booking.projectName || '-'}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Data:</span>
            <span class="detail-value">${formattedDate}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Orario:</span>
            <span class="detail-value">${booking.isAllDay ? 'All Day' : (booking.startTime + ' - ' + booking.endTime)}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Descrizione:</span>
            <span class="detail-value">${booking.description || booking.serviceType}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Priorità:</span>
            <span class="detail-value" style="color: ${booking.priority === 'urgente' ? 'var(--violet)' : 'var(--cyan)'}">${booking.priority.toUpperCase()}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Creato da:</span>
            <span class="detail-value">${booking.creator || 'N/A'}</span>
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
    
    document.getElementById('modalTitle').textContent = 'Modifica Task';
    document.getElementById('deleteBtn').style.display = 'block';

    document.getElementById('bookingId').value = booking.id;
    document.getElementById('bookingDate').value = booking.date;
    document.getElementById('taskName').value = booking.taskName || booking.clientName;
    document.getElementById('projectSelect').value = booking.projectId || '';
    document.getElementById('description').value = booking.description || booking.serviceType;
    
    const isAllDay = !!booking.isAllDay;
    document.getElementById('allDayCheckbox').checked = isAllDay;
    document.getElementById('timeFields').style.display = isAllDay ? 'none' : 'grid';
    
    document.getElementById('startTime').required = !isAllDay;
    document.getElementById('endTime').required = !isAllDay;
    
    document.getElementById('startTime').value = booking.startTime || '';
    document.getElementById('endTime').value = booking.endTime || '';
    document.getElementById('priority').value = booking.priority;
    document.getElementById('notes').value = booking.notes || '';
    document.getElementById('creator').value = booking.creator || '';

    modal.classList.add('show');
}

function editFromDetails() {
    // Logic is handled via onclick assignment in showBookingDetails
}

// Salva prenotazione (crea o modifica)
async function saveBooking(e) {
    e.preventDefault();

    const bookingId = document.getElementById('bookingId').value;
    const projectSelect = document.getElementById('projectSelect');
    const projectName = projectSelect.options[projectSelect.selectedIndex].text;
    
    const bookingData = {
        id: bookingId || Date.now().toString(),
        date: document.getElementById('bookingDate').value,
        taskName: document.getElementById('taskName').value,
        projectId: projectSelect.value,
        projectName: projectName,
        description: document.getElementById('description').value,
        isAllDay: document.getElementById('allDayCheckbox').checked,
        startTime: document.getElementById('startTime').value,
        endTime: document.getElementById('endTime').value,
        priority: document.getElementById('priority').value,
        notes: document.getElementById('notes').value,
        creator: document.getElementById('creator').value
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
    if (!confirm('Sei sicuro di voler eliminare questa task?')) {
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
