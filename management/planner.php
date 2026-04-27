<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NextGen Agency Planner</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/planner.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="noise"></div>
    
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <span class="logo-mark">⚡</span>
                <div class="logo-text">NextGen <em>Agency Planner</em></div>
            </div>
            <div class="header-controls">
                <a href="dashboard.html" class="nav-btn" style="text-decoration: none;">Back to Dashboard</a>
                <button id="prevMonth" class="nav-btn">◀</button>
                <div class="month-selector">
                    <span id="currentMonth"></span>
                </div>
                <button id="nextMonth" class="nav-btn">▶</button>
                <button id="todayBtn" class="today-btn">Today</button>
            </div>
        </header>

        <!-- View Controls -->
        <div class="view-controls">
            <div class="control-group">
                <label>Start Hour</label>
                <input type="time" id="startHour" value="08:00">
            </div>
            <div class="control-group">
                <label>End Hour</label>
                <input type="time" id="endHour" value="20:00">
            </div>
            <div class="control-group">
                <label>Interval (min)</label>
                <select id="intervalMinutes">
                    <option value="15">15</option>
                    <option value="30" selected>30</option>
                    <option value="60">60</option>
                </select>
            </div>
            <button id="applyTimeSettings" class="apply-btn">Apply</button>
        </div>

        <!-- Planner Grid -->
        <div class="planner-wrapper">
            <div id="plannerGrid" class="planner-grid">
                <!-- Content generated dynamically via JS -->
            </div>
        </div>
    </div>

    <!-- New/Edit Task Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">New Task</h2>
            <form id="bookingForm">
                <input type="hidden" id="bookingId">
                <input type="hidden" id="bookingDate">
                <input type="hidden" id="bookingTime">
                <input type="hidden" id="creator">
                
                <div class="form-group">
                    <label>Task Name *</label>
                    <input type="text" id="taskName" required placeholder="Project task name...">
                </div>

                <div class="form-group">
                    <label>Project *</label>
                    <select id="projectSelect" required>
                        <option value="">Select Project...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Description *</label>
                    <textarea id="description" rows="2" required placeholder="Task details..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group checkbox-group">
                        <label for="allDayCheckbox">
                            <input type="checkbox" id="allDayCheckbox">
                            All Day Task
                        </label>
                    </div>
                </div>

                <div id="timeFields" class="form-row">
                    <div class="form-group">
                        <label>Start Time *</label>
                        <input type="time" id="startTime">
                    </div>
                    <div class="form-group">
                        <label>End Time *</label>
                        <input type="time" id="endTime">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Priority *</label>
                        <select id="priority" required>
                            <option value="normal">Normal</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="notes" rows="2"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" id="deleteBtn" class="delete-btn" style="display:none;">Delete</button>
                    <button type="submit" class="save-btn">Save Task</button>
                    <button type="button" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Task Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Task Details</h2>
            <div id="bookingDetails"></div>
            <div class="form-actions">
                <button id="editBookingBtn" class="save-btn">Edit</button>
                <button class="cancel-btn">Close</button>
            </div>
        </div>
    </div>

    <script src="js/identity.js?v=<?php echo time(); ?>"></script>
    <script src="js/planner.js?v=<?php echo time(); ?>"></script>
</body>
</html>
