// DOM element references
const monthYearEl = document.getElementById('monthYear');
const datesEl = document.getElementById('calendarDates');
const modal = document.getElementById('eventModal');
const weekDaysHeader = document.getElementById('weekDaysHeader'); // For day headers in Month/Week view

// Input elements for the Add/Edit Task Modal
const eventIdInput = document.getElementById('eventId');
const eventTitleInput = document.getElementById('eventTitle');
const subjectInput = document.getElementById('subject');
const startDateInput = document.getElementById('startDate');
const deadlineInput = document.getElementById('deadline');
const priorityInput = document.getElementById('priority');
const statusInput = document.getElementById('status');

// Buttons and titles for the Add/Edit Task Modal
const deleteBtn = document.getElementById('deleteBtn');
const modalTitle = document.getElementById('modalTitle');

// DOM elements for the Task Details Popover
const taskDetailsPopover = document.getElementById('taskDetailsPopover');
const popoverTitle = document.getElementById('popoverTitle');
const popoverSubject = document.getElementById('popoverSubject');
const popoverStartDate = document.getElementById('popoverStartDate');
const popoverDeadline = document.getElementById('popoverDeadline');
const popoverPriority = document.getElementById('popoverPriority');
const popoverStatus = document.getElementById('popoverStatus');

// Global state variables
let currentDate = new Date(); // Represents the date currently being displayed (e.g., first day of month/week/day)
let events = []; // Array to store all fetched tasks
let currentView = 'month'; // Tracks the active calendar view ('month', 'week', 'day', 'agenda')
let currentEditingTaskId = null; // Stores the ID of the task when the popover is open, for passing to edit modal

/**
 * Fetches tasks from the backend (api.php) and updates the global 'events' array.
 * After fetching, it re-renders the current calendar view.
 */
async function fetchTasks() {
  try {
    const response = await fetch("api.php"); // Fetch from the new API endpoint
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }
    const result = await response.json(); // Expect JSON response

    if (result.success && Array.isArray(result.data)) {
        events = result.data; // Update the global events array with fetched data
        console.log('Fetched events:', events); // Debugging: Log fetched events
        renderView(); // Render the current view with the newly fetched events
    } else if (!result.success) {
        console.error("API error fetching tasks:", result.error);
        alert(`Failed to load tasks: ${result.error}`);
    } else {
        console.error("Unexpected data format from API:", result);
        alert("Unexpected data from server when loading tasks.");
    }
  } catch (error) {
    console.error("Error fetching tasks:", error);
    alert("Failed to load tasks. Please try again later.");
  }
}

/**
 * Sets the current calendar view and triggers a re-render.
 * @param {string} view - The view type ('month', 'week', 'day', 'agenda').
 */
function setView(view) {
    currentView = view;
    renderView(); // Render the new view
}

/**
 * Central function to render the appropriate calendar view based on 'currentView'.
 * Manages visibility of day headers and navigation buttons.
 */
function renderView() {
    datesEl.innerHTML = ''; // Clear previous view content

    // Adjust visibility of the weekDaysHeader (Sun-Sat) based on view
    weekDaysHeader.style.display = (currentView === 'month' || currentView === 'week') ? 'grid' : 'none';

    // Get navigation buttons in the header
    const prevBtn = document.querySelector('.header button:first-of-type');
    const nextBtn = document.querySelector('.header button:last-of-type');

    // Hide navigation buttons for Agenda view, show for others
    if (currentView === 'agenda') {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'inline-block';
        nextBtn.style.display = 'inline-block';
    }

    // Call the specific render function for the current view
    switch (currentView) {
        case 'month':
            renderMonthView(currentDate);
            break;
        case 'week':
            renderWeekView(currentDate);
            break;
        case 'day':
            renderDayView(currentDate);
            break;
        case 'agenda':
            renderAgendaView(currentDate);
            break;
        default:
            renderMonthView(currentDate); // Fallback to month view
    }
}

/**
 * Renders the traditional month view of the calendar.
 * @param {Date} date - The date object representing the month to render.
 */
