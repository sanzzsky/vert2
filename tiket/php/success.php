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
                    },
                    animation: {
                        'bounce-slow': 'bounce 2s infinite',
                        'pulse-slow': 'pulse 3s infinite',
                        'fade-in': 'fadeIn 1s ease-in-out',
                        'slide-up': 'slideUp 0.8s ease-out',
                        'scale-in': 'scaleIn 0.6s ease-out'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(30px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        },
                        scaleIn: {
                            '0%': { transform: 'scale(0.8)', opacity: '0' },
                            '100%': { transform: 'scale(1)', opacity: '1' }
                        }
                    }
                }
            }
        }
    </script>
</head>
<<<<<<< HEAD
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
=======
<body class="min-h-screen bg-gradient-to-br from-indigo-200 via-violet-100 to-white">
    <!-- Animated Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-10 left-10 w-32 h-32 bg-green-200 rounded-full opacity-20 animate-pulse-slow"></div>
        <div class="absolute top-1/3 right-20 w-24 h-24 bg-blue-200 rounded-full opacity-30 animate-bounce-slow"></div>
        <div class="absolute bottom-20 left-1/4 w-20 h-20 bg-indigo-200 rounded-full opacity-25 animate-pulse-slow"></div>
        <div class="absolute bottom-1/3 right-1/3 w-16 h-16 bg-green-300 rounded-full opacity-20 animate-bounce-slow"></div>
    </div>
