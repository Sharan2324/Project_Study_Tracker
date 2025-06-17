<?php
header('Content-Type: application/json');
ini_set('display_errors', 0); // Keep this at 1 TEMPORARILY for now
error_reporting(E_ALL);

session_start(); // Ensure session is started for user ID retrieval

include 'db.php'; // Assumes db.php defines $pdo (renamed from db_config.php earlier)

// Get the logged-in user's ID from the session
$loggedInUserId = $_SESSION['user_id'] ?? null;

// --- IMPORTANT: Unauthorized access check ---
// If no user is logged in, respond with unauthorized error
if (!$loggedInUserId) {
    http_response_code(401); // Unauthorized
    echo json_encode(["success" => false, "error" => "Unauthorized access. Please log in."]);
    exit;
}

// Get the request method and input data
$method = $_SERVER['REQUEST_METHOD'];
$inputData = json_decode(file_get_contents('php://input'), true);

// Main switch for HTTP method
switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? '';

        if ($action === 'fetch_sessions') {
            try {
                // Fetch sessions for the CURRENT logged-in user
                $sql = "SELECT id, task_name, task_subject, duration, duration_ms, notes, timestamp
                        FROM sessions
                        WHERE user_id = :user_id -- Filter by user_id
                        ORDER BY timestamp DESC LIMIT 10";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT); // Bind user ID
                $stmt->execute();
                $sessions = $stmt->fetchAll();
                echo json_encode(["success" => true, "data" => $sessions]);
            } catch (PDOException $e) {
                error_log("GET fetch_sessions query error (User ID: {$loggedInUserId}): " . $e->getMessage());
                echo json_encode(["success" => false, "error" => "Failed to retrieve sessions."]);
            }
            break;
        }

        // --- Existing GET logic for tasks (UPDATED for user_id) ---
        $status = $_GET['status'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? '';

        $sql = "SELECT id, title, subject, start_date, deadline, priority, status FROM tasks WHERE user_id = :user_id"; // Filter by user_id
        $params = [':user_id' => $loggedInUserId]; // Add user_id to parameters

        // Apply filters
        if (!empty($status)) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        if (!empty($priority)) {
            $sql .= " AND priority = :priority";
            $params[':priority'] = $priority;
        }
        if (!empty($search)) {
            $sql .= " AND (title LIKE :search1 OR subject LIKE :search2)";
            $params[':search1'] = "%" . $search . "%";
            $params[':search2'] = "%" . $search . "%";
        }

        // Apply sorting
        switch ($sort) {
            case 'start_date_asc': $sql .= " ORDER BY start_date ASC"; break;
            case 'start_date_desc': $sql .= " ORDER BY start_date DESC"; break;
            case 'deadline_asc': $sql .= " ORDER BY deadline ASC"; break;
            case 'deadline_desc': $sql .= " ORDER BY deadline DESC"; break;
            default: $sql .= " ORDER BY id DESC"; break;
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tasks = $stmt->fetchAll();
            echo json_encode(["success" => true, "data" => $tasks]);
        } catch (PDOException $e) {
            error_log("GET tasks query error (User ID: {$loggedInUserId}): " . $e->getMessage() . " SQL: " . $sql);
            echo json_encode(["success" => false, "error" => "Failed to retrieve tasks."]);
        }
        break;

    case 'POST':
        $action = $inputData['action'] ?? null;

        if ($action === 'save_session') {
            if (isset($inputData['task_name'], $inputData['task_subject'], $inputData['duration'], $inputData['duration_ms'])) {
                $task_name = $inputData['task_name'];
                $task_subject = $inputData['task_subject'];
                $duration = $inputData['duration'];
                $duration_ms = $inputData['duration_ms'];
                $notes = $inputData['notes'] ?? '';

                try {
                    // Insert new session for the CURRENT logged-in user
                    $sql = "INSERT INTO sessions (task_name, task_subject, duration, duration_ms, notes, user_id)
                            VALUES (:task_name, :task_subject, :duration, :duration_ms, :notes, :user_id)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':task_name' => $task_name,
                        ':task_subject' => $task_subject,
                        ':duration' => $duration,
                        ':duration_ms' => $duration_ms,
                        ':notes' => $notes,
                        ':user_id' => $loggedInUserId // Bind user ID
                    ]);
                    echo json_encode(["success" => true, "message" => "Session saved successfully!", "id" => $pdo->lastInsertId()]);
                } catch (PDOException $e) {
                    error_log("POST save_session query error (User ID: {$loggedInUserId}): " . $e->getMessage());
                    echo json_encode(["success" => false, "error" => "Failed to save session. Internal server error."]);
                }
            } else {
                echo json_encode(["success" => false, "error" => "Missing data for saving session."]);
            }
        } else {
            // --- Existing task save/update logic (UPDATED for user_id) ---
            if (!isset($inputData['title'], $inputData['subject'], $inputData['start_date'], $inputData['deadline'], $inputData['priority'], $inputData['status'])) {
                echo json_encode(["success" => false, "error" => "Invalid input data provided for task."]);
                exit;
            }

            $taskId = $inputData['id'] ?? null;

            try {
                if ($taskId) {
                    // Update existing task for the CURRENT logged-in user
                    $sql = "UPDATE tasks SET title=:title, subject=:subject, start_date=:start_date, deadline=:deadline, priority=:priority, status=:status
                            WHERE id=:id AND user_id=:user_id"; // Ensure user owns the task
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':title' => $inputData['title'],
                        ':subject' => $inputData['subject'],
                        ':start_date' => $inputData['start_date'],
                        ':deadline' => $inputData['deadline'],
                        ':priority' => $inputData['priority'],
                        ':status' => $inputData['status'],
                        ':id' => $taskId,
                        ':user_id' => $loggedInUserId // Bind user ID
                    ]);
                    // Check if any row was actually updated (rowCount will be 0 if task_id exists but user_id doesn't match)
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["success" => true, "message" => "Task updated successfully."]);
                    } else {
                        echo json_encode(["success" => false, "error" => "Task not found or not authorized."]);
                    }
                } else {
                    // Insert new task for the CURRENT logged-in user
                    $sql = "INSERT INTO tasks (title, subject, start_date, deadline, priority, status, user_id)
                            VALUES (:title, :subject, :start_date, :deadline, :priority, :status, :user_id)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':title' => $inputData['title'],
                        ':subject' => $inputData['subject'],
                        ':start_date' => $inputData['start_date'],
                        ':deadline' => $inputData['deadline'],
                        ':priority' => $inputData['priority'],
                        ':status' => $inputData['status'],
                        ':user_id' => $loggedInUserId // Bind user ID
                    ]);
                    echo json_encode(["success" => true, "message" => "Task added successfully.", "id" => $pdo->lastInsertId()]);
                }
            } catch (PDOException $e) {
                error_log("POST task query error (User ID: {$loggedInUserId}): " . $e->getMessage());
                echo json_encode(["success" => false, "error" => "Failed to save task. Internal server error."]);
            }
        }
        break;

    case 'DELETE':
        $action = $inputData['action'] ?? null;

        if ($action === 'delete_session') {
            if (!isset($inputData['id']) || empty($inputData['id'])) {
                echo json_encode(["success" => false, "error" => "Session ID not provided for deletion."]);
                exit;
            }
            $sessionId = $inputData['id'];

            try {
                // Delete session for the CURRENT logged-in user
                $sql = "DELETE FROM sessions WHERE id=:id AND user_id=:user_id"; // Ensure user owns the session
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $sessionId,
                    ':user_id' => $loggedInUserId // Bind user ID
                ]);

                if ($stmt->rowCount() > 0) {
                    echo json_encode(["success" => true, "message" => "Session deleted successfully."]);
                } else {
                    echo json_encode(["success" => false, "error" => "Session not found or not authorized."]);
                }
            } catch (PDOException $e) {
                error_log("DELETE delete_session query error (User ID: {$loggedInUserId}): " . $e->getMessage());
                echo json_encode(["success" => false, "error" => "Failed to delete session. Internal server error."]);
            }
        } else {
            // --- Existing task delete logic (UPDATED for user_id) ---
            if (!isset($inputData['id']) || empty($inputData['id'])) {
                echo json_encode(["success" => false, "error" => "Task ID not provided for deletion."]);
                exit;
            }
            $taskId = $inputData['id'];

            try {
                // Delete task for the CURRENT logged-in user
                $sql = "DELETE FROM tasks WHERE id=:id AND user_id=:user_id"; // Ensure user owns the task
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $taskId,
                    ':user_id' => $loggedInUserId // Bind user ID
                ]);

                if ($stmt->rowCount() > 0) {
                    echo json_encode(["success" => true, "message" => "Task deleted successfully."]);
                } else {
                    echo json_encode(["success" => false, "error" => "Task not found or not authorized."]);
                }
            } catch (PDOException $e) {
                error_log("DELETE task query error (User ID: {$loggedInUserId}): " . $e->getMessage());
                echo json_encode(["success" => false, "error" => "Failed to delete task. Internal server error."]);
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Method not allowed."]);
        break;
}

