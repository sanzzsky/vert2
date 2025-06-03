<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.html");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->execute([$id]);
}
header("Location: ../admin.php");
exit();
?>
