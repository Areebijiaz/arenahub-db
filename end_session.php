<?php
// end_session.php
require_once 'config/db.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';

// Get active sessions
$stmt = $pdo->query("
    SELECT s.*, c.name as customer_name, p.pc_name 
    FROM session s
    JOIN customer c ON s.customerid = c.customerid
    JOIN pc p ON s.pcid = p.pcid
    WHERE s.endtime IS NULL
");
$activeSessions = $stmt->fetchAll();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sessionid'])) {
    try {
        $stmt = $pdo->prepare("CALL EndSession(?)");
        $stmt->execute([$_POST['sessionid']]);
        $result = $stmt->fetch();
        $message = "Session ended! Total hours: " . round($result['total_hours'], 2) . 
                   ", Amount: Rs. " . number_format($result['amount']);
        header("Refresh:2");
    } catch(Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>End Session - ArenaHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .navbar-brand { font-weight: bold; }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
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
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">End Gaming Session</div>
                    <div class="card-body">
                        <?php if($message): ?>
                            <div class="alert alert-info"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <?php if(count($activeSessions) == 0): ?>
                            <div class="alert alert-warning">No active sessions found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>PC</th>
                                            <th>Start Time</th>
                                            <th>Duration</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($activeSessions as $session): ?>
                                        <tr>
                                            <td><?php echo $session['customer_name']; ?></td>
                                            <td><?php echo $session['pc_name']; ?></td>
                                            <td><?php echo date('H:i:s', strtotime($session['starttime'])); ?></td>
                                            <td>
                                                <?php 
                                                $minutes = (time() - strtotime($session['starttime'])) / 60;
                                                echo floor($minutes/60) . 'h ' . ($minutes % 60) . 'm';
                                                ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display:inline">
                                                    <input type="hidden" name="sessionid" value="<?php echo $session['sessionid']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('End this session?')">End Session</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <a href="start_session.php" class="btn btn-primary mt-3">Start New Session</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>