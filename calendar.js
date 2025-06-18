const monthYearEl = document.getElementById('monthYear');
const datesEl = document.getElementById('calendarDates');
const modal = document.getElementById('eventModal');
const weekDaysHeader = document.getElementById('weekDaysHeader');


const eventIdInput = document.getElementById('eventId');
const eventTitleInput = document.getElementById('eventTitle');
const subjectInput = document.getElementById('subject');
const startDateInput = document.getElementById('startDate');
const deadlineInput = document.getElementById('deadline');
const priorityInput = document.getElementById('priority');
const statusInput = document.getElementById('status');


const deleteBtn = document.getElementById('deleteBtn');
const modalTitle = document.getElementById('modalTitle');


const taskDetailsPopover = document.getElementById('taskDetailsPopover');
const popoverTitle = document.getElementById('popoverTitle');
const popoverSubject = document.getElementById('popoverSubject');
const popoverStartDate = document.getElementById('popoverStartDate');
const popoverDeadline = document.getElementById('popoverDeadline');
const popoverPriority = document.getElementById('popoverPriority');
const popoverStatus = document.getElementById('popoverStatus');

let currentDate = new Date(); 
let events = []; 
let currentView = 'month'; 
let currentEditingTaskId = null; 
async function fetchTasks() {
  try {
    const response = await fetch("api.php");
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }
    const result = await response.json();

    if (result.success && Array.isArray(result.data)) {
        events = result.data; 
        console.log('Fetched events:', events);
        renderView(); 
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
function setView(view) {
    currentView = view;
    renderView(); 
}
function renderView() {
    datesEl.innerHTML = ''; 

    
    weekDaysHeader.style.display = (currentView === 'month' || currentView === 'week') ? 'grid' : 'none';

    
    const prevBtn = document.querySelector('.header button:first-of-type');
    const nextBtn = document.querySelector('.header button:last-of-type');

    
    if (currentView === 'agenda') {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'inline-block';
        nextBtn.style.display = 'inline-block';
    }
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
            renderMonthView(currentDate); 
    }
}

function renderMonthView(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const today = new Date(); 

    monthYearEl.textContent = date.toLocaleString("default", { month: "long", year: "numeric" });

    const firstDay = new Date(year, month, 1).getDay(); 
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let cells = ""; 

    
    for (let i = 0; i < firstDay; i++) {
        cells += `<div class="date-cell"></div>`;
    }

    
    for (let day = 1; day <= daysInMonth; day++) {
      
       const fullDate = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
        
        const dayEvents = events.filter(e => e.start_date === fullDate);

        
        const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();

        let eventHTML = "";
        
        for (let i = 0; i < dayEvents.length; i++) {
            const event = dayEvents[i];
            
            const priorityClass = event.priority ? event.priority.toLowerCase().replace(/\s/g, '-') : '';

            
            eventHTML += `
                <div class="event ${priorityClass}" onclick="event.stopPropagation(); openTaskDetails(${Number(event.id)})">
                  <strong>${event.title}</strong>
                </div>`;
        }
        cells += `
            <div class="date-cell" onclick="openAddEvent('${fullDate}')">
              ${isToday ? `<div class="today">${day}</div>` : day}
              ${eventHTML}
            </div>
        `;
    }

    datesEl.innerHTML = cells;
}
function renderWeekView(date) {
    const startOfWeek = new Date(date);
    startOfWeek.setDate(date.getDate() - date.getDay()); 

    let weekCells = '<div class="week-grid">';

    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);
    monthYearEl.textContent = `${startOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;

     weekCells += '<div class="week-day-headers-with-time">';
    weekCells += '<div class="time-column-header"></div>'; 
    for (let i = 0; i < 7; i++) {
        const day = new Date(startOfWeek);
        day.setDate(startOfWeek.getDate() + i);
        const isToday = day.toDateString() === new Date().toDateString();
        weekCells += `<div class="week-day-header ${isToday ? 'today-header' : ''}">
                        ${day.toLocaleDateString('default', { weekday: 'short' })}<br>
                        ${day.getDate()}
                      </div>`;
    }
    weekCells += '</div>'; 
    const hoursInDay = 24;
    for (let hour = 0; hour < hoursInDay; hour++) {
        weekCells += `<div class="week-hour-row">`;
        weekCells += `<div class="time-label">${String(hour).padStart(2, '0')}:00</div>`; 
        for (let i = 0; i < 7; i++) {
            const currentDay = new Date(startOfWeek);
            currentDay.setDate(startOfWeek.getDate() + i);
            const fullDate = `${currentDay.getFullYear()}-${String(currentDay.getMonth() + 1).padStart(2, "0")}-${String(currentDay.getDate()).padStart(2, "0")}`;
            
            
            const dailyEventsForHour = events.filter(e => {
                const eventDate = new Date(e.start_date);
                
                return eventDate.getFullYear() === currentDay.getFullYear() &&
                       eventDate.getMonth() === currentDay.getMonth() &&
                       eventDate.getDate() === currentDay.getDate();
            });

            let eventBlocks = '';
                                    if (hour === 8) {
                dailyEventsForHour.forEach(event => {
                    const priorityClass = event.priority ? event.priority.toLowerCase().replace(/\s/g, '-') : '';
                    eventBlocks += `
                        <div class="event-week ${priorityClass}" onclick="event.stopPropagation(); openTaskDetails(${Number(event.id)})">
                            ${event.title}
                        </div>`;
                });
            }
            weekCells += `<div class="week-day-cell" onclick="openAddEvent('${fullDate}')">${eventBlocks}</div>`;
        }
        weekCells += `</div>`;
    }
    weekCells += '</div>'; 
    datesEl.innerHTML = weekCells;
}
function renderDayView(date) {
    const fullDate = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const dayEvents = events.filter(e => e.start_date === fullDate);

    monthYearEl.textContent = date.toLocaleDateString('default', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    let dayCells = '<div class="day-view-grid">';
    for (let hour = 0; hour < 24; hour++) {
        dayCells += `<div class="day-hour-row">`;
        dayCells += `<div class="day-hour-label">${String(hour).padStart(2, '0')}:00</div>`;

        let eventBlocks = '';
        if (hour === 8) {
            dayEvents.forEach(event => {
                const priorityClass = event.priority ? event.priority.toLowerCase().replace(/\s/g, '-') : '';
                eventBlocks += `
                    <div class="event-day ${priorityClass}" onclick="event.stopPropagation(); openTaskDetails(${Number(event.id)})">
                        ${event.title} (Due: ${event.deadline})
                    </div>`;
            });
        }
        dayCells += `<div class="day-events-container" onclick="openAddEvent('${fullDate}')">${eventBlocks}</div>`;
        dayCells += `</div>`; 
    }
    dayCells += '</div>';
    datesEl.innerHTML = dayCells;
}
function renderAgendaView(date) {
        const sixMonthsFromNow = new Date(date);
    sixMonthsFromNow.setMonth(date.getMonth() + 6);

    const futureEvents = events.filter(e => {
        const eventStartDate = new Date(e.start_date);
        return eventStartDate >= date && eventStartDate <= sixMonthsFromNow;
    }).sort((a, b) => new Date(a.start_date) - new Date(b.start_date));

    monthYearEl.textContent = 'Upcoming Tasks'; 

    let agendaList = '<div class="agenda-list">';
    if (futureEvents.length === 0) {
        agendaList += '<p>No upcoming tasks found in the next 6 months.</p>';
    } else {
        let currentHeaderDate = '';
        futureEvents.forEach(event => {
            const eventDate = new Date(event.start_date);
            
            const formattedDate = eventDate.toLocaleDateString('default', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

            
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
    datesEl.innerHTML = agendaList; 
}
function changePeriod(offset) {
    if (currentView === 'month') {
        currentDate.setMonth(currentDate.getMonth() + offset);
    } else if (currentView === 'week') {
        currentDate.setDate(currentDate.getDate() + (offset * 7));
    } else if (currentView === 'day') {
        currentDate.setDate(currentDate.getDate() + offset); 
    }
    renderView();
}
function openAddEvent(date) {
  eventIdInput.value = ''; 
  eventTitleInput.value = '';
  subjectInput.value = '';
  startDateInput.value = date; 
  deadlineInput.value = date; 
  priorityInput.value = 'not-important';
  statusInput.value = 'Not Started'; 
  deleteBtn.style.display = 'none'; 
  modalTitle.textContent = 'Add Task';
  modal.style.display = 'flex';
}
function editEvent(taskId) {
  console.log('Attempting to edit taskId:', taskId, 'Type:', typeof taskId);
  const event = events.find(e => Number(e.id) === taskId);

  if (!event) {
    console.error('Event not found for ID:', taskId, 'Current events array:', events);
    return;
  }

  console.log('Found event for editing:', event);
  eventIdInput.value = event.id;
  eventTitleInput.value = event.title;
  subjectInput.value = event.subject;
  startDateInput.value = event.start_date;
  deadlineInput.value = event.deadline;
  priorityInput.value = event.priority;
  statusInput.value = event.status;
  deleteBtn.style.display = 'inline-block';
  modalTitle.textContent = 'Edit Task'; 
  modal.style.display = 'flex'; 
}
async function saveEvent() {
  const taskId = eventIdInput.value;
  const task = {
    title: eventTitleInput.value.trim(),
    subject: subjectInput.value.trim(),
    start_date: startDateInput.value,
    deadline: deadlineInput.value,
    priority: priorityInput.value,
    status: statusInput.value,
  };

  if (!task.title || !task.subject || !task.start_date || !task.deadline) {
    alert("Please fill in all required fields (Title, Subject, Start Date, Deadline).");
    return false;
  }

  if (taskId) { 
    task.id = taskId;
  }

  try {
    const response = await fetch('api.php', { 
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(task),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }

    const result = await response.json(); 

    if (result.success) {
      alert(result.message); 
      closeModal(); 
      fetchTasks(); 
    } else {
      alert('Error saving task: ' + result.error); 
    }
  } catch (error) {
    console.error('Fetch error:', error);
    alert('Failed to save task. Check server connection.');
  }
  return false; 
}
async function deleteEvent() {
  const taskId = eventIdInput.value; 
  if (!taskId || !confirm("Are you sure you want to delete this task?")) {
    return; 
  }

  try {
    const response = await fetch('api.php', { 
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: taskId }),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }

    const result = await response.json(); 

    if (result.success) {
      alert(result.message);
      closeModal();
      fetchTasks();
    } else {
      alert('Error deleting task: ' + result.error);
    }
  } catch (error) {
    console.error('Fetch error:', error);
    alert('Failed to delete task. Check server connection.');
  }
}
function closeModal() {
  modal.style.display = 'none';
}
function openTaskDetails(taskId) {
    const event = events.find(e => Number(e.id) === taskId);
    if (!event) {
        console.error('Task not found for details:', taskId);
        return;
    }

    currentEditingTaskId = taskId;

    
    popoverTitle.textContent = event.title;
    popoverSubject.textContent = event.subject;
    popoverStartDate.textContent = event.start_date;
    popoverDeadline.textContent = event.deadline;
    popoverPriority.textContent = event.priority;
    popoverStatus.textContent = event.status;

    taskDetailsPopover.style.display = 'flex';
}
function closePopover() {
    taskDetailsPopover.style.display = 'none';
    currentEditingTaskId = null; 
}
function openEditModalFromPopover() {
    closePopover();
    if (currentEditingTaskId) {
        editEvent(currentEditingTaskId); 
    }
}



window.setView = setView;
window.changePeriod = changePeriod;
window.openAddEvent = openAddEvent;
window.editEvent = editEvent;
window.saveEvent = saveEvent;
window.deleteEvent = deleteEvent;
window.closeModal = closeModal;
window.openTaskDetails = openTaskDetails;
window.closePopover = closePopover;
window.openEditModalFromPopover = openEditModalFromPopover;



document.addEventListener("DOMContentLoaded", () => {
  fetchTasks();
});
