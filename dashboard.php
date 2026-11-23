<?php
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }
include 'includes/db.php';
$user_id = $_SESSION["user_id"];

$subs = $conn->query("SELECT * FROM subscriptions WHERE user_id = $user_id ORDER BY renewal_date ASC");
$all  = $subs->fetch_all(MYSQLI_ASSOC);

$monthly_total = 0; $yearly_total = 0;
foreach ($all as $s) {
    if ($s["billing_cycle"] === "monthly") $monthly_total += $s["cost"];
    if ($s["billing_cycle"] === "yearly")  $yearly_total  += $s["cost"];
}
$today    = date("Y-m-d");
$in7      = date("Y-m-d", strtotime("+7 days"));
$upcoming = array_filter($all, fn($s) => $s["renewal_date"] >= $today && $s["renewal_date"] <= $in7);
$grand    = $monthly_total + ($yearly_total / 12);

// Category breakdown for side panel
$cat_res  = $conn->query("SELECT category, SUM(cost) as total FROM subscriptions WHERE user_id=$user_id GROUP BY category ORDER BY total DESC");
$cats     = $cat_res->fetch_all(MYSQLI_ASSOC);
$max_cat  = !empty($cats) ? floatval($cats[0]["total"]) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SubSync — Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a class="navbar-brand" href="dashboard.php">SUB<span>SYNC</span></a>
    <div class="nav-links">
        <a class="nav-link active" href="dashboard.php">Dashboard</a>
        <a class="nav-link" href="subscriptions.php?action=add">+ Add</a>
        <a class="nav-link" href="reports.php">Reports</a>
        <a class="nav-link" href="reminders.php">Reminders</a>
    </div>
    <div class="nav-right">
        <span style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:#64748b;letter-spacing:1px;">
            <?= strtoupper($_SESSION["username"]) ?>
        </span>
        <a class="nav-logout" href="logout.php">Logout</a>
    </div>
</nav>

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="eyebrow">Subscription Analytics · <?= date("d M Y") ?></div>
    <h1>SUBSCRIPTION<br><span>DASHBOARD</span></h1>
    <div class="meta">PERIOD · 30 DAYS &nbsp;·&nbsp; <?= count($all) ?> ACTIVE SUBSCRIPTIONS</div>
</div>

<!-- UPCOMING BANNER -->
<?php if (!empty($upcoming)): ?>
<div class="reminder-banner">
    <span class="rb-label">⚠ Renewing Soon —</span>
    <?php foreach ($upcoming as $u): ?>
        <span class="rb-item"><?= htmlspecialchars($u["service_name"]) ?> · <?= $u["renewal_date"] ?></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="stat-row">
    <div class="stat-card">
        <div class="label"><span class="num">01</span> Total Subscriptions</div>
        <div class="value"><?= count($all) ?></div>
        <div class="delta delta-note">active services tracked</div>
        <div class="stat-bar">
            <?php for($i=0;$i<16;$i++): ?>
                <span class="<?= $i < min(count($all), 16) ? 'filled' : '' ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="label"><span class="num">02</span> Monthly Spend</div>
        <div class="value">£<?= number_format($monthly_total, 0) ?></div>
        <div class="delta delta-note">monthly billing total</div>
        <div class="stat-bar">
            <?php $bars = min(intval($monthly_total / 5), 16);
            for($i=0;$i<16;$i++): ?>
                <span class="<?= $i < $bars ? 'filled' : '' ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="label"><span class="num">03</span> Yearly Spend</div>
        <div class="value">£<?= number_format($yearly_total, 0) ?></div>
        <div class="delta delta-note">annual billing total</div>
        <div class="stat-bar">
            <?php $bars = min(intval($yearly_total / 30), 16);
            for($i=0;$i<16;$i++): ?>
                <span class="<?= $i < $bars ? 'filled' : '' ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="label"><span class="num">04</span> Monthly Equivalent</div>
        <div class="value">£<?= number_format($grand, 2) ?></div>
        <div class="delta <?= $grand > 50 ? 'delta-down' : 'delta-up' ?>">
            <?= $grand > 50 ? '↑ above £50 threshold' : '↓ within budget' ?>
        </div>
        <div class="stat-bar">
            <?php for($i=0;$i<16;$i++): ?>
                <span class="<?= $i === 15 ? 'accent' : 'filled' ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- PANELS ROW -->
<div class="panels">
    <!-- Subscriptions Table -->
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title">Active Subscriptions</span>
            <a href="subscriptions.php?action=add" class="btn btn-primary btn-sm">+ Add New</a>
        </div>
        <div style="overflow-x:auto;">
        <?php if (empty($all)): ?>
            <div class="empty-state">
                <p>No subscriptions yet</p>
                <a href="subscriptions.php?action=add" class="btn btn-primary">Add Your First →</a>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Category</th>
                    <th>Billing</th>
                    <th>Cost</th>
                    <th>Renewal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all as $s):
                    $days_left = (int)ceil((strtotime($s["renewal_date"]) - strtotime($today)) / 86400);
                ?>
                <tr>
                    <td>
                        <div class="service-name"><?= htmlspecialchars($s["service_name"]) ?></div>
                        <?php if ($days_left <= 7 && $days_left >= 0): ?>
                            <span class="badge badge-warning" style="font-size:0.58rem;">
                                <?= $days_left === 0 ? 'TODAY' : "IN {$days_left}D" ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--gray);font-size:0.82rem;"><?= htmlspecialchars($s["category"]) ?></td>
                    <td><span class="badge badge-<?= $s["billing_cycle"] ?>"><?= ucfirst($s["billing_cycle"]) ?></span></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-weight:600;">£<?= number_format($s["cost"], 2) ?></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:var(--gray);"><?= $s["renewal_date"] ?></td>
                    <td>
                        <a href="subscriptions.php?action=edit&id=<?= $s["id"] ?>" class="btn btn-edit btn-sm">Edit</a>
                        <a href="subscriptions.php?action=delete&id=<?= $s["id"] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Del</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </div>
    </div>

    <!-- Category Breakdown Side Panel -->
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title">By Category</span>
            <span class="panel-badge">BREAKDOWN</span>
        </div>
        <div class="panel-body">
            <?php if (empty($cats)): ?>
                <div class="empty-state"><p>No data</p></div>
            <?php else: ?>
                <?php foreach ($cats as $i => $c): ?>
                <div class="plan-row">
                    <div class="plan-row-header">
                        <span class="plan-name"><?= htmlspecialchars($c["category"]) ?></span>
                        <span class="plan-val">£<?= number_format($c["total"], 2) ?></span>
                    </div>
                    <div class="plan-bar">
                        <div class="plan-fill <?= $i === 0 ? 'lime' : ($i === 1 ? 'blue' : '') ?>"
                             style="width:<?= round(($c["total"] / $max_cat) * 100) ?>%">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--light);">
                    <a href="reports.php" class="btn btn-ghost" style="width:100%;text-align:center;display:block;">View Full Report →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>