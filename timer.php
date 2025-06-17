<?php
session_start(); // Start the session to access $_SESSION

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Study Timer Tracker</title>
  <style>
    /* Moved from inline style block */
    body {
      font-family: 'Segoe UI', sans-serif;
      max-width: 600px;
      margin: auto;
      padding: 30px 20px;
      background-color: #f2f4f8;
      color: #333;
    }

    h2, h3 {
      text-align: center;
      margin-bottom: 20px; /* Added spacing */
    }

    .form-group {
      margin-bottom: 15px;
    }

    label {
      font-weight: 600;
      display: block;
      margin-bottom: 5px;
    }

    input[type="text"], textarea {
      width: 100%;
      padding: 10px;
      font-size: 1rem;
      box-sizing: border-box;
      border: 1px solid #ccc;
      border-radius: 6px;
      background-color: white;
    }

    textarea {
      height: 100px;
      resize: vertical;
    }

    .timer {
      font-size: 48px;
      font-weight: bold;
      background-color: #fff;
      padding: 20px;
      text-align: center;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      margin: 30px 0;
    }

    .buttons {
      text-align: center;
      margin-top: 20px; /* Added spacing */
    }

    .buttons button {
      padding: 12px 24px;
      margin: 5px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      background-color: #007bff;
      color: white;
      cursor: pointer;
      transition: background-color 0.3s ease; /* Smooth transition */
      box-shadow: 0 2px 5px rgba(0,0,0,0.2); /* Added subtle shadow */
    }

    .buttons button:hover {
      background-color: #0056b3;
      box-shadow: 0 4px 8px rgba(0,0,0,0.3); /* Enhanced shadow on hover */
    }

    /* Stop button specific style */
    #stopBtn {
        background-color: #dc3545; /* Red for Stop */
    }
    #stopBtn:hover {
        background-color: #c82333;
    }

    .history {
      margin-top: 30px;
    }

    .session {
      border-left: 5px solid #007bff;
      padding: 15px 20px;
      margin-bottom: 15px;
      border-radius: 10px;
      background-color: white;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      position: relative; /* For delete button positioning */
    }

    .session-title {
      font-weight: bold;
      font-size: 1.1rem;
      margin-bottom: 5px;
      color: #0056b3; /* Make title stand out */
    }

    .session div {
        margin-bottom: 3px; /* Spacing for session details */
        color: #555;
    }

    .session-actions {
        margin-top: 10px;
        text-align: right;
    }

    .session-actions button {
        padding: 8px 12px;
        font-size: 0.9em;
        margin-left: 5px;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .session-delete-btn {
        background-color: #f8d7da; /* Light red */
        color: #dc3545; /* Red text */
        border: 1px solid #dc3545;
    }

    .session-delete-btn:hover {
        background-color: #dc3545;
        color: white;
    }

    .session-resume-btn {
        background-color: #d1ecf1; /* Light blue */
        color: #0c5460; /* Dark blue text */
        border: 1px solid #0c5460;
    }

    .session-resume-btn:hover {
        background-color: #0c5460;
        color: white;
    }

    .message-box {
        display: none; /* Hidden by default */
        position: fixed;
        left: 50%;
        top: 20px;
        transform: translateX(-50%);
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        z-index: 1000;
        font-weight: bold;
        color: white;
        text-align: center;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    .message-box.success {
        background-color: #28a745; /* Green */
    }

    .message-box.error {
        background-color: #dc3545; /* Red */
    }

    .message-box.show {
        opacity: 1;
    }
  </style>
</head>
<body>

<h2>Study Timer Tracker</h2>

<div class="form-group">
  <label for="task_name">Task Name:</label>
  <input type="text" id="task_name" placeholder="e.g., Read Chapter 4">
</div>

<div class="form-group">
  <label for="task_subject">Subject:</label>
  <input type="text" id="task_subject" placeholder="e.g., Biology">
</div>

<div class="form-group">
  <label for="notes">Notes:</label>
  <textarea id="notes" placeholder="Write notes or thoughts here..."></textarea>
</div>

<div class="timer" id="timerDisplay">00:00:00</div>

<div class="buttons">
  <button id="startPauseBtn" onclick="toggleTimer()">Start</button>
  <button id="stopBtn" onclick="stopTimer()" style="display: none;">Stop</button>
</div>

<h3>Session History</h3>
<div class="history" id="sessionHistory">
  <!-- Session history will be loaded dynamically by JavaScript -->
  <p>Loading session history...</p>
</div>

<div id="messageBox" class="message-box"></div>

<script>
  let startTime = null;
  let elapsedTime = 0; // Stores total elapsed time in milliseconds
  let timerInterval = null;
  let isRunning = false;
  let loadedSessions = []; // Store fetched sessions for easy access

  const taskNameInput = document.getElementById("task_name");
  const taskSubjectInput = document.getElementById("task_subject");
  const notesInput = document.getElementById("notes");
  const timerDisplay = document.getElementById("timerDisplay");
  const startPauseBtn = document.getElementById("startPauseBtn");
  const stopBtn = document.getElementById("stopBtn");
  const sessionHistoryDiv = document.getElementById("sessionHistory");
  const messageBox = document.getElementById("messageBox");

  /**
   * Displays a message box with a given message and type.
   * @param {string} message - The message to display.
   * @param {string} type - 'success' or 'error'.
   */
  function showMessage(message, type) {
    messageBox.textContent = message;
    messageBox.className = `message-box show ${type}`; // Add 'show' class to fade in
    messageBox.style.display = 'block'; // Make it visible

    setTimeout(() => {
      messageBox.classList.remove('show'); // Fade out
      setTimeout(() => {
        messageBox.style.display = 'none'; // Hide after transition
      }, 300); // Match CSS transition duration
    }, 3000); // Hide after 3 seconds
  }

  /**
   * Updates the timer display (HH:MM:SS format).
   */
  function updateDisplay() {
    const total = elapsedTime + (isRunning ? Date.now() - startTime : 0);
    const hours = Math.floor(total / 3600000);
    const minutes = Math.floor((total % 3600000) / 60000);
    const seconds = Math.floor((total % 60000) / 1000);
    timerDisplay.textContent =
      `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
  }

  /**
   * Toggles the timer between Start and Pause.
   */
  function toggleTimer() {
    if (!isRunning) {
      // Start timer
      if (!taskNameInput.value.trim() || !taskSubjectInput.value.trim()) {
        showMessage("Please enter both Task Name and Subject to start the timer.", "error");
        return;
      }
      startTime = Date.now();
      timerInterval = setInterval(updateDisplay, 1000);
      isRunning = true;
      startPauseBtn.textContent = "Pause";
      stopBtn.style.display = "inline-block";
    } else {
      // Pause timer
      elapsedTime += Date.now() - startTime;
      clearInterval(timerInterval);
      isRunning = false;
      startPauseBtn.textContent = "Start";
    }
  }

  /**
   * Pauses the timer without stopping the session.
   * (Internal helper for stopTimer)
   */
  function pauseTimer() {
    if (isRunning) {
      elapsedTime += Date.now() - startTime;
      clearInterval(timerInterval);
      isRunning = false;
    }
  }

  /**
   * Stops the current timer session, saves it, and resets the timer UI.
   */
  async function stopTimer() {
    pauseTimer(); // Pause the timer before saving
    updateDisplay(); // Ensure display is updated one last time

    const task_name = taskNameInput.value.trim();
    const task_subject = taskSubjectInput.value.trim();
    const notes = notesInput.value.trim();

    // Basic validation before saving
    if (!task_name || !task_subject || elapsedTime === 0) {
      showMessage("Please enter Task Name and Subject, and ensure the timer has run.", "error");
      return;
    }

    const totalMs = elapsedTime;
    const totalSeconds = Math.floor(totalMs / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    const duration = `${hours}h ${minutes}m ${seconds}s`;

    try {
      const response = await fetch('api.php', { // Relative path is usually fine if api.php is in the same dir or root
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'save_session', // Indicate action for api.php
            task_name,
            task_subject,
            duration,
            duration_ms: totalMs,
            notes,
          }),
        });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const result = await response.json(); // Parse JSON response from API

      if (result.success) {
        showMessage('Session saved successfully!', 'success');
        resetTimerUI(); // Reset UI elements
        loadSessionHistory(); // Dynamically load and update history
      } else {
        console.error('Server error:', result.error);
        showMessage('Failed to save session: ' + result.error, 'error');
      }
    } catch (error) {
      console.error('Fetch error:', error);
      showMessage('Failed to connect to the server or save session. Please check your network.', 'error');
    }
  }

  /**
   * Resets the timer UI elements and state variables to initial state.
   */
  function resetTimerUI() {
    elapsedTime = 0;
    startTime = null;
    isRunning = false;
    timerDisplay.textContent = "00:00:00";
    taskNameInput.value = "";
    taskSubjectInput.value = "";
    notesInput.value = "";
    startPauseBtn.textContent = "Start";
    stopBtn.style.display = "none";
    clearInterval(timerInterval); // Ensure interval is cleared
  }

  /**
   * Renders the session history in the dedicated div.
   * @param {Array<Object>} sessions - An array of session objects.
   */
  function renderSessionHistory(sessions) {
      sessionHistoryDiv.innerHTML = ''; // Clear existing history
      loadedSessions = sessions; // Store sessions for easy access by resume function

      if (sessions.length === 0) {
          sessionHistoryDiv.innerHTML = '<p>No session history found.</p>';
          return;
      }

      sessions.forEach(session => {
          const sessionDiv = document.createElement('div');
          sessionDiv.className = 'session';
          sessionDiv.innerHTML = `
              <div class="session-title">${htmlspecialchars(session.task_name)} (${htmlspecialchars(session.task_subject)})</div>
              <div><strong>Duration:</strong> ${htmlspecialchars(session.duration)}</div>
              <div><strong>Time:</strong> ${htmlspecialchars(session.timestamp)}</div>
              ${session.notes ? `<div><strong>Notes:</strong> ${nl2br(htmlspecialchars(session.notes))}</div>` : ''}
              <div class="session-actions">
                  <button class="session-resume-btn" onclick="resumeSession(${session.id})">Resume</button>
                  <button class="session-delete-btn" onclick="deleteSession(${session.id})">Delete</button>
              </div>
          `;
          sessionHistoryDiv.appendChild(sessionDiv);
      });
  }

  /**
   * Fetches session history from the API and calls renderSessionHistory.
   */
  async function loadSessionHistory() {
      sessionHistoryDiv.innerHTML = '<p>Loading session history...</p>'; // Show loading state
      try {
          const response = await fetch('api.php?action=fetch_sessions'); // Relative path is usually fine
          if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
          }
          const result = await response.json();

          if (result.success && Array.isArray(result.data)) {
              renderSessionHistory(result.data);
          } else if (!result.success) {
              console.error('Server error fetching history:', result.error);
              showMessage('Failed to load history: ' + result.error, 'error');
              sessionHistoryDiv.innerHTML = '<p style="color:red;">Failed to load history.</p>';
          } else {
              console.error('Unexpected data format fetching history:', result);
              showMessage('Unexpected data from server for history.', 'error');
              sessionHistoryDiv.innerHTML = '<p style="color:red;">Failed to load history.</p>';
          }
      } catch (error) {
          console.error('Fetch error loading history:', error);
          showMessage('Failed to connect to the server to load history.', 'error');
          sessionHistoryDiv.innerHTML = '<p style="color:red;">Failed to connect to server.</p>';
      }
  }

  /**
   * Deletes a session by its ID.
   * @param {number} sessionId - The ID of the session to delete.
   */
  async function deleteSession(sessionId) {
      if (!confirm('Are you sure you want to delete this session?')) {
          return;
      }
      try {
          const response = await fetch('api.php', { // Relative path is usually fine
              method: 'DELETE',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                  action: 'delete_session', // Indicate action for api.php
                  id: sessionId
              }),
          });
          if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
          }
          const result = await response.json();
          if (result.success) {
              showMessage('Session deleted successfully!', 'success');
              loadSessionHistory(); // Reload history after deletion
          } else {
              console.error('Server error deleting session:', result.error);
              showMessage('Failed to delete session: ' + result.error, 'error');
          }
      } catch (error) {
          console.error('Fetch error deleting session:', error);
          showMessage('Failed to connect to server to delete session.', 'error');
      }
  }

  /**
   * Resumes a session from history.
   * Pre-fills task details and sets the timer to the session's duration.
   * @param {number} sessionId - The ID of the session to resume.
   */
  function resumeSession(sessionId) {
      // Find the session in the loadedSessions array
      const sessionToResume = loadedSessions.find(s => Number(s.id) === sessionId);

      if (!sessionToResume) {
          showMessage('Error: Session not found for resuming.', 'error');
          console.error('Session not found for resuming:', sessionId);
          return;
      }

      // If timer is already running, pause it first
      if (isRunning) {
          toggleTimer(); // This will pause the current timer
      }

      // Pre-fill input fields
      taskNameInput.value = sessionToResume.task_name;
      taskSubjectInput.value = sessionToResume.task_subject;
      notesInput.value = sessionToResume.notes || ''; // Use empty string if notes are null/undefined

      // Set elapsed time for the timer
      elapsedTime = Number(sessionToResume.duration_ms);

      // Update display immediately with the loaded time
      updateDisplay();

      // Start the timer
      toggleTimer();

      showMessage(`Resumed session: ${sessionToResume.task_name}`, 'success');
  }


  // HTML sanitization functions (re-implemented in JS as they were in PHP)
  function htmlspecialchars(str) {
      if (typeof str !== 'string') return str;
      let div = document.createElement('div');
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
  }

  function nl2br(str) {
      if (typeof str !== 'string') return str;
      return str.replace(/\n/g, '<br />');
  }


  // Initial load of session history when the page loads
  document.addEventListener('DOMContentLoaded', loadSessionHistory);
</script>

</body>
</html>
