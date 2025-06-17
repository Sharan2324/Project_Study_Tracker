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

    // Validate email format and sanitize
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        try {
            // Prepare and execute the query to fetch user by email
            $stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(); // Fetch the user data

            // Verify user exists and password is correct
            if ($user && password_verify($password, $user["password"])) {
                // Login successful
                session_regenerate_id(true); // Prevent session fixation attacks
                $_SESSION["user_id"] = $user["id"]; // Store user ID
                $_SESSION["user_email"] = $user["email"]; // Store user email

                // Redirect to the homepage
                header("Location: homepage.php");
                exit(); // Always exit after a header redirect
            } else {
                // Invalid credentials (generic message for security)
                $message = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            // Log the actual database error
            error_log("Login query error: " . $e->getMessage());
            // Provide a generic error message to the user
            $message = "An error occurred during login. Please try again.";
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
  <title>Login Page</title>
  <!-- Link to external CSS file -->
  <link rel="stylesheet" href="auto.css">
</head>
<body>

  <div class="login-container">
    <h2>Login to your account</h2>
    <form method="POST" action="">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <?php if ($message): ?>
      <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <p style="margin-top: 15px; color: #bbb; font-size: 0.9em;">
        Don't have an account? <a href="register.php" style="color: #aab3b7; text-decoration: none; font-weight: bold;">Register here</a>
    </p>
  </div>

</body>
</html>
