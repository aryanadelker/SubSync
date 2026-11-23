<?php
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }
include 'includes/db.php';

$user_id = $_SESSION["user_id"];

$cat_result = $conn->query("SELECT category, SUM(cost) as total FROM subscriptions WHERE user_id = $user_id GROUP BY category");
$cat_labels = []; $cat_totals = [];
while ($row = $cat_result->fetch_assoc()) {
    $cat_labels[] = $row["category"];
    $cat_totals[] = floatval($row["total"]);
}

$cyc_result = $conn->query("SELECT billing_cycle, SUM(cost) as total FROM subscriptions WHERE user_id = $user_id GROUP BY billing_cycle");
$cyc_labels = []; $cyc_totals = [];
while ($row = $cyc_result->fetch_assoc()) {
    $cyc_labels[] = ucfirst($row["billing_cycle"]);
    $cyc_totals[] = floatval($row["total"]);
}

$all = $conn->query("SELECT * FROM subscriptions WHERE user_id = $user_id ORDER BY cost DESC")->fetch_all(MYSQLI_ASSOC);
$grand_total = array_sum(array_column($all, "cost"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SubSync — Reports</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid var(--black);
        }
        .report-panel {
            border-right: 1px solid var(--black);
            display: flex;
            flex-direction: column;
        }
        .report-panel:last-child { border-right: none; }
        .chart-wrap {
            padding: 1.5rem;
            flex: 1;
            position: relative;
            min-height: 300px;
        }
        .total-strip {
            background: var(--lime);
            border-bottom: 2px solid var(--black);
            padding: 0.9rem 1.6rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .total-strip .ts-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--black);
        }
        .total-strip .ts-value {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: -1px;
            color: var(--black);
        }
        @media (max-width: 700px) {
            .reports-grid { grid-template-columns: 1fr; }
            .report-panel { border-right: none; border-bottom: 1px solid var(--black); }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a class="navbar-brand" href="dashboard.php">SUB<span>SYNC</span></a>
    <div class="nav-links">
        <a class="nav-link" href="dashboard.php">Dashboard</a>
        <a class="nav-link" href="subscriptions.php?action=add">+ Add</a>
        <a class="nav-link active" href="reports.php">Reports</a>
        <a class="nav-link" href="reminders.php">Reminders</a>
    </div>
    <div class="nav-right">
        <a class="nav-logout" href="logout.php">Logout</a>
    </div>
</nav>

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="eyebrow">Analytics · <?= date("d M Y") ?></div>
    <h1>EXPENSE<br><span>REPORTS</span></h1>
    <div class="meta"><?= count($all) ?> SUBSCRIPTIONS TRACKED</div>
</div>

<!-- TOTAL STRIP -->
<div class="total-strip">
    <span class="ts-label">Grand Total — All Subscriptions</span>
    <span class="ts-value">£<?= number_format($grand_total, 2) ?></span>
</div>

<!-- CHARTS GRID -->
<div class="reports-grid">

    <!-- Category Doughnut -->
    <div class="report-panel">
        <div class="panel-header">
            <span class="panel-title">Spending by Category</span>
            <span class="panel-badge"><?= count($cat_labels) ?> CATEGORIES</span>
        </div>
        <div class="chart-wrap">
            <?php if (empty($cat_labels)): ?>
                <div class="empty-state"><p>No data yet</p></div>
            <?php else: ?>
                <canvas id="catChart"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Billing Cycle Bar -->
    <div class="report-panel">
        <div class="panel-header">
            <span class="panel-title">Spending by Billing Cycle</span>
            <span class="panel-badge">CYCLE SPLIT</span>
        </div>
        <div class="chart-wrap">
            <?php if (empty($cyc_labels)): ?>
                <div class="empty-state"><p>No data yet</p></div>
            <?php else: ?>
                <canvas id="cycChart"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- BREAKDOWN TABLE -->
<div class="panel-header" style="border-top: 1px solid var(--black);">
    <span class="panel-title">Full Subscription Breakdown</span>
    <a href="subscriptions.php?action=add" class="btn btn-primary btn-sm">+ Add New</a>
</div>

<div style="overflow-x:auto;">
<?php if (empty($all)): ?>
    <div class="empty-state" style="padding:3rem;">
        <p>No subscriptions yet</p>
        <a href="subscriptions.php?action=add" class="btn btn-primary">Add Your First →</a>
    </div>
<?php else: ?>
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Service</th>
            <th>Category</th>
            <th>Billing</th>
            <th>Cost</th>
            <th>Renewal Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($all as $i => $s): ?>
        <tr>
            <td style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:var(--gray);">
                <?= str_pad($i + 1, 2, "0", STR_PAD_LEFT) ?>
            </td>
            <td><div class="service-name"><?= htmlspecialchars($s["service_name"]) ?></div></td>
            <td style="color:var(--gray);font-size:0.82rem;"><?= htmlspecialchars($s["category"]) ?></td>
            <td><span class="badge badge-<?= $s["billing_cycle"] ?>"><?= ucfirst($s["billing_cycle"]) ?></span></td>
            <td style="font-family:'JetBrains Mono',monospace;font-weight:700;">
                £<?= number_format($s["cost"], 2) ?>
            </td>
            <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:var(--gray);">
                <?= $s["renewal_date"] ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <!-- TOTAL ROW -->
        <tr style="background:#f0f0eb;border-top:3px solid var(--black);">
            <td colspan="4" style="font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:0.9rem;letter-spacing:1px;text-transform:uppercase;">
                GRAND TOTAL
            </td>
            <td style="font-family:'JetBrains Mono',monospace;font-weight:700;font-size:1rem;">
                £<?= number_format($grand_total, 2) ?>
            </td>
            <td></td>
        </tr>
    </tbody>
</table>
<?php endif; ?>
</div>

<script>
const LIME  = '#C8FF00';
const BLACK = '#0a0a0a';
const GRAY  = '#6b7280';
const COLORS = [BLACK, '#2563eb', '#6b7280', '#d97706', '#16a34a', '#7c3aed'];

Chart.defaults.font.family = "'JetBrains Mono', monospace";
Chart.defaults.color = GRAY;

<?php if (!empty($cat_labels)): ?>
new Chart(document.getElementById('catChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($cat_labels) ?>,
        datasets: [{
            data: <?= json_encode($cat_totals) ?>,
            backgroundColor: COLORS,
            borderColor: '#f5f5f0',
            borderWidth: 3,
            hoverOffset: 6
        }]
    },
    options: {
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: BLACK,
                    font: { size: 11, weight: '600' },
                    padding: 16,
                    usePointStyle: true,
                    pointStyleWidth: 10
                }
            },
            tooltip: {
                backgroundColor: BLACK,
                titleColor: LIME,
                bodyColor: '#f5f5f0',
                padding: 12,
                callbacks: {
                    label: ctx => ` £${ctx.parsed.toFixed(2)}`
                }
            }
        }
    }
});
<?php endif; ?>

<?php if (!empty($cyc_labels)): ?>
new Chart(document.getElementById('cycChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($cyc_labels) ?>,
        datasets: [{
            label: 'Total (£)',
            data: <?= json_encode($cyc_totals) ?>,
            backgroundColor: [LIME, BLACK],
            borderColor: [BLACK, BLACK],
            borderWidth: 2,
            borderRadius: 0,
        }]
    },
    options: {
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: BLACK,
                titleColor: LIME,
                bodyColor: '#f5f5f0',
                padding: 12,
                callbacks: {
                    label: ctx => ` £${ctx.parsed.y.toFixed(2)}`
                }
            }
        },
        scales: {
            x: {
                ticks: { color: BLACK, font: { size: 11, weight: '700' } },
                grid: { display: false },
                border: { color: BLACK, width: 2 }
            },
            y: {
                ticks: {
                    color: GRAY,
                    font: { size: 10 },
                    callback: val => '£' + val
                },
                grid: { color: '#e8e8e3' },
                border: { color: BLACK, width: 2 }
            }
        }
    }
});
<?php endif; ?>
</script>

</body>
</html>