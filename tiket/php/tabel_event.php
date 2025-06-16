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
                $sql_select = "SELECT image FROM tickets WHERE id = :id";
                $stmt_select = $conn->prepare($sql_select);
                $stmt_select->execute([':id' => $id]);
                $event = $stmt_select->fetch(PDO::FETCH_ASSOC);
                
                if ($event) {
                    if (!empty($event['image'])) {
                        $target_dir = "../images/";
                        $image_path = $target_dir . $event['image'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    
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
                $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'error';
            }
        } else {
            $_SESSION['flash_message'] = 'ID tidak valid!';
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
    
    <div class="fixed inset-y-0 left-0 z-50 w-64 gradient-bg transform transition-transform duration-300 ease-in-out lg:translate-x-0">
        <div class="flex items-center justify-center h-16 px-4 border-b border-indigo-500/20">
            <a href="admin.php" class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                    <i class="fas fa-ticket-alt text-primary text-sm"></i>
                </div>
                <h1 class="text-white text-xl font-bold">TIKETFEST.ID</h1>
            </a>
        </div>
        
        <nav class="mt-8 px-4 space-y-2">
            <div class="text-indigo-200 text-xs uppercase tracking-wider font-semibold mb-4 px-3">NAVIGATION</div>
            
            <a href="admin.php" class="flex items-center px-4 py-3 text-indigo-200 hover:bg-white/10 hover:text-white rounded-xl transition-all duration-200 group hover-lift">
                <i class="fas fa-calendar-alt w-5 h-5 mr-3 group-hover:scale-110 transition-transform"></i>
                <span>Event Management</span>
            </a>

            <a href="tabel_event.php" class="flex items-center px-4 py-3 bg-white/20 text-white rounded-xl shadow-lg">
                <i class="fas fa-table w-5 h-5 mr-3"></i>
                <span>Tabel Event</span>
            </a>
        </nav>
        
        <div class="absolute bottom-4 left-4 right-4">
            <div class="glass-effect rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-white rounded-full mx-auto mb-2 flex items-center justify-center">
                    <i class="fas fa-user-shield text-primary"></i>
                </div>
                <div class="text-white text-sm font-medium">Admin Panel</div>
                <div class="text-indigo-200 text-xs">v2.0</div>
            </div>
        </div>
    </div>

    <div class="lg:ml-64">
        <header class="bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200/50 sticky top-0 z-40">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <nav class="flex items-center space-x-2 text-sm">
                        <span class="text-gray-500">Dashboard</span>
                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                        <span class="text-primary font-medium">Tabel Event</span>
                    </nav>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">Administrator</div>
                        </div>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600 transition-colors p-2" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-6 animate-fade-in">
            <?php if ($flash_message): ?>
            <div id="flashMessage" class="mb-6 p-4 rounded-lg <?php echo $flash_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?>">
                <?php echo htmlspecialchars($flash_message); ?>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Daftar Semua Event</h2>
                        <p class="text-gray-600 mt-1">Total: <?php echo count($events); ?> event ditemukan.</p>
                    </div>
                    <a href="admin.php" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>Tambah Event
                    </a>
                </div>

                <?php if (empty($events)): ?>
                    <div class="text-center py-16">
                        <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-700">Belum Ada Event</h3>
                        <p class="text-gray-500">Silakan tambahkan event baru melalui halaman Event Management.</p>
                    </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($events as $event): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            <img class="h-12 w-12 rounded-lg object-cover" src="../images/<?php echo htmlspecialchars($event['image'] ?: 'placeholder.png'); ?>" alt="Event">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($event['event_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($event['location']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><?php echo htmlspecialchars($event['category']); ?></span></td>
                                <td class="px-6 py-4"><div class="text-sm text-gray-900"><?php echo date('d M Y', strtotime($event['event_date'])); ?></div></td>
                                <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $event['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo ucfirst($event['status']); ?></span></td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="admin.php?action=edit&id=<?php echo $event['id']; ?>" class="text-yellow-500 hover:text-yellow-700 p-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <form method="POST" onsubmit="return confirm('Anda yakin ingin menghapus event ini?');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                            <!-- Tombol Hapus -->
                                            <button 
                                                type="button" 
                                                onclick="openDeleteModal(<?php echo $event['id']; ?>)" 
                                                class="text-red-500 hover:text-red-700 p-2" 
                                                title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            <!-- Modal Konfirmasi -->
                                            <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                                                <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-lg text-center">
                                                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Konfirmasi Penghapusan</h2>
                                                    <p class="mb-6 text-gray-600">Apakah Anda yakin ingin menghapus event ini?</p>
                                                    
                                                    <form id="deleteForm" method="POST" class="flex justify-center gap-4">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" id="deleteEventId">
                                                        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Batal</button>
                                                        <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<!-- Script Modal -->
<script>
    function openDeleteModal(eventId) {
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteEventId').value = eventId;
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
</script>