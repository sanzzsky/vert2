<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once 'db.php';
$tickets = $conn->query("SELECT * FROM tickets")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <title>Admin Panel</title>
</head>
<body>
    <div class="container">
        <h1>Welcome, Admin</h1>
        <a href="logout.php">Logout</a>
        <h2>Add New Ticket</h2>
        <form action="add_ticket.php" method="post" enctype="multipart/form-data">
            <label>Event Name: <input type="text" name="event_name" required></label><br>
            <label>Date: <input type="date" name="date" required></label><br>
            <label>Price: <input type="number" name="price" required></label><br>
            <label>Image: <input type="file" name="image" required></label><br>
            <button type="submit">Add Ticket</button>
        </form>
        <h2>All Tickets</h2>
        <table>
            <tr><th>Event</th><th>Date</th><th>Price</th><th>Image</th><th>Action</th></tr>
            <?php foreach($tickets as $ticket): ?>
            <tr>
                <td><?= htmlspecialchars($ticket['event_name']) ?></td>
                <td><?= htmlspecialchars($ticket['date']) ?></td>
                <td><?= htmlspecialchars($ticket['price']) ?></td>
                <td><img src="images/<?= htmlspecialchars($ticket['image']) ?>" width="50"></td>
                <td><a href="php/delete_ticket.php?id=<?= $ticket['id'] ?>" onclick="return confirm('Delete this ticket?')">Delete</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
