<?php
 // Session security settings
  ini_set('session.cookie_httponly', 1);
  ini_set('session.cookie_secure', 1);
  ini_set('session.use_strict_mode', 1);


if(session_status()===PHP_SESSION_NONE){
    session_start();
  }

 

  //set session timeout (30 minutes)
  $session_timeout = 1800;

  if (isset($_SESSION['admin_logged_in']) && isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] > $session_timeout) {
      //session expired
      session_unset();
      session_destroy();
      header("Location: admin_login.php?expired=1");
      exit();
    } else {
      // update login time on activity
      $_SESSION['login_time'] = time();
    }
  }

  date_default_timezone_set('Africa/Accra'); // Set your desired timezone

  $host = "sql100.infinityfree.com";
  $dbname = "if0_40130084_qrattendance";
  $username = "if0_40130084";
  $password = "LZ3nm0tQe2Z";

  error_reporting(E_ALL);
  ini_set('displaying_errors', 1);


  try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // echo "MYsql connection successful!";
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("SET NAMES utf8");

  } catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
  }

  // Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is admin
function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Redirect if not admin
function redirect_if_not_admin() {
    if (!is_admin_logged_in()) {
        header("Location: admin_login.php");
        exit();
    }
}

// IP-based security function
function get_client_ip() {
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    return $_SERVER['HTTP_CLIENT_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    return $_SERVER['HTTP_X_FORWARDED_FOR'];
  } else {
    return $_SERVER['REMOTE_ADDR'];
  }
}

?>