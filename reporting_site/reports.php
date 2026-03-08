<?php
session_start();

echo 'reports page reached<br>';
echo '<pre>';
var_dump($_SESSION);
echo '</pre>';
exit();
?>