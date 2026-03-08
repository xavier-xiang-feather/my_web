<?php
session_start();

define('APP_USERNAME', 'grader');

define('APP_PASSWORD', password_hash('cse135', PASSWORD_DEFAULT));
?>