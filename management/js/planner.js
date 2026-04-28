
function parseDateLocal(dateString) {
    if (!dateString) return null;
    const [year, month, day] = dateString.split('-').map(Number);
    return new Date(year, month - 1, day);
}

function formatDateEnglish(dateObj) {
    return new Intl.DateTimeFormat('en-US', {
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

// Global Configuration
let currentDate = new Date();
let currentYear = currentDate.getFullYear();
let currentMonth = currentDate.getMonth();
let bookings = [];
let projects = [];
let timeSlots = [];

// Time Slot Configuration
let config = {
    startHour: 8,
    startMinute: 0,
    endHour: 20,
    endMinute: 0,
    intervalMinutes: 30
};

// Initialization
document.addEventListener('DOMContentLoaded', async function() {
    initializeEventListeners();
    await loadProjects();
    await loadBookings();
    updateCalendar();
});

// Event Listeners
function initializeEventListeners() {
    // Month Navigation
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

    // Apply Time Settings
    document.getElementById('applyTimeSettings').addEventListener('click', applyTimeSettings);

    // Modal
    document.querySelectorAll('.close').forEach(btn => {
        btn.addEventListener('click', closeModal);
    });

    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', closeModal);
    });

    // Booking Form
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

    // Close modal on outside click
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeModal();
        }
    });
}

// Load Projects
async function loadProjects() {
    try {
        const response = await fetch('projects.json', { cache: 'no-store' });
        projects = await response.json();
        populateProjectSelect();
    } catch (error) {
        console.error('Error loading projects:', error);
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

// Apply Time Configuration
function applyTimeSettings() {
    const startHourValue = document.getElementById('startHour').value;
    const endHourValue = document.getElementById('endHour').value;
    const intervalMinutes = parseInt(document.getElementById('intervalMinutes').value);

    const [startH, startM] = startHourValue.split(':').map(Number);
    const [endH, endM] = endHourValue.split(':').map(Number);

    config.startHour = startH;
    config.startMinute = startM;
    config.endHour = endH;
    config.endMinute = endM;
    config.intervalMinutes = intervalMinutes;

    updateCalendar();
}

// Generate Time Slots
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

// Update Calendar
function updateCalendar() {
    timeSlots = generateTimeSlots();

    // Update Month Header
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('currentMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;

    const grid = document.getElementById('plannerGrid');
    if (!grid) return;
    grid.innerHTML = '';

    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    
    // Configure grid columns: 80px for day cell, 100px for All Day, and then time slots
    grid.style.gridTemplateColumns = `70px 90px repeat(${timeSlots.length}, 85px)`;
    
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
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(currentYear, currentMonth, day);
        const dayOfWeek = date.getDay();
        const row = day + 1;

        // Day Cell
        const dayCell = document.createElement('div');
        dayCell.className = 'day-cell';
        if (date.toDateString() === today.toDateString()) dayCell.classList.add('today');
        if (dayOfWeek === 0 || dayOfWeek === 6) dayCell.classList.add('weekend');
        dayCell.style.gridRow = `${row} / ${row + 1}`;
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
        allDayCell.style.gridRow = `${row} / ${row + 1}`;
        allDayCell.style.gridColumn = '2';
        allDayCell.addEventListener('click', () => openNewBookingModal(day, 'all-day'));
        grid.appendChild(allDayCell);

        // Time Grid Cells
        timeSlots.forEach((slot, index) => {
            const cell = document.createElement('div');
            cell.className = 'grid-cell';
            cell.dataset.day = day;
            cell.dataset.slot = slot;
            cell.style.gridRow = `${row} / ${row + 1}`;
            cell.style.gridColumn = (index + 3).toString();
            cell.addEventListener('click', () => openNewBookingModal(day, slot));
            grid.appendChild(cell);
        });
    }

    renderBookings();
}

// Load Bookings
async function loadBookings() {
    try {
        const response = await fetch('load_bookings.php', {
            cache: 'no-store'
        });
        const data = await response.json();
        bookings = data.bookings || [];
        
        // Add virtual bookings from project milestones
        addProjectMilestones();
        
    } catch (error) {
        console.error('Error loading bookings:', error);
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
                priority: 'normal',
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
                priority: 'urgent',
                creator: 'System',
                isVirtual: true
            });
        }
    });
}

function getTimeSlotIndex(timeStr, roundUp = false) {
    const [h, m] = timeStr.split(':').map(Number);
    const totalMinutes = h * 60 + m;
    
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

// Render Bookings on the Grid
function renderBookings() {
    // Remove existing blocks
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
                block.style.gridRow = `${row} / ${row + 1}`;
                block.style.gridColumn = '2';
            } else {
                const startIndex = getTimeSlotIndex(booking.startTime, false);
                let endIndex = getTimeSlotIndex(booking.endTime, true);
                
                // Ensure at least one slot
                if (endIndex <= startIndex) endIndex = startIndex + 1;
                
                const startCol = startIndex + 3;
                const endCol = endIndex + 3;
                
                block.style.gridRow = `${row} / ${row + 1}`;
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

// Open New Booking Modal
function openNewBookingModal(day, slot) {
    const modal = document.getElementById('bookingModal');
    const form = document.getElementById('bookingForm');

    document.getElementById('modalTitle').textContent = 'New Task - ' + day + '/' + (currentMonth + 1);
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

        // Calculate end time (add interval)
        const [hours, minutes] = slot.split(':').map(Number);
        const totalMinutes = hours * 60 + minutes + config.intervalMinutes;
        const endHours = Math.floor(totalMinutes / 60);
        const finalMinutes = totalMinutes % 60;
        document.getElementById('endTime').value =
            `${String(endHours).padStart(2, '0')}:${String(finalMinutes).padStart(2, '0')}`;
    }

    modal.classList.add('show');
}

// Show Booking Details
function showBookingDetails(booking) {
    const modal = document.getElementById('detailsModal');
    const details = document.getElementById('bookingDetails');

    const date = parseDateLocal(booking.date);
    const formattedDate = formatDateEnglish(date);

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
            <span class="detail-label">Date:</span>
            <span class="detail-value">${formattedDate}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Time:</span>
            <span class="detail-value">${booking.isAllDay ? 'All Day' : (booking.startTime + ' - ' + booking.endTime)}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Description:</span>
            <span class="detail-value">${booking.description || booking.serviceType}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Priority:</span>
            <span class="detail-value" style="color: ${booking.priority === 'urgent' ? 'var(--violet)' : 'var(--cyan)'}">${booking.priority.toUpperCase()}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Created by:</span>
            <span class="detail-value">${booking.creator || 'N/A'}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Notes:</span>
            <span class="detail-value">${booking.notes || '-'}</span>
        </div>
    `;

    document.getElementById('editBookingBtn').onclick = () => {
        closeModal();
        editBooking(booking);
    };

    modal.classList.add('show');
}

// Edit Booking
function editBooking(booking) {
    const modal = document.getElementById('bookingModal');
    
    document.getElementById('modalTitle').textContent = 'Edit Task';
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

// Save Booking (create or modify)
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
            loadBookings().then(() => updateCalendar());
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving:', error);
        alert('Error during saving');
    }
}

// Delete Booking
async function deleteBooking() {
    if (!confirm('Are you sure you want to delete this task?')) {
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
            loadBookings().then(() => updateCalendar());
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting:', error);
        alert('Error during deletion');
    }
}

// Close Modal
function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('show');
    });
}
