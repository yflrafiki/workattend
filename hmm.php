<?php
require_once 'db_config.php';
try {
  $stmt = $pdo->query("SELECT NOW()");
  echo "✅ DB Connected!";
} catch (PDOException $e) {
  echo "❌ DB Error: " . $e->getMessage();
}
?>
