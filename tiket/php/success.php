<?php
session_start();
require 'db.php';

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_STRING);

if (!$order_id) {
    die("Order tidak ditemukan.");
}

// Ambil data order untuk ditampilkan
$stmt = $conn->prepare("SELECT o.*, t.event_name, t.event_date FROM orders o JOIN tickets t ON o.event_id = t.id WHERE o.order_id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Detail order tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto max-w-lg text-center py-24 px-4">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check-circle text-6xl text-green-500"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Pembayaran Berhasil!</h1>
            <p class="text-gray-600 mb-6">Terima kasih, pesanan Anda telah kami terima. E-tiket telah dikirimkan ke email Anda.</p>
            
            <div class="text-left bg-gray-50 border rounded-lg p-4">
                <p class="font-semibold mb-2">Detail Pesanan:</p>
                <p><strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']) ?></p>
                <p><strong>Nama:</strong> <?= htmlspecialchars($order['buyer_name']) ?></p>
                <p><strong>Acara:</strong> <?= htmlspecialchars($order['event_name']) ?></p>
                <p><strong>Jumlah Tiket:</strong> <?= htmlspecialchars($order['quantity']) ?></p>
            </div>

            <a href="../index.php" class="mt-8 inline-block w-full bg-violet-600 hover:bg-violet-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>