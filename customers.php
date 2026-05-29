<?php
// customers.php
require_once 'config/db.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

// Add Customer
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    try {
        $stmt = $pdo->prepare("INSERT INTO customer (name, idcard, phone, email, adminid) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['idcard'],
            $_POST['phone'],
            $_POST['email'],
            $_SESSION['admin_id']
        ]);
        $message = "Customer added successfully!";
    } catch(Exception $e) {
        $error = "Error adding customer: " . $e->getMessage();
    }
}

// Get all customers
$stmt = $pdo->query("SELECT * FROM customer ORDER BY customerid DESC");
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - ArenaHub</title>
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
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Add New Customer</div>
                    <div class="card-body">
                        <?php if($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label>Full Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>ID Card Number</label>
                                <input type="text" name="idcard" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Add Customer</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Customer List</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>ID Card</th>
                                        <th>Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo $customer['customerid']; ?></td>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                        <td><?php echo $customer['phone']; ?></td>
                                        <td><?php echo $customer['email']; ?></td>
                                        <td><?php echo $customer['idcard']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($customer['registration_date'])); ?></td>
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