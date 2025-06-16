<?php
session_start();
require 'db.php';

// Ambil semua data dari halaman sebelumnya
$buyer_name = filter_input(INPUT_POST, 'buyer_name', FILTER_SANITIZE_STRING);
$buyer_email = filter_input(INPUT_POST, 'buyer_email', FILTER_VALIDATE_EMAIL);
$buyer_phone = filter_input(INPUT_POST, 'buyer_phone', FILTER_SANITIZE_STRING);
$id_type = filter_input(INPUT_POST, 'id_type', FILTER_SANITIZE_STRING);
$id_number = filter_input(INPUT_POST, 'id_number', FILTER_SANITIZE_STRING);
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$ticket_type_name = filter_input(INPUT_POST, 'ticket_type_name', FILTER_SANITIZE_STRING);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$total_price = filter_input(INPUT_POST, 'total_price', FILTER_VALIDATE_FLOAT);

// Validasi dasar
if (!$buyer_name || !$buyer_email || !$event_id) {
    die("Data tidak lengkap. Silakan kembali dan isi form dengan benar.");
}

// Ambil detail event untuk ditampilkan
$stmt = $conn->prepare("SELECT event_name FROM tickets WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Metode Pembayaran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto max-w-2xl py-16 px-4">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-2">Pilih Metode Pembayaran</h1>
            <p class="text-center text-gray-500 mb-8">Anda akan membayar untuk tiket "<?= htmlspecialchars($event['event_name']) ?>"</p>

            <div class="bg-gray-50 border rounded-lg p-4 mb-6">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total Pembayaran</span>
                    <span class="text-2xl font-bold text-violet-600">Rp <?= number_format($total_price, 0, ',', '.') ?></span>
                </div>
            </div>

            <form action="process_payment.php" method="POST">
                <input type="hidden" name="buyer_name" value="<?= htmlspecialchars($buyer_name) ?>">
                <input type="hidden" name="buyer_email" value="<?= htmlspecialchars($buyer_email) ?>">
                <input type="hidden" name="buyer_phone" value="<?= htmlspecialchars($buyer_phone) ?>">
                <input type="hidden" name="id_type" value="<?= htmlspecialchars($id_type) ?>">
                <input type="hidden" name="id_number" value="<?= htmlspecialchars($id_number) ?>">
                <input type="hidden" name="event_id" value="<?= $event_id ?>">
                <input type="hidden" name="ticket_type_name" value="<?= htmlspecialchars($ticket_type_name) ?>">
                <input type="hidden" name="quantity" value="<?= $quantity ?>">
                <input type="hidden" name="total_price" value="<?= $total_price ?>">

                <div class="space-y-4">
                    <h3 class="font-semibold text-gray-700">Metode Pembayaran (Simulasi)</h3>
                    
                    <label class="flex items-center p-4 border rounded-lg hover:border-violet-500 cursor-pointer">
                        <input type="radio" name="payment_method" value="BCA Virtual Account" class="w-5 h-5 text-violet-600" checked>
                        <span class="ml-4 font-medium">BCA Virtual Account</span>
                    </label>
                    <label class="flex items-center p-4 border rounded-lg hover:border-violet-500 cursor-pointer">
                        <input type="radio" name="payment_method" value="GoPay" class="w-5 h-5 text-violet-600">
                        <span class="ml-4 font-medium">GoPay / GoTo</span>
                    </label>
                    <label class="flex items-center p-4 border rounded-lg hover:border-violet-500 cursor-pointer">
                        <input type="radio" name="payment_method" value="OVO" class="w-5 h-5 text-violet-600">
                        <span class="ml-4 font-medium">OVO</span>
                    </label>
                </div>

                <div class="mt-8">
                    <button type="submit" class="w-full bg-violet-600 hover:bg-violet-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg transition-transform transform hover:scale-105">
                        Bayar Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>