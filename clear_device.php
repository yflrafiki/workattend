<?php
session_start();
setcookie('attendance_device_id', '', time() - 3600, '/');
$_SESSION['success'] = "Device cookie cleared. You can test again.";
header("Location: form.php");
exit();
?>