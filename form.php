<?php
require_once 'db_config.php';
session_start();

// Simple device ID using cookies
function getDeviceId() {
    if (!isset($_COOKIE['attendance_device_id'])) {
        $device_id = 'device_' . uniqid() . '_' . bin2hex(random_bytes(8));
        setcookie('attendance_device_id', $device_id, time() + (365 * 24 * 60 * 60), '/');
        return $device_id;
    }
    return $_COOKIE['attendance_device_id'];
}

// Distance calculation function
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000;
    
    $latFrom = deg2rad($lat1);
    $lonFrom = deg2rad($lon1);
    $latTo = deg2rad($lat2);
    $lonTo = deg2rad($lon2);
    
    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;
    
    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $status = $_POST['status'];
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    $current_hour = date('H');
    
    // Get basic info
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $device_id = getDeviceId();
    
    // 1. Validate working hours (6am to 5pm)
    if($current_hour < 6 || $current_hour >= 17){
        $_SESSION['error'] = "⏰ Outside Working Hours (6:00 AM - 5:00 PM)";
        header("Location: form.php");
        exit();
    }

    // 2. Validate inputs
    if (empty($name) || empty($status)) {
        $_SESSION['error'] = "⚠️ Please fill in all fields!";
        header("Location: form.php");
        exit();
    }

    // 3. Validate name format
    if (!preg_match("/^[A-Za-z\s]{2,100}$/", $name)) {
        $_SESSION['error'] = "⚠️ Please enter a valid name (2-100 characters, letters only)";
        header("Location: form.php");
        exit();
    }

    try {
        // Check if this person already submitted today
        $stmt = $pdo->prepare("SELECT id, time, departure_time FROM attendance WHERE name = ? AND date = ?");
        $stmt->execute([$name, $today]);
        
        if ($stmt->rowCount() > 0) {
            $existing_record = $stmt->fetch();
            
            // If person already has BOTH arrival and departure
            if (!empty($existing_record['departure_time'])) {
                $_SESSION['error'] = "✅ " . $name . " has already completed attendance for today (Arrival: " . date('h:i A', strtotime($existing_record['time'])) . " | Departure: " . date('h:i A', strtotime($existing_record['departure_time'])) . ")";
                header("Location: form.php");
                exit();
            }
            
            // If person has only arrival - mark departure (AUTO DEPARTURE)
            $update_stmt = $pdo->prepare("UPDATE attendance SET departure_time = ? WHERE id = ?");
            $update_stmt->execute([$current_time, $existing_record['id']]);
            
            $_SESSION['success'] = "🚪 Departure recorded for " . $name . " at " . date('h:i A') . " (Arrival: " . date('h:i A', strtotime($existing_record['time'])) . ")";
            header("Location: form.php");
            exit();
        }

        // Check if this DEVICE already submitted today
        $stmt = $pdo->prepare("SELECT name, time, departure_time FROM attendance WHERE device_id = ? AND date = ?");
        $stmt->execute([$device_id, $today]);
        
        if ($stmt->rowCount() > 0) {
            $existing_record = $stmt->fetch();
            
            // If device user hasn't marked departure yet, allow them to mark departure
            if (empty($existing_record['departure_time'])) {
                // Auto-mark departure for the existing record
                $update_stmt = $pdo->prepare("UPDATE attendance SET departure_time = ? WHERE device_id = ? AND date = ?");
                $update_stmt->execute([$current_time, $device_id, $today]);
                
                $_SESSION['success'] = "🚪 Departure recorded for " . $existing_record['name'] . " at " . date('h:i A') . " (Arrival: " . date('h:i A', strtotime($existing_record['time'])) . ")";
                header("Location: form.php");
                exit();
            } else {
                $_SESSION['error'] = "📱 This device already completed attendance for " . $existing_record['name'] . " today";
                header("Location: form.php");
                exit();
            }
        }
        
        // Insert NEW arrival record (first time submission)
        $user_lat = $_POST['latitude'] ?? null;
        $user_lon = $_POST['longitude'] ?? null;
        $location_status = 'not_verified';

        // STRICT LOCATION VERIFICATION - BLOCK IF NO LOCATION OR OUTSIDE RANGE
        if (empty($user_lat) || empty($user_lon)) {
            // BLOCK if no location data
            $_SESSION['error'] = "❌ Location verification failed. Please enable location access and try again.";
            header("Location: form.php");
            exit();
        }

        // VERIFIED COMPANY COORDINATES - Updated with your exact location
        $company_lat = 5.697908841689993;
        $company_lon = -0.1765738052755317;
        $allowed_radius = 100;

        $distance = calculateDistance($user_lat, $user_lon, $company_lat, $company_lon);

        if ($distance <= $allowed_radius) {
            $location_status = 'verified';
        } else {
            // BLOCK if outside range
            $_SESSION['error'] = "❌ ACCESS DENIED! You are " . round($distance) . "m away from company location. You must be within 100m.";
            header("Location: form.php");
            exit();
        }
        
        $stmt = $pdo->prepare("INSERT INTO attendance (name, status, date, time, ip_address, device_id, user_latitude, user_longitude, location_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $status, $today, $current_time, $user_ip, $device_id, $user_lat, $user_lon, $location_status]);
        
        $_SESSION['success'] = "✅ Arrival recorded for " . $name . " at " . date('h:i A');
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "⚠️ System error. Please try again.";
        error_log("Attendance error: " . $e->getMessage());
    }
    
    header("Location: form.php");
    exit();
}

