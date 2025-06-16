<?php
session_start();
require 'db.php';

// Data dari form detail_event.php
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$ticket_type_name = filter_input(INPUT_POST, 'ticket_type_name', FILTER_SANITIZE_STRING);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!$event_id || !$ticket_type_name || !$quantity || $quantity <= 0) {
    header("Location: index.php");
    exit();
}

// Ambil data event
$stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Event tidak ditemukan.");
}

// Ambil detail jenis tiket
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

// Ambil data pengguna jika login untuk isi form otomatis
$user = []; // Inisialisasi variabel user
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
}


// Hitung harga
$price_per_ticket = $selected_ticket_details['price'];
$subtotal = $price_per_ticket * $quantity;
$service_fee = $subtotal * 0.05;
$total_price = $subtotal + $service_fee;

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo htmlspecialchars($event['event_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-dropdown .dropdown-content { display: none; position: absolute; right: 0; background-color: white; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 20; border-radius: 0.5rem; overflow: hidden; margin-top: 0.5rem; }
        .profile-dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a, .dropdown-content span { color: black; padding: 12px 16px; text-decoration: none; display: block; text-align: left; font-size: 0.875rem; }
        .dropdown-content a:hover { background-color: #f1f1f1; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5', secondary: '#7C3AED', accent: '#06B6D4',
                        success: '#10B981', warning: '#F59E0B', danger: '#EF4444',
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-indigo-400 via-violet-200 to-white font-sans flex flex-col min-h-screen">
    
    <header class="bg-white shadow-lg border-b-4 border-primary">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-primary to-secondary rounded-full flex items-center justify-center">
                            <i class="fas fa-ticket-alt text-white text-lg"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-800">TICKETFEST.ID</h1>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center space-x-2 text-gray-600">
                        <i class="fas fa-shield-alt text-success"></i>
                        <span class="text-sm font-medium">Pembayaran Aman</span>
                    </div>
                    <div class="border-l pl-4">
                        <?php if (isset($_SESSION['username'])): ?>
                            <div class="profile-dropdown relative">
                                <img src="images/images.jpg" alt="Profile" class="w-8 h-8 rounded-full cursor-pointer object-cover">
                                <div class="dropdown-content">
                                    <span class="block px-4 py-2 text-sm text-gray-700">Hello, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                                    <a href="php/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="../login.php" title="Login" class="text-gray-500 hover:text-primary transition-colors">
                                <i class="fas fa-user-circle text-2xl"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="bg-gradient-to-r from-warning to-orange-400 shadow-md">
        <div class="container mx-auto px-6 py-3">
            <div class="flex items-center justify-between text-white">
                <div class="flex items-center space-x-3">
                    <div class="animate-pulse-slow"><i class="fas fa-clock text-xl"></i></div>
                    <div><span class="font-bold text-lg" id="timer">15:00</span><span class="mx-2">•</span><span class="font-medium">Selesaikan dalam</span></div>
                </div>
                <div class="text-sm font-medium" id="timer-warning" style="display: none;"><i class="fas fa-exclamation-triangle mr-1"></i>Waktu hampir habis!</div>
            </div>
        </div>
    </div>

    <main class="container mx-auto px-6 py-8 flex-grow">
    <form action="payment.php" method="POST" id="orderForm">
        
        <div class="mb-8">
            <div class="flex items-center justify-center space-x-4 md:space-x-8">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-success rounded-full flex items-center justify-center text-white font-bold text-sm"><i class="fas fa-check"></i></div>
                    <span class="ml-2 text-sm font-medium text-success">Pilih Tiket</span>
                </div>
                <div class="w-12 h-1 bg-success rounded"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white font-bold text-sm">2</div>
                    <span class="ml-2 text-sm font-medium text-primary">Data Pembeli</span>
                </div>
                <div class="w-12 h-1 bg-gray-300 rounded"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-gray-500 font-bold text-sm">3</div>
                    <span class="ml-2 text-sm font-medium text-gray-500">Pembayaran</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            
            <div class="lg:col-span-2 order-1 lg:order-2">
                <div class="bg-white rounded-2xl shadow-xl p-6 sticky top-24 border border-gray-100">
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Ringkasan Pesanan</h2>
                        <p class="text-sm text-gray-500 mt-1">Periksa kembali detail tiket Anda</p>
                    </div>
                    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-xl p-4 mb-6 text-white relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -translate-y-16 translate-x-16"></div>
                        <div class="relative z-10">
                            <h3 class="font-bold text-lg mb-2 truncate"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                            <div class="space-y-1 text-sm">
                                <div class="flex items-center"><i class="fas fa-calendar-alt w-4 mr-2"></i><span><?php echo date('d F Y', strtotime($event['event_date'])); ?></span></div>
                                <?php if ($event['event_time']): ?><div class="flex items-center"><i class="fas fa-clock w-4 mr-2"></i><span><?php echo date('H:i', strtotime($event['event_time'])); ?> WIB</span></div><?php endif; ?>
                                <?php if ($event['location']): ?><div class="flex items-center"><i class="fas fa-map-marker-alt w-4 mr-2"></i><span class="truncate"><?php echo htmlspecialchars($event['location']); ?></span></div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-xl">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($ticket_type_name); ?></h4>
                                <p class="text-sm text-gray-500"><?php echo formatRupiah($price_per_ticket); ?> per tiket</p>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-lg text-gray-800">×<?php echo $quantity; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 pt-4 space-y-3">
                        <div class="flex justify-between text-gray-600"><span>Subtotal (<?php echo $quantity; ?> tiket)</span><span class="font-medium"><?php echo formatRupiah($subtotal); ?></span></div>
                        <div class="flex justify-between text-gray-600"><span class="flex items-center">Biaya Layanan<i class="fas fa-info-circle ml-1 text-xs text-gray-400" title="5% dari subtotal"></i></span><span class="font-medium"><?php echo formatRupiah($service_fee); ?></span></div>
                        <div class="border-t border-gray-200 pt-3 mt-2">
                            <div class="flex justify-between items-center"><span class="text-lg font-bold text-gray-800">Total Pembayaran</span><span class="text-2xl font-bold text-primary"><?php echo formatRupiah($total_price); ?></span></div>
                        </div>
                    </div>
                    <div class="mt-6 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center justify-center space-x-2 text-green-700">
                            <i class="fas fa-shield-alt"></i><span class="text-sm font-medium">Transaksi 100% Aman & Terpercaya</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3 order-2 lg:order-1">
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                    <div class="mb-8">
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-10 h-10 bg-gradient-to-r from-accent to-blue-500 rounded-lg flex items-center justify-center"><i class="fas fa-user-edit text-white"></i></div>
                            <h2 class="text-2xl font-bold text-gray-800">Informasi Pembeli</h2>
                        </div>
                        <p class="text-gray-600">Lengkapi data diri Anda untuk melanjutkan pembelian</p>
                    </div>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label for="buyer_name" class="block text-sm font-semibold text-gray-700"><i class="fas fa-user mr-2 text-gray-400"></i>Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" id="buyer_name" name="buyer_name" required placeholder="Masukkan nama lengkap sesuai identitas" class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-4 focus:ring-primary/20" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-semibold text-gray-700"><i class="fas fa-id-card mr-2 text-gray-400"></i>Jenis Identitas <span class="text-danger">*</span></label>
                                <select name="id_type" required class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-4 focus:ring-primary/20">
                                    <option value="">Pilih jenis identitas</option>
                                    <option value="KTP">KTP</option>
                                    <option value="SIM">SIM</option>
                                    <option value="Passport">Passport</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="id_number" class="block text-sm font-semibold text-gray-700"><i class="fas fa-hashtag mr-2 text-gray-400"></i>Nomor Identitas <span class="text-danger">*</span></label>
                                <input type="text" id="id_number" name="id_number" required placeholder="Nomor identitas" class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-4 focus:ring-primary/20">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label for="buyer_email" class="block text-sm font-semibold text-gray-700"><i class="fas fa-envelope mr-2 text-gray-400"></i>Email <span class="text-danger">*</span></label>
                            <input type="email" id="buyer_email" name="buyer_email" required placeholder="email@contoh.com" class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-4 focus:ring-primary/20" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </div>
                        <div class="space-y-2">
                            <label for="buyer_phone" class="block text-sm font-semibold text-gray-700"><i class="fab fa-whatsapp mr-2 text-green-500"></i>Nomor WhatsApp <span class="text-danger">*</span></label>
                            <input type="tel" id="buyer_phone" name="buyer_phone" required placeholder="08123456789" class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-4 focus:ring-primary/20" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="bg-blue-50 border-l-4 border-primary p-4 rounded-lg">
                            <div class="flex items-start space-x-3">
                                <input type="checkbox" id="terms" name="terms" required class="mt-1 w-4 h-4 text-primary border-2 border-gray-300 rounded focus:ring-primary">
                                <label for="terms" class="text-sm text-gray-700">
                                    Saya menyetujui <a href="#" class="text-primary font-medium hover:underline">syarat dan ketentuan</a> 
                                    serta <a href="#" class="text-primary font-medium hover:underline">kebijakan privasi</a> yang berlaku.
                                    <span class="text-danger">*</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8">
                        <button type="submit" id="submitBtn" class="w-full bg-gradient-to-r from-primary to-secondary hover:from-primary/90 hover:to-secondary/90 text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-[0.98] shadow-lg">
                            <div class="flex items-center justify-center space-x-3">
                                <i class="fas fa-credit-card text-lg"></i><span class="text-lg">Lanjut ke Pembayaran</span>
                            </div>
                        </button>
                    </div>

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

    <footer class="bg-violet-900 text-gray-200 py-8 mt-16">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">TICKETFEST.ID</h3>
                    <p class="text-gray-400">Platform tiket online terpercaya untuk berbagai event menarik.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Bantuan</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition">FAQ</a></li>
                        <li><a href="#" class="hover:text-white transition">Hubungi Kami</a></li>
                        <li><a href="#" class="hover:text-white transition">Kebijakan Refund</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Keamanan</h4>
                    <div class="flex items-center space-x-2 text-green-400"><i class="fas fa-shield-alt"></i><span class="text-sm">SSL Encrypted</span></div>
                </div>
            </div>
            <div class="border-t border-violet-800 mt-8 pt-8 text-center text-gray-400"><p>&copy; <?php echo date("Y"); ?> TICKETFEST.ID . All rights reserved.</p></div>
        </div>
    </footer>

    <!-- POP UP WAKTU ISI DATA HABIS -->
    <div id="timeoutModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-lg text-center animate-scaleIn">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Waktu Habis</h2>
            <p class="mb-6 text-gray-600">Sesi pembelian Anda telah berakhir.</p>
            <button onclick="redirectToHome()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Kembali ke Beranda</button>
        </div>
    </div>

    <script>
        let timeLeft = 15 * 60, timerInterval, formSubmitted = false;
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60), seconds = timeLeft % 60;
            document.getElementById("timer").textContent = `${minutes.toString().padStart(2,"0")}:${seconds.toString().padStart(2,"0")}`;
            if (timeLeft <= 180) {
                document.getElementById("timer-warning").style.display = "block";
                document.getElementById("timer").closest(".bg-gradient-to-r").className = "bg-gradient-to-r from-danger to-red-500 shadow-md";
            }
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                if (!formSubmitted) {
                    document.getElementById('timeoutModal').classList.remove('hidden');
                    // window.location.href = "../index.php";
                }
                
            } 
            timeLeft--;
        }
        timerInterval = setInterval(updateTimer, 1e3); updateTimer();
        document.getElementById("orderForm").addEventListener("submit", function(e) {
            formSubmitted = true; clearInterval(timerInterval);
            const t = document.getElementById("submitBtn");
            t.disabled = true;
            t.innerHTML = `<div class="flex items-center justify-center space-x-3"><i class="fas fa-spinner fa-spin text-lg"></i><span class="text-lg">Memproses...</span></div>`;
        });

        // fungsi redirectToHome
        function redirectToHome() {
            window.location.href = "../index.php";
        }

    </script>
</body>
</html>