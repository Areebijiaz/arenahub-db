<?php
// games.php
require_once 'config/db.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';

// Add Game
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_game'])) {
    $stmt = $pdo->prepare("INSERT INTO game (title, genre, price, pcid) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['title'], $_POST['genre'], $_POST['price'], $_POST['pcid'] ?: null]);
    $message = "Game added successfully!";
}

// Get all games with PC info
$stmt = $pdo->query("
    SELECT g.*, p.pc_name 
    FROM game g 
    LEFT JOIN pc p ON g.pcid = p.pcid 
    ORDER BY g.gameid DESC
");
$games = $stmt->fetchAll();

// Get PCs for dropdown
$stmt = $pdo->query("SELECT pcid, pc_name FROM pc");
$pcs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games Management - ArenaHub</title>
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
                    <div class="card-header">Add New Game</div>
                    <div class="card-body">
                        <?php if($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="add_game" value="1">
                            <div class="mb-3">
                                <label>Game Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Genre</label>
                                <input type="text" name="genre" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Price (Rs.)</label>
                                <input type="number" step="0.01" name="price" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Install on PC</label>
                                <select name="pcid" class="form-control">
                                    <option value="">None</option>
                                    <?php foreach($pcs as $pc): ?>
                                        <option value="<?php echo $pc['pcid']; ?>"><?php echo htmlspecialchars($pc['pc_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Add Game</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Game Library</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Genre</th>
                                        <th>Price</th>
                                        <th>Installed On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($games as $game): ?>
                                    <tr>
                                        <td><?php echo $game['gameid']; ?></td>
                                        <td><?php echo htmlspecialchars($game['title']); ?></td>
                                        <td><?php echo $game['genre']; ?></td>
                                        <td>Rs. <?php echo number_format($game['price']); ?></td>
                                        <td><?php echo $game['pc_name'] ?? 'Not Installed'; ?></td>
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