// Check user and device status
$device_id = getDeviceId();
$today = date('Y-m-d');
$device_used_today = false;
$existing_name = '';
$user_status = 'new'; // new, arrived, completed
$user_record = null;

try {
    // Check device usage
    $stmt = $pdo->prepare("SELECT name, time, departure_time FROM attendance WHERE device_id = ? AND date = ?");
    $stmt->execute([$device_id, $today]);
    if ($stmt->rowCount() > 0) {
        $device_used_today = true;
        $user_record = $stmt->fetch();
        $existing_name = $user_record['name'];
        $existing_time = $user_record['time'];
        
        if (!empty($user_record['departure_time'])) {
            $user_status = 'completed';
        } else {
            $user_status = 'arrived';
        }
    }
} catch(PDOException $e) {
    $device_used_today = false;
}

$current_hour = date('H');
$is_working_hours = ($current_hour >= 6 && $current_hour < 17);
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
        .status-badge {
            font-size: 0.9em;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .arrival-status { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .departure-status { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .completed-status { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .auto-departure-btn { background: #ffc107; border-color: #ffc107; color: #000; }
        .auto-departure-btn:hover { background: #e0a800; border-color: #e0a800; color: #000; }
        .location-test-btn { transition: all 0.3s ease; }
        .location-error { background: #dc3545 !important; color: white !important; }
        .location-success { background: #198754 !important; color: white !important; }
        .loading-spinner { display: none; }
        .location-status { font-size: 0.8em; margin-top: 5px; }
        #locationHelp { display: none; }
        .coordinates-info { background: #e7f3ff; border-left: 4px solid #0d6efd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Company Attendance</h4>
                        <small>Working Hours: 6:00 AM - 5:00 PM</small>
                    </div>
                    
                    <div class="card-body">
                        <!-- Location Status -->
                        <div class="alert alert-success coordinates-info">
                            <strong>✅ Location System Updated</strong><br>
                            <small>Company coordinates verified: 5.697908841689993, -0.1765738052755317</small>
                        </div>

                        <div class="alert alert-info" id="locationStatus">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><i class="fas fa-map-marker-alt"></i> Location Verification</strong>
                                    <div class="location-status" id="locationStatusText">System ready - Test your location</div>
                                </div>
                                <button onclick="testLocation()" class="btn btn-info btn-sm location-test-btn" id="locationTestBtn">
                                    <i class="fas fa-satellite-dish"></i> Test Location
                                </button>
                            </div>
                            <div class="loading-spinner mt-2 text-center" id="locationLoading">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <small class="ms-2">Checking your location...</small>
                            </div>
                        </div>

                        <!-- Mobile Location Help -->
                        <div class="alert alert-warning" id="locationHelp">
                            <h6><i class="fas fa-mobile-alt"></i> Mobile Tips:</h6>
                            <ul class="small mb-0">
                                <li>Enable Location/GPS in your phone settings</li>
                                <li>Allow location access when prompted</li>
                                <li>Use Chrome or Safari browser</li>
                                <li>Test location first before marking attendance</li>
                            </ul>
                        </div>

                        <!-- User Status -->
                        <?php if ($device_used_today && $user_status === 'arrived'): ?>
                            <div class="alert alert-warning">
                                <strong>👤 Ready for Departure</strong><br>
                                <strong><?php echo $existing_name; ?></strong> arrived at <?php echo date('h:i A', strtotime($user_record['time'])); ?><br>
                                <span class="badge arrival-status">Click below to mark departure</span>
                            </div>
                        <?php elseif ($device_used_today && $user_status === 'completed'): ?>
                            <div class="alert alert-success">
                                <strong>✅ Attendance Completed</strong><br>
                                <strong><?php echo $existing_name; ?></strong><br>
                                Arrival: <?php echo date('h:i A', strtotime($user_record['time'])); ?> | 
                                Departure: <?php echo date('h:i A', strtotime($user_record['departure_time'])); ?>
                                <span class="badge completed-status">Completed for today</span>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <strong>✅ Device Ready</strong><br>
                                This device can submit attendance
                            </div>
                        <?php endif; ?>

                        <!-- Working Hours -->
                        <?php if (!$is_working_hours): ?>
                            <div class="alert alert-warning">
                                <strong>⏰ Outside Working Hours</strong><br>
                                Current time: <?php echo date('h:i A'); ?><br>
                                Attendance can only be submitted between 6:00 AM - 5:00 PM
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <strong>✅ Within Working Hours</strong><br>
                                You can submit your attendance now
                            </div>
                        <?php endif; ?>

                        <!-- Messages -->
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>

                        <!-- Attendance Form -->
                        <form method="POST" id="attendanceForm">
                            <?php if (!$device_used_today || $user_status === 'new'): ?>
                                <!-- New User Form -->
                                <div class="mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="name" required 
                                           pattern="[A-Za-z\s]{2,100}" 
                                           placeholder="Enter your full name"
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                    <div class="form-text">Enter your official name (letters and spaces only)</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Status *</label>
                                    <select class="form-select" name="status" required>
                                        <option value="">Select your status</option>
                                        <option value="Staff" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Staff') ? 'selected' : ''; ?>>Staff</option>
                                        <option value="Intern" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Intern') ? 'selected' : ''; ?>>Intern</option>
                                    </select>
                                </div>
                            <?php else: ?>
                                <!-- Auto-filled for existing users -->
                                <input type="hidden" name="name" value="<?php echo $existing_name; ?>">
                                <input type="hidden" name="status" value="<?php echo $user_record['status'] ?? 'Staff'; ?>">
                            <?php endif; ?>
                            
                            <?php if ($device_used_today && $user_status === 'completed'): ?>
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-check-circle"></i> Your attendance is complete for today.
                                </div>
                                <button type="button" class="btn btn-success w-100" disabled>
                                    <i class="fas fa-check-double"></i> Attendance Completed
                                </button>
                            <?php elseif ($device_used_today && $user_status === 'arrived'): ?>
                                <button type="submit" class="btn auto-departure-btn w-100" id="submitBtn">
                                    <i class="fas fa-sign-out-alt"></i> Mark Departure Time
                                </button>
                                <div class="form-text text-center mt-2">
                                    <strong><?php echo $existing_name; ?></strong> - Arrived at <?php echo date('h:i A', strtotime($user_record['time'])); ?>
                                </div>
                            <?php elseif (!$is_working_hours): ?>
                                <div class="alert alert-warning text-center">
                                    <i class="fas fa-clock"></i> Outside working hours.
                                </div>
                                <button type="button" class="btn btn-secondary w-100" disabled>Outside Working Hours</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                    <i class="fas fa-sign-in-alt"></i> Mark Arrival Time
                                </button>
                            <?php endif; ?>
                        </form>

                        <!-- Quick Actions -->
                        <div class="mt-3 text-center">
                            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-tachometer-alt"></i> View Dashboard
                            </a>
                            <?php if ($device_used_today): ?>
                                <button onclick="clearDeviceCookie()" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-sync"></i> Clear Device Cache
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Clear device cookie (for testing/debugging)
    function clearDeviceCookie() {
        document.cookie = "attendance_device_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        alert('Device cache cleared. Page will reload.');
        location.reload();
    }

    // Update location status display
    function updateLocationStatus(message, isError = false, isSuccess = false) {
        const statusText = document.getElementById('locationStatusText');
        const testBtn = document.getElementById('locationTestBtn');
        const statusAlert = document.getElementById('locationStatus');
        const locationHelp = document.getElementById('locationHelp');
        
        statusText.textContent = message;
        
        if (isError) {
            statusAlert.className = 'alert alert-danger';
            testBtn.classList.add('location-error');
            testBtn.classList.remove('location-success');
            locationHelp.style.display = 'block';
        } else if (isSuccess) {
            statusAlert.className = 'alert alert-success';
            testBtn.classList.add('location-success');
            testBtn.classList.remove('location-error');
            locationHelp.style.display = 'none';
        } else {
            statusAlert.className = 'alert alert-info';
            testBtn.classList.remove('location-error', 'location-success');
            locationHelp.style.display = 'block';
        }
    }

    // Test location first
    function testLocation() {
        const testBtn = document.getElementById('locationTestBtn');
        const loadingSpinner = document.getElementById('locationLoading');
        const originalHtml = testBtn.innerHTML;
        
        // Show loading state
        testBtn.innerHTML = '<i class="fas fa-satellite"></i> Testing...';
        testBtn.disabled = true;
        loadingSpinner.style.display = 'block';
        updateLocationStatus('Detecting your location...');

        if (!navigator.geolocation) {
            updateLocationStatus('❌ Browser does not support location services', true);
            testBtn.innerHTML = '<i class="fas fa-satellite-dish"></i> Test Location';
            testBtn.disabled = false;
            loadingSpinner.style.display = 'none';
            alert('❌ Your browser does not support location services. Please use Chrome or Safari on your phone.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const user_lat = position.coords.latitude;
                const user_lon = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                
                // UPDATED COMPANY COORDINATES - Your exact location
                const company_lat = 5.697908841689993;
                const company_lon = -0.1765738052755317;
                const allowed_radius = 100;
                
                // Calculate distance
                const distance = calculateDistance(user_lat, user_lon, company_lat, company_lon);
                
                let message = `📍 Your Location:\n` +
                             `Latitude: ${user_lat.toFixed(6)}\n` +
                             `Longitude: ${user_lon.toFixed(6)}\n` +
                             `Accuracy: ${Math.round(accuracy)} meters\n\n` +
                             `🏢 Company Location:\n` +
                             `Latitude: ${company_lat}\n` +
                             `Longitude: ${company_lon}\n\n` +
                             `📏 Distance: ${Math.round(distance)} meters\n` +
                             `✅ Required: Within ${allowed_radius} meters\n\n`;
                
                if (distance <= allowed_radius) {
                    message += `🎉 SUCCESS! You are at the company location and can mark attendance.`;
                    updateLocationStatus(`✅ Location working! You are ${Math.round(distance)}m away`, false, true);
                } else {
                    message += `❌ ACCESS DENIED! You are too far away.`;
                    updateLocationStatus(`❌ You are ${Math.round(distance)}m away (must be within ${allowed_radius}m)`, true);
                }
                
                alert(message);
                testBtn.innerHTML = originalHtml;
                testBtn.disabled = false;
                loadingSpinner.style.display = 'none';
            },
            function(error) {
                testBtn.innerHTML = originalHtml;
                testBtn.disabled = false;
                loadingSpinner.style.display = 'none';
                
                let message = 'Location access denied. ';
                let statusMessage = '';
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = '❌ Location permission denied.\n\nPlease:\n1. Click "Allow" when asked for location access\n2. Check browser settings if you accidentally denied\n3. Refresh the page and try again';
                        statusMessage = '❌ Location permission denied. Please allow access.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = '❌ Location unavailable.\n\nPlease:\n1. Turn on device Location/GPS\n2. Check location settings\n3. Ensure you have internet connection';
                        statusMessage = '❌ Location unavailable. Check device settings.';
                        break;
                    case error.TIMEOUT:
                        message = '❌ Location request timeout.\n\nPlease:\n1. Ensure location services are enabled\n2. Try again in a better signal area\n3. Check internet connection';
                        statusMessage = '❌ Location timeout. Please try again.';
                        break;
                    default:
                        message = '❌ Location error: ' + error.message;
                        statusMessage = '❌ Location error occurred.';
                }
                
                updateLocationStatus(statusMessage, true);
                alert(message);
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    }

    // Distance calculation function
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const earthRadius = 6371000;
        const latFrom = deg2rad(lat1);
        const lonFrom = deg2rad(lon1);
        const latTo = deg2rad(lat2);
        const lonTo = deg2rad(lon2);
        
        const latDelta = latTo - latFrom;
        const lonDelta = lonTo - lonFrom;
        
        const angle = 2 * Math.asin(Math.sqrt(Math.pow(Math.sin(latDelta / 2), 2) +
            Math.cos(latFrom) * Math.cos(latTo) * Math.pow(Math.sin(lonDelta / 2), 2)));
        
        return angle * earthRadius;
    }

    function deg2rad(deg) {
        return deg * (Math.PI/180);
    }

    // STRICT Location verification - BLOCKS submissions from outside company
    document.getElementById('attendanceForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Stop form submission
        
        if (!navigator.geolocation) {
            alert('❌ Your browser does not support location services. Cannot mark attendance.');
            return;
        }

        // Show loading message
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking Location...';
        submitBtn.disabled = true;

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const user_lat = position.coords.latitude;
                const user_lon = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                
                // UPDATED COMPANY COORDINATES - Your exact location
                const company_lat = 5.697908841689993;
                const company_lon = -0.1765738052755317;
                const allowed_radius = 100;
                
                // Calculate distance
                const distance = calculateDistance(user_lat, user_lon, company_lat, company_lon);
                
                if (distance <= allowed_radius) {
                    // Location is good - submit the form
                    submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Location Verified! Submitting...';
                    
                    // Add location data to form as hidden fields
                    const form = e.target;
                    const latInput = document.createElement('input');
                    const lonInput = document.createElement('input');
                    latInput.type = 'hidden';
                    lonInput.type = 'hidden';
                    latInput.name = 'latitude';
                    lonInput.name = 'longitude';
                    latInput.value = user_lat;
                    lonInput.value = user_lon;
                    form.appendChild(latInput);
                    form.appendChild(lonInput);
                    
                    // Submit the form
                    form.submit();
                } else {
                    // Location is outside company - BLOCK submission
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    alert('❌ ACCESS DENIED! You are ' + Math.round(distance) + 'm away from company location. You must be within ' + allowed_radius + 'm to mark attendance.\n\nPlease come to the company location to mark attendance.');
                }
            },
            function(error) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                let message = 'Location access denied. ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = '❌ Location permission denied. You MUST allow location access to mark attendance.\n\nPlease:\n1. Allow location access for this website\n2. Refresh the page and try again\n3. Check browser settings if location is blocked';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = '❌ Location unavailable. Please check your device location settings and ensure GPS is enabled.';
                        break;
                    case error.TIMEOUT:
                        message = '❌ Location request timeout. Please try again.';
                        break;
                }
                alert(message);
            },
            {
                enableHighAccuracy: true,
                timeout: 20000, // Increased timeout for mobile devices
                maximumAge: 0
            }
        );
    });

    // Initialize location check when page loads
    window.addEventListener('load', function() {
        updateLocationStatus('System ready - Test your location first');
        
        // Show mobile help by default
        const locationHelp = document.getElementById('locationHelp');
        locationHelp.style.display = 'block';
        
        // Test if geolocation is available
        if (!navigator.geolocation) {
            updateLocationStatus('❌ Browser does not support location services', true);
        }
    });
    </script>
</body>
</html>