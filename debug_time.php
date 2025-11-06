<?php
require_once 'db_config.php';

echo "<h2>Time Debug</h2>";
echo "<pre>";

echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Timezone: " . date_default_timezone_get() . "\n";
echo "Current Timestamp: " . time() . "\n";

// Test inserting a record with current time
$test_time = date('H:i:s');
echo "Time that would be inserted: " . $test_time . "\n";

// Check latest records
$stmt = $pdo->query("SELECT name, date, time, created_at FROM attendance ORDER BY id DESC LIMIT 3");
$records = $stmt->fetchAll();

echo "\nLatest Attendance Records:\n";
foreach ($records as $record) {
    echo "- " . $record['name'] . " | Date: " . $record['date'] . " | Time: " . $record['time'] . " | Created: " . $record['created_at'] . "\n";
}

echo "</pre>";
?>