function renderMonthView(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const today = new Date(); // Current date for highlighting 'today'

    // Update the header with current month and year
    monthYearEl.textContent = date.toLocaleString("default", { month: "long", year: "numeric" });

    const firstDay = new Date(year, month, 1).getDay(); // Day of the week for the 1st of the month (0-6)
    const daysInMonth = new Date(year, month + 1, 0).getDate(); // Number of days in the current month

    let cells = ""; // HTML string to build the calendar cells

    // Add empty cells for days before the 1st of the month
    for (let i = 0; i < firstDay; i++) {
        cells += `<div class="date-cell"></div>`;
    }

    // Populate cells for each day of the month
    for (let day = 1; day <= daysInMonth; day++) {
        // Format date to YYYY-MM-DD for consistency with backend and input fields
        const fullDate = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
        // Filter events that start on the current day
        const dayEvents = events.filter(e => e.start_date === fullDate);

        // Check if the current day is 'today'
        const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();

        let eventHTML = "";
        // Generate HTML for each event on this day
        for (let i = 0; i < dayEvents.length; i++) {
            const event = dayEvents[i];
            // Determine CSS class based on priority, converting to lowercase and replacing spaces/hyphens
            const priorityClass = event.priority ? event.priority.toLowerCase().replace(/\s/g, '-') : '';

            // Event block with onclick to open details popover
            eventHTML += `
                <div class="event ${priorityClass}" onclick="event.stopPropagation(); openTaskDetails(${Number(event.id)})">
                  <strong>${event.title}</strong>
                </div>`;
        }

        // Add the date cell to the calendar grid
        // Clicking a date cell opens the add event modal for that date
        cells += `
            <div class="date-cell" onclick="openAddEvent('${fullDate}')">
              ${isToday ? `<div class="today">${day}</div>` : day}
              ${eventHTML}
            </div>
        `;
    }

    datesEl.innerHTML = cells; // Inject generated cells into the calendar dates container
}

/**
 * Renders the week view of the calendar.
 * Shows a grid with days of the week and hourly slots.
 * Note: Current task data does not include time, so tasks are placed conceptually.
 * @param {Date} date - The date object representing a day within the week to render.
 */
