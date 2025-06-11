<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'todo_list');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if (!$conn) {
    die("ERROR: Could not connect to MySQL. " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    // Select the database
    mysqli_select_db($conn, DB_NAME);
    
    // Create users table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);
    
    // Create tasks table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS tasks (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        task VARCHAR(255) NOT NULL,
        status TINYINT(1) NOT NULL DEFAULT 0,
        due_time DATETIME NULL,
        completed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);
    
    // Add new columns to existing tasks table if they don't exist
    $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM tasks LIKE 'due_time'");
    if(mysqli_num_rows($check_columns) == 0) {
        mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN due_time DATETIME NULL AFTER status");
    }
    
    $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM tasks LIKE 'completed_at'");
    if(mysqli_num_rows($check_columns) == 0) {
        mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN completed_at TIMESTAMP NULL AFTER due_time");
    }
    
    // Create task_history table for deleted completed tasks
    $sql = "CREATE TABLE IF NOT EXISTS task_history (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        task VARCHAR(255) NOT NULL,
        completed_at TIMESTAMP NOT NULL,
        deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);
    
} else {
    echo "Error creating database: " . mysqli_error($conn);
}
?>