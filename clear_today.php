<?php
require_once 'db_config.php';

echo "<h2>Clear Today's Attendance for Testing</h2>";
echo "<pre>";

try {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("DELETE FROM attendance WHERE date = ?");
    $stmt->execute([$today]);
    $deleted = $stmt->rowCount();
    
    echo "✅ Deleted $deleted attendance records for today ($today)\n";
    echo "🎉 All devices can now submit attendance again!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='form.php' class='btn btn-success'>Test Attendance Form</a>";
echo "<br><br><small>Use this only for testing. In production, devices can submit again tomorrow automatically.</small>";
?>