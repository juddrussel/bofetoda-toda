<?php

require 'driver-database.php';

if (!empty($_SESSION['logged_in'])) {
    header('Location: driver-home.php');
} else {
    header('Location: driver-login.php');
}
exit;