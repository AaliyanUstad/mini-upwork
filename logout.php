<?php
include_once 'includes/session.php';
Session::destroy();
header("Location: login.php");
exit();
?>