>>>>>>> 2005cd255a16b305643b4d0d94bd350d4b064bb4

    <!-- Navigation Steps -->
    <div class="container mx-auto max-w-4xl px-4 pt-8 relative z-10">
        <div class="flex items-center justify-center mb-8">
            <!-- Desktop Version -->
            <div class="hidden md:flex items-center space-x-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-white text-sm"></i>
                    </div>
                    <span class="ml-2 text-sm text-gray-600">Informasi Pembeli</span>
                </div>
                <div class="w-12 h-0.5 bg-green-400"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-white text-sm"></i>
                    </div>
                    <span class="ml-2 text-sm text-gray-600">Metode Pembayaran</span>
                </div>
                <div class="w-12 h-0.5 bg-green-400"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-white text-sm"></i>
                    </div>
                    <span class="ml-2 text-sm text-green-600 font-medium">Konfirmasi</span>
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
                <div class="flex-1 h-0.5 bg-green-400 mx-2"></div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-check text-white text-xs"></i>
                    </div>
                    <span class="text-xs text-gray-600 text-center">Bayar</span>
                </div>
                <div class="flex-1 h-0.5 bg-green-400 mx-2"></div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-check text-white text-xs"></i>
                    </div>
                    <span class="text-xs text-green-600 font-medium text-center">Selesai</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-4xl px-4 pb-16 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Success Message Section -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden animate-slide-up">
                    <!-- Success Header -->
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-8 text-white text-center">
                        <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4 animate-scale-in">
                            <i class="fas fa-check-circle text-5xl text-white animate-bounce-slow"></i>
                        </div>
                        <h1 class="text-3xl font-bold mb-2">Pembayaran Berhasil!</h1>
                        <p class="text-green-100 opacity-90">Selamat! Transaksi Anda telah berhasil diproses</p>
                    </div>

                    <!-- Success Content -->
                    <div class="p-8">
                        <div class="text-center mb-8">
                            <div class="flex items-center justify-center text-sm text-gray-500">
                                <i class="fas fa-clock mr-2"></i>
                                <span>Diproses pada <?= date('d M Y, H:i') ?> WIB</span>
                            </div>
                        </div>

                        <!-- Next Steps -->
                        <div class="bg-blue-50 rounded-2xl p-6 mb-8">
                            <h3 class="font-semibold text-blue-800 mb-4 flex items-center">
                                <i class="fas fa-info-circle mr-2"></i>
                                Langkah Selanjutnya
                            </h3>
                            <div class="space-y-3 text-sm text-blue-700">
                                <div class="flex items-start">
                                    <i class="fas fa-envelope mt-1 mr-3 text-blue-500"></i>
                                    <span>Cek email Anda untuk mendapatkan e-tiket dan detail acara</span>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-mobile-alt mt-1 mr-3 text-blue-500"></i>
                                    <span>Simpan e-tiket di smartphone Anda untuk memudahkan akses</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="../index.php" class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 px-6 rounded-2xl shadow-xl transition-all duration-300 transform hover:scale-[1.02] text-center">
                                <i class="fas fa-home mr-2"></i>
                                Kembali ke Beranda
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary Section -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden sticky top-8 animate-fade-in">
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 p-6 text-white">
                        <h2 class="text-xl font-bold flex items-center">
                            <i class="fas fa-receipt mr-2"></i>
                            Detail Pesanan
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-6">
                            <!-- Order ID -->
                            <div class="bg-green-50 rounded-xl p-4 border-2 border-green-200">
                                <div class="text-center">
                                    <div class="text-sm text-gray-600 mb-1">Order ID</div>
                                    <div class="text-lg font-bold text-green-600 font-mono tracking-wider">
                                        <?= htmlspecialchars($order['order_id']) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Event Details -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                    Detail Acara
                                </h3>
                                <div class="space-y-3 text-sm">
                                    <div>
                                        <div class="text-gray-600">Nama Acara</div>
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($order['event_name']) ?></div>
                                    </div>
                                    <div>
                                        <div class="text-gray-600">Tanggal</div>
                                        <div class="font-medium text-gray-800"><?= date('d M Y', strtotime($order['event_date'])) ?></div>
                                    </div>
                                    <div>
                                        <div class="text-gray-600">Tipe Tiket</div>
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($order['ticket_type_name']) ?></div>
                                    </div>
                                    <div>
                                        <div class="text-gray-600">Jumlah Tiket</div>
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($order['quantity']) ?> tiket</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Buyer Details -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-user mr-2 text-green-500"></i>
                                    Detail Pembeli
                                </h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center">
                                        <i class="fas fa-user w-4 text-gray-400 mr-2"></i>
                                        <span class="text-gray-800"><?= htmlspecialchars($order['buyer_name']) ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-envelope w-4 text-gray-400 mr-2"></i>
                                        <span class="text-gray-800 truncate"><?= htmlspecialchars($order['buyer_email']) ?></span>
                                    </div>
                                    <?php if (!empty($order['buyer_phone'])): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone w-4 text-gray-400 mr-2"></i>
                                        <span class="text-gray-800"><?= htmlspecialchars($order['buyer_phone']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Payment Info -->
                            <div class="bg-blue-50 rounded-xl p-4 border-2 border-blue-200">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-700 font-medium">Total Dibayar</span>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-blue-600">
                                            Rp <?= number_format($order['total_price'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 flex items-center justify-between">
                                    <span>Metode: <?= htmlspecialchars($order['payment_method']) ?></span>
                                    <span class="text-green-600 font-medium">✓ LUNAS</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Add celebration animation on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Create confetti effect
            function createConfetti() {
                const colors = ['#10B981', '#3B82F6', '#8B5CF6', '#F59E0B', '#EF4444'];
                const confettiCount = 50;
                
                for (let i = 0; i < confettiCount; i++) {
                    const confetti = document.createElement('div');
                    confetti.style.position = 'fixed';
                    confetti.style.width = '10px';
                    confetti.style.height = '10px';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.top = '-10px';
                    confetti.style.borderRadius = '50%';
                    confetti.style.pointerEvents = 'none';
                    confetti.style.zIndex = '9999';
                    confetti.style.animation = `fall ${Math.random() * 3 + 2}s linear forwards`;
                    
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 5000);
                }
            }
            
            // Add CSS for falling animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fall {
                    to {
                        transform: translateY(100vh) rotate(360deg);
                    }
                }
            `;
            document.head.appendChild(style);
            
            // Trigger confetti after a short delay
            setTimeout(createConfetti, 500);
        });
    </script>
</body>
</html>