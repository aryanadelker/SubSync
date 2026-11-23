<?php
session_start();
include 'includes/db.php';
$error = $success = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm  = trim($_POST["confirm_password"]);
    if ($password !== $confirm) { $error = "Passwords do not match."; }
    elseif (strlen($password) < 6) { $error = "Password must be at least 6 characters."; }
    else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email); $check->execute(); $check->store_result();
        if ($check->num_rows > 0) { $error = "An account with that email already exists."; }
        else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed);
            if ($stmt->execute()) { $success = "Account created! You can now log in."; }
            else { $error = "Something went wrong. Please try again."; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SubSync — Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-box">
        <div class="auth-header">
            <div class="auth-logo">SUB<span>SYNC</span></div>
            <div class="auth-sub">Create Your Account</div>
        </div>
        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?> <a href="login.php" style="color:inherit;font-weight:700;">Go to Login →</a></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="username" class="form-control" required placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Min. 6 characters">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;padding:0.8rem;">CREATE ACCOUNT →</button>
            </form>
        </div>
        <div class="auth-footer">Already have an account? <a href="login.php">Login</a></div>
    </div>
</div>
</body>
</html>