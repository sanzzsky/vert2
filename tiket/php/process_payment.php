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

// Validasi data dasar
if (!$buyer_name || !$buyer_email || !$event_id || !$id_number) {
    die("Data tidak lengkap. Silakan kembali dan isi semua field yang wajib diisi.");
}

// Konfigurasi Midtrans
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
];

try {
    // Minta Snap Token dari Midtrans
    $snapToken = \Midtrans\Snap::getSnapToken($params);

    // Simpan pesanan ke database kita dengan status 'pending'
    $stmt = $conn->prepare(
        "INSERT INTO orders (order_id, event_id, user_id, buyer_name, buyer_email, buyer_phone, id_type, id_number, ticket_type_name, quantity, total_price, order_status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    $stmt->execute([
        $order_id_unique, $event_id, $_SESSION['user_id'] ?? null, $buyer_name, $buyer_email, $buyer_phone,
        $id_type, $id_number, $ticket_type_name, $quantity, $total_price
    ]);

    // Tampilkan halaman pembayaran dengan auto-trigger
    echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Memproses Pembayaran - Tiket Event</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-0C4YrkYTIAQVdcOf"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        "spin-slow": "spin 3s linear infinite",
                        "bounce-slow": "bounce 2s infinite",
                        "pulse-gentle": "pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite",
                    }
                }
            }
        }
    </script>
    <style>
        /* Minimal CSS untuk Midtrans compatibility */
        .snap-midtrans {
            z-index: 999999 !important;
        }
        
        #snap-container {
            z-index: 999999 !important;
        }
        
        /* Responsive iframe untuk mobile */
        @media (max-width: 768px) {
            .snap-midtrans {
                width: 100% !important;
                height: 100% !important;
            }
        }
        
        /* Ensure buttons are not covered */
        .midtrans-payment-list, .midtrans-payment-methods {
            z-index: 1000000 !important;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen">
    
    <!-- Main Container -->
    <div class="container mx-auto px-4 py-6 max-w-2xl">
        
        <!-- Processing Card -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6 text-white">
                <div class="text-center">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 animate-pulse-gentle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold mb-2">Memproses Pembayaran</h1>
                    <p class="text-indigo-100">Mohon tunggu, jendela pembayaran akan segera muncul...</p>
                </div>
            </div>
            
            <!-- Loading Animation -->
            <div class="px-8 py-6 text-center border-b border-gray-100">
                <div class="relative inline-block">
                    <div class="w-12 h-12 border-4 border-indigo-200 rounded-full animate-spin-slow"></div>
                    <div class="absolute inset-0 w-12 h-12 border-4 border-transparent border-t-indigo-600 rounded-full animate-spin"></div>
                </div>
                <p class="text-gray-600 mt-4 text-sm">Menghubungkan ke sistem pembayaran yang aman...</p>
            </div>
            
            <!-- Order Summary -->
            <div class="px-8 py-6">
                <div class="mb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Ringkasan Pesanan</h3>
                    </div>
                    
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Order ID</span>
                            <span class="font-mono text-sm bg-white px-2 py-1 rounded border">'. $order_id_unique .'</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Nama Pemesan</span>
                            <span class="font-medium text-gray-900">'. htmlspecialchars($buyer_name) .'</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Jenis Tiket</span>
                            <span class="font-medium text-gray-900">'. htmlspecialchars($ticket_type_name) .'</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Jumlah</span>
                            <span class="font-medium text-gray-900">'. $quantity .' tiket</span>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">Total Pembayaran</span>
                                <span class="text-xl font-bold text-indigo-600">Rp '. number_format($total_price, 0, ',', '.') .'</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-start">
                        <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3 mt-0.5">
                            <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-medium text-blue-900 mb-1">Informasi Pembayaran</h4>
                            <p class="text-sm text-blue-700 leading-relaxed">
                                Jendela pembayaran akan muncul secara otomatis. Jika tidak muncul, pastikan popup blocker tidak aktif dan refresh halaman ini.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="px-8 py-4 bg-gray-50 border-t border-gray-100">
                <div class="flex items-center justify-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Pembayaran aman dengan enkripsi SSL 256-bit
                </div>
            </div>
        </div>
        
        <!-- Alternative Action Button (Backup) -->
        <div class="mt-6 text-center">
            <button id="retryPayment" class="hidden bg-white border-2 border-indigo-600 text-indigo-600 px-6 py-3 rounded-xl font-medium hover:bg-indigo-50 transition-colors duration-200">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Coba Lagi
            </button>
        </div>
        
    </div>

    <script type="text/javascript">
        // Auto-trigger payment after page loads
        window.addEventListener("load", function() {
            setTimeout(function() {
                startPayment();
            }, 2000); // 2 second delay for better UX
        });
        
        function startPayment() {
            snap.pay("'. $snapToken .'", {
                onSuccess: function(result) {
                    console.log("Payment success:", result);
                    // Show success message briefly before redirect
                    document.body.innerHTML = `
                        <div class="min-h-screen bg-green-50 flex items-center justify-center p-4">
                            <div class="bg-white rounded-3xl shadow-2xl p-8 text-center max-w-md">
                                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900 mb-2">Pembayaran Berhasil!</h2>
                                <p class="text-gray-600 mb-4">Terima kasih, pembayaran Anda telah berhasil diproses.</p>
                                <div class="animate-spin w-6 h-6 border-2 border-green-600 border-t-transparent rounded-full mx-auto"></div>
                            </div>
                        </div>
                    `;
                    setTimeout(() => {
                        window.location.href = "payment_notification.php?order_id='. $order_id_unique .'&status=success";
                    }, 2000);
                },
                onPending: function(result) {
                    console.log("Payment pending:", result);
                    alert("Pembayaran Anda tertunda! Silakan selesaikan pembayaran.");
                    window.location.href = "../index.php";
                },
                onError: function(result) {
                    console.log("Payment error:", result);
                    alert("Pembayaran gagal! Silakan coba lagi.");
                    // Show retry button
                    document.getElementById("retryPayment").classList.remove("hidden");
                },
                onClose: function() {
                    console.log("Payment popup closed");
                    // Show retry button
                    document.getElementById("retryPayment").classList.remove("hidden");
                }
            });
        }
        
        // Retry payment button
        document.getElementById("retryPayment").addEventListener("click", function() {
            this.classList.add("hidden");
            startPayment();
        });
    </script>
</body>
</html>';
    exit();

} catch (Exception $e) {
    die('Error saat berkomunikasi dengan Midtrans: ' . $e->getMessage());
}

?>