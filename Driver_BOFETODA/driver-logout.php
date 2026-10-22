<?php

require 'driver-database.php';
do_logout();
header('Location: driver-login.php');
exit;