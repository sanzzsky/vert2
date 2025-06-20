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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-200 via-violet-100 to-white">
    <!-- Navigation Steps -->
     <div class="container mx-auto max-w-4xl px-4 pt-8">
        <div class="flex items-center justify-center mb-8">
            <!-- Desktop Version -->
            <div class="hidden md:flex items-center space-x-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-white text-sm"></i>
                    </div>
                    <span class="ml-2 text-sm text-gray-600">Informasi Pembeli</span>
                </div>
                <div class="w-12 h-0.5 bg-gray-300"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">2</span>
                    </div>
                    <span class="ml-2 text-sm text-blue-600 font-medium">Metode Pembayaran</span>
                </div>
                <div class="w-12 h-0.5 bg-gray-300"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                        <span class="text-gray-500 text-sm">3</span>
                    </div>
                    <span class="ml-2 text-sm text-gray-500">Konfirmasi</span>
                </div>
            </div>
            
            <!-- Mobile Version -->
            <div class="flex md:hidden items-center justify-between w-full max-w-xs">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-check text-white text-xs"></i>
                    </div>
                    <span class="text-xs text-gray-600 text-center">Info</span>
                </div>
                <div class="flex-1 h-0.5 bg-gray-300 mx-2"></div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center mb-2">
                        <span class="text-white text-xs font-bold">2</span>
                    </div>
                    <span class="text-xs text-blue-600 font-medium text-center">Bayar</span>
                </div>
                <div class="flex-1 h-0.5 bg-gray-300 mx-2"></div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mb-2">
                        <span class="text-gray-500 text-xs">3</span>
                    </div>
                    <span class="text-xs text-gray-500 text-center">Selesai</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-4xl px-4 pb-16">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Payment Methods Section -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-white">
                        <h1 class="text-3xl font-bold mb-2">Pilih Metode Pembayaran</h1>
                        <p class="text-blue-100 opacity-90">Pilih cara pembayaran yang paling nyaman untuk Anda</p>
                    </div>

                    <div class="p-8">
                        <form action="process_payment.php" method="POST" id="paymentForm">
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
                                <h3 class="text-lg font-semibold text-gray-800 mb-6">Metode Pembayaran (Simulasi)</h3>
                                
                                <!-- BCA Virtual Account -->
                                <label class="group relative flex items-center p-6 border-2 border-gray-200 rounded-2xl hover:border-blue-400 hover:shadow-lg cursor-pointer transition-all duration-300 hover:bg-blue-50">
                                    <input type="radio" name="payment_method" value="BCA Virtual Account" class="sr-only" checked>
                                    <div class="absolute inset-0 rounded-2xl border-2 border-transparent group-has-[:checked]:border-blue-500 group-has-[:checked]:bg-blue-50 transition-all duration-200"></div>
                                    <div class="relative flex items-center w-full">
                                        <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mr-4">
                                            <i class="fas fa-university text-white text-lg"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800">BCA Virtual Account</h4>
                                            <p class="text-sm text-gray-500">Transfer melalui ATM, Mobile Banking, atau Internet Banking</p>
                                        </div>
                                        <div class="w-5 h-5 border-2 border-gray-300 rounded-full group-has-[:checked]:border-blue-500 group-has-[:checked]:bg-blue-500 flex items-center justify-center">
                                            <div class="w-2 h-2 bg-white rounded-full opacity-0 group-has-[:checked]:opacity-100 transition-opacity duration-200"></div>
                                        </div>
                                    </div>
                                </label>

                                <!-- GoPay -->
                                <label class="group relative flex items-center p-6 border-2 border-gray-200 rounded-2xl hover:border-green-400 hover:shadow-lg cursor-pointer transition-all duration-300 hover:bg-green-50">
                                    <input type="radio" name="payment_method" value="GoPay" class="sr-only">
                                    <div class="absolute inset-0 rounded-2xl border-2 border-transparent group-has-[:checked]:border-green-500 group-has-[:checked]:bg-green-50 transition-all duration-200"></div>
                                    <div class="relative flex items-center w-full">
                                        <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center mr-4">
                                            <i class="fas fa-mobile-alt text-white text-lg"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800">GoPay / GoTo</h4>
                                            <p class="text-sm text-gray-500">Bayar dengan saldo GoPay atau GoTo Pay</p>
                                        </div>
                                        <div class="w-5 h-5 border-2 border-gray-300 rounded-full group-has-[:checked]:border-green-500 group-has-[:checked]:bg-green-500 flex items-center justify-center">
                                            <div class="w-2 h-2 bg-white rounded-full opacity-0 group-has-[:checked]:opacity-100 transition-opacity duration-200"></div>
                                        </div>
                                    </div>
                                </label>

                                <!-- OVO -->
                                <label class="group relative flex items-center p-6 border-2 border-gray-200 rounded-2xl hover:border-purple-400 hover:shadow-lg cursor-pointer transition-all duration-300 hover:bg-purple-50">
                                    <input type="radio" name="payment_method" value="OVO" class="sr-only">
                                    <div class="absolute inset-0 rounded-2xl border-2 border-transparent group-has-[:checked]:border-purple-500 group-has-[:checked]:bg-purple-50 transition-all duration-200"></div>
                                    <div class="relative flex items-center w-full">
                                        <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mr-4">
                                            <i class="fas fa-wallet text-white text-lg"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800">OVO</h4>
                                            <p class="text-sm text-gray-500">Bayar dengan saldo OVO atau OVO PayLater</p>
                                        </div>
                                        <div class="w-5 h-5 border-2 border-gray-300 rounded-full group-has-[:checked]:border-purple-500 group-has-[:checked]:bg-purple-500 flex items-center justify-center">
                                            <div class="w-2 h-2 bg-white rounded-full opacity-0 group-has-[:checked]:opacity-100 transition-opacity duration-200"></div>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <div class="mt-8 pt-6 border-t border-gray-100">
                                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 px-8 rounded-2xl shadow-xl transition-all duration-300 transform hover:scale-[1.02] hover:shadow-2xl">
                                    <div class="flex items-center justify-center">
                                        <i class="fas fa-credit-card mr-3"></i>
                                        Lanjutkan Pembayaran
                                    </div>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Order Summary Section -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden sticky top-8">
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 p-6 text-white">
                        <h2 class="text-xl font-bold">Ringkasan Pesanan</h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="bg-gray-50 rounded-xl p-4">
                                <h3 class="font-semibold text-gray-800 mb-2"><?= htmlspecialchars($event['event_name']) ?></h3>
                                <div class="text-sm text-gray-600 space-y-1">
                                    <div class="flex justify-between">
                                        <span>Tipe Tiket:</span>
                                        <span class="font-medium"><?= htmlspecialchars($ticket_type_name) ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Jumlah:</span>
                                        <span class="font-medium"><?= $quantity ?> tiket</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 rounded-xl p-4 border-2 border-blue-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700 font-medium">Total Pembayaran</span>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-blue-600">
                                            Rp <?= number_format($total_price, 0, ',', '.') ?>
                                        </div>
                                        <div class="text-xs text-gray-500">Sudah termasuk pajak</div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t pt-4">
                                <h4 class="font-semibold text-gray-800 mb-3">Detail Pembeli</h4>
                                <div class="text-sm text-gray-600 space-y-2">
                                    <div class="flex items-center">
                                        <i class="fas fa-user w-4 text-gray-400 mr-2"></i>
                                        <span><?= htmlspecialchars($buyer_name) ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-envelope w-4 text-gray-400 mr-2"></i>
                                        <span class="truncate"><?= htmlspecialchars($buyer_email) ?></span>
                                    </div>
                                    <?php if ($buyer_phone): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone w-4 text-gray-400 mr-2"></i>
                                        <span><?= htmlspecialchars($buyer_phone) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="mt-6 bg-green-50 border border-green-200 rounded-2xl p-4">
                    <div class="flex items-start">
                        <i class="fas fa-shield-alt text-green-600 mt-1 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-green-800 mb-1">Pembayaran Aman</h4>
                            <p class="text-sm text-green-700">Transaksi Anda dilindungi dengan enkripsi SSL 256-bit</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add smooth hover effects for payment options
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove active state from all labels
                document.querySelectorAll('label').forEach(label => {
                    label.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
                });
                
                // Add active state to selected label
                if (this.checked) {
                    this.closest('label').classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
                }
            });
        });

        // Set initial active state
        document.querySelector('input[name="payment_method"]:checked').closest('label').classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
    </script>
</body>
</html>