<?php
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Environment variables:<br>";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "<br>";
echo "DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET') . "<br>";
echo "DB_USER: " . (getenv('DB_USER') ?: 'NOT SET') . "<br>";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET') . "<br>";
phpinfo();
?>
