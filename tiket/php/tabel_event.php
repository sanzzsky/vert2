<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// === HANDLE POST REQUESTS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- DELETE EVENT ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if ($id) {
            try {
                // Ambil data event termasuk statusnya
                $sql_select = "SELECT image, status FROM tickets WHERE id = :id";
                $stmt_select = $conn->prepare($sql_select);
                $stmt_select->execute([':id' => $id]);
                $event = $stmt_select->fetch(PDO::FETCH_ASSOC);
                
                if ($event) {
                    // PERUBAHAN: Cek status event sebelum menghapus
                    if ($event['status'] === 'active') {
                        $_SESSION['flash_message'] = 'Error: Event yang aktif tidak dapat dihapus. Nonaktifkan terlebih dahulu.';
                        $_SESSION['flash_type'] = 'error';
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();
                    }

                    // Hapus file gambar jika ada
                    if (!empty($event['image'])) {
                        $target_dir = "../images/";
                        $image_path = $target_dir . $event['image'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    
                    // Hapus dari database
                    $sql_delete = "DELETE FROM tickets WHERE id = :id";
                    $stmt_delete = $conn->prepare($sql_delete);
                    $stmt_delete->execute([':id' => $id]);
                    
                    $_SESSION['flash_message'] = 'Event berhasil dihapus!';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Event tidak ditemukan!';
                    $_SESSION['flash_type'] = 'error';
                }
            } catch (PDOException $e) {
                // Menangani error foreign key constraint (jika ada order terkait)
                if ($e->getCode() == '23000') {
                    $_SESSION['flash_message'] = 'Error: Event ini tidak dapat dihapus karena sudah memiliki data pesanan (order).';
                } else {
                    $_SESSION['flash_message'] = 'Error Database: ' . $e->getMessage();
                }
                $_SESSION['flash_type'] = 'error';
            }
        } else {
            $_SESSION['flash_message'] = 'ID Event tidak valid!';
            $_SESSION['flash_type'] = 'error';
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// === GET EVENTS DATA ===
try {
    $sql = "SELECT * FROM tickets ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = [];
    $error_message = "Error saat mengambil data event: " . $e->getMessage();
}

$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabel Event - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1', 'primary-dark': '#4f46e5',
                        sidebar: '#1e1b4b', 'sidebar-hover': '#312e81',
                        accent: '#06b6d4', 'accent-dark': '#0891b2'
                    },
                    fontFamily: { 'inter': ['Inter', 'system-ui', 'sans-serif'] },
                    animation: { 'fade-in': 'fadeIn 0.5s ease-in-out', 'slide-up': 'slideUp 0.3s ease-out' },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0', transform: 'translateY(10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } }
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100 font-inter min-h-screen">
    
    <!-- Sidebar -->
    <button 
        id="sidebarToggle" 
        class="fixed top-4 left-4 z-50 lg:hidden bg-gradient-to-r from-indigo-500 to-purple-600 text-white p-3 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105"
    >
        <i class="fas fa-bars text-lg"></i>
    </button>

    <!-- Overlay -->
    <div 
        id="sidebarOverlay" 
        class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden opacity-0 pointer-events-none transition-opacity duration-300"
    ></div>

    <!-- Sidebar -->
    <div 
        id="sidebar" 
        class="fixed inset-y-0 left-0 z-50 w-64 gradient-bg transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0"
    >
        <!-- Header -->
        <div class="flex items-center justify-between h-16 px-4 border-b border-indigo-500/20">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                    <i class="fas fa-ticket-alt text-primary text-sm"></i>
                </div>
                <h1 class="text-white text-xl font-bold">TIKETFEST.ID</h1>
            </div>
            
            <!-- Close Button (Mobile) -->
            <button 
                id="sidebarClose" 
                class="lg:hidden text-white hover:text-gray-300 transition-colors duration-200"
            >
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Navigation -->
        <nav class="mt-8 px-4 space-y-2">
            <div class="text-indigo-200 text-xs uppercase tracking-wider font-semibold mb-4 px-3">NAVIGATION</div>
            
            <a href="../php/admin.php" class="flex items-center px-4 py-3  text-white  backdrop-blur-sm hover:bg-white/10 hover:text-white rounded-xl transition-all duration-200 group hover:-translate-y-0.5">
                <i class="fas fa-calendar-alt w-5 h-5 mr-3 group-hover:scale-110 transition-transform"></i>
                <span>Event Management</span>
            </a>

            <a href="../php/tabel_event.php" class="flex items-center px-4 py-3 text-indigo-200 hover:bg-white/10 hover:text-white rounded-xl transition-all duration-200 group hover:-translate-y-0.5">
                <i class="fas fa-chart-pie w-5 h-5 mr-3 group-hover:scale-110 transition-transform"></i>
                <span>Tabel Event</span>
            </a>

        <div class="absolute bottom-4 left-4 right-4">
            <div class="glass-effect rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-white rounded-full mx-auto mb-2 flex items-center justify-center"><i class="fas fa-user-shield text-primary"></i></div>
                <div class="text-white text-sm font-medium">Administrator</div>
                <div class="text-indigo-200 text-xs">v2.0</div>
            </div>
        </div>
    </div>

    <div class="lg:ml-64">
        <header class="bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200/50 sticky top-0 z-40">
                <div class="px-6 py-4 flex items-center justify-between">
                
                <div class="flex items-center space-x-4">
                        <button class="lg:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100">
                            <i class="fas fa-bars"></i>
                        </button>
                        <nav class="flex items-center space-x-2 text-sm">
                            <span class="text-gray-500">Dashboard</span>
                            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            <span class="text-primary font-medium">Event Table</span>
                        </nav>
                    </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right"><div class="text-sm font-medium text-gray-900">Administrator</div></div>
                    <a href="logout.php" class="text-gray-500 hover:text-red-600 transition-colors p-2" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </header>
        
        <main class="p-6 animate-fade-in">
            <?php if ($flash_message): ?>
            <div id="flashMessage" class="mb-6 p-4 rounded-lg <?php echo $flash_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?>">
                <?php echo htmlspecialchars($flash_message); ?>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Daftar Semua Event</h2>
                    <a href="admin.php" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg flex items-center"><i class="fas fa-plus mr-2"></i>Tambah Event</a>
                </div>

                <?php if (empty($events)): ?>
                    <div class="text-center py-16"><i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i><h3 class="text-lg font-medium text-gray-700">Belum Ada Event</h3></div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($events as $event): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4"><div class="flex items-center"><div class="flex-shrink-0 h-12 w-12"><img class="h-12 w-12 rounded-lg object-cover" src="../images/<?php echo htmlspecialchars($event['image'] ?: 'placeholder.png'); ?>" alt="Event"></div><div class="ml-4"><div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($event['event_name']); ?></div></div></div></td>
                                <td class="px-6 py-4"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"><?php echo htmlspecialchars($event['category']); ?></span></td>
                                <td class="px-6 py-4"><div class="text-sm text-gray-900"><?php echo date('d M Y', strtotime($event['event_date'])); ?></div></td>
                                <td class="px-6 py-4"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $event['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo ucfirst($event['status']); ?></span></td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="admin.php?action=edit&id=<?php echo $event['id']; ?>" class="text-yellow-500 hover:text-yellow-700 p-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        
                                        <?php if ($event['status'] === 'inactive'): ?>
                                            <form method="POST" onsubmit="return confirm('Anda yakin ingin menghapus event ini? Tindakan ini tidak dapat dibatalkan.');" class="inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                                <button type="submit" class="text-red-500 hover:text-red-700 p-2" title="Hapus"><i class="fas fa-trash"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <button class="text-gray-300 p-2 cursor-not-allowed" title="Nonaktifkan event terlebih dahulu untuk menghapus"><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- tambahan untuk offcanvas pada mobile -->
<script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('opacity-0', 'pointer-events-none');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0', 'pointer-events-none');
        }

        sidebarToggle.addEventListener('click', openSidebar);
        sidebarClose.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);

        // Close sidebar when clicking on nav links (mobile)
        const navLinks = sidebar.querySelectorAll('nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    closeSidebar();
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                overlay.classList.add('opacity-0', 'pointer-events-none');
            }
        });
    </script>


</body>
</html>