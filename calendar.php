<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Task Calendar</title>
  <link rel="stylesheet" href="calendar.css">
</head>
<body>
  <div class="calendar">
    <div class="header">
      <button onclick="changePeriod(-1)" aria-label="Previous Period">←</button>
      <div id="monthYear" class="month-year" role="heading" aria-live="polite"></div>
      <button onclick="changePeriod(1)" aria-label="Next Period">→</button>
    </div>

        <div class="view-switcher">
      <button onclick="setView('month')">Month</button>
      <button onclick="setView('week')">Week</button>
      <button onclick="setView('day')">Day</button>
      <button onclick="setView('agenda')">Agenda</button>
    </div>

    
    <div class="days" role="row" id="weekDaysHeader">
      <div role="columnheader">Sun</div>
      <div role="columnheader">Mon</div>
      <div role="columnheader">Tue</div>
      <div role="columnheader">Wed</div>
      <div role="columnheader">Thu</div>
      <div role="columnheader">Fri</div>
      <div role="columnheader">Sat</div>
    </div>
    
    <div id="calendarDates" class="dates" role="grid"></div>
  </div>

  <a href="homepage.php"><button type="button">Back</button></a> 

  
  <div id="taskDetailsPopover" class="popover" role="dialog" aria-labelledby="popoverTitle">
    <div class="popover-content">
      <span class="close-popover" onclick="closePopover()">&times;</span>
      <h4 id="popoverTitle"></h4>
      <p><strong>Subject:</strong> <span id="popoverSubject"></span></p>
      <p><strong>Start Date:</strong> <span id="popoverStartDate"></span></p>
      <p><strong>Deadline:</strong> <span id="popoverDeadline"></span></p>
      <p><strong>Priority:</strong> <span id="popoverPriority"></span></p>
      <p><strong>Status:</strong> <span id="popoverStatus"></span></p>
      <button onclick="openEditModalFromPopover()">Edit Task</button>
    </div>
  </div>

  <div id="eventModal" class="modal" role="dialog" aria-labelledby="modalTitle">
    <div class="modal-content">
      <h3 id="modalTitle">Add Task</h3>
      <form id="eventForm" onsubmit="return saveEvent()">
        
        <input type="hidden" id="eventId" />
        <label for="eventTitle">Task Title:</label>
        <input type="text" id="eventTitle" placeholder="Task Title" required/>

        <label for="subject">Subject:</label>
        <input type="text" id="subject" placeholder="Subject" required />

        <label for="startDate">Start Date:</label>
        <input type="date" id="startDate" required />

        <label for="deadline">Deadline:</label>
        <input type="date" id="deadline" required />

        <label for="priority">Priority:</label>
        <select id="priority" required>
          <option value="important">Important</option>
          <option value="not-important">Not Important</option>
        </select>

        <label for="status">Status:</label>
        <select id="status" required>
          <option value="Not Started">Not Started</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
        </select>

        <button type="submit">Save</button>
        <button type="button" onclick="deleteEvent()" id="deleteBtn" style="display:none; color: red;">Delete</button>
        <button type="button" onclick="closeModal()">Cancel</button>
      </form>
    </div>
  </div>

 <script src="calendar.js"></script>
</body>
</html>
