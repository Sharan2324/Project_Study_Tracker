<?php
// Start session at the very beginning
session_start();

// Include centralized database configuration
include 'db.php'; // This file should define $pdo

// Initialize message variable
$message = "";

// Handle form submission only for POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    // Basic server-side validations
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {
        try {
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $message = "Email is already registered. Please login instead.";
            } else {
                // Hash the password securely
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user into database
                $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    // Registration successful
                    session_regenerate_id(true); // Prevent session fixation attacks
                    $_SESSION["user_id"] = $pdo->lastInsertId(); // Store new user's ID
                    $_SESSION["user_email"] = $email; // Store user's email

                    $message = "Registration successful. Welcome, " . htmlspecialchars($email) . "!";
                    // Redirect to the homepage
                    header("Location: homepage.php");
                    exit(); // Always exit after a header redirect
                } else {
                    // This specific error might indicate a database issue
                    $message = "Error registering user. Please try again.";
                    error_log("Registration INSERT error for email $email: " . $stmt->errorInfo()[2]);
                }
            }
        } catch (PDOException $e) {
            // Log the actual database error
            error_log("Registration query error: " . $e->getMessage());
            // Provide a generic error message to the user
            $message = "An error occurred during registration. Please try again.";
        }
    }
}
// No need to close PDO connection manually, it closes when script ends
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <!-- Link to external CSS file -->
  <link rel="stylesheet" href="auto.css">
</head>
<body>

  <div class="login-container">
    <h2>Create an account</h2>
    <form method="POST" action="">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <button type="submit">Register</button>
    </form>
    <?php if ($message): ?>
      <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <p style="margin-top: 15px; color: #bbb; font-size: 0.9em;">
        Already have an account? <a href="login.php" style="color: #aab3b7; text-decoration: none; font-weight: bold;">Login here</a>
    </p>
  </div>

</body>
</html>
