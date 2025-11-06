<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Set default date to today, but allow selection of other dates
$default_date = date('Y-m-d');
$selected_date = $_GET['date'] ?? $default_date;

// Handle filters and search
$whereClause = "WHERE 1=1";
$params = [];

// Apply date filter - if no date selected, default to today
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $whereClause .= " AND date = ?";
    $params[] = $selected_date;
} else {
    // Default to today's records
    $whereClause .= " AND date = ?";
    $params[] = $default_date;
    $selected_date = $default_date; // Ensure selected_date is set to today
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $whereClause .= " AND (name LIKE ? OR status LIKE ?)";
    $searchTerm = "%{$_GET['search']}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $whereClause .= " AND status = ?";
    $params[] = $_GET['status'];
}

// Fetch attendance records with departure_time
$stmt = $pdo->prepare("SELECT id, name, status, date, time, departure_time FROM attendance $whereClause ORDER BY date DESC, time DESC");
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get unique dates for the date dropdown
$dates_stmt = $pdo->prepare("SELECT DISTINCT date FROM attendance ORDER BY date DESC");
$dates_stmt->execute();
$available_dates = $dates_stmt->fetchAll();

// Calculate statistics
$total_records = count($records);
$staff_count = 0;
$intern_count = 0;
$present_count = 0;
$completed_count = 0;

foreach ($records as $record) {
    if ($record['status'] === 'Staff') $staff_count++;
    if ($record['status'] === 'Intern') $intern_count++;
    if (empty($record['departure_time'])) $present_count++;
    if (!empty($record['departure_time'])) $completed_count++;
}

