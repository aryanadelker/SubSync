<?php
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }
include 'includes/db.php';

$user_id = $_SESSION["user_id"];
$action  = $_GET["action"] ?? "list";
$id      = intval($_GET["id"] ?? 0);
$message = "";

// --- HANDLE DELETE ---
if ($action === "delete" && $id > 0) {
    // Delete linked reminders first
    $stmt = $conn->prepare("DELETE FROM reminders WHERE subscription_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Now delete the subscription
    $stmt = $conn->prepare("DELETE FROM subscriptions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();

    header("Location: dashboard.php"); exit();
}

// --- HANDLE ADD / EDIT FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $service  = trim($_POST["service_name"]);
    $category = trim($_POST["category"]);
    $cycle    = $_POST["billing_cycle"];
    $cost     = floatval($_POST["cost"]);
    $renewal  = $_POST["renewal_date"];
    $edit_id  = intval($_POST["edit_id"] ?? 0);

    if ($renewal < date("Y-m-d")) {
        $message = "Renewal date cannot be in the past.";
        $action  = $edit_id > 0 ? "edit" : "add";
    } else {
        if ($edit_id > 0) {
            $stmt = $conn->prepare("UPDATE subscriptions SET service_name=?, category=?, billing_cycle=?, cost=?, renewal_date=? WHERE id=? AND user_id=?");
            $stmt->bind_param("ssssdii", $service, $category, $cycle, $cost, $renewal, $edit_id, $user_id);
            $stmt->execute();
            $message = "Subscription updated.";
        } else {
            $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, service_name, category, billing_cycle, cost, renewal_date) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("isssds", $user_id, $service, $category, $cycle, $cost, $renewal);
            $stmt->execute();
            $message = "Subscription added.";
        }
        $action = "list";
    }
}

// --- FETCH FOR EDIT ---
$edit_data = null;
if ($action === "edit" && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SubSync — <?= $action === "edit" ? "Edit" : "Add" ?> Subscription</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <a class="navbar-brand" href="dashboard.php">SUB<span>SYNC</span></a>
    <div class="nav-links">
        <a class="nav-link" href="dashboard.php">Dashboard</a>
        <a class="nav-link active" href="subscriptions.php?action=add">+ Add</a>
        <a class="nav-link" href="reports.php">Reports</a>
        <a class="nav-link" href="reminders.php">Reminders</a>
    </div>
    <div class="nav-right">
        <a class="nav-logout" href="logout.php">Logout</a>
    </div>
</nav>

<div class="page-header">
    <div class="eyebrow"><?= $action === "edit" ? "Editing Subscription" : "New Subscription" ?></div>
    <h1><?= $action === "edit" ? "EDIT<br><span>SUBSCRIPTION</span>" : "ADD NEW<br><span>SUBSCRIPTION</span>" ?></h1>
</div>

<div class="form-page" style="background:var(--white);padding:2.5rem 1rem;">
    <div class="form-box">
        <div class="form-box-header">
            <span class="form-box-title">
                <?= $action === "edit" ? "Update Details" : "Subscription Details" ?>
            </span>
            <a href="dashboard.php" class="btn btn-ghost btn-sm" style="color:#94a3b8;border-color:#374151;">
                ← Back
            </a>
        </div>

        <div class="form-box-body">
            <?php if ($message): ?>
                <div class="alert <?= str_contains($message, 'past') ? 'alert-danger' : 'alert-success' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="edit_id" value="<?= $edit_data["id"] ?? 0 ?>">

                <div class="form-group">
                    <label class="form-label">Service Name</label>
                    <input type="text" name="service_name" class="form-control" required
                        value="<?= htmlspecialchars($edit_data["service_name"] ?? "") ?>"
                        placeholder="e.g. Netflix, Spotify, Adobe">
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <?php $cats = ["Entertainment", "Productivity", "Fitness", "News", "Shopping", "Other"];
                        foreach ($cats as $c): ?>
                            <option value="<?= $c ?>" <?= ($edit_data["category"] ?? "") === $c ? "selected" : "" ?>>
                                <?= $c ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Billing Cycle</label>
                    <select name="billing_cycle" class="form-select">
                        <option value="monthly" <?= ($edit_data["billing_cycle"] ?? "") === "monthly" ? "selected" : "" ?>>Monthly</option>
                        <option value="yearly"  <?= ($edit_data["billing_cycle"] ?? "") === "yearly"  ? "selected" : "" ?>>Yearly</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Cost (£)</label>
                    <input type="number" name="cost" class="form-control" step="0.01" min="0" required
                        value="<?= $edit_data["cost"] ?? "" ?>"
                        placeholder="e.g. 9.99">
                </div>

                <div class="form-group">
                    <label class="form-label">Next Renewal Date</label>
                    <input type="date" name="renewal_date" class="form-control" required
                        value="<?= $edit_data["renewal_date"] ?? "" ?>"
                        min="<?= date("Y-m-d") ?>">
                    <small style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;
                                  color:var(--gray);letter-spacing:1px;margin-top:4px;display:block;">
                        PAST DATES NOT ALLOWED
                    </small>
                </div>

                <div style="display:flex;gap:0.8rem;margin-top:1.8rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1;padding:0.75rem;text-align:center;">
                        <?= $action === "edit" ? "UPDATE →" : "ADD SUBSCRIPTION →" ?>
                    </button>
                    <a href="dashboard.php" class="btn btn-ghost" style="padding:0.75rem 1.2rem;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>