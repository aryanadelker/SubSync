<?php
session_start();
include 'includes/db.php';
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user["password"])) {
            $_SESSION["user_id"]  = $user["id"];
            $_SESSION["username"] = $user["username"];
            header("Location: dashboard.php"); exit();
        } else { $error = "Incorrect password."; }
    } else { $error = "No account found with that email."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SubSync — Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-box">
        <div class="auth-header">
            <div class="auth-logo">SUB<span>SYNC</span></div>
            <div class="auth-sub">Subscription Tracker</div>
        </div>
        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary w-100" style="width:100%;padding:0.8rem;">LOGIN →</button>
            </form>
        </div>
        <div class="auth-footer">
            No account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>
</body>
</html>