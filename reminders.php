<?php
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }
include 'includes/db.php';

$user_id = $_SESSION["user_id"];
$today   = date("Y-m-d");
$in7     = date("Y-m-d", strtotime("+7 days"));

// Auto-insert reminders for subscriptions due within 7 days
$due = $conn->query("SELECT id FROM subscriptions WHERE user_id = $user_id AND renewal_date BETWEEN '$today' AND '$in7'");
while ($row = $due->fetch_assoc()) {
    $sub_id = $row["id"];
    $exists = $conn->query("SELECT id FROM reminders WHERE subscription_id = $sub_id")->num_rows;
    if ($exists === 0) {
        $conn->query("INSERT INTO reminders (subscription_id, remind_days_before, status) VALUES ($sub_id, 7, 'pending')");
    }
}

// Status update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reminder_id"])) {
    $rid     = intval($_POST["reminder_id"]);
    $status  = $_POST["status"];
    $allowed = ["pending", "sent", "dismissed"];
    if (in_array($status, $allowed)) {
        $conn->query("UPDATE reminders SET status = '$status' WHERE id = $rid");
    }
    header("Location: reminders.php"); exit();
}

// Delete
if (isset($_GET["delete"])) {
    $rid = intval($_GET["delete"]);
    $conn->query("DELETE FROM reminders WHERE id = $rid");
    header("Location: reminders.php"); exit();
}

// Fetch all reminders for this user
$reminders = $conn->query("
    SELECT r.id, r.subscription_id, r.remind_days_before, r.status, r.created_at,
           s.service_name, s.renewal_date, s.cost, s.billing_cycle
    FROM reminders r
    JOIN subscriptions s ON r.subscription_id = s.id
    WHERE s.user_id = $user_id
    ORDER BY s.renewal_date ASC
")->fetch_all(MYSQLI_ASSOC);

$pending   = count(array_filter($reminders, fn($r) => $r["status"] === "pending"));
$sent      = count(array_filter($reminders, fn($r) => $r["status"] === "sent"));
$dismissed = count(array_filter($reminders, fn($r) => $r["status"] === "dismissed"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SubSync — Reminders</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <a class="navbar-brand" href="dashboard.php">SUB<span>SYNC</span></a>
    <div class="nav-links">
        <a class="nav-link" href="dashboard.php">Dashboard</a>
        <a class="nav-link" href="subscriptions.php?action=add">+ Add</a>
        <a class="nav-link" href="reports.php">Reports</a>
        <a class="nav-link active" href="reminders.php">Reminders</a>
    </div>
    <div class="nav-right">
        <a class="nav-logout" href="logout.php">Logout</a>
    </div>
</nav>

<div class="page-header">
    <div class="eyebrow">Notification Centre · <?= date("d M Y") ?></div>
    <h1>REMINDER<br><span>HISTORY</span></h1>
    <div class="meta">AUTO-GENERATED FOR SUBSCRIPTIONS DUE WITHIN 7 DAYS</div>
</div>

<!-- STAT ROW -->
<div class="stat-row" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card">
        <div class="label"><span class="num">01</span> Pending</div>
        <div class="value" style="color:#d97706;"><?= $pending ?></div>
        <div class="delta delta-note">awaiting action</div>
        <div class="stat-bar">
            <?php for($i=0;$i<16;$i++): ?>
                <span class="<?= $i < min($pending, 16) ? 'filled' : '' ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="label"><span class="num">02</span> Sent</div>
        <div class="value" style="color:#16a34a;"><?= $sent ?></div>
        <div class="delta delta-up">completed</div>
        <div class="stat-bar">
            <?php for($i=0;$i<16;$i++): ?>
                <span class="<?= $i < min($sent, 16) ? 'accent' : '' ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
    <div class="stat-card" style="border-right:none;">
        <div class="label"><span class="num">03</span> Dismissed</div>
        <div class="value" style="color:var(--gray);"><?= $dismissed ?></div>
        <div class="delta delta-note">ignored</div>
        <div class="stat-bar">
            <?php for($i=0;$i<16;$i++): ?>
                <span class="<?= $i < min($dismissed, 16) ? 'filled' : '' ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="panel-header" style="border-top:1px solid var(--black);">
    <span class="panel-title">All Reminders</span>
    <span class="panel-badge"><?= count($reminders) ?> TOTAL</span>
</div>

<div style="overflow-x:auto;">
<?php if (empty($reminders)): ?>
    <div class="empty-state" style="padding:4rem;">
        <p>No reminders yet</p>
        <small style="font-family:'JetBrains Mono',monospace;font-size:0.72rem;letter-spacing:1px;">
            Reminders auto-appear when a subscription is due within 7 days
        </small>
    </div>
<?php else: ?>
<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Sub ID</th>
            <th>Service</th>
            <th>Renewal Date</th>
            <th>Cost</th>
            <th>Remind Before</th>
            <th>Status</th>
            <th>Created</th>
            <th>Delete</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reminders as $r):
            $days_left = (int) ceil((strtotime($r["renewal_date"]) - strtotime($today)) / 86400);
        ?>
        <tr>
            <td style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:var(--gray);">#<?= $r["id"] ?></td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:var(--gray);">#<?= $r["subscription_id"] ?></td>
            <td>
                <div class="service-name"><?= htmlspecialchars($r["service_name"]) ?></div>
                <?php if ($days_left <= 3 && $days_left >= 0): ?>
                    <span class="badge badge-warning" style="font-size:0.58rem;margin-top:3px;display:inline-block;">
                        <?= $days_left === 0 ? "DUE TODAY" : "IN {$days_left}D" ?>
                    </span>
                <?php endif; ?>
            </td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;"><?= $r["renewal_date"] ?></td>
            <td style="font-family:'JetBrains Mono',monospace;font-weight:700;">£<?= number_format($r["cost"], 2) ?></td>
            <td>
                <span style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;
                             background:var(--light);padding:3px 8px;border:1px solid var(--black);">
                    <?= $r["remind_days_before"] ?> DAYS
                </span>
            </td>
            <td>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="reminder_id" value="<?= $r["id"] ?>">
                    <select name="status" class="status-select" onchange="this.form.submit()">
                        <option value="pending"   <?= $r["status"] === "pending"   ? "selected" : "" ?>>⏳ Pending</option>
                        <option value="sent"      <?= $r["status"] === "sent"      ? "selected" : "" ?>>✅ Sent</option>
                        <option value="dismissed" <?= $r["status"] === "dismissed" ? "selected" : "" ?>>🚫 Dismissed</option>
                    </select>
                </form>
            </td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:0.72rem;color:var(--gray);">
                <?= isset($r["created_at"]) ? date("d M Y", strtotime($r["created_at"])) : "—" ?>
            </td>
            <td>
                <a href="reminders.php?delete=<?= $r["id"] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete this reminder?')">Del</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div>

</body>
</html>