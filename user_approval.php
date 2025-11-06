<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['admin_logged_in'])) {
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
    } elseif (isset($_POST['bulk_approve'])) {
        $user_ids = $_POST['user_ids'] ?? [];
        if (!empty($user_ids)) {
            $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE users SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$_SESSION['admin_id']], $user_ids));
            $_SESSION['success'] = count($user_ids) . " users approved successfully!";
        }
    }
    header("Location: user_approval.php");
    exit();
}

// Get pending and approved users
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
    error_log("User approval error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Approval - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Admin Panel
                </a>
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3">
                        <i class="fas fa-user me-1"></i>Welcome, <?php echo $_SESSION['admin_username']; ?>
                    </span>
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </nav>

        <div class="container mt-4">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <!-- Pending Approval -->
            <div class="card">
                <div class="card-header bg-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Pending Approval (<?php echo count($pending_users); ?>)
                        </h5>
                        <?php if (!empty($pending_users)): ?>
                            <form method="POST" class="d-inline">
                                <?php foreach ($pending_users as $user): ?>
                                    <input type="hidden" name="user_ids[]" value="<?php echo $user['id']; ?>">
                                <?php endforeach; ?>
                                <button type="submit" name="bulk_approve" class="btn btn-success btn-sm" 
                                        onclick="return confirm('Approve all <?php echo count($pending_users); ?> pending users?')">
                                    <i class="fas fa-check-double me-1"></i>Approve All
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_users)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-success">No Pending Approvals</h5>
                            <p class="text-muted">All user registration requests have been processed.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                        </th>
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
                                            <td>
                                                <input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" class="user-checkbox">
                                            </td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $user['status'] == 'Staff' ? 'bg-primary' : 'bg-success'; ?>">
                                                    <?php echo htmlspecialchars($user['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="approve_user" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="reject_user" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Reject <?php echo htmlspecialchars($user['name']); ?>? This action cannot be undone.')">
                                                        <i class="fas fa-times me-1"></i>Reject
                                                    </button>
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
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Approved Users (<?php echo count($approved_users); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($approved_users)): ?>
                        <p class="text-muted">No approved users yet.</p>
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
                                            <td>
                                                <span class="badge <?php echo $user['status'] == 'Staff' ? 'bg-primary' : 'bg-success'; ?>">
                                                    <?php echo htmlspecialchars($user['status']); ?>
                                                </span>
                                            </td>
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

    <script>
        function toggleSelectAll(source) {
            const checkboxes = document.getElementsByClassName('user-checkbox');
            for (let checkbox of checkboxes) {
                checkbox.checked = source.checked;
            }
        }
    </script>
</body>
</html>