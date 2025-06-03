<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticket_id = intval($_POST['ticket_id']);
    $buyer_name = htmlspecialchars($_POST['buyer_name']);
    $buyer_email = filter_var($_POST['buyer_email'], FILTER_VALIDATE_EMAIL);

    if (!$buyer_email) {
        die("Invalid email.");
    }

    $stmt = $conn->prepare("INSERT INTO purchases (ticket_id, buyer_name, buyer_email) VALUES (?, ?, ?)");
    if ($stmt->execute([$ticket_id, $buyer_name, $buyer_email])) {
        echo "Purchase successful!";
    } else {
        echo "Purchase failed.";
    }
}
?>
