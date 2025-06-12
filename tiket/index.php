<?php
session_start();
require 'php/db.php';

// Ambil hanya event yang statusnya 'active' untuk ditampilkan di home
$stmt = $conn->query("SELECT * FROM tickets WHERE status = 'active' ORDER BY event_date ASC");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <h2>Available Events</h2>
        <div class="ticket-grid">
            <?php foreach($tickets as $ticket): ?>
                <div class="ticket">
                    <?php if (!empty($ticket['image'])): ?>
                        <img src="images/<?= htmlspecialchars($ticket['image']) ?>" alt="<?= htmlspecialchars($ticket['event_name']) ?>" class="ticket-image">
                    <?php endif; ?>
                    
                    <h3><?= htmlspecialchars($ticket['event_name']) ?></h3>
                    
                    <p class="event-time">
                        🗓️ <?= date('d F Y', strtotime($ticket['event_date'])) ?>
                        <?php if (!empty($ticket['event_time'])): ?>
                            | ⏰ <?= date('H:i', strtotime($ticket['event_time'])) ?> WIB
                        <?php endif; ?>
                    </p>
                    
                    <a href="php/detail_event.php?id=<?= $ticket['id'] ?>" class="detail-button">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?= date("Y") ?> Concert Ticket Sales</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>