// Export functionality
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_' . $selected_date . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Status', 'Date', 'Arrival Time', 'Departure Time', 'Duration']);
    
    foreach ($records as $record) {
        $duration = '';
        if (!empty($record['departure_time'])) {
            $arrival = new DateTime($record['time']);
            $departure = new DateTime($record['departure_time']);
            $duration_obj = $arrival->diff($departure);
            $duration = $duration_obj->format('%hh %im');
        }
        
        fputcsv($output, [
            $record['id'],
            $record['name'],
            $record['status'],
            $record['date'],
            $record['time'],
            $record['departure_time'] ?? '',
            $duration,
        ]);
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .auto-submit {
            cursor: pointer;
        }
        .export-btn {
            text-decoration: none;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .today-badge {
            background-color: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        .today-option {
            font-weight: bold;
            color: #28a745;
        }
        .present-badge {
            background-color: #ffc107;
            color: #000;
        }
        .completed-badge {
            background-color: #28a745;
            color: white;
        }
        .duration-cell {
            font-weight: bold;
            color: #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                </a>
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3">
    <i class="fas fa-user me-1"></i>Welcome, <?php echo $_SESSION['admin_name'] . ' (' . $_SESSION['admin_username'] . ')'; ?>
</span>
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </nav>

        <div class="container mt-4">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card stats-card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h4><?php echo $total_records; ?></h4>
                            <p class="mb-0">
                                <?php echo ($selected_date === $default_date) ? "Today's Records" : "Selected Records"; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stats-card bg-warning text-dark">
                        <div class="card-body text-center">
                            <i class="fas fa-user-clock fa-2x mb-2"></i>
                            <h4><?php echo $present_count; ?></h4>
                            <p class="mb-0">Present</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stats-card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h4><?php echo $completed_count; ?></h4>
                            <p class="mb-0">Completed</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stats-card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-user-tie fa-2x mb-2"></i>
                            <h4><?php echo $staff_count; ?></h4>
                            <p class="mb-0">Staff</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stats-card bg-secondary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-user-graduate fa-2x mb-2"></i>
                            <h4><?php echo $intern_count; ?></h4>
                            <p class="mb-0">Interns</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stats-card bg-dark text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-day fa-2x mb-2"></i>
                            <h4><?php echo count($available_dates); ?></h4>
                            <p class="mb-0">Total Days</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Approval Quick Stats -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="fas fa-user-check me-2"></i>User Approval Management</h5>
            </div>
            <div class="card-body">
                <?php
                // Get pending users count
                $pending_stmt = $pdo->prepare("SELECT COUNT(*) as pending_count FROM users WHERE is_approved = 0");
                $pending_stmt->execute();
                $pending_count = $pending_stmt->fetch()['pending_count'];

                // Get approved users count
                $approved_stmt = $pdo->prepare("SELECT COUNT(*) as approved_count FROM users WHERE is_approved = 1");
                $approved_stmt->execute();
                $approved_count = $approved_stmt->fetch()['approved_count'];
                ?>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h4><?php echo $pending_count; ?></h4>
                                <p class="mb-0">Pending Approval</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <h4><?php echo $approved_count; ?></h4>
                                <p class="mb-0">Approved Users</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid gap-2">
                            <a href="user_approval.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-users-cog me-2"></i>Manage User Approvals
                            </a>
                            <small class="text-muted text-center">
                                Click to approve/reject user registration requests
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Quick Recent Pending Users -->
                <?php
                $recent_pending_stmt = $pdo->prepare("SELECT name, email, status, created_at FROM users WHERE is_approved = 0 ORDER BY created_at DESC LIMIT 3");
                $recent_pending_stmt->execute();
                $recent_pending = $recent_pending_stmt->fetchAll();
                ?>

                <?php if ($pending_count > 0): ?>
                    <div class="mt-3">
                        <h6>Recent Pending Requests:</h6>
                        <div class="list-group">
                            <?php foreach ($recent_pending as $user): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
                                        <small><?php echo date('M j, g:i A', strtotime($user['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                                    <small class="text-muted">Status: <?php echo htmlspecialchars($user['status']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($pending_count > 3): ?>
                            <div class="text-center mt-2">
                                <small class="text-muted">... and <?php echo ($pending_count - 3); ?> more pending</small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

            <!-- Filters and Search -->
            <div class="filter-section">
                <form method="GET" id="filterForm" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-search me-1"></i>Search</label>
                        <input type="text" class="form-control" name="search" placeholder="Search by name or status..." 
                               value="<?php echo $_GET['search'] ?? ''; ?>"
                               onkeyup="if (this.value.length >= 2 || this.value.length === 0) this.form.submit();">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-calendar me-1"></i>Filter by Date</label>
                        <select class="form-select auto-submit" name="date">
                            <option value="" class="today-option" <?php echo ($selected_date === $default_date) ? 'selected' : ''; ?>>
                                📅 Today (<?php echo date('M j, Y'); ?>)
                            </option>
                            <?php foreach ($available_dates as $date_row): ?>
                                <?php if ($date_row['date'] !== $default_date): ?>
                                    <option value="<?php echo $date_row['date']; ?>" <?php echo ($selected_date === $date_row['date']) ? 'selected' : ''; ?>>
                                        <?php echo date('M j, Y', strtotime($date_row['date'])); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-user-tag me-1"></i>Status</label>
                        <select class="form-select auto-submit" name="status">
                            <option value="">All Status</option>
                            <option value="Staff" <?php echo (($_GET['status'] ?? '') == 'Staff') ? 'selected' : ''; ?>>Staff</option>
                            <option value="Intern" <?php echo (($_GET['status'] ?? '') == 'Intern') ? 'selected' : ''; ?>>Intern</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="fas fa-download me-1"></i>Export</label>
                        <a href="?export=csv&date=<?php echo $selected_date; ?><?php 
                            echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; 
                            echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; 
                        ?>" class="btn btn-success w-100 export-btn">
                            <i class="fas fa-file-csv me-1"></i>CSV
                        </a>
                    </div>
                </form>
                
                <?php if (isset($_GET['search']) || isset($_GET['status']) || $selected_date !== $default_date): ?>
                <div class="mt-3">
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-home me-1"></i>Show Today's Records
                    </a>
                    <small class="text-muted ms-2">
                        Showing <?php echo count($records); ?> records
                        <?php 
                        if ($selected_date === $default_date) {
                            echo " for today";
                        } else {
                            echo " for " . date('M j, Y', strtotime($selected_date));
                        }
                        if (isset($_GET['search']) && !empty($_GET['search'])) {
                            echo " matching: '" . htmlspecialchars($_GET['search']) . "'";
                        }
                        if (isset($_GET['status']) && !empty($_GET['status'])) {
                            echo " with status: " . htmlspecialchars($_GET['status']);
                        }
                        ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>

            <!-- Records Table -->
            <div class="card">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        <?php if ($selected_date === $default_date): ?>
                            Today's Attendance Records
                        <?php else: ?>
                            Attendance Records for <?php echo date('M j, Y', strtotime($selected_date)); ?>
                        <?php endif; ?>
                    </h4>
                    <div>
                        <span class="badge bg-light text-dark me-2">Total: <?php echo $total_records; ?></span>
                        <span class="badge bg-warning text-dark me-2">Present: <?php echo $present_count; ?></span>
                        <span class="badge bg-success">Completed: <?php echo $completed_count; ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($records)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No records found</h5>
                            <p class="text-muted">
                                <?php if ($selected_date === $default_date): ?>
                                    No one has marked attendance today yet.
                                <?php else: ?>
                                    No attendance records for <?php echo date('M j, Y', strtotime($selected_date)); ?>.
                                <?php endif; ?>
                            </p>
                            <?php if ($selected_date !== $default_date): ?>
                                <a href="dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-calendar-day me-1"></i>Show Today's Records
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Arrival Time</th>
                                        <th>Departure Time</th>
                                        <th>Duration</th>
                                        <th>Day Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <td><strong>#<?php echo $record['id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($record['name']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $record['status'] == 'Staff' ? 'bg-primary' : 'bg-success'; ?>">
                                                    <i class="fas <?php echo $record['status'] == 'Staff' ? 'fa-user-tie' : 'fa-user-graduate'; ?> me-1"></i>
                                                    <?php echo $record['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($record['date'])); ?>
                                                <?php if ($record['date'] === $default_date): ?>
                                                    <span class="today-badge">Today</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-success">
                                                    <i class="fas fa-sign-in-alt me-1"></i>
                                                    <?php echo date('g:i A', strtotime($record['time'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($record['departure_time'])): ?>
                                                    <span class="text-danger">
                                                        <i class="fas fa-sign-out-alt me-1"></i>
                                                        <?php echo date('g:i A', strtotime($record['departure_time'])); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        Still present
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="duration-cell">
                                                <?php if (!empty($record['departure_time'])): ?>
                                                    <?php
                                                    $arrival = new DateTime($record['time']);
                                                    $departure = new DateTime($record['departure_time']);
                                                    $duration = $arrival->diff($departure);
                                                    echo $duration->format('%hh %im');
                                                    ?>
                                                <?php else: ?>
                                                    <span class="text-warning">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (empty($record['departure_time'])): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-user-clock me-1"></i>Present
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>Completed
                                                    </span>
                                                <?php endif; ?>
                                            </td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form when dropdown changes
        document.addEventListener('DOMContentLoaded', function() {
            const autoSubmitElements = document.querySelectorAll('.auto-submit');
            
            autoSubmitElements.forEach(element => {
                element.addEventListener('change', function() {
                    document.getElementById('filterForm').submit();
                });
            });

            // Add loading indicator for exports
            const exportBtn = document.querySelector('.export-btn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Exporting...';
                });
            }
        });
    </script>
</body>
</html>