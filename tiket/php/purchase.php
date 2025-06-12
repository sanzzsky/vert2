<?php
session_start();
require 'db.php';

// Data ini didapat dari form di halaman detail_event.php
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$ticket_type_name = filter_input(INPUT_POST, 'ticket_type_name', FILTER_SANITIZE_STRING);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

// Jika data awal tidak ada (misal, akses langsung ke halaman ini), kembali ke index.
if (!$event_id || !$ticket_type_name || !$quantity || $quantity <= 0) {
    header("Location: index.php");
    exit();
}

// Ambil data lengkap event dari database
$stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Event tidak ditemukan.");
}

// Decode JSON dan cari detail tiket yang dipilih
$ticket_types = json_decode($event['ticket_types'], true);
$selected_ticket_details = null;
foreach ($ticket_types as $type) {
    if ($type['type_name'] === $ticket_type_name) {
        $selected_ticket_details = $type;
        break;
    }
}

if (!$selected_ticket_details) {
    die("Jenis tiket tidak valid.");
}

// Hitung total harga
$price_per_ticket = $selected_ticket_details['price'];
$total_price = $price_per_ticket * $quantity;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>Isi Data Diri & Konfirmasi Pesanan</title>
    <style>
        .checkout-container { display: flex; flex-wrap: wrap; gap: 40px; }
        .order-summary, .buyer-form { flex: 1; min-width: 300px; }
        .summary-box { list-style: none; padding: 20px; margin: 0; background-color: #fafafa; border-radius: 8px; border: 1px solid #f0f0f0; }
        .summary-box li { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .summary-box li:last-child { border-bottom: none; }
        .summary-box span { font-weight: bold; }
        .total-price { font-size: 1.5em; color: #28a745; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 8px; margin-top: 15px; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="tel"] { width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1em; }
        .radio-group label { display: inline-block; margin-right: 15px; font-weight: normal;}
    </style>
</head>
<body>
    <header class="header">
        <h1>Checkout</h1>
    </header>

    <main class="container">
        <form action="process_order.php" method="POST">
            <div class="checkout-container">
                
                <div class="order-summary">
                    <h3>Ringkasan Pesanan Anda</h3>
                    <ul class="summary-box">
                        <li>Nama Acara: <span><?= htmlspecialchars($event['event_name']) ?></span></li>
                        <li>Jenis Tiket: <span><?= htmlspecialchars($selected_ticket_details['type_name']) ?></span></li>
                        <li>Jumlah: <span><?= htmlspecialchars($quantity) ?></span></li>
                        <hr>
                        <li>Total: <span class="total-price">Rp <?= number_format($total_price, 0, ',', '.') ?></span></li>
                    </ul>
                </div>

                <div class="buyer-form">
                    <h3>Isi Data Pembeli</h3>
                    
                    <div class="form-group">
                        <label for="buyer_name">Nama Lengkap</label>
                        <input type="text" id="buyer_name" name="buyer_name" required>
                    </div>

                    <div class="form-group">
                        <label for="buyer_email">Alamat Email</label>
                        <input type="email" id="buyer_email" name="buyer_email" required>
                    </div>

                    <div class="form-group">
                        <label for="buyer_phone">Nomor Telepon</label>
                        <input type="tel" id="buyer_phone" name="buyer_phone" placeholder="Contoh: 08123456789" required>
                    </div>

                    <div class="form-group">
                        <label>Jenis Identitas</label>
                        <div class="radio-group">
                            <label><input type="radio" name="id_type" value="NIK" checked> NIK</label>
                            <label><input type="radio" name="id_type" value="SIM"> SIM</label>
                            <label><input type="radio" name="id_type" value="KTM"> KTM</label>
                            <label><input type="radio" name="id_type" value="Lainnya"> Lainnya</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="id_number">Nomor Identitas</label>
                        <input type="text" id="id_number" name="id_number" required>
                    </div>

                    <input type="hidden" name="event_id" value="<?= $event_id ?>">
                    <input type="hidden" name="ticket_type_name" value="<?= htmlspecialchars($ticket_type_name) ?>">
                    <input type="hidden" name="quantity" value="<?= $quantity ?>">
                    <input type="hidden" name="total_price" value="<?= $total_price ?>">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                    <?php endif; ?>

                    <button type="submit" class="purchase-button">Selesaikan Pesanan</button>
                </div>

            </div>
        </form>
    </main>

    <footer class="footer">
        <p>&copy; 2025 Concert Ticket Sales</p>
    </footer>
</body>
</html>