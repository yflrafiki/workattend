<?php
require_once 'db_config.php';
session_start();

// Check if user is logged in and approved
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_approved']) || $_SESSION['is_approved'] != 1) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$device_id = $_SESSION['device_id'];

// Check current attendance status
$today = date('Y-m-d');
$current_time = date('H:i:s');
$current_hour = date('H');
$current_minute = date('i');

try {
    $stmt = $pdo->prepare("SELECT time, departure_time FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $today]);
    $attendance_today = $stmt->fetch();

    $attendance_status = 'none'; // none, arrived, completed
    $arrival_time = null;
    $departure_time = null;

    if ($attendance_today) {
        $arrival_time = $attendance_today['time'];
        if ($attendance_today['departure_time']) {
            $attendance_status = 'completed';
            $departure_time = $attendance_today['departure_time'];
        } else {
            $attendance_status = 'arrived';
        }
    }
} catch(PDOException $e) {
    error_log("Attendance check error: " . $e->getMessage());
    $attendance_status = 'none';
}

$is_working_hours = ($current_hour >= 6 && $current_hour < 17);
$is_after_5pm = ($current_hour >= 17);
$can_mark_arrival = $is_working_hours && $attendance_status === 'none';
$can_mark_departure = $is_after_5pm && $attendance_status === 'arrived';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-card { 
            border-left: 4px solid; 
            margin-bottom: 20px;
        }
        .status-arrived { border-left-color: #28a745; }
        .status-completed { border-left-color: #17a2b8; }
        .status-none { border-left-color: #6c757d; }
        .attendance-btn {
            padding: 20px;
            font-size: 1.2rem;
            margin: 10px 0;
        }
        .time-display {
            font-size: 1.1rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-check"></i> Attendance System
                            <small class="float-end">Welcome, <?php echo htmlspecialchars($user_name); ?></small>
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- User Status -->
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>👤 User:</strong> <?php echo htmlspecialchars($user_name); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>⏰ Current Time:</strong> 
                                    <span class="time-display"><?php echo date('h:i A'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Status -->
                        <div class="card status-card <?php echo 'status-' . $attendance_status; ?>">
                            <div class="card-body">
                                <h5>📊 Today's Attendance Status</h5>
                                <?php if ($attendance_status === 'none'): ?>
                                    <p class="text-muted">
                                        <i class="fas fa-clock me-2"></i>No attendance recorded for today
                                    </p>
                                    <?php if ($is_working_hours): ?>
                                        <p class="text-success">
                                            <i class="fas fa-info-circle me-2"></i>You can mark your arrival now
                                        </p>
                                    <?php else: ?>
                                        <p class="text-warning">
                                            <i class="fas fa-info-circle me-2"></i>Arrival can only be marked from 6:00 AM - 5:00 PM
                                        </p>
                                    <?php endif; ?>
                                <?php elseif ($attendance_status === 'arrived'): ?>
                                    <p class="text-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Arrival Recorded:</strong> 
                                        <?php echo date('h:i A', strtotime($arrival_time)); ?>
                                    </p>
                                    <?php if ($is_after_5pm): ?>
                                        <p class="text-success">
                                            <i class="fas fa-info-circle me-2"></i>You can mark your departure now
                                        </p>
                                    <?php else: ?>
                                        <p class="text-warning">
                                            <i class="fas fa-info-circle me-2"></i>Departure can be marked after 5:00 PM
                                        </p>
                                        <?php
                                        $hours_left = 16 - $current_hour;
                                        $minutes_left = 60 - $current_minute;
                                        if ($hours_left > 0 || $minutes_left > 0) {
                                            echo '<p class="text-muted"><small>Time until departure: ' . ($hours_left + 1) . ' hours ' . $minutes_left . ' minutes</small></p>';
                                        }
                                        ?>
                                    <?php endif; ?>
                                <?php elseif ($attendance_status === 'completed'): ?>
                                    <p class="text-info">
                                        <i class="fas fa-check-double me-2"></i>
                                        <strong>Attendance Completed for Today</strong>
                                    </p>
                                    <p>
                                        Arrival: <?php echo date('h:i A', strtotime($arrival_time)); ?> | 
                                        Departure: <?php echo date('h:i A', strtotime($departure_time)); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Time Status -->
                        <?php if ($is_working_hours): ?>
                            <div class="alert alert-success">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-sun fa-2x me-3"></i>
                                    <div>
                                        <strong>Working Hours (6:00 AM - 5:00 PM)</strong><br>
                                        Current time: <?php echo date('h:i A'); ?><br>
                                        <small>You can mark arrival and work until 5:00 PM</small>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($is_after_5pm): ?>
                            <div class="alert alert-info">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-moon fa-2x me-3"></i>
                                    <div>
                                        <strong>After Working Hours</strong><br>
                                        Current time: <?php echo date('h:i A'); ?><br>
                                        <small>You can mark your departure</small>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock fa-2x me-3"></i>
                                    <div>
                                        <strong>Outside Working Hours</strong><br>
                                        Current time: <?php echo date('h:i A'); ?><br>
                                        <small>Attendance system opens at 6:00 AM</small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Attendance Button -->
                        <?php if ($can_mark_arrival || $can_mark_departure): ?>
                            <div class="text-center mt-4">
                                <button onclick="markAttendance()" class="btn btn-primary btn-lg attendance-btn w-100">
                                    <i class="fas fa-fingerprint me-2"></i>
                                    <?php echo $attendance_status === 'arrived' ? 'Mark Departure' : 'Mark Arrival'; ?>
                                </button>
                                <p class="text-muted mt-2">
                                    <small>Your location will be verified automatically (Company: 5.697855, -0.176617)</small>
                                </p>
                            </div>
                        <?php elseif ($attendance_status === 'completed'): ?>
                            <div class="alert alert-info text-center py-4">
                                <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                <h5>Attendance Completed for Today</h5>
                                <p class="mb-0">Your attendance has been recorded for today. See you tomorrow!</p>
                            </div>
                        <?php elseif ($attendance_status === 'arrived' && !$is_after_5pm): ?>
                            <div class="alert alert-warning text-center py-4">
                                <i class="fas fa-clock fa-3x mb-3 text-warning"></i>
                                <h5>Working in Progress</h5>
                                <p class="mb-0">You have marked arrival. Departure will be available after 5:00 PM.</p>
                            </div>
                        <?php elseif ($attendance_status === 'none' && !$is_working_hours): ?>
                            <div class="alert alert-warning text-center py-4">
                                <i class="fas fa-door-closed fa-3x mb-3 text-warning"></i>
                                <h5>System Closed</h5>
                                <p class="mb-0">Arrival can only be marked during working hours (6:00 AM - 5:00 PM).</p>
                            </div>
                        <?php endif; ?>

                        <!-- Messages -->
                        <div id="message"></div>

                        <!-- Quick Actions -->
                        <div class="text-center mt-4">
                            <a href="logout.php" class="btn btn-outline-danger me-2">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Mark attendance directly
    function markAttendance() {
        const formData = new FormData();
        formData.append('user_id', '<?php echo $user_id; ?>');
        formData.append('device_id', '<?php echo $device_id; ?>');

        // Get location first, then submit
        if (!navigator.geolocation) {
            showMessage('error', '❌ Geolocation not supported. Cannot mark attendance.');
            return;
        }

        // Show loading
        showLoading();

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const user_lat = position.coords.latitude;
                const user_lon = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                
                console.log('User Location:', user_lat, user_lon);
                
                formData.append('latitude', user_lat);
                formData.append('longitude', user_lon);
                formData.append('accuracy', accuracy);

                fetch('process_attendance.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Server Response:', data);
                    if (data.success) {
                        showMessage('success', data.message);
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage('error', data.message);
                        resetButton();
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    showMessage('error', 'Network error. Please check your connection and try again.');
                    resetButton();
                });
            },
            function(error) {
                console.error('Geolocation Error:', error);
                let errorMessage = '❌ Location access required to mark attendance.';
                if (error.code === error.PERMISSION_DENIED) {
                    errorMessage = '❌ Location permission denied. Please allow location access in your browser settings.';
                } else if (error.code === error.TIMEOUT) {
                    errorMessage = '❌ Location request timeout. Please try again.';
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    errorMessage = '❌ Location information unavailable. Please check your device location settings.';
                }
                showMessage('error', errorMessage);
                resetButton();
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    }

    // Show loading
    function showLoading() {
        const button = document.querySelector('.attendance-btn');
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking Location...';
        button.disabled = true;
    }

    // Reset button
    function resetButton() {
        const button = document.querySelector('.attendance-btn');
        const attendanceStatus = '<?php echo $attendance_status; ?>';
        button.innerHTML = `<i class="fas fa-fingerprint me-2"></i>${attendanceStatus === 'arrived' ? 'Mark Departure' : 'Mark Arrival'}`;
        button.disabled = false;
    }

    // Show message
    function showMessage(type, message) {
        const messageDiv = document.getElementById('message');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        
        messageDiv.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show">
                <i class="fas ${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
    </script>
</body>
</html>