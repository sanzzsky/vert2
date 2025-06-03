<?php
$host = 'localhost';
$db = 'concert_tickets';
$user = 'root';  // change as necessary
$pass = '';      // change as necessary

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