function renderWeekView(date) {
    const startOfWeek = new Date(date);
    startOfWeek.setDate(date.getDate() - date.getDay()); // Adjust to the Sunday of the current week

    let weekCells = '<div class="week-grid">';

    // Update header to show the range of the current week
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);
    monthYearEl.textContent = `${startOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;

    // Time slots / Day headers row
    weekCells += '<div class="week-day-headers-with-time">';
    weekCells += '<div class="time-column-header"></div>'; // Empty top-left corner for time column
    for (let i = 0; i < 7; i++) {
        const day = new Date(startOfWeek);
        day.setDate(startOfWeek.getDate() + i);
        const isToday = day.toDateString() === new Date().toDateString(); // Check if this day is today
        weekCells += `<div class="week-day-header ${isToday ? 'today-header' : ''}">
                        ${day.toLocaleDateString('default', { weekday: 'short' })}<br>
                        ${day.getDate()}
                      </div>`;
    }
    weekCells += '</div>'; // End week-day-headers-with-time

    // Generate rows for each hour of the day
    const hoursInDay = 24; // Full 24 hours for granularity
    for (let hour = 0; hour < hoursInDay; hour++) {
        weekCells += `<div class="week-hour-row">`;
        weekCells += `<div class="time-label">${String(hour).padStart(2, '0')}:00</div>`; // Hourly label

        // Cells for each day within the current hour row
        for (let i = 0; i < 7; i++) {
            const currentDay = new Date(startOfWeek);
            currentDay.setDate(startOfWeek.getDate() + i);
            const fullDate = `${currentDay.getFullYear()}-${String(currentDay.getMonth() + 1).padStart(2, "0")}-${String(currentDay.getDate()).padStart(2, "0")}`;
            
            // Filter events for this specific day. (Time filtering would need 'time' columns in DB).
            const dailyEventsForHour = events.filter(e => {
                const eventDate = new Date(e.start_date);
                // Basic check for matching day. If tasks had start_time, add that here.
                return eventDate.getFullYear() === currentDay.getFullYear() &&
                       eventDate.getMonth() === currentDay.getMonth() &&
                       eventDate.getDate() === currentDay.getDate();
            });

            let eventBlocks = '';
            // For simplicity, we'll display tasks starting on this day within a specific hour slot (e.g., 8 AM).
            // A more advanced implementation would render events across time slots based on their duration.
            if (hour === 8) { // Only display tasks in the 8 AM slot as a placeholder
                dailyEventsForHour.forEach(event => {
                    const priorityClass = event.priority ? event.priority.toLowerCase().replace(/\s/g, '-') : '';
                    eventBlocks += `
                        <div class="event-week ${priorityClass}" onclick="event.stopPropagation(); openTaskDetails(${Number(event.id)})">
                            ${event.title}
                        </div>`;
                });
            }
            
            // Clicking a week-day-cell opens the add event modal for that specific date
            weekCells += `<div class="week-day-cell" onclick="openAddEvent('${fullDate}')">${eventBlocks}</div>`;
        }
        weekCells += `</div>`; // End week-hour-row
    }
    weekCells += '</div>'; // End week-grid
    datesEl.innerHTML = weekCells; // Inject generated HTML into the dates container
}

/**
 * Renders the day view of the calendar.
 * Shows a detailed hourly breakdown for a single day.
 * @param {Date} date - The date object representing the day to render.
 */
function renderDayView(date) {
    const fullDate = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const dayEvents = events.filter(e => e.start_date === fullDate); // Get all events for this specific day

    // Update header to show the full date of the current day
    monthYearEl.textContent = date.toLocaleDateString('default', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    let dayCells = '<div class="day-view-grid">';

    // Generate rows for each hour of the day
    for (let hour = 0; hour < 24; hour++) {
        dayCells += `<div class="day-hour-row">`;
        dayCells += `<div class="day-hour-label">${String(hour).padStart(2, '0')}:00</div>`;

        let eventBlocks = '';
        // As with week view, this would need 'time' columns for proper placement
        // For simplicity, all tasks for the day are displayed in a conceptual hourly slot (e.g., 8 AM).
        // A proper implementation would require 'time' columns in the database for tasks.
        if (hour === 8) { // Placeholder: display all daily tasks here
            dayEvents.forEach(event => {
                const priorityClass = event.priority ? event.priority.toLowerCase().replace(/\s/g, '-') : '';
                eventBlocks += `
                    <div class="event-day ${priorityClass}" onclick="event.stopPropagation(); openTaskDetails(${Number(event.id)})">
                        ${event.title} (Due: ${event.deadline})
                    </div>`;
            });
        }
        // Clicking an hourly slot opens the add event modal for that day
        dayCells += `<div class="day-events-container" onclick="openAddEvent('${fullDate}')">${eventBlocks}</div>`;
        dayCells += `</div>`; // End day-hour-row
    }
    dayCells += '</div>';
    datesEl.innerHTML = dayCells; // Inject generated HTML
}

/**
 * Renders the agenda view, displaying a list of upcoming tasks.
 * @param {Date} date - The current date, used to filter for tasks from this date onwards.
 */
function renderAgendaView(date) {
    // Define a period for upcoming tasks (e.g., next 6 months)
    const sixMonthsFromNow = new Date(date);
    sixMonthsFromNow.setMonth(date.getMonth() + 6);

    // Filter events starting from the current date up to six months in the future
    // and sort them by start date
    const futureEvents = events.filter(e => {
        const eventStartDate = new Date(e.start_date);
        return eventStartDate >= date && eventStartDate <= sixMonthsFromNow;
    }).sort((a, b) => new Date(a.start_date) - new Date(b.start_date));

    monthYearEl.textContent = 'Upcoming Tasks'; // Update header for Agenda view

    let agendaList = '<div class="agenda-list">';
    if (futureEvents.length === 0) {
        agendaList += '<p>No upcoming tasks found in the next 6 months.</p>';
    } else {
        let currentHeaderDate = ''; // To group tasks by day
        futureEvents.forEach(event => {
            const eventDate = new Date(event.start_date);
            // Format date for the header
            const formattedDate = eventDate.toLocaleDateString('default', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

            // Add a date header if the day changes
            if (formattedDate !== currentHeaderDate) {
                agendaList += `<h4>${formattedDate}</h4>`;
                currentHeaderDate = formattedDate;
            }
            const priorityClass = event.priority ? event.priority.toLowerCase().replace(/\s/g, '-') : '';
            agendaList += `
                <div class="agenda-item ${priorityClass}" onclick="openTaskDetails(${Number(event.id)})">
                    <strong>${event.title}</strong> (${event.subject}) - Due: ${event.deadline}<br/>
                    <small>Priority: ${event.priority}, Status: ${event.status}</small>
                </div>`;
        });
    }
    agendaList += '</div>';
    datesEl.innerHTML = agendaList; // Inject generated HTML
}

/**
 * Changes the current period (month, week, or day) based on the current view.
 * @param {number} offset - The amount to change the period by (e.g., -1 for previous, 1 for next).
 */
function changePeriod(offset) {
    if (currentView === 'month') {
        currentDate.setMonth(currentDate.getMonth() + offset);
    } else if (currentView === 'week') {
        currentDate.setDate(currentDate.getDate() + (offset * 7)); // Change by 7 days for a week
    } else if (currentView === 'day') {
        currentDate.setDate(currentDate.getDate() + offset); // Change by 1 day
    }
    renderView(); // Re-render the current view
}

/**
 * Opens the modal for adding a new task.
 * Pre-fills the start date with the clicked date from the calendar.
 * @param {string} date - The date (YYYY-MM-DD) to pre-fill in the start date input.
 */
function openAddEvent(date) {
  eventIdInput.value = ''; // Clear ID as this is for a new event
  eventTitleInput.value = '';
  subjectInput.value = '';
  startDateInput.value = date; // Pre-fill with clicked date
  deadlineInput.value = date; // Also pre-fill deadline with start date for convenience
  priorityInput.value = 'not-important'; // Set default priority
  statusInput.value = 'Not Started'; // Set default status
  deleteBtn.style.display = 'none'; // Hide delete button for new events
  modalTitle.textContent = 'Add Task'; // Set modal title
  modal.style.display = 'flex'; // Show the modal
}

/**
 * Opens the modal for editing an existing task.
 * Populates modal inputs with existing task data. Called from the popover's "Edit" button.
 * @param {number} taskId - The ID of the task to edit.
 */
function editEvent(taskId) {
  console.log('Attempting to edit taskId:', taskId, 'Type:', typeof taskId); // Debugging
  // Find the task in the global events array by ID
  const event = events.find(e => Number(e.id) === taskId);

  if (!event) {
    console.error('Event not found for ID:', taskId, 'Current events array:', events); // Debugging
    return; // Exit if task not found
  }

  console.log('Found event for editing:', event); // Debugging

  // Populate modal inputs with task data
  eventIdInput.value = event.id;
  eventTitleInput.value = event.title;
  subjectInput.value = event.subject;
  startDateInput.value = event.start_date;
  deadlineInput.value = event.deadline;
  priorityInput.value = event.priority;
  statusInput.value = event.status;
  deleteBtn.style.display = 'inline-block'; // Show delete button for existing tasks
  modalTitle.textContent = 'Edit Task'; // Set modal title
  modal.style.display = 'flex'; // Show the modal
}

/**
 * Handles saving (adding or updating) a task.
 * Sends data to api.php via POST request.
 * Prevents default form submission.
 */
async function saveEvent() {
  const taskId = eventIdInput.value; // Get ID (if editing)
  const task = { // Create task object from form inputs
    title: eventTitleInput.value.trim(),
    subject: subjectInput.value.trim(),
    start_date: startDateInput.value,
    deadline: deadlineInput.value,
    priority: priorityInput.value,
    status: statusInput.value,
  };

  // Basic validation for required fields
  if (!task.title || !task.subject || !task.start_date || !task.deadline) {
    alert("Please fill in all required fields (Title, Subject, Start Date, Deadline).");
    return false;
  }

  if (taskId) { // If taskId exists, it's an update operation
    task.id = taskId;
  }

  try {
    const response = await fetch('api.php', { // Send to the new API endpoint
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(task),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }

    const result = await response.json(); // Parse the JSON response from PHP

    if (result.success) {
      alert(result.message); // Show success message from API
      closeModal(); // Close the modal
      fetchTasks(); // Re-fetch and re-render the calendar to show changes
    } else {
      alert('Error saving task: ' + result.error); // Show error message from API
    }
  } catch (error) {
    console.error('Fetch error:', error); // Log fetch errors
    alert('Failed to save task. Check server connection.'); // Generic user-facing error
  }
  return false; // Prevent default form submission and page reload
}

/**
 * Handles deleting a task.
 * Sends a DELETE request to api.php.
 */
async function deleteEvent() {
  const taskId = eventIdInput.value; // Get ID of task to delete
  // Confirm deletion with the user
  if (!taskId || !confirm("Are you sure you want to delete this task?")) {
    return; // Abort if no ID or user cancels
  }

  try {
    const response = await fetch('api.php', { // Send to the new API endpoint
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: taskId }), // Send task ID for deletion
    });

    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }

    const result = await response.json(); // Parse response

    if (result.success) {
      alert(result.message);
      closeModal();
      fetchTasks(); // Re-fetch and re-render calendar
    } else {
      alert('Error deleting task: ' + result.error);
    }
  } catch (error) {
    console.error('Fetch error:', error);
    alert('Failed to delete task. Check server connection.');
  }
}

/**
 * Closes the Add/Edit Task modal.
 */
function closeModal() {
  modal.style.display = 'none';
}


// Popover Functions

/**
 * Opens the task details popover and populates it with task data.
 * @param {number} taskId - The ID of the task whose details are to be displayed.
 */
function openTaskDetails(taskId) {
    // Find the task in the global events array
    const event = events.find(e => Number(e.id) === taskId);
    if (!event) {
        console.error('Task not found for details:', taskId);
        return;
    }

    currentEditingTaskId = taskId; // Store the ID for when "Edit" is clicked

    // Populate the popover elements with task data
    popoverTitle.textContent = event.title;
    popoverSubject.textContent = event.subject;
    popoverStartDate.textContent = event.start_date;
    popoverDeadline.textContent = event.deadline;
    popoverPriority.textContent = event.priority;
    popoverStatus.textContent = event.status;

    taskDetailsPopover.style.display = 'flex'; // Show the popover (using flex for centering)
}

/**
 * Closes the task details popover.
 */
function closePopover() {
    taskDetailsPopover.style.display = 'none';
    currentEditingTaskId = null; // Clear the stored ID
}

/**
 * Called when the "Edit Task" button inside the popover is clicked.
 * Closes the popover and then opens the main edit modal for the task.
 */
function openEditModalFromPopover() {
    closePopover(); // Close the popover first
    if (currentEditingTaskId) {
        editEvent(currentEditingTaskId); // Call the existing editEvent function to open the main modal
    }
}


// Attach global functions to the window object so they can be called from HTML inline onclick attributes
window.setView = setView;
window.changePeriod = changePeriod;
window.openAddEvent = openAddEvent;
window.editEvent = editEvent; // Still needed for internal calls from popover
window.saveEvent = saveEvent;
window.deleteEvent = deleteEvent;
window.closeModal = closeModal;
window.openTaskDetails = openTaskDetails;
window.closePopover = closePopover;
window.openEditModalFromPopover = openEditModalFromPopover;


// Initial fetch of tasks and rendering of the default view when the DOM is fully loaded.
document.addEventListener("DOMContentLoaded", () => {
  fetchTasks();
});
