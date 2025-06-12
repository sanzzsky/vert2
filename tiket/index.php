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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>Concert Ticket Sales</title>
</head>
<body>
    <header class="header">
        <h1>Concert Ticket Sales</h1>
        <nav>
            <?php if (isset($_SESSION['username'])): ?>
                <div class="profile-container">
                    <img src="images/images.jpg" alt="Profile" class="profile-img">
                    <div class="dropdown-content">
                        <span class="dropdown-greeting">Hello, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                        <a href="php/logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="login-link">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">
        <h2>Available Tickets</h2>
        <div class="ticket-grid">
            <?php foreach($tickets as $ticket): ?>
                <div class="ticket">
                    <img src="images/<?= htmlspecialchars($ticket['image']) ?>" alt="<?= htmlspecialchars($ticket['event_name']) ?>" class="ticket-image">
                    <h3><?= htmlspecialchars($ticket['event_name']) ?></h3>
                    <p>Date: <?= htmlspecialchars($ticket['date']) ?></p>
                    <p>Price: Rp <?= number_format($ticket['price'], 0, ',', '.') ?></p>
                    <button onclick="showPurchaseForm(<?= $ticket['id'] ?>)">Buy Ticket</button>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div id="purchaseModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close-button" onclick="closePurchaseForm()">&times;</span>
            <h3>Confirm Your Purchase</h3>
            <form id="purchaseForm" method="post" action="php/purchase.php">
                <input type="hidden" name="ticket_id" id="modal_ticket_id">
                <label for="buyer_name">Name:</label>
                <input type="text" id="buyer_name" name="buyer_name" required>
                <label for="buyer_email">Email:</label>
                <input type="email" id="buyer_email" name="buyer_email" required>
                <div class="form-buttons">
                    <button type="submit">Confirm Purchase</button>
                    <button type="button" onclick="closePurchaseForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?= date("Y") ?> Concert Ticket Sales</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>