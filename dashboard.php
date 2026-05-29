<?php
// dashboard.php
require_once 'config/db.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM customer");
$totalCustomers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM session WHERE endtime IS NULL");
$activeSessions = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM bill WHERE paystatus = 'paid' AND DATE(payment_date) = CURDATE()");
$todayRevenue = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pc WHERE status = 'available'");
$availablePCs = $stmt->fetch()['total'];

// Get recent sessions
$stmt = $pdo->query("
    SELECT s.*, c.name as customer_name, p.pc_name 
    FROM session s
    JOIN customer c ON s.customerid = c.customerid
    JOIN pc p ON s.pcid = p.pcid
    ORDER BY s.starttime DESC LIMIT 5
");
$recentSessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ArenaHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: bold;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">🎮 ArenaHub MS</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-white">Welcome, <?php echo $_SESSION['admin_name']; ?></span>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <h3>Total Customers</h3>
                    <div class="number"><?php echo $totalCustomers; ?></div>
                    <i class="bi bi-people fs-1" style="float: right; margin-top: -40px; opacity: 0.3;"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3>Active Sessions</h3>
                    <div class="number"><?php echo $activeSessions; ?></div>
                    <i class="bi bi-play-circle fs-1" style="float: right; margin-top: -40px; opacity: 0.3;"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3>Today's Revenue</h3>
                    <div class="number">Rs. <?php echo number_format($todayRevenue); ?></div>
                    <i class="bi bi-cash-stack fs-1" style="float: right; margin-top: -40px; opacity: 0.3;"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3>Available PCs</h3>
                    <div class="number"><?php echo $availablePCs; ?></div>
                    <i class="bi bi-pc fs-1" style="float: right; margin-top: -40px; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">Quick Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="start_session.php" class="btn btn-primary">▶ Start New Session</a>
                            <a href="end_session.php" class="btn btn-warning">⏹ End Session</a>
                            <a href="customers.php" class="btn btn-info">👥 Manage Customers</a>
                            <a href="pcs.php" class="btn btn-secondary">🖥 Manage PCs</a>
                            <a href="games.php" class="btn btn-dark">🎮 Manage Games</a>
                            <a href="bills.php" class="btn btn-success">💰 Manage Bills</a>
                            <a href="reports.php" class="btn btn-danger">📊 View Reports</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">Recent Sessions</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr><th>Customer</th><th>PC</th><th>Start Time</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recentSessions as $session): ?>
                                    <tr>
                                        <td><?php echo $session['customer_name']; ?></td>
                                        <td><?php echo $session['pc_name']; ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($session['starttime'])); ?></td>
                                        <td>
                                            <?php if($session['endtime']): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Active</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>