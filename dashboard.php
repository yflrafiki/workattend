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