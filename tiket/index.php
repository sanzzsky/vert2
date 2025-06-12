<?php
session_start();
require 'php/db.php';
$stmt = $conn->query("SELECT * FROM tickets");
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/script.js"></script>
    <title>Concert Ticket Sales</title>
    
</head>
<body>
    <div class="header">
        <h1>Concert Ticket Sales</h1>
        <?php if (isset($_SESSION['username'])): ?>
            <div class="profile-container">
                <img src="images/images.jpg" alt="Profile" class="profile-img">
                <div class="dropdown-content">
                    <span style="padding: 12px 16px; display: block; color: #888; font-size: 14px;">Hello, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                    <a href="php/logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="admin-link">Login</a>
        <?php endif; ?>
    </div>
    <div class="container">
        <h2>Available Tickets</h2>
        <?php foreach($tickets as $ticket): ?>
            <div class="ticket">
                <h3><?= htmlspecialchars($ticket['event_name']) ?></h3>
                <img src="images/<?= htmlspecialchars($ticket['image']) ?>" alt="<?= htmlspecialchars($ticket['event_name']) ?>" width="200">
                <p>Date: <?= htmlspecialchars($ticket['date']) ?></p>
                <p>Price: Rp <?= htmlspecialchars($ticket['price']) ?></p>
                <button onclick="showPurchaseForm(<?= $ticket['id'] ?>)">Buy Ticket</button>
            </div>
        <?php endforeach; ?>
    </div>
    <div id="purchaseModal" class="modal" style="display:none;">
        <form id="purchaseForm" method="post" action="php/purchase.php">
            <input type="hidden" name="ticket_id" id="modal_ticket_id">
            <label>Name: <input type="text" name="buyer_name" required></label><br>
            <label>Email: <input type="email" name="buyer_email" required></label><br>
            <button type="submit">Confirm Purchase</button>
            <button type="button" onclick="closePurchaseForm()">Cancel</button>
        </form>
    </div>
    <div class="footer">
        <p>&copy; 2025 Concert Ticket Sales</p>
    </div>
</body>
</html>
