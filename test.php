<?php
// test.php - Simple cookie clearer
setcookie('attendance_device_id', '', time() - 3600, '/');
echo "✅ Cookie cleared! <a href='form.php'>Go to attendance form</a>";
?>