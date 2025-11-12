<?php
// Railway healthcheck test file
echo "OK - RTS Ticket System is running!";
echo "\n";
echo "PHP Version: " . PHP_VERSION;
echo "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');
?>