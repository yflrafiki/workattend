<?php
require_once 'db_config.php';

echo "<h2>Updating Database for Security</h2>";
echo "<pre>";

try {
    // Add IP address column if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM attendance LIKE 'ip_address'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        $pdo->exec("ALTER TABLE attendance ADD COLUMN ip_address VARCHAR(45)");
        echo "✅ Added ip_address column\n";
    } else {
        echo "✅ ip_address column already exists\n";
    }
    
    // Add unique constraint for IP + date
    try {
        $pdo->exec("ALTER TABLE attendance ADD UNIQUE unique_ip_date (ip_address, date)");
        echo "✅ Added unique constraint for IP+date\n";
    } catch (Exception $e) {
        echo "ℹ️ Unique constraint may already exist\n";
    }
    
    echo "\n🎉 Database updated successfully!\n";
    echo "Now each device can only submit attendance once per day.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='form.php' class='btn btn-success'>Test Attendance Form</a>";
?>