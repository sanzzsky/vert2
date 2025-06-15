<?php
session_start();
require 'php/db.php';

// Logika pencarian yang sudah benar
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($searchQuery)) {
    $sql = "SELECT * FROM tickets 
            WHERE status = 'active' 
            AND (event_name LIKE ? OR category LIKE ?) 
            ORDER BY event_date ASC";
    
    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $searchQuery . "%";
    $stmt->execute([$searchTerm, $searchTerm]);

} else {
    $stmt = $conn->query("SELECT * FROM tickets WHERE status = 'active' ORDER BY event_date ASC");
}

$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Concert Ticket Sales</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/src/output.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <style>
        .profile-dropdown .dropdown-content { display: none; position: absolute; right: 0; background-color: white; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 10; border-radius: 0.5rem; overflow: hidden; margin-top: 0.5rem; }
        .profile-dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a, .dropdown-content span { color: black; padding: 12px 16px; text-decoration: none; display: block; text-align: left; }
        .dropdown-content a:hover { background-color: #f1f1f1; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-400 via-violet-200 to-white font-sans text-gray-800 flex flex-col min-h-screen">

    <header class="flex justify-between items-center px-8 py-6">
        <div>
            <h2 class="text-2xl font-bold text-black">Concert Ticket Sales</h2>
            <p class="text-sm text-black">Temukan Event menarik</p>
        </div>
        <nav class="flex items-center gap-6">
            <?php if (isset($_SESSION['username'])): ?>
                <div class="relative" x-data="{ open: false }">
                    <img 
                        src="images/images.jpg" 
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
                        <a href="php/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="bg-violet-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-violet-700">LOGIN</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="flex justify-center px-4 mb-6 pt-5">
        <form action="" method="GET" class="w-full sm:w-2/3 lg:w-1/2">
            <div class="relative">
                <input type="text" name="search" placeholder="Cari berdasarkan nama atau kategori event..."
                       class="w-full px-6 py-3 pr-12 rounded-xl shadow-sm border border-gray-300 focus:outline-none focus:ring-2 focus:ring-violet-500 transition bg-white"
                       value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit" class="absolute inset-y-0 right-0 flex items-center px-4 text-gray-500 hover:text-violet-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </button>
            </div>
        </form>
    </div>

    <div class="flex justify-center gap-4 mb-10">
        <?php
          $kategori = [ '../images/music.avif',
                        '../images/foodfest.jpg',
                        '../images/sportfest.png',
                        '../images/artfest.webp',
                        '../images/cinemafest.avif',];
          foreach ($kategori as $k) {
            echo "<img src='icons/$k' alt='kategori' class='w-14 h-14 rounded-xl bg-white p-2 shadow-md cursor-pointer hover:shadow-lg transition' />";
          }
        ?>
    </div>

    <main class="px-4 sm:px-6 md:px-12 flex-grow w-full">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-2 sm:gap-0">
            <h2 class="text-xl font-semibold text-center sm:text-left">
                <?php if (!empty($searchQuery)): ?>
                    Hasil Pencarian untuk "<?= htmlspecialchars($searchQuery) ?>"
                <?php else: ?>
                    Available Events
                <?php endif; ?>
            </h2>
            <a href="index.php" class="text-orange-500 text-sm font-medium">Lihat Semua</a>
        </div>
        
        <div class="flex flex-wrap justify-center gap-6 px-6 py-6">
            <?php if (count($tickets) > 0): ?>
                <?php foreach($tickets as $ticket): ?>
                <?php
                    $location = isset($ticket['location']) && !empty($ticket['location']) ? htmlspecialchars($ticket['location']) : 'Lokasi TBA';
                    $event_time = isset($ticket['event_time']) && !empty($ticket['event_time']) ? date('H:i', strtotime($ticket['event_time'])) . ' WIB' : 'Waktu TBA';
                ?>
                <div class="w-64 bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="h-40 w-full overflow-hidden">
                        <img src="images/<?= htmlspecialchars($ticket['image']) ?>" alt="<?= htmlspecialchars($ticket['event_name']) ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-grow p-4 flex flex-col justify-between">
                        <div>
                            <h4 class="font-bold text-base mb-2 leading-tight text-gray-900"><?= htmlspecialchars($ticket['event_name']) ?></h4>
                            <div class="flex items-center text-sm text-gray-600 mb-1">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
                                <?= date('d F Y', strtotime($ticket['event_date'])) ?>
                            </div>
                            <div class="flex items-center text-sm text-gray-600 mb-1">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <?= $event_time ?>
                            </div>
                            <div class="flex items-center text-sm text-gray-600 mb-3">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                                <?= $location ?>
                            </div>
                        </div>
                        <div class="flex justify-end items-center mt-3">
                            <a href="php/detail_event.php?id=<?= $ticket['id'] ?>" class="bg-violet-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-violet-700 transition-colors duration-200 shadow-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-600">Tidak ada event yang ditemukan.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="bg-violet-900 text-gray-200 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; <?= date("Y") ?> Concert Ticket Sales. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="js/script.js"></script>
</body>
</html>