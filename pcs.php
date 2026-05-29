<?php
// pcs.php
require_once 'config/db.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';

// Update PC status
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE pc SET status = ? WHERE pcid = ?");
    $stmt->execute([$_POST['status'], $_POST['pcid']]);
    $message = "PC status updated successfully!";
}

// Add new PC
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_pc'])) {
    $stmt = $pdo->prepare("INSERT INTO pc (pc_name, hourlyrate, status, specifications) VALUES (?, ?, 'available', ?)");
    $stmt->execute([$_POST['pc_name'], $_POST['hourlyrate'], $_POST['specifications']]);
    $message = "PC added successfully!";
}

// Get all PCs
$stmt = $pdo->query("SELECT * FROM pc ORDER BY pcid");
$pcs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Management - ArenaHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .navbar-brand { font-weight: bold; }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
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
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Add New PC</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="add_pc" value="1">
                            <div class="mb-3">
                                <label>PC Name</label>
                                <input type="text" name="pc_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Hourly Rate (Rs.)</label>
                                <input type="number" step="0.01" name="hourlyrate" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Specifications</label>
                                <textarea name="specifications" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Add PC</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Gaming PCs</div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach($pcs as $pc): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5><?php echo htmlspecialchars($pc['pc_name']); ?></h5>
                                        <p><strong>Rate:</strong> Rs. <?php echo $pc['hourlyrate']; ?>/hour</p>
                                        <p><strong>Status:</strong> 
                                            <?php if($pc['status'] == 'available'): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php elseif($pc['status'] == 'busy'): ?>
                                                <span class="badge bg-danger">Busy</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Maintenance</span>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Specs:</strong> <?php echo htmlspecialchars($pc['specifications']); ?></p>
                                        
                                        <form method="POST" class="mt-2">
                                            <input type="hidden" name="pcid" value="<?php echo $pc['pcid']; ?>">
                                            <select name="status" class="form-control mb-2">
                                                <option value="available" <?php echo $pc['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                                <option value="busy" <?php echo $pc['status'] == 'busy' ? 'selected' : ''; ?>>Busy</option>
                                                <option value="maintenance" <?php echo $pc['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>