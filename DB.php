<?php

// Include the database class file
require_once __DIR__ . '/vendor/autoload.php';

// Database connection details
$host = '127.0.0.1';        // Your MySQL server
$username = 'root';         // Your MySQL username
$password = '';     // Your MySQL password
$dbname = 'food_rescue_app';    // Your database name

$db = null;
// Create a new database instance
try {
    $db = new MysqliDb($host, $username, $password, $dbname);
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}

function closeDBConnection(){
    if(isset($db)) {
        $db->disconnect();
        $db = null;
    }
    exit;
}