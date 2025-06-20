<?php
session_start();
require 'db.php';
require '../vendor/autoload.php';
require 'mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_STRING);
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);

if ($status !== 'success' || !$order_id) {
    header("Location: ../index.php?payment=failed");
    exit();
}

$conn->beginTransaction();
try {
    // 1. Ambil data pesanan yang statusnya masih 'pending'
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND order_status = 'pending'");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        // Mungkin halaman di-refresh, arahkan saja ke halaman sukses
        header("Location: ../success.php?order_id=" . $order_id);
        exit();
    }
    
    // 2. Ambil data event & kurangi stok
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ? FOR UPDATE");
    $stmt->execute([$order['event_id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    $ticket_types = json_decode($event['ticket_types'], true);
    $new_ticket_types = [];
    foreach ($ticket_types as $type) {
        if ($type['type_name'] === $order['ticket_type_name']) {
            if ($type['quantity'] < $order['quantity']) throw new Exception("Stok tidak mencukupi saat konfirmasi.");
            $type['quantity'] -= $order['quantity'];
        }
        $new_ticket_types[] = $type;
    }
    $update_stmt = $conn->prepare("UPDATE tickets SET ticket_types = ? WHERE id = ?");
    $update_stmt->execute([json_encode($new_ticket_types), $order['event_id']]);

    // 3. Update status pesanan menjadi 'paid' dan buat kode tiket
    $ticket_code = 'TIX-' . strtoupper(uniqid());
    $update_order = $conn->prepare("UPDATE orders SET order_status = 'paid', ticket_code = ? WHERE order_id = ?");
    $update_order->execute([$ticket_code, $order_id]);

    $conn->commit();
} catch (Exception $e) {
    $conn->rollBack();
    die("Terjadi kesalahan saat memfinalisasi pesanan: " . $e->getMessage());
}

 // Buat QR Code
 $qr_code_path = null;
 try {
     $qr_code_data = $ticket_code;
 
     // --- PERBAIKAN FINAL: Menggunakan cara pemanggilan yang paling sesuai dengan library Anda ---
     $builder = new Builder(
         writer: new PngWriter(),
         data: $qr_code_data,
         encoding: new Encoding('UTF-8'),
         errorCorrectionLevel: ErrorCorrectionLevel::Low,
         size: 300,
         margin: 10
     );
     
     $result = $builder->build();
     
     $qr_code_path = '../qrcodes/' . $ticket_code . '.png';
     $result->saveToFile($qr_code_path);
 
 } catch(Exception $e) {
     die("Gagal membuat QR Code: " . $e->getMessage());
 }

 // ----------------------------------------------------------------
// BAGIAN 2: TAMPILKAN HALAMAN LOADING KE PENGGUNA
// ----------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memproses Pesanan Anda...</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta http-equiv="refresh" content="2;url=../php/success.php?order_id=<?php echo htmlspecialchars($order_id); ?>">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="text-center">
        <img src="../assets/loading.gif" alt="Loading..." class="mx-auto h-24 w-24">
        <h1 class="text-2xl font-bold text-gray-700 mt-4">Pesanan Anda Sedang Diproses</h1>
        <p class="text-gray-500">Mengirim e-tiket ke email Anda, mohon tunggu...</p>
        <p class="text-sm text-gray-400 mt-8">Anda akan dialihkan secara otomatis.</p>
    </div>
</body>
</html>
<?php
// Memastikan output HTML terkirim ke browser sebelum melanjutkan proses lambat
if (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

// -----------------------------------------------------------------
// BAGIAN 3: PROSES LAMBAT (KIRIM EMAIL) DI LATAR BELAKANG
// -----------------------------------------------------------------
// Tutup koneksi agar tidak memblokir proses lain jika ada
session_write_close(); 

// 5. Kirim Email (menggunakan data dari $order dan $event)
$mail = new PHPMailer(true);
try {
    $mail->isSMTP(); $mail->Host = MAIL_HOST; $mail->SMTPAuth = true; $mail->Username = MAIL_USERNAME; $mail->Password = MAIL_PASSWORD; $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = MAIL_PORT; $mail->CharSet = 'UTF-8';
    $mail->setFrom(MAIL_USERNAME, MAIL_SENDER_NAME);
    $mail->addAddress($order['buyer_email'], $order['buyer_name']);
    if ($qr_code_path && file_exists($qr_code_path)) { $mail->addAttachment($qr_code_path); }
    $mail->isHTML(true);
    $mail->Subject = 'E-Tiket Anda untuk ' . $event['event_name'];
    $mail->Body = "
        <div style='font-family: Arial, sans-serif;'>
            <h2>Pembayaran Berhasil!</h2>
            <p>Halo " . htmlspecialchars($order['buyer_name']) . ", detail e-tiket Anda:</p><hr>
            <p><strong>Order ID:</strong> " . $order['order_id'] . "</p>
            <p><strong>Kode Tiket:</strong> " . $ticket_code . "</p>
            <p><strong>Acara:</strong> " . htmlspecialchars($event['event_name']) . "</p>
            <p><strong>Jenis Tiket:</strong> " . htmlspecialchars($order['ticket_type_name']) . " (x" . $order['quantity'] . ")</p>
            <p><strong>Total Bayar:</strong> Rp " . number_format($order['total_price'], 0, ',', '.') . "</p><hr>
            <p>Tunjukkan QR Code terlampir saat masuk. Terima kasih.</p>
        </div>";
    $mail->send();
} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
}

exit();
?>