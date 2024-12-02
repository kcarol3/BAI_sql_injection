<?php
session_start();

if (isset($_SESSION['expiry_time']) && time() > $_SESSION['expiry_time']) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
} else {
    $_SESSION['expiry_time'] = time() + 10;
}

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

echo 'Welcome '. $_SESSION['user'].'<BR/>';

