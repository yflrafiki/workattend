<?php
require_once 'db_config.php';
session_start();

// Admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id'], $user_id]);
        $_SESSION['success'] = "User approved successfully!";
    } elseif (isset($_POST['reject_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND is_approved = 0");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "User rejected and removed!";
    }
    header("Location: admin.php");
    exit();
}

// Get pending users
$pending_users = [];
$approved_users = [];

try {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, status, created_at FROM users WHERE is_approved = 0 ORDER BY created_at DESC");
    $stmt->execute();
    $pending_users = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.phone, u.status, u.approved_at, a.name as approved_by_name 
                          FROM users u 
                          LEFT JOIN admins a ON u.approved_by = a.id 
                          WHERE u.is_approved = 1 
                          ORDER BY u.approved_at DESC");
    $stmt->execute();
    $approved_users = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Admin panel error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - User Approval</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <nav class="navbar navbar-dark bg-dark">
                    <div class="container-fluid">
                        <span class="navbar-brand">Admin Panel - User Approval</span>
                        <a href="admin_logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                    </div>
                </nav>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success mt-3"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Pending Approval -->
                <div class="card mt-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">Pending Approval (<?php echo count($pending_users); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_users)): ?>
                            <p class="text-muted">No pending users for approval</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Status</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($user['status']); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="approve_user" class="btn btn-success btn-sm">Approve</button>
                                                    </form>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="reject_user" class="btn btn-danger btn-sm">Reject</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Approved Users -->
                <div class="card mt-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Approved Users (<?php echo count($approved_users); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($approved_users)): ?>
                            <p class="text-muted">No approved users</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Status</th>
                                            <th>Approved By</th>
                                            <th>Approved At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approved_users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($user['status']); ?></td>
                                                <td><?php echo htmlspecialchars($user['approved_by_name'] ?? 'System'); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($user['approved_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>