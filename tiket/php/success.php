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

<body class="min-h-screen bg-gradient-to-br from-blue-100 via-white to-green-100">
    <!-- Navigation Steps -->
    <div class="container mx-auto max-w-4xl px-4 pt-8">
        <div class="flex items-center justify-center mb-8">
            <!-- Desktop Version -->
            <div class="hidden md:flex items-center space-x-6">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-check text-white text-sm"></i>
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700">Informasi Pembeli</span>
                </div>
                <div class="w-16 h-1 bg-green-400 rounded-full"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-check text-white text-sm"></i>
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700">Metode Pembayaran</span>
                </div>
                <div class="w-16 h-1 bg-green-400 rounded-full"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center shadow-lg animate-pulse">
                        <i class="fas fa-check text-white text-sm"></i>
                    </div>
                    <span class="ml-3 text-sm font-semibold text-green-600">Konfirmasi</span>
                </div>
            </div>
            
            <!-- Mobile Version -->
            <div class="flex md:hidden items-center justify-between w-full max-w-sm">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mb-2 shadow-md">
                        <i class="fas fa-check text-white text-xs"></i>
                    </div>
                    <span class="text-xs text-gray-600 text-center font-medium">Info</span>
                </div>
                <div class="flex-1 h-1 bg-green-400 rounded-full mx-3"></div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mb-2 shadow-md">
                        <i class="fas fa-check text-white text-xs"></i>
                    </div>
                    <span class="text-xs text-gray-600 text-center font-medium">Bayar</span>
                </div>
                <div class="flex-1 h-1 bg-green-400 rounded-full mx-3"></div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mb-2 shadow-md animate-pulse">
                        <i class="fas fa-check text-white text-xs"></i>
                    </div>
                    <span class="text-xs text-green-600 font-semibold text-center">Selesai</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto max-w-6xl px-4 pb-16">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Success Message Section -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Success Card -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden animate-slide-up">
                    <!-- Success Header -->
                    <div class="bg-gradient-to-br from-green-500 via-green-600 to-emerald-600 p-8 text-white text-center relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-full bg-white opacity-10 transform -skew-y-1"></div>
                        <div class="relative z-10">
                            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4 animate-scale-in backdrop-blur-sm">
                                <i class="fas fa-check-circle text-4xl text-white animate-bounce-slow"></i>
                            </div>
                            <h1 class="text-3xl lg:text-4xl font-bold mb-2">Pembayaran Berhasil!</h1>
                            <p class="text-green-100 text-lg opacity-90">Selamat! Transaksi Anda telah berhasil diproses</p>
                        </div>
                    </div>

                    <!-- Success Content -->
                    <div class="p-8">
                        <div class="text-center mb-8">
                            <div class="inline-flex items-center px-4 py-2 bg-gray-100 rounded-full text-sm text-gray-600">
                                <i class="fas fa-clock mr-2 text-gray-500"></i>
                                <span>Diproses pada <?= date('d M Y, H:i') ?> WIB</span>
                            </div>
                        </div>

                        <!-- Next Steps -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 mb-8 border border-blue-100">
                            <h3 class="font-semibold text-blue-800 mb-4 flex items-center">
                                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                                Langkah Selanjutnya
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-start group">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0 group-hover:scale-110 transition-transform">
                                        <i class="fas fa-envelope text-white text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-blue-800 font-medium">Cek Email Anda</p>
                                        <p class="text-blue-600 text-sm">E-tiket dan detail acara telah dikirim ke email Anda</p>
                                    </div>
                                </div>
                                <div class="flex items-start group">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mr-4 flex-shrink-0 group-hover:scale-110 transition-transform">
                                        <i class="fas fa-mobile-alt text-white text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-blue-800 font-medium">Simpan E-Tiket</p>
                                        <p class="text-blue-600 text-sm">Download dan simpan e-tiket di smartphone untuk akses mudah</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="../index.php" class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 px-6 rounded-2xl shadow-lg transition-all duration-300 transform hover:scale-105 hover:shadow-xl text-center group">
                                <i class="fas fa-home mr-2 group-hover:scale-110 transition-transform inline-block"></i>
                                Kembali ke Beranda
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary Section -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden sticky top-8 animate-fade-in">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 p-6 text-white">
                        <h2 class="text-xl font-bold flex items-center">
                            <i class="fas fa-receipt mr-3 text-gray-300"></i>
                            Detail Pesanan
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-6">
                            <!-- Order ID -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 border-2 border-green-200">
                                <div class="text-center">
                                    <div class="text-sm text-gray-600 mb-2 font-medium">Order ID</div>
                                    <div class="text-lg font-bold text-green-600 font-mono tracking-wider break-all">
                                        <?= htmlspecialchars($order['order_id']) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Event Details -->
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                                <h3 class="font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                    Detail Acara
                                </h3>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between items-start">
                                        <span class="text-gray-600 font-medium">Nama Acara:</span>
                                        <span class="font-semibold text-gray-800 text-right ml-2"><?= htmlspecialchars($order['event_name']) ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 font-medium">Tanggal:</span>
                                        <span class="font-semibold text-gray-800"><?= date('d M Y', strtotime($order['event_date'])) ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 font-medium">Tipe Tiket:</span>
                                        <span class="font-semibold text-gray-800"><?= htmlspecialchars($order['ticket_type_name']) ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 font-medium">Jumlah:</span>
                                        <span class="font-semibold text-gray-800"><?= htmlspecialchars($order['quantity']) ?> tiket</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Buyer Details -->
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                                <h3 class="font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-user mr-2 text-green-500"></i>
                                    Detail Pembeli
                                </h3>
                                <div class="space-y-3 text-sm">
                                    <div class="flex items-center">
                                        <i class="fas fa-user w-4 text-gray-400 mr-3 flex-shrink-0"></i>
                                        <span class="text-gray-800 font-medium"><?= htmlspecialchars($order['buyer_name']) ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-envelope w-4 text-gray-400 mr-3 flex-shrink-0"></i>
                                        <span class="text-gray-800 break-all"><?= htmlspecialchars($order['buyer_email']) ?></span>
                                    </div>
                                    <?php if (!empty($order['buyer_phone'])): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone w-4 text-gray-400 mr-3 flex-shrink-0"></i>
                                        <span class="text-gray-800 font-medium"><?= htmlspecialchars($order['buyer_phone']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Payment Info -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border-2 border-blue-200">
                                <div class="flex justify-between items-start mb-3">
                                    <span class="text-gray-700 font-semibold">Total Dibayar</span>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-blue-600">
                                            <?= formatRupiah($order['total_price']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-credit-card mr-2"></i>
                                        <span><?= htmlspecialchars($order['payment_method']) ?></span>
                                    </div>
                                    <div class="flex items-center text-green-600 font-bold">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <span>LUNAS</span>
                                    </div>
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
                    confetti.style.width = '8px';
                    confetti.style.height = '8px';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.top = '-10px';
                    confetti.style.borderRadius = '50%';
                    confetti.style.pointerEvents = 'none';
                    confetti.style.zIndex = '9999';
                    confetti.style.animation = `fall ${Math.random() * 3 + 2}s linear forwards`;
                    confetti.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                    
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
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
            
            // Trigger confetti after a short delay
            setTimeout(createConfetti, 500);
            
            // Add smooth scroll behavior
            document.documentElement.style.scrollBehavior = 'smooth';
        });
    </script>
</body>
</html>