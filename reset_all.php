<?php
require_once 'db_config.php';

echo "<h2>Reset All Attendance Data</h2>";
echo "<pre>";

try {
    // Delete all attendance records
    $stmt = $pdo->prepare("DELETE FROM attendance");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    
    echo "✅ Deleted $deleted attendance records\n";
    echo "🎉 All devices can now submit attendance again!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='form.php' class='btn btn-success'>Test Fixed Form</a>";
?>