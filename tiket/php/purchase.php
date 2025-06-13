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
$subtotal = $price_per_ticket * $quantity;
$service_fee = $subtotal * 0.05; // 5% dari subtotal
$total_price = $subtotal + $service_fee;

// Format mata uang
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Pembelian Tiket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B5CF6',
                        secondary: '#7C3AED',
                        accent: '#A855F7',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-600 to-purple-700 text-white py-4 shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Beranda</h1>
                <div class="flex items-center space-x-4">
                    <button class="bg-primary hover:bg-secondary px-4 py-2 rounded-lg text-sm font-medium transition duration-200">
                        Login
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Progress Bar with Timer -->
    <div class="bg-yellow-400 py-2">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between text-sm font-medium">
                <div class="flex items-center">
                    <span class="text-gray-800" id="timer">15:00</span>
                    <span class="mx-2 text-gray-800">|</span>
                    <span class="text-gray-800">Segera Lengkapi Data Pesananmu</span>
                </div>
                <div class="text-gray-800" id="timer-warning" style="display: none;">
                    ⚠️ Waktu hampir habis!
                </div>
            </div>
        </div>
    </div>

    <main class="container mx-auto px-4 py-8">
        <form action="process_order.php" method="POST" id="orderForm">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column - Form Data -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Data Pemesan Section -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-800">Data Pemesan</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label for="buyer_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="buyer_name" name="buyer_name" required
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition duration-200">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Identitas <span class="text-red-500">*</span>
                                </label>
                                <select name="id_type" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition duration-200">
                                    <option value="KTP">KTP</option>
                                    <option value="NIK">NIK</option>
                                    <option value="SIM">SIM</option>
                                    <option value="KTM">KTM</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div>
                                <label for="id_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nomor Identitas <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="id_number" name="id_number" required
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition duration-200">
                            </div>

                            <div class="md:col-span-2">
                                <label for="buyer_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" id="buyer_email" name="buyer_email" required
                                    placeholder="e-Tiket akan dikirimkan ke email ini"
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition duration-200">
                                <p class="text-xs text-gray-500 mt-1">e-Tiket akan dikirimkan ke email ini</p>
                            </div>

                            <div class="md:col-span-2">
                                <label for="buyer_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    No. Whatsapp <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" id="buyer_phone" name="buyer_phone" required
                                    placeholder="Contoh: 08123456789"
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition duration-200">
                            </div>
                        </div>
                    </div>

                    <!-- Data Pemilik Tiket Section -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-800">Data Pemilik Tiket</h2>
                        </div>

                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-purple-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="text-sm text-purple-700">
                                    Pastikan nama pemilik tiket sesuai dengan identitas yang akan dibawa saat masuk venue
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <?php for ($i = 1; $i <= $quantity; $i++): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Pemilik Tiket Ke-<?php echo $i; ?> <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="ticket_owner_<?php echo $i; ?>" required
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition duration-200">
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                </div>

                <!-- Right Column - Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <div class="flex items-center mb-6">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-800">Rincian Pesanan</h2>
                        </div>

                        <!-- Event Details -->
                        <div class="bg-gradient-to-r from-purple-500 to-purple-700 rounded-lg p-4 mb-4 text-white">
                            <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                            <div class="text-sm space-y-1">
                                <div class="flex justify-between items-center">
                                    <span>📅 <?php echo date('d M Y', strtotime($event['event_date'])); ?></span>
                                </div>
                                <?php if ($event['event_time']): ?>
                                <div class="flex justify-between items-center">
                                    <span>🕐 <?php echo date('H:i', strtotime($event['event_time'])); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($event['location']): ?>
                                <div class="flex justify-between items-center">
                                    <span>📍 <?php echo htmlspecialchars($event['location']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Ticket Details -->
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Tiket</span>
                                <span>Jumlah</span>
                            </div>
                            <div class="flex justify-between">
                                <div>
                                    <div class="font-medium"><?php echo htmlspecialchars($ticket_type_name); ?></div>
                                    <div class="text-sm text-gray-600"><?php echo formatRupiah($price_per_ticket); ?></div>
                                </div>
                                <span class="font-medium">x<?php echo $quantity; ?></span>
                            </div>
                        </div>

                        <!-- Voucher Code -->
                        <div class="mb-4">
                            <div class="flex">
                                <input type="text" name="voucher_code" placeholder="Masukkan kode voucher" 
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm">
                                <button type="button" class="px-4 py-2 bg-primary text-white rounded-r-lg hover:bg-secondary transition duration-200 text-sm font-medium">
                                    Terapkan
                                </button>
                            </div>
                        </div>

                        <!-- Price Summary -->
                        <div class="border-t pt-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal (<?php echo $quantity; ?> tiket)</span>
                                <span class="text-gray-600"><?php echo formatRupiah($subtotal); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Biaya Layanan (5%)</span>
                                <span class="text-gray-600"><?php echo formatRupiah($service_fee); ?></span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t pt-2">
                                <span>Total</span>
                                <span class="text-primary"><?php echo formatRupiah($total_price); ?></span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="submitBtn" class="w-full bg-primary hover:bg-secondary text-white font-semibold py-3 rounded-lg transition duration-200 mt-6">
                            Lanjutkan Pembayaran
                        </button>

                        <!-- Hidden Fields -->
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <input type="hidden" name="ticket_type_name" value="<?php echo htmlspecialchars($ticket_type_name); ?>">
                        <input type="hidden" name="quantity" value="<?php echo $quantity; ?>">
                        <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">
                        <input type="hidden" name="subtotal" value="<?php echo $subtotal; ?>">
                        <input type="hidden" name="service_fee" value="<?php echo $service_fee; ?>">
                    </div>
                </div>

            </div>
        </form>
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-purple-700 to-purple-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2025 Concert Ticket Sales. All rights reserved.</p>
        </div>
    </footer>

    <!-- Timer and Form Validation Script -->
    <script>
        // Timer functionality
        let timeLeft = 15 * 60; // 15 minutes in seconds
        let timerInterval;
        let formSubmitted = false;

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const timerDisplay = document.getElementById('timer');
            const warningDisplay = document.getElementById('timer-warning');
            
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Show warning when less than 2 minutes left
            if (timeLeft <= 120) {
                warningDisplay.style.display = 'block';
                timerDisplay.parentElement.parentElement.classList.remove('bg-yellow-400');
                timerDisplay.parentElement.parentElement.classList.add('bg-red-400');
            }
            
            // Time's up
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                if (!formSubmitted) {
                    alert('Waktu habis! Pembelian tiket dibatalkan. Silakan mulai lagi.');
                    window.location.href = 'index.php';
                }
            }
            
            timeLeft--;
        }

        // Start timer
        timerInterval = setInterval(updateTimer, 1000);
        updateTimer(); // Initial call

        // Form submission handling
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            formSubmitted = true;
            clearInterval(timerInterval);
        });

        // Page visibility handling (pause timer when page is not visible)
        let isVisible = true;
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                isVisible = false;
                clearInterval(timerInterval);
            } else {
                isVisible = true;
                if (!formSubmitted && timeLeft > 0) {
                    timerInterval = setInterval(updateTimer, 1000);
                }
            }
        });

        // Warning before page unload
        window.addEventListener('beforeunload', function(e) {
            if (!formSubmitted && timeLeft > 0) {
                e.preventDefault();
                e.returnValue = 'Anda akan kehilangan data yang telah diisi. Yakin ingin meninggalkan halaman?';
                return e.returnValue;
            }
        });
    </script>
</body>
</html>