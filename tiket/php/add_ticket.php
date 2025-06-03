<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = htmlspecialchars($_POST['event_name']);
    $date = $_POST['date'];
    $price = $_POST['price'];

    // File upload
    $image = $_FILES['image']['name'];
    $target = "../images/" . basename($image);

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        die("Invalid file type.");
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        $stmt = $conn->prepare("INSERT INTO tickets (event_name, date, price, image) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$event_name, $date, $price, $image])) {
            header("Location: ../admin.php?success=1");
            exit();
        } else {
            die("Failed to add ticket.");
        }
    } else {
        die("Failed to upload image.");
    }
}
?>
