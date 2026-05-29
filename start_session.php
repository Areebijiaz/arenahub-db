<?php
// start_session.php
require_once 'config/db.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

// Get available PCs
$stmt = $pdo->query("SELECT * FROM pc WHERE status = 'available'");
$availablePCs = $stmt->fetchAll();

// Get customers
$stmt = $pdo->query("SELECT * FROM customer ORDER BY name");
$customers = $stmt->fetchAll();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("CALL StartSession(?, ?, ?)");
        $stmt->execute([$_POST['customerid'], $_POST['pcid'], $_SESSION['admin_id']]);
        $result = $stmt->fetch();
        $message = "Session started successfully! Session ID: " . $result['sessionid'];
    } catch(Exception $e) {
        $error = "Failed to start session: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Session - ArenaHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .navbar-brand { font-weight: bold; }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px 10px 0 0 !important; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
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
            <div class="col-md-6 mx-auto">
                <div class="card">
                    <div class="card-header">Start New Gaming Session</div>
                    <div class="card-body">
                        <?php if($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if(count($availablePCs) == 0): ?>
                            <div class="alert alert-warning">No PCs available at the moment!</div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="customerid" class="form-label">Select Customer</label>
                                <select name="customerid" class="form-control" required>
                                    <option value="">Choose customer...</option>
                                    <?php foreach($customers as $customer): ?>
                                        <option value="<?php echo $customer['customerid']; ?>">
                                            <?php echo $customer['name'] . ' - ' . $customer['phone']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pcid" class="form-label">Select Gaming PC</label>
                                <select name="pcid" class="form-control" required>
                                    <option value="">Choose PC...</option>
                                    <?php foreach($availablePCs as $pc): ?>
                                        <option value="<?php echo $pc['pcid']; ?>">
                                            <?php echo $pc['pc_name'] . ' - Rs. ' . $pc['hourlyrate'] . '/hour - ' . $pc['specifications']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Start Session</button>
                            <a href="end_session.php" class="btn btn-warning w-100 mt-2">End Session</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>