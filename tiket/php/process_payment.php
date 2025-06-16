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

// Ambil semua data dari halaman pembayaran
$buyer_name = filter_input(INPUT_POST, 'buyer_name', FILTER_SANITIZE_STRING);
$buyer_email = filter_input(INPUT_POST, 'buyer_email', FILTER_VALIDATE_EMAIL);
$buyer_phone = filter_input(INPUT_POST, 'buyer_phone', FILTER_SANITIZE_STRING);
$id_type = filter_input(INPUT_POST, 'id_type', FILTER_SANITIZE_STRING);
$id_number = filter_input(INPUT_POST, 'id_number', FILTER_SANITIZE_STRING);
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$ticket_type_name = filter_input(INPUT_POST, 'ticket_type_name', FILTER_SANITIZE_STRING);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$total_price = filter_input(INPUT_POST, 'total_price', FILTER_VALIDATE_FLOAT);
$payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);

if (!$buyer_name || !$buyer_email || !$event_id || !$id_number) {
    die("Data tidak lengkap. Silakan kembali dan isi semua field yang wajib diisi.");
}

// Logika Database (tidak ada perubahan)
$conn->beginTransaction();
try {
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ? FOR UPDATE");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) throw new Exception("Event tidak ditemukan.");

    $ticket_types = json_decode($event['ticket_types'], true);
    $ticket_found = false;
    $new_ticket_types = [];

    foreach ($ticket_types as $type) {
        if ($type['type_name'] === $ticket_type_name) {
            if ($type['quantity'] < $quantity) {
                throw new Exception("Maaf, stok tiket untuk jenis '" . htmlspecialchars($ticket_type_name) . "' tidak mencukupi.");
            }
            $type['quantity'] -= $quantity;
            $ticket_found = true;
        }
        $new_ticket_types[] = $type;
    }

    if (!$ticket_found) throw new Exception("Jenis tiket tidak ditemukan.");

    $update_stmt = $conn->prepare("UPDATE tickets SET ticket_types = ? WHERE id = ?");
    $update_stmt->execute([json_encode($new_ticket_types), $event_id]);

    $order_id_unique = 'ORD-' . strtoupper(uniqid());
    $ticket_code = 'TIX-' . strtoupper(uniqid());

    $order_stmt = $conn->prepare(
        "INSERT INTO orders (order_id, ticket_code, event_id, user_id, buyer_name, buyer_email, buyer_phone, id_type, id_number, ticket_type_name, quantity, total_price, payment_method, order_status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid')"
    );
    $order_stmt->execute([
        $order_id_unique, $ticket_code, $event_id, $_SESSION['user_id'] ?? null, $buyer_name, $buyer_email,
        $buyer_phone, $id_type, $id_number, $ticket_type_name, $quantity, $total_price, $payment_method
    ]);

    $conn->commit();
} catch (Exception $e) {
    $conn->rollBack();
    die("Terjadi kesalahan pada database: " . $e->getMessage());
}

// Buat QR Code
$qr_code_path = null;
try {
    $qr_code_data = $ticket_code;

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
    // Tangkap error dan tampilkan pesan yang lebih informatif untuk debugging
    die("Gagal membuat QR Code: " . $e->getMessage());
}

// Kirim Email (tidak ada perubahan)
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(MAIL_USERNAME, MAIL_SENDER_NAME);
    $mail->addAddress($buyer_email, $buyer_name);

    if ($qr_code_path && file_exists($qr_code_path)) {
        $mail->addAttachment($qr_code_path);
    }

    $mail->isHTML(true);
    $mail->Subject = 'E-Tiket Anda untuk ' . $event['event_name'];
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h2>Pembayaran Berhasil!</h2>
            <p>Halo " . htmlspecialchars($buyer_name) . ",</p>
            <p>Terima kasih telah melakukan pembelian. Berikut adalah detail e-tiket Anda:</p><hr>
            <h3>Detail Pesanan</h3>
            <p><strong>Order ID:</strong> $order_id_unique</p>
            <p><strong>Acara:</strong> " . htmlspecialchars($event['event_name']) . "</p>
            <p><strong>Jenis Tiket:</strong> " . htmlspecialchars($ticket_type_name) . "</p>
            <p><strong>Jumlah:</strong> $quantity</p>
            <p><strong>Total Pembayaran:</strong> Rp " . number_format($total_price, 0, ',', '.') . "</p><hr>
            <h3>E-Tiket Anda</h3>
            <p>Silakan tunjukkan QR Code yang terlampir di email ini saat di pintu masuk.</p>
            <p><strong>Kode Tiket:</strong> $ticket_code</p><br>
            <p>Terima kasih,</p><p><strong>TIKETFEST.ID</strong></p>
        </div>";
    $mail->send();
} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
}

// Redirect ke Halaman Sukses
header("Location: ../php/success.php?order_id=" . $order_id_unique);
exit();
?>