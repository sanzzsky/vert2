<?php
session_start();
require 'db.php';

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($event_id === 0) {
    die("Halaman tidak ditemukan. ID Acara tidak valid.");
}

$stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ? AND status = 'active'");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Acara yang Anda cari tidak ditemukan atau sudah tidak tersedia.");
}

$ticket_types = json_decode($event['ticket_types'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles.css">
    <title>Detail: <?= htmlspecialchars($event['event_name']) ?></title>
</head>
<body>
    <header class="header">
        <h1>Concert Ticket Sales</h1>
        <nav class="main-nav">
            <a href="../index.php">Home</a>
            <?php if (isset($_SESSION['username'])): ?>
                <div class="profile-container">
                    <img src="../images/images.jpg" alt="Profile" class="profile-img">
                    <div class="dropdown-content">
                        <span class="dropdown-greeting">Hello, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="../login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">
        <div class="event-detail-container">
            <div class="event-image-container">
                <?php if (!empty($event['image'])): ?>
                    <img src="../images/<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['event_name']) ?>">
                <?php else: ?>
                    <img src="../images/placeholder.png" alt="No image available"> 
                <?php endif; ?>
            </div>

            <div class="event-info-container">
                <h1 class="event-title"><?= htmlspecialchars($event['event_name']) ?></h1>
                
                <ul class="event-meta">
                    <li><strong>Kategori:</strong> <?= htmlspecialchars($event['category']) ?></li>
                    <li><strong>🗓️ Tanggal:</strong> <?= date('d F Y', strtotime($event['event_date'])) ?></li>
                    <li><strong>⏰ Waktu:</strong> <?= !empty($event['event_time']) ? date('H:i', strtotime($event['event_time'])) . ' WIB' : 'N/A' ?></li>
                    <li><strong>📍 Lokasi:</strong> <?= htmlspecialchars($event['location']) ?></li>
                </ul>
                
                <h3 class="section-title">Deskripsi Acara</h3>
                <p class="event-description"><?= nl2br(htmlspecialchars($event['description'])) ?></p>

                <div class="ticket-purchase-section">
                    <h2 class="section-title">Beli Tiket</h2>
                     <form action="../php/purchase.php" method="POST">
                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">

                        <label for="ticket_type">Pilih Jenis Tiket:</label>
                        <select id="ticket_type" name="ticket_type_name" required>
                            <option value="">-- Pilih Tiket --</option>
                            <?php if (is_array($ticket_types)) : ?>
                                <?php foreach ($ticket_types as $type): ?>
                                    <option value="<?= htmlspecialchars($type['type_name']) ?>">
                                        <?= htmlspecialchars($type['type_name']) ?> - Rp <?= number_format($type['price'], 0, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <label for="quantity">Jumlah:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="10" required>
                        
                        <button type="submit" class="purchase-button">Beli Sekarang</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?= date("Y") ?> Concert Ticket Sales</p>
    </footer>

</body>
</html>