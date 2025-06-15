<?php
// --- Fungsionalitas Inti dari Kode 1 Dipertahankan ---
session_start();
require 'db.php';

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($event_id === 0) {
    die("Halaman tidak ditemukan. ID Acara tidak valid.");
}

// Menggunakan PDO dari Kode 1
$stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ? AND status = 'active'");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Acara yang Anda cari tidak ditemukan atau sudah tidak tersedia.");
}

$ticket_types = json_decode($event['ticket_types'], true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail: <?= htmlspecialchars($event['event_name']) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Style tambahan untuk dropdown profil di header baru */
        .profile-dropdown .dropdown-content { display: none; position: absolute; right: 0; background-color: white; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 20; border-radius: 0.5rem; overflow: hidden; margin-top: 0.5rem; }
        .profile-dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a, .dropdown-content span { color: black; padding: 12px 16px; text-decoration: none; display: block; text-align: left; font-size: 0.875rem; }
        .dropdown-content a:hover { background-color: #f1f1f1; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-400 via-violet-200 to-white font-sans text-gray-800 flex flex-col min-h-screen">

    <header class="bg-white/80 backdrop-blur-sm shadow-sm sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <nav class="flex space-x-6">
                        <a href="../index.php" class="text-violet-600 font-medium hover:text-violet-700">Beranda</a>
                    </nav>
                </div>
                <nav class="flex items-center gap-6">
                    <?php if (isset($_SESSION['username'])): ?>
                        <div class="relative" x-data="{ open: false }">
                            <img 
                                src="../images/images.jpg" 
                                alt="Profile" 
                                class="w-8 h-8 rounded-full cursor-pointer object-cover" 
                                @click="open = !open"
                            >
                            <div 
                                x-show="open" 
                                @click.away="open = false"
                                class="absolute right-0 mt-2 w-40 bg-white shadow-lg rounded-lg py-2 z-50"
                            >
                                <span class="block px-4 py-2 text-sm text-gray-700">Hello, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="../login.php" class="bg-violet-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-violet-700">LOGIN</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow w-full">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-12">
            
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden sticky top-24">
                    <img src="../images/<?= htmlspecialchars($event['image']) ?>" 
                         alt="<?= htmlspecialchars($event['event_name']) ?>" 
                         class="w-full h-auto object-cover">
                </div>
            </div>

            <div class="lg:col-span-3">
                <div class="space-y-6">
                    <span class="bg-violet-100 text-violet-700 px-3 py-1 rounded-full text-sm font-semibold capitalize">
                        <?= htmlspecialchars($event['category']) ?>
                    </span>
                    <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 leading-tight">
                        <?= htmlspecialchars($event['event_name']) ?>
                    </h1>
                    <div class="flex items-center text-lg text-gray-600">
                        <svg class="w-5 h-5 mr-3 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
                        <?= date('d F Y', strtotime($event['event_date'])) ?>
                        <?= !empty($event['event_time']) ? ' • ' . date('H:i', strtotime($event['event_time'])) . ' WIB' : '' ?>
                    </div>
                    <div class="flex items-center text-lg text-gray-600">
                        <svg class="w-5 h-5 mr-3 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                        <?= htmlspecialchars($event['location']) ?>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border overflow-hidden pt-2">
                        <div class="flex border-b">
                            <button onclick="showTab('description')" id="description-tab" class="flex-1 px-6 py-3 text-center font-semibold bg-violet-600 text-white transition-colors">Deskripsi</button>
                            <button onclick="showTab('tickets')" id="tickets-tab" class="flex-1 px-6 py-3 text-center font-semibold bg-gray-50 text-gray-600 transition-colors">Beli Tiket</button>
                        </div>
                        
                        <div class="p-6">
                            <div id="description-content" class="tab-content prose max-w-none">
                                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                            </div>

                            <div id="tickets-content" class="tab-content hidden">
                                <form action="../php/purchase.php" method="POST" class="space-y-4">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    
                                    <div>
                                        <label for="ticket_type" class="block text-sm font-medium text-gray-700 mb-1">Pilih Jenis Tiket:</label>
                                        <select id="ticket_type" name="ticket_type_name" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-violet-500 focus:border-violet-500">
                                            <option value="">-- Pilih Tiket --</option>
                                            <?php if (is_array($ticket_types)) : ?>
                                                <?php foreach ($ticket_types as $type): ?>
                                                    <option value="<?= htmlspecialchars($type['type_name']) ?>">
                                                        <?= htmlspecialchars($type['type_name']) ?> - Rp <?= number_format($type['price'], 0, ',', '.') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Jumlah:</label>
                                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="10" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-violet-500 focus:border-violet-500">
                                    </div>
                                    
                                    <button type="submit" class="w-full bg-violet-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-violet-700 transition-colors duration-200 shadow-lg">
                                        Beli Sekarang
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-violet-900 text-gray-200 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; <?= date("Y") ?> Concert Ticket Sales. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        function showTab(tabName) {
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.add('hidden'));
            
            const tabs = document.querySelectorAll('[id$="-tab"]');
            tabs.forEach(tab => {
                tab.classList.remove('bg-violet-600', 'text-white');
                tab.classList.add('bg-gray-50', 'text-gray-600');
            });
            
            document.getElementById(tabName + '-content').classList.remove('hidden');
            
            const activeTab = document.getElementById(tabName + '-tab');
            activeTab.classList.remove('bg-gray-50', 'text-gray-600');
            activeTab.classList.add('bg-violet-600', 'text-white');
        }

        document.addEventListener('DOMContentLoaded', function() {
            showTab('description');
        });
    </script>
</body>
</html>