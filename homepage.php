<?php
session_start(); // Start the session to access $_SESSION

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

// Optional: Retrieve user email to display on the page
$userEmail = $_SESSION['user_email'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Event Planner</title>
  <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>

  <header>
    <div>
      <h1>Task Planner</h1>
      <p id="currentDate"></p>
      <p style="font-size: 0.9em; color: #E3E8EA;">Welcome, <?php echo htmlspecialchars($userEmail); ?>!</p>
    </div>
    <div class="top-icons">
      <img src="https://img.icons8.com/ios-filled/50/E3E8EA/bell.png" alt="Notifications"/>
      <a href="calendar.php"><img src="https://img.icons8.com/ios-filled/50/E3E8EA/calendar.png" alt="Calendar"></a>
      <img src="https://img.icons8.com/ios-filled/50/E3E8EA/user.png" alt="User"/>
      <a href="logout.php" style="margin-left: 10px;"><button type="button">Logout</button></a>
    </div>
  </header>

  <div class="filters">
    <input type="text" placeholder="Search Event..." id="searchInput" oninput="loadTasks()">
    <select id="statusFilter" onchange="loadTasks()">
      <option value="">All Statuses</option>
      <option value="not-started">Not Started</option>
      <option value="in-progress">In Progress</option>
      <option value="completed">Completed</option>
    </select>
    <select id="priorityFilter"  onchange="loadTasks()">
      <option value="">All Priorities</option>
      <option value="important">Important</option>
      <option value="not-important">Not Important</option>
    </select>
    <select id="sortBy" onchange="loadTasks()">
        <option value="">Sort By</option>
        <option value="start_date_asc">Start Date ↑</option>
        <option value="start_date_desc">Start Date ↓</option>
        <option value="deadline_asc">Deadline ↑</option>
        <option value="deadline_desc">Deadline ↓</option>
    </select>
    <button onclick="clearFilters()">Clear Filters</button>
  </div>

  <div class="task-section" id="task-section"></div>

  <div class="bottom-bar">
    <a href="timer.php"><img src="https://img.icons8.com/ios-filled/50/E3E8EA/timer.png" alt="Timer" /></a>
    <button onclick="addTask()" title="Add New Task"><img src="https://img.icons8.com/?size=100&id=24717&format=png&color=000000" alt="Add"/></button>
    <a href="calendar.php"><button><img src="https://img.icons8.com/ios-filled/50/000000/calendar.png" alt="Calendar" /></button></a>
  </div>

  <script>
    let taskCount = 1; // Used for unique IDs for new task forms before they get a DB ID

    /**
     * Adds a new task form to the task section.
     */
    function addTask() {
      const taskSection = document.getElementById('task-section');
      // Use a negative ID for new tasks to distinguish from database IDs
      const newTaskForm = createTaskForm('new-' + taskCount);
      taskSection.prepend(newTaskForm);
      taskCount++;
    }

    function createTaskForm(id, title = '', subject = '', start_date = '', deadline = '', priority = 'important', status = 'not-started') {
      const taskDiv = document.createElement('div');
      taskDiv.className = 'task';
      taskDiv.id = 'task-form-' + id; // Use 'task-form-id' for the editable form

      taskDiv.innerHTML = `
        <div class="task-fields">
          <div>
            <label for="Title${id}">Title:</label>
            <input type="text" id="Title${id}" value="${title}" placeholder="Task Title" required/>
            <label for="Starting_Date${id}">Starting Date:</label>
            <input type="date" id="Starting_Date${id}" value="${start_date}" />
            <label for="Priority${id}">Priority:</label>
            <select id="Priority${id}">
              <option value="important" ${priority === 'important' ? 'selected' : ''}>Important</option>
              <option value="not-important" ${priority === 'not-important' ? 'selected' : ''}>Not Important</option>
            </select>
          </div>
          <div>
            <label for="Subject${id}">Subject:</label>
            <input type="text" id="Subject${id}" value="${subject}" placeholder="Subject" required/>
            <label for="deadline${id}">Deadline:</label>
            <input type="date" id="deadline${id}" value="${deadline}" />
            <label for="Status${id}">Status:</label>
            <select id="Status${id}">
              <option value="not-started" ${status === 'not-started' ? 'selected' : ''}>Not Started</option>
              <option value="in-progress" ${status === 'in-progress' ? 'selected' : ''}>In Progress</option>
              <option value="completed" ${status === 'completed' ? 'selected' : ''}>Completed</option>
            </select>
          </div>
        </div>
        <div class="add-btn-wrapper">
          <input type="button" value="SAVE" onclick="saveTaskFromHomepage('${id}')" />
        </div>
      `;
      return taskDiv;
    }

    /**
     * Saves a new or edited task from the homepage form.
     * Sends data to api.php.
     * @param {string} formId - The ID of the form being saved ('new-X' or actual task ID).
     */
    async function saveTaskFromHomepage(formId) {
        const isNewTask = formId.startsWith('new-');
        const taskId = isNewTask ? null : formId; // If new, ID is null, otherwise it's the actual ID

        const title = document.getElementById(`Title${formId}`).value;
        const subject = document.getElementById(`Subject${formId}`).value;
        const start_date = document.getElementById(`Starting_Date${formId}`).value;
        const deadline = document.getElementById(`deadline${formId}`).value || '';
        const priority = document.getElementById(`Priority${formId}`).value;
        const status = document.getElementById(`Status${formId}`).value;

        // Basic validation
        if (!title || !subject || !start_date) {
            alert("Please fill in Title, Subject, and Start Date.");
            return;
        }

        const taskData = { title, subject, start_date, deadline, priority, status };
        if (taskId) {
            taskData.id = taskId; // Include ID if updating an existing task
        }

        try {
            const response = await fetch("api.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(taskData),
            });

            const result = await response.json(); // Parse JSON response

            if (result.success) {
                alert(result.message); // Show success message from API
                loadTasks(); // Reload all tasks to update the list
            } else {
                alert(`❌ Error: ${result.error}`); // Show error message from API
            }
        } catch (error) {
            console.error("Error saving task:", error);
            alert("❌ Failed to communicate with the server.");
        }
    }

    /**
     * Replaces a displayed task with an editable form.
     * @param {number} id - The ID of the task to edit.
     */
    function editTask(id) {
        // Find the displayed task div
        const taskDiv = document.getElementById('task-display-' + id);
        if (!taskDiv) return;

        // Fetch the specific task details from the API to ensure up-to-date data
        // Ensure api.php can handle a GET request for a single task by ID and user_id
        fetch(`api.php?id=${id}`)
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data && result.data.length > 0) {
                    const task = result.data[0]; // Assuming it returns an array even for single fetch
                    const editableForm = createTaskForm(task.id, task.title, task.subject, task.start_date, task.deadline, task.priority, task.status);
                    // Change the onclick to saveTaskFromHomepage specific for this ID
                    editableForm.querySelector('.add-btn-wrapper input[type="button"]').setAttribute('onclick', `saveTaskFromHomepage(${task.id})`);
                    taskDiv.replaceWith(editableForm);
                } else {
                    alert(`Error fetching task for edit: ${result.error || 'Task not found'}`);
                    console.error("Error fetching task for edit:", result);
                }
            })
            .catch(error => {
                console.error("Fetch error for edit:", error);
                alert("Failed to fetch task details for editing.");
            });
    }

    /**
     * Deletes a task by its ID.
     * Sends DELETE request to api.php.
     * @param {number} id - The ID of the task to delete.
     */
    async function deleteTask(id) {
        if (!confirm("Are you sure you want to delete this task?")) {
            return;
        }

        try {
            const response = await fetch("api.php", {
                method: "DELETE",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: id }), // Send ID as JSON
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message); // Show success message from API
                loadTasks(); // Reload all tasks to update the list
            } else {
                alert(`❌ Error: ${result.error}`); // Show error message from API
            }
        } catch (error) {
            console.error("Delete failed:", error);
            alert("❌ Failed to communicate with the server.");
        }
    }

    /**
     * Applies filters and sorting by reloading tasks from the server.
     */
    function applyFilters() {
        loadTasks();
    }

    // Event listeners for filters and search
    document.getElementById("statusFilter").onchange = applyFilters;
    document.getElementById("priorityFilter").onchange = applyFilters;
    document.getElementById("searchInput").oninput = applyFilters;
    document.getElementById("sortBy").onchange = applyFilters; // Added sortBy listener

    /**
     * Clears all filter inputs and reloads tasks.
     */
    function clearFilters() {
      document.getElementById("searchInput").value = "";
      document.getElementById("statusFilter").value = "";
      document.getElementById("priorityFilter").value = "";
      document.getElementById("sortBy").value = ""; // Clear sort by
      loadTasks();
    }

    /**
     * Loads tasks from the API based on current filter and sort selections.
     */
    async function loadTasks() {
        const status = document.getElementById("statusFilter").value;
        const priority = document.getElementById("priorityFilter").value;
        const search = document.getElementById("searchInput").value;
        const sort = document.getElementById("sortBy").value;

        const params = new URLSearchParams();
        if (status) params.append("status", status);
        if (priority) params.append("priority", priority);
        if (search) params.append("search", search);
        if (sort) params.append("sort", sort);

        const fetchUrl = `api.php?${params.toString()}`;

        try {
            // Fetch from api.php with query parameters
            const response = await fetch(fetchUrl);
            const result = await response.json(); // Expect JSON response

            const taskSection = document.getElementById("task-section");
            taskSection.innerHTML = ""; // Clear old tasks

            if (result.success && Array.isArray(result.data)) {
                if (result.data.length === 0 && search) {
                    // Specific message if no results found for a search
                    taskSection.innerHTML = `<p style="text-align: center; color: #555;">No tasks found matching your search "${search}".</p>`;
                } else if (result.data.length === 0) {
                     taskSection.innerHTML = `<p style="text-align: center; color: #555;">No tasks available.</p>`;
                }
                result.data.forEach(task => renderTask(task));
            } else if (!result.success) {
                console.error("API error loading tasks:", result.error);
                alert(`❌ Error loading tasks: ${result.error}`);
            } else {
                console.error("Unexpected data format from API:", result);
                alert("❌ Unexpected data from server when loading tasks.");
            }
        } catch (err) {
            console.error("Failed to load tasks:", err);
            alert("❌ Failed to connect to the server to load tasks.");
        }
    }

    /**
     * Helper function to render a single task display block.
     * @param {object} task - The task object from the API.
     */
    function renderTask(task) {
      const taskSection = document.getElementById("task-section");
      // Use 'important' vs 'not-important' priority directly for color
      const color = task.priority === "important" ? "#e74c3c" : "#2ecc71"; // Red for important, green for not-important

      const taskDiv = document.createElement("div");
      taskDiv.className = "task";
      taskDiv.id = "task-display-" + task.id; // Unique ID for displayed task

      taskDiv.innerHTML = `
          <div style="border-left: 8px solid ${color}; padding-left: 10px;">
          <h3>Title: ${task.title}</h3>
          <p>Subject: ${task.subject}</p>
          <p>Start: ${task.start_date} | Deadline: ${task.deadline}</p>
          <p>Priority: ${task.priority} | Status: ${task.status}</p>
          <button onclick="editTask(${task.id})">Edit</button>
          <button onclick="deleteTask(${task.id})" style="color:red;">Delete</button>
          </div>
      `;
      taskSection.appendChild(taskDiv);
    }

    // Set current date when page loads
    document.getElementById('currentDate').textContent = new Date().toLocaleDateString();

    // Load tasks when the page loads
    window.onload = function () {
      loadTasks();
    }
  </script>
</body>
</html>
