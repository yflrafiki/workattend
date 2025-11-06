<?php
require_once 'db_config.php';
session_start();

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$user_id = $_POST['user_id'] ?? null;
$device_id = $_POST['device_id'] ?? null;
$user_lat = $_POST['latitude'] ?? null;
$user_lon = $_POST['longitude'] ?? null;

// Validate inputs
if (empty($user_id) || empty($device_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required data: user_id or device_id']);
    exit();
}

// Verify user exists and is approved
try {
    $stmt = $pdo->prepare("SELECT id, name, status, device_id, is_approved FROM users WHERE id = ? AND device_id = ?");
    $stmt->execute([$user_id, $device_id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found or device not registered']);
        exit();
    }

    $user = $stmt->fetch();
    
    if (!$user['is_approved']) {
        echo json_encode(['success' => false, 'message' => 'Account not approved. Please contact administrator.']);
        exit();
    }

} catch(PDOException $e) {
    error_log("User verification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: User verification failed - ' . $e->getMessage()]);
    exit();
}

// Check location
if (empty($user_lat) || empty($user_lon)) {
    echo json_encode(['success' => false, 'message' => 'Location verification failed - no location data received']);
    exit();
}

// Verify location is within company area
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meters
    
    $latFrom = deg2rad($lat1);
    $lonFrom = deg2rad($lon1);
    $latTo = deg2rad($lat2);
    $lonTo = deg2rad($lon2);
    
    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;
    
    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

// Company location coordinates
$company_lat = 5.697855462553735;
$company_lon = -0.1766167206202497;
$allowed_radius = 200; // 200 meters

$distance = calculateDistance($user_lat, $user_lon, $company_lat, $company_lon);

if ($distance > $allowed_radius) {
    echo json_encode(['success' => false, 'message' => 'You are ' . round($distance) . 'm away from company. Must be within 200m.']);
    exit();
}

// Process attendance
try {
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    $current_hour = date('H');
    $current_minute = date('i');

    // Check existing attendance for today
    $stmt = $pdo->prepare("SELECT id, time, departure_time FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $today]);
    
    if ($stmt->rowCount() > 0) {
        $attendance = $stmt->fetch();
        
        // If already has departure time
        if (!empty($attendance['departure_time'])) {
            echo json_encode(['success' => false, 'message' => '✅ Attendance already completed for today.']);
            exit();
        }
        
        // MARK DEPARTURE - Only allow after 5:00 PM
        if ($current_hour < 17) {
            // Before 5:00 PM - not allowed to mark departure
            $time_until_5pm = '';
            if ($current_hour < 17) {
                $hours_left = 16 - $current_hour;
                $minutes_left = 60 - $current_minute;
                $time_until_5pm = " You can mark departure after 5:00 PM (" . ($hours_left + 1) . " hours " . $minutes_left . " minutes from now).";
            }
            
            echo json_encode(['success' => false, 'message' => '❌ Departure can only be marked after 5:00 PM.' . $time_until_5pm]);
            exit();
        }
        
        // Mark departure (after 5:00 PM)
        $update_stmt = $pdo->prepare("UPDATE attendance SET departure_time = ? WHERE id = ?");
        $result = $update_stmt->execute([$current_time, $attendance['id']]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => '✅ Departure recorded successfully at ' . date('h:i A')]);
        } else {
            echo json_encode(['success' => false, 'message' => '❌ Failed to record departure']);
        }
        exit();
        
    } else {
        // MARK ARRIVAL - Only allow during working hours (6:00 AM - 5:00 PM)
        if ($current_hour < 6 || $current_hour >= 17) {
            echo json_encode(['success' => false, 'message' => '❌ Arrival can only be marked during working hours (6:00 AM - 5:00 PM). Current time: ' . date('h:i A')]);
            exit();
        }
        
        // Insert arrival record without IP address
        $insert_stmt = $pdo->prepare("INSERT INTO attendance (user_id, name, status, date, time, device_id, user_latitude, user_longitude, location_status) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'verified')");
        $result = $insert_stmt->execute([
            $user_id, 
            $user['name'], 
            $user['status'], 
            $today, 
            $current_time, 
            $device_id, 
            $user_lat, 
            $user_lon
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => '✅ Arrival recorded successfully at ' . date('h:i A')]);
        } else {
            echo json_encode(['success' => false, 'message' => '❌ Failed to record arrival']);
        }
        exit();
    }

} catch(PDOException $e) {
    error_log("Attendance processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>