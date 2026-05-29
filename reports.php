<?php
// reports.php
require_once 'config/db.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

// Get revenue summary
$stmt = $pdo->query("
    SELECT DATE(payment_date) as sale_date, 
           COUNT(*) as transactions, 
           SUM(final_amount) as total_revenue 
    FROM bill 
    WHERE paystatus = 'paid' 
    GROUP BY DATE(payment_date) 
    ORDER BY sale_date DESC 
    LIMIT 10
");
$revenueData = $stmt->fetchAll();

// Get popular games
$stmt = $pdo->query("
    SELECT g.title, COUNT(sg.sessionid) as times_played
    FROM game g
    LEFT JOIN session_game sg ON g.gameid = sg.gameid
    GROUP BY g.gameid
    ORDER BY times_played DESC
    LIMIT 5
");
$popularGames = $stmt->fetchAll();

// Get top customers
$stmt = $pdo->query("
    SELECT c.name, COALESCE(SUM(b.final_amount), 0) as total_spent, COUNT(s.sessionid) as sessions
    FROM customer c
    LEFT JOIN session s ON c.customerid = s.customerid
    LEFT JOIN bill b ON s.sessionid = b.sessionid AND b.paystatus = 'paid'
    GROUP BY c.customerid
    ORDER BY total_spent DESC
    LIMIT 10
");
$topCustomers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - ArenaHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .navbar-brand { font-weight: bold; }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px 10px 0 0 !important; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">🎮 ArenaHub MS</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">Daily Revenue</div>
                    <div class="card-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">Most Popular Games</div>
                    <div class="card-body">
                        <canvas id="gamesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">Top Spending Customers</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Total Spent (Rs.)</th>
                                        <th>Number of Sessions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($topCustomers as $customer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                        <td>Rs. <?php echo number_format($customer['total_spent']); ?></td>
                                        <td><?php echo $customer['sessions']; ?></td>
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

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenueData, 'sale_date')); ?>,
                datasets: [{
                    label: 'Revenue (Rs.)',
                    data: <?php echo json_encode(array_column($revenueData, 'total_revenue')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            }
        });
        
        // Games Chart
        const gamesCtx = document.getElementById('gamesChart').getContext('2d');
        new Chart(gamesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($popularGames, 'title')); ?>,
                datasets: [{
                    label: 'Times Played',
                    data: <?php echo json_encode(array_column($popularGames, 'times_played')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)'
                }]
            }
        });
    </script>
</body>
</html>