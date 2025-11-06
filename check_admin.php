<?php
require_once 'db_config.php';

echo "<h2>Check Admin User in Database</h2>";
echo "<pre>";

try {
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();

    if ($admin) {
        echo "✅ Admin user found:\n";
        echo "ID: " . $admin['id'] . "\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Password Hash: " . $admin['password'] . "\n";
        echo "Created: " . $admin['created_at'] . "\n\n";

        // Test password verification
        echo "Password Verification Tests:\n";
        
        $passwords_to_test = [
            'admin123',
            'admin',
            'password', 
            '123456',
            'Admin123'
        ];

        foreach ($passwords_to_test as $pwd) {
            $result = password_verify($pwd, $admin['password']);
            echo "  Testing '$pwd': " . ($result ? "✅ MATCH" : "❌ NO MATCH") . "\n";
        }

        echo "\n";

        // Check hash info
        echo "Hash Information:\n";
        $hash_info = password_get_info($admin['password']);
        echo "  Algorithm: " . ($hash_info['algo'] ?? 'Unknown') . "\n";
        echo "  Algorithm Name: " . ($hash_info['algoName'] ?? 'Unknown') . "\n";
        echo "  Options: " . print_r($hash_info['options'] ?? [], true) . "\n";

    } else {
        echo "❌ No admin user found in database!\n";
        
        // Create admin user
        echo "Creating admin user...\n";
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmt->execute(['admin', $hashed_password]);
        echo "✅ Admin user created with password: admin123\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Show all admin users
echo "<h3>All Admin Users:</h3>";
try {
    $stmt = $pdo->query("SELECT id, username, created_at FROM admin");
    $admins = $stmt->fetchAll();

    if (empty($admins)) {
        echo "No admin users found.";
    } else {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Username</th><th>Created</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr>";
            echo "<td>" . $admin['id'] . "</td>";
            echo "<td>" . $admin['username'] . "</td>";
            echo "<td>" . $admin['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error fetching admin users: " . $e->getMessage();
}

echo "<br><br><a href='admin_login.php' class='btn btn-primary'>Back to Login</a>";
?>