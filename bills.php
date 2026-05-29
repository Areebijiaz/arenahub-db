<?php
// bills.php
require_once 'config/db.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';

// Process payment
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    try {
        $stmt = $pdo->prepare("CALL ProcessPayment(?, ?)");
        $stmt->execute([$_POST['billid'], $_POST['payment_method']]);
        $message = "Payment processed successfully!";
    } catch(Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Get all bills with customer info
$stmt = $pdo->query("
    SELECT b.*, c.name as customer_name, s.starttime, s.endtime, p.pc_name
    FROM bill b
    JOIN session s ON b.sessionid = s.sessionid
    JOIN customer c ON s.customerid = c.customerid
    JOIN pc p ON s.pcid = p.pcid
    ORDER BY b.billid DESC
");
$bills = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bills - ArenaHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <div class="card">
            <div class="card-header">Bills Management</div>
            <div class="card-body">
                <?php if($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Bill ID</th>
                                <th>Customer</th>
                                <th>PC</th>
                                <th>Amount</th>
                                <th>Discount</th>
                                <th>Final Amount</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($bills as $bill): ?>
                            <tr>
                                <td><?php echo $bill['billid']; ?></td>
                                <td><?php echo htmlspecialchars($bill['customer_name']); ?></td>
                                <td><?php echo $bill['pc_name']; ?></td>
                                <td>Rs. <?php echo number_format($bill['amount']); ?></td>
                                <td>Rs. <?php echo number_format($bill['discount']); ?></td>
                                <td>Rs. <?php echo number_format($bill['final_amount']); ?></td>
                                <td>
                                    <?php if($bill['paystatus'] == 'paid'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $bill['payment_method'] ?? '-'; ?></td>
                                <td>
                                    <?php if($bill['paystatus'] != 'paid'): ?>
                                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $bill['billid']; ?>">
                                            Process Payment
                                        </button>
                                        
                                        <!-- Payment Modal -->
                                        <div class="modal fade" id="paymentModal<?php echo $bill['billid']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Process Payment</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="billid" value="<?php echo $bill['billid']; ?>">
                                                            <input type="hidden" name="process_payment" value="1">
                                                            <p><strong>Bill Amount:</strong> Rs. <?php echo number_format($bill['final_amount']); ?></p>
                                                            <div class="mb-3">
                                                                <label>Payment Method</label>
                                                                <select name="payment_method" class="form-control" required>
                                                                    <option value="">Select...</option>
                                                                    <option value="Cash">Cash</option>
                                                                    <option value="Card">Credit/Debit Card</option>
                                                                    <option value="Online">Online Payment</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Confirm Payment</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Paid on <?php echo date('Y-m-d', strtotime($bill['payment_date'])); ?></span>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>