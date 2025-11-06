<?php
require_once 'db_config.php';
session_start();

// Check if user is logged in and approved
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_approved']) || $_SESSION['is_approved'] != 1) {
    echo "ERROR: User not logged in or not approved";
    exit();
}

$user_id = $_SESSION['user_id'];
$device_id = $_SESSION['device_id'];

// Check working hours
$current_hour = date('H');
if ($current_hour < 6 || $current_hour >= 17) {
    echo "ERROR: Outside working hours (6:00 AM - 5:00 PM)";
    exit();
}

// Get location from query parameters (from QR code scan)
$user_lat = $_GET['lat'] ?? null;
$user_lon = $_GET['lon'] ?? null;

// If no location in QR code, try to get current location
if (!$user_lat || !$user_lon) {
    // This would be called via JavaScript geolocation
    echo "ERROR: Location data required";
    exit();
}

// Verify location is within company area
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

$company_lat = 5.697908841689993;
$company_lon = -0.1765738052755317;
$allowed_radius = 200;

$distance = calculateDistance($user_lat, $user_lon, $company_lat, $company_lon);

if ($distance > $allowed_radius) {
    echo "ERROR: You are " . round($distance) . "m away from company. Must be within 200m.";
    exit();
}

// Process attendance
try {
    $today = date('Y-m-d');
    $current_time = date('H:i:s');

    // Check existing attendance for today
    $stmt = $pdo->prepare("SELECT id, time, departure_time FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $today]);
    
    if ($stmt->rowCount() > 0) {
        $attendance = $stmt->fetch();
        
        // If already has departure time
        if (!empty($attendance['departure_time'])) {
            echo "SUCCESS: Attendance already completed for today";
            exit();
        }
        
        // Mark departure
        $update_stmt = $pdo->prepare("UPDATE attendance SET departure_time = ? WHERE id = ?");
        $update_stmt->execute([$current_time, $attendance['id']]);
        
        echo "SUCCESS: Departure recorded successfully at " . date('h:i A');
        exit();
    } else {
        // Mark arrival
        $insert_stmt = $pdo->prepare("INSERT INTO attendance (user_id, name, status, date, time, ip_address, device_id, user_latitude, user_longitude, location_status) 
                                     SELECT ?, name, status, ?, ?, ?, ?, ?, ?, 'verified' 
                                     FROM users WHERE id = ?");
        $insert_stmt->execute([$user_id, $today, $current_time, $_SERVER['REMOTE_ADDR'], $device_id, $user_lat, $user_lon, $user_id]);
        
        echo "SUCCESS: Arrival recorded successfully at " . date('h:i A');
        exit();
    }

} catch(PDOException $e) {
    error_log("Attendance processing error: " . $e->getMessage());
    echo "ERROR: System error processing attendance";
    exit();
}
?>