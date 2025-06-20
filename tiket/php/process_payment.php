<?php
session_start();
require 'db.php';
require '../vendor/autoload.php';
require 'mail_config.php';

// Ambil semua data dari halaman checkout (purchase.php)
$buyer_name = filter_input(INPUT_POST, 'buyer_name', FILTER_SANITIZE_STRING);
$buyer_email = filter_input(INPUT_POST, 'buyer_email', FILTER_VALIDATE_EMAIL);
$buyer_phone = filter_input(INPUT_POST, 'buyer_phone', FILTER_SANITIZE_STRING);
$id_type = filter_input(INPUT_POST, 'id_type', FILTER_SANITIZE_STRING);
$id_number = filter_input(INPUT_POST, 'id_number', FILTER_SANITIZE_STRING);
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$ticket_type_name = filter_input(INPUT_POST, 'ticket_type_name', FILTER_SANITIZE_STRING);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$total_price = filter_input(INPUT_POST, 'total_price', FILTER_VALIDATE_FLOAT);
// $payment_method tidak lagi diterima dari form, karena Midtrans yang akan menampilkannya

// Validasi data dasar
if (!$buyer_name || !$buyer_email || !$event_id || !$id_number) {
    die("Data tidak lengkap. Silakan kembali dan isi semua field yang wajib diisi.");
}

// =======================================================
// SEMUA TRANSAKSI SEKARANG LANGSUNG DIARAHKAN KE MIDTRANS
// =======================================================

// Konfigurasi Midtrans (gunakan kunci Sandbox Anda)
\Midtrans\Config::$serverKey = 'SB-Mid-server-4mHdaYU8xxGaJDFCTrq3akmL';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Buat ID Pesanan yang akan digunakan di kedua sistem
$order_id_unique = 'ORD-' . strtoupper(uniqid());

// Siapkan parameter untuk Midtrans
$params = [
    'transaction_details' => [
        'order_id' => $order_id_unique,
        'gross_amount' => $total_price,
    ],
    'customer_details' => [
        'first_name' => $buyer_name,
        'email' => $buyer_email,
        'phone' => $buyer_phone,
    ],
    // Kosongkan 'enabled_payments' agar Midtrans menampilkan semua metode yang aktif di dashboard Anda
];

try {
    // Minta Snap Token dari Midtrans
    $snapToken = \Midtrans\Snap::getSnapToken($params);

    // Simpan pesanan ke database kita dengan status 'pending'
    // Ini penting agar pesanan tercatat meskipun pengguna menutup jendela pembayaran
    $stmt = $conn->prepare(
        "INSERT INTO orders (order_id, event_id, user_id, buyer_name, buyer_email, buyer_phone, id_type, id_number, ticket_type_name, quantity, total_price, order_status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    // Kolom payment_method dihapus dari query sementara, akan diupdate oleh notifikasi Midtrans
    $stmt->execute([
        $order_id_unique, $event_id, $_SESSION['user_id'] ?? null, $buyer_name, $buyer_email, $buyer_phone,
        $id_type, $id_number, $ticket_type_name, $quantity, $total_price
    ]);

    // Tampilkan halaman pembayaran Midtrans (Snap.js)
    echo '<!DOCTYPE html><html><head><title>Lanjutkan Pembayaran</title><script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-0C4YrkYTIAQVdcOf"></script></head><body><p>Mengarahkan ke halaman pembayaran...</p><script type="text/javascript">snap.pay("'. $snapToken .'", {
        onSuccess: function(result){
            /* Pembayaran sukses, arahkan ke halaman notifikasi kita untuk proses akhir */
            window.location.href = "payment_notification.php?order_id='. $order_id_unique .'&status=success";
        },
        onPending: function(result){
            alert("Pembayaran Anda tertunda!"); window.location.href = "../index.php";
        },
        onError: function(result){
            alert("Pembayaran gagal!"); window.location.href = "../index.php";
        },
        onClose: function(){
            alert("Anda menutup jendela pembayaran tanpa menyelesaikan transaksi.");
        }
    });</script></body></html>';
    exit();

} catch (Exception $e) {
    die('Error saat berkomunikasi dengan Midtrans: ' . $e->getMessage());
}

?>