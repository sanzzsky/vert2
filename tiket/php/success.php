<?php
session_start();
require 'db.php';

$order_id_unique = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_STRING);

if (!$order_id_unique) {
    die("ID Pesanan tidak valid.");
}

// Ambil data pesanan dari database menggunakan JOIN
$stmt = $conn->prepare(
    "SELECT o.*, t.event_name, t.event_date, t.event_time, t.location 
     FROM orders o 
     JOIN tickets t ON o.event_id = t.id 
     WHERE o.order_id = ?"
);
$stmt->execute([$order_id_unique]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Pesanan tidak ditemukan.");
}

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
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
    <div class="container mx-auto max-w-2xl text-center py-20 px-4">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check text-6xl text-green-500"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Pembayaran Berhasil!</h1>
            <p class="text-gray-600 mb-8">Terima kasih, pesanan Anda telah kami terima. E-tiket sudah dikirimkan ke email <strong><?= htmlspecialchars($order['buyer_email']) ?></strong>.</p>
            
            <div class="text-left bg-gray-50 border rounded-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold text-center border-b pb-3 mb-4">Detail Pesanan</h3>
                <div class="flex justify-between">
                    <span class="text-gray-500">Order ID</span>
                    <span class="font-mono text-gray-800"><?= htmlspecialchars($order['order_id']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Nama Pembeli</span>
                    <span class="font-medium text-gray-800"><?= htmlspecialchars($order['buyer_name']) ?></span>
                </div>
                <div class="border-t my-4"></div>
                <h4 class="font-semibold"><?= htmlspecialchars($order['event_name']) ?></h4>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Jenis Tiket</span>
                    <span class="font-medium text-gray-800"><?= htmlspecialchars($order['ticket_type_name']) ?> (x<?= htmlspecialchars($order['quantity']) ?>)</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Tanggal Acara</span>
                    <span class="font-medium text-gray-800"><?= date('d F Y', strtotime($order['event_date'])) ?></span>
                </div>
                <div class="border-t my-4"></div>
                <div class="flex justify-between text-lg font-bold">
                    <span>Total Pembayaran</span>
                    <span class="text-violet-700"><?= formatRupiah($order['total_price']) ?></span>
                </div>
            </div>

            <a href="../index.php" class="mt-8 inline-block w-full bg-violet-600 hover:bg-violet-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>