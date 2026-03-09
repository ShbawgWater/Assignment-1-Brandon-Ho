<?php
// db_connect.php 
function get_db_connection() {
    $db_path = __DIR__ . '\data\stocks.db';

    try {
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die('<p class="error">Database connection failed: ' . $e->getMessage() . '</p>');
    }
}
?>
