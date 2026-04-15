<?php
session_start();
session_unset();
session_destroy();
include '../conn.php';

    header('location: ../auth/login.php');
?>