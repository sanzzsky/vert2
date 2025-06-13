<?php
session_start();
require 'db.php';

// --- Logika dari kode Anda, dipertahankan dan ditingkatkan ---

// Data ini didapat dari form di halaman detail_event.php
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$ticket_type_name = filter_input(INPUT_POST, 'ticket_type_name', FILTER_SANITIZE_STRING);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

// Jika data awal tidak ada, kembali ke halaman utama.
if (!$event_id || !$ticket_type_name || !$quantity || $quantity <= 0) {
    header("Location: ../index.php");
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
if (is_array($ticket_types)) {
    foreach ($ticket_types as $type) {
        if ($type['type_name'] === $ticket_type_name) {
            $selected_ticket_details = $type;
            break;
        }
    }
}

if (!$selected_ticket_details) {
    die("Jenis tiket tidak valid.");
}


// --- PERUBAHAN: CEK LOGIN TIDAK LAGI WAJIB ---
// Inisialisasi variabel user sebagai array kosong.
$user = []; 
// Jika user ternyata login, baru kita ambil datanya untuk isi form otomatis.
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Pembelian Tiket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .profile-dropdown .dropdown-content { display: none; position: absolute; right: 0; background-color: white; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 20; border-radius: 0.5rem; overflow: hidden; margin-top: 0.5rem; }
        .profile-dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a, .dropdown-content span { color: black; padding: 12px 16px; text-decoration: none; display: block; text-align: left; font-size: 0.875rem; }
        .dropdown-content a:hover { background-color: #f1f1f1; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    
    <header class="bg-violet-700 text-white py-4 shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between">
                <a href="../index.php" class="text-2xl font-bold">Beranda</a>
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['username'])): ?>
                        <div class="profile-dropdown relative">
                            <img src="../images/images.jpg" alt="Profile" class="w-8 h-8 rounded-full cursor-pointer object-cover">
                            <div class="dropdown-content">
                                <span class="px-4 py-2 text-sm text-gray-700">Hello, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                                <a href="logout.php" class="text-sm">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="../login.php" class="bg-violet-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-violet-700">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

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
                
                <div class="lg:col-span-2 space-y-6">
                    
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-8 h-8 bg-violet-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-violet-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-800">Data Pemesan</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label for="buyer_name" class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" id="buyer_name" name="buyer_name" required class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Identitas <span class="text-red-500">*</span></label>
                                <select name="id_type" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500">
                                    <option value="KTP">KTP</option>
                                    <option value="SIM">SIM</option>
                                    <option value="KTM">KTM</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div>
                                <label for="id_number" class="block text-sm font-medium text-gray-700 mb-2">Nomor Identitas <span class="text-red-500">*</span></label>
                                <input type="text" id="id_number" name="id_number" required class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500">
                            </div>

                            <div class="md:col-span-2">
                                <label for="buyer_email" class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                                <input type="email" id="buyer_email" name="buyer_email" required placeholder="e-Tiket akan dikirimkan ke email ini" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                <p class="text-xs text-gray-500 mt-1">e-Tiket akan dikirimkan ke email ini</p>
                            </div>

                            <div class="md:col-span-2">
                                <label for="buyer_phone" class="block text-sm font-medium text-gray-700 mb-2">No. Whatsapp <span class="text-red-500">*</span></label>
                                <input type="tel" id="buyer_phone" name="buyer_phone" required placeholder="Contoh: 08123456789" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                        <div class="flex items-center mb-6">
                            <div class="w-8 h-8 bg-violet-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-violet-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path></svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-800">Rincian Pesanan</h2>
                        </div>

                        <div class="bg-gradient-to-r from-violet-600 to-purple-600 rounded-lg p-4 mb-4 text-white">
                            <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                            <div class="text-sm space-y-1">
                                <p>📅 <?php echo date('d M Y', strtotime($event['event_date'])); ?></p>
                                <?php if ($event['event_time']): ?><p>🕐 <?php echo date('H:i', strtotime($event['event_time'])); ?></p><?php endif; ?>
                                <?php if ($event['location']): ?><p>📍 <?php echo htmlspecialchars($event['location']); ?></p><?php endif; ?>
                            </div>
                        </div>

                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between font-medium">
                                <span>Tiket</span>
                                <span>Jumlah</span>
                            </div>
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-medium"><?php echo htmlspecialchars($ticket_type_name); ?></div>
                                    <div class="text-sm text-gray-600"><?php echo formatRupiah($price_per_ticket); ?></div>
                                </div>
                                <span class="font-medium">x<?php echo $quantity; ?></span>
                            </div>
                        </div>
                        
                        <div class="border-t pt-4 space-y-2">
                            <div class="flex justify-between text-sm"><span class="text-gray-600">Subtotal</span> <span class="text-gray-800"><?php echo formatRupiah($subtotal); ?></span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-600">Biaya Layanan (5%)</span> <span class="text-gray-800"><?php echo formatRupiah($service_fee); ?></span></div>
                            <div class="flex justify-between text-lg font-bold border-t pt-2 mt-2"><span>Total</span> <span class="text-violet-600"><?php echo formatRupiah($total_price); ?></span></div>
                        </div>

                        <button type="submit" id="submitBtn" class="w-full bg-violet-600 hover:bg-violet-700 text-white font-semibold py-3 rounded-lg transition duration-200 mt-6 shadow-md">
                            Lanjutkan Pembayaran
                        </button>
                        
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <input type="hidden" name="ticket_type_name" value="<?php echo htmlspecialchars($ticket_type_name); ?>">
                        <input type="hidden" name="quantity" value="<?php echo $quantity; ?>">
                        <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">
                    </div>
                </div>
            </div>
        </form>
    </main>
    
    <script>
        let timeLeft = 15 * 60;
        let timerInterval;
        let formSubmitted = false;

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            if (timeLeft <= 120) {
                document.getElementById('timer-warning').style.display = 'block';
                document.getElementById('timer').parentElement.parentElement.parentElement.classList.replace('bg-yellow-400', 'bg-red-500');
                document.getElementById('timer').parentElement.parentElement.parentElement.classList.add('text-white');
            }
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                if (!formSubmitted) {
                    alert('Waktu habis! Pembelian tiket dibatalkan. Silakan mulai lagi.');
                    window.location.href = '../index.php';
                }
            }
            timeLeft--;
        }
        timerInterval = setInterval(updateTimer, 1000);
        updateTimer();
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            formSubmitted = true;
            clearInterval(timerInterval);
        });
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