<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
require_once 'db.php';

// ===================================================================
// BAGIAN 1: CONTROLLER - Memproses semua Aksi (Add, Update & Delete)
// ===================================================================

// Cek jika ada request POST (dari submit form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Logika untuk DELETE Event ---
    if (isset($_POST['delete_ticket'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if ($id) {
            // Ambil data event terlebih dahulu untuk mendapatkan nama gambar
            $sql_select = "SELECT image FROM tickets WHERE id = :id";
            $stmt_select = $conn->prepare($sql_select);
            $stmt_select->execute([':id' => $id]);
            $event = $stmt_select->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                // Hapus file gambar jika ada
                if (!empty($event['image'])) {
                    $target_dir = "../images/";
                    $image_path = $target_dir . $event['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                // Hapus record dari database
                $sql_delete = "DELETE FROM tickets WHERE id = :id";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->execute([':id' => $id]);
                
                header("Location: admin.php?status=delete_success");
                exit();
            } else {
                header("Location: admin.php?status=delete_error&msg=Event tidak ditemukan");
                exit();
            }
        } else {
            header("Location: admin.php?status=delete_error&msg=ID tidak valid");
            exit();
        }
    }
    
    // --- Logika untuk UPDATE Event ---
    if (isset($_POST['update_ticket'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        // Proses Gambar
        $image_name = $_POST['existing_image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../images/";
            if (!empty($image_name) && file_exists($target_dir . $image_name)) {
                unlink($target_dir . $image_name);
            }
            $new_image_name = basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $new_image_name;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_name = $new_image_name;
            }
        }

        // Proses Jenis Tiket
        $ticket_types_array = [];
        if (isset($_POST['types']) && is_array($_POST['types'])) {
            foreach ($_POST['types'] as $type) {
                if (!empty($type['type_name']) && !empty($type['price']) && !empty($type['quantity'])) {
                    $ticket_types_array[] = ['type_name' => trim($type['type_name']), 'price' => (float)$type['price'], 'quantity' => (int)$type['quantity']];
                }
            }
        }
        $ticket_types_json = json_encode($ticket_types_array);

        // Query UPDATE
        $sql = "UPDATE tickets SET event_name = :event_name, description = :description, category = :category, location = :location, event_date = :event_date, event_time = :event_time, image = :image, status = :status, ticket_types = :ticket_types WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':event_name' => $_POST['event_name'], 
            ':description' => $_POST['description'], 
            ':category' => $_POST['category'], 
            ':location' => $_POST['location'], 
            ':event_date' => $_POST['event_date'], 
            ':event_time' => !empty($_POST['event_time']) ? $_POST['event_time'] : null, 
            ':image' => $image_name, 
            ':status' => $_POST['status'], 
            ':ticket_types' => $ticket_types_json, 
            ':id' => $id
        ]);

        header("Location: admin.php?status=update_success");
        exit();
    }

    // --- Logika untuk ADD Event ---
    if (isset($_POST['add_ticket'])) {
        // Validasi field wajib
        $required_fields = ['event_name', 'category', 'description', 'event_date', 'event_time', 'location'];
        $missing = false;
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing = true;
                break;
            }
        }

        // Validasi gambar wajib
        $image_missing = !(isset($_FILES['image']) && $_FILES['image']['error'] == 0);

        // Minimal satu jenis tiket
        $ticket_types_array = [];
        if (isset($_POST['types']) && is_array($_POST['types'])) {
            foreach ($_POST['types'] as $type) {
                if (!empty($type['type_name']) && !empty($type['price']) && !empty($type['quantity'])) {
                    $ticket_types_array[] = [
                        'type_name' => trim($type['type_name']),
                        'price' => (float)$type['price'],
                        'quantity' => (int)$type['quantity']
                    ];
                }
            }
        }
        // Function error jika fields tidak lengkap
        if ($missing || $image_missing || count($ticket_types_array) == 0) {
            echo "
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap' rel='stylesheet'>
                <style>
                    .swal2-popup {
                        font-family: 'Poppins', sans-serif;
                    }
                </style>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Data Tidak Lengkap',
                            html: 'Harap mengisi semua field:<br><b>Nama, Kategori, Deskripsi, Tanggal, Waktu, Tempat, Gambar</b><br>dan minimal satu jenis tiket!',
                            confirmButtonText: 'Kembali'
                        }).then(() => {
                            window.history.back();
                        });
                    });
                </script>
                ";

            exit();
        }

        // Proses upload gambar
        $image_name = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../images/";
            $image_name = basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image_name;
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        }

        $ticket_types_json = json_encode($ticket_types_array);

        $sql = "INSERT INTO tickets (event_name, description, category, location, event_date, event_time, image, status, ticket_types, price) VALUES (:event_name, :description, :category, :location, :event_date, :event_time, :image, :status, :ticket_types, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':event_name' => $_POST['event_name'],
            ':description' => $_POST['description'],
            ':category' => $_POST['category'],
            ':location' => $_POST['location'],
            ':event_date' => $_POST['event_date'],
            ':event_time' => !empty($_POST['event_time']) ? $_POST['event_time'] : null,
            ':image' => $image_name,
            ':status' => $_POST['status'],
            ':ticket_types' => $ticket_types_json
        ]);

        header("Location: admin.php?status=add_success");
        exit();
    }

}

// ===================================================================
// BAGIAN 2: PERSIAPAN DATA untuk Tampilan (View)
// ===================================================================

$edit_mode = false;
$ticket_to_edit = null;

// Cek jika ada request GET untuk 'edit'
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $id_to_edit = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->execute([$id_to_edit]);
    $ticket_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    // Jika tidak ada data, kembali ke mode 'add'
    if (!$ticket_to_edit) {
        $edit_mode = false;
    }
}

// Delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->execute([$id_to_delete]);
    header("Location: admin.php?status=delete_success");
    exit();
}

// Selalu ambil semua data tiket untuk ditampilkan di tabel bawah
$all_tickets = $conn->query("SELECT * FROM tickets ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - TIKETFEST.ID</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        'primary-dark': '#4f46e5',
                        sidebar: '#1e1b4b',
                        'sidebar-hover': '#312e81',
                        accent: '#06b6d4',
                        'accent-dark': '#0891b2'
                    },
                    fontFamily: {
                        'inter': ['Inter', 'system-ui', 'sans-serif']
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'pulse-slow': 'pulse 3s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .input-focus:focus {
            transform: scale(1.02);
            transition: all 0.2s ease-in-out;
        }
        .floating-label {
            transition: all 0.2s ease-in-out;
        }
        .floating-label.active {
            transform: translateY(-24px) scale(0.85);
            color: #6366f1;
        }
        .success-pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100 font-inter min-h-screen">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 gradient-bg transform transition-transform duration-300 ease-in-out lg:translate-x-0">
        <div class="flex items-center justify-center h-16 px-4 border-b border-indigo-500/20">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                    <i class="fas fa-ticket-alt text-primary text-sm"></i>
                </div>
                <h1 class="text-white text-xl font-bold">TIKETFEST.ID</h1>
            </div>
        </div>
        
        <nav class="mt-8 px-4 space-y-2">
            <div class="text-indigo-200 text-xs uppercase tracking-wider font-semibold mb-4 px-3">NAVIGATION</div>
            
            <a href="#" class="flex items-center px-4 py-3 bg-white/20 text-white rounded-xl shadow-lg">
                <i class="fas fa-calendar-alt w-5 h-5 mr-3"></i>
                <span>Event Management</span>
            </a>

            <a href="tabel_event.php" class="flex items-center px-4 py-3 text-indigo-200 hover:bg-white/10 hover:text-white rounded-xl transition-all duration-200 group hover-lift">
                <i class="fas fa-chart-pie w-5 h-5 mr-3 group-hover:scale-110 transition-transform"></i>
                <span>Tabel event</span>
            </a>
            
            <a href="#" class="flex items-center px-4 py-3 text-indigo-200 hover:bg-white/10 hover:text-white rounded-xl transition-all duration-200 group hover-lift">
                <i class="fas fa-file-alt w-5 h-5 mr-3 group-hover:scale-110 transition-transform"></i>
                <span>Reports</span>
            </a>
            
            <a href="#" class="flex items-center px-4 py-3 text-indigo-200 hover:bg-white/10 hover:text-white rounded-xl transition-all duration-200 group hover-lift">
                <i class="fas fa-users w-5 h-5 mr-3 group-hover:scale-110 transition-transform"></i>
                <span>Users</span>
            </a>
        </nav>
        
        <div class="absolute bottom-4 left-4 right-4">
            <div class="glass-effect rounded-xl p-4 text-center">
                <div class="w-12 h-12 bg-white rounded-full mx-auto mb-2 flex items-center justify-center">
                    <i class="fas fa-user-shield text-primary"></i>
                </div>
                <div class="text-white text-sm font-medium">Administrator</div>
                <div class="text-indigo-200 text-xs">v2.0</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-64">
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200/50 sticky top-0 z-40">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button class="lg:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100">
                            <i class="fas fa-bars"></i>
                        </button>
                        <nav class="flex items-center space-x-2 text-sm">
                            <span class="text-gray-500">Dashboard</span>
                            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                            <span class="text-primary font-medium">Event Management</span>
                        </nav>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">Administrator</div>
                            <div class="text-xs text-gray-500">admin@tiketfest.id</div>
                        </div>

                        <!-- ini profile -->
                        <div class="w-10 h-10 bg-gradient-to-r from-primary to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-white font-medium text-sm">A</span>
                        </div>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600 transition-colors p-2">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="p-6 animate-fade-in">
            <!-- Success Messages -->
            <?php if (isset($_GET['status'])): ?>
            <div id="successMessage" class="mb-6 bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl shadow-sm animate-slide-up">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-lg success-pulse"></i>
                    <span class="font-medium">
                        <?php 
                        switch($_GET['status']) {
                            case 'add_success': echo 'Event berhasil ditambahkan!'; break;
                            case 'update_success': echo 'Event berhasil diperbarui!'; break;
                            case 'delete_success': echo 'Event berhasil dihapus!'; break;
                        }
                        ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <!-- Form Section -->
                <div class="xl:col-span-2">
                    <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/50 hover-lift transition-all duration-300">
                        <div class="px-8 py-6 border-b border-gray-200/50 bg-gradient-to-r from-primary/5 to-purple-500/5 rounded-t-2xl">
                            <h2 class="text-2xl font-bold text-gray-900" id="formTitle">
                                <?php echo $edit_mode ? 'Edit Festival/Event' : 'Tambah Festival/Event'; ?>
                            </h2>
                            <p class="text-gray-600 mt-2">
                                <?php echo $edit_mode ? 'Perbarui informasi event yang sudah ada' : 'Buat event baru dengan mengisi form di bawah ini'; ?>
                            </p>
                        </div>
                        
                        <form id="eventForm" class="p-8 space-y-8" action="admin.php" method="post" enctype="multipart/form-data">
                            <!-- Hidden fields for edit mode -->
                            <?php if ($edit_mode): ?>
                            <input type="hidden" name="id" value="<?php echo $ticket_to_edit['id']; ?>">
                            <input type="hidden" name="existing_image" value="<?php echo $ticket_to_edit['image']; ?>">
                            <?php endif; ?>
                            
                            <!-- Event Name -->
                            <div class="relative">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">
                                    <i class="fas fa-star text-primary mr-2"></i>Nama Event
                                </label>
                                <input type="text" name="event_name" value="<?php echo $edit_mode ? htmlspecialchars($ticket_to_edit['event_name']) : ''; ?>" 
                                       placeholder="Masukkan nama event yang menarik..." 
                                       class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-primary/20 focus:border-primary transition-all duration-300 input-focus text-lg font-medium" required>
                                <div class="text-xs text-gray-500 mt-2 flex items-center">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <span id="nameCount">0</span>/100 karakter, minimal 5 karakter
                                </div>
                            </div>

                            <!-- Categories and Status Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fas fa-tags text-accent mr-2"></i>Kategori Event
                                    </label>
                                    <select name="category" class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-primary/20 focus:border-primary transition-all duration-300 bg-white">
                                        <option value="">Pilih kategori event</option>
                                        <option value="Music Festival" <?php echo ($edit_mode && $ticket_to_edit['category'] == 'Music Festival') ? 'selected' : ''; ?>>🎵 Music Festival</option>
                                        <option value="Food Festival" <?php echo ($edit_mode && $ticket_to_edit['category'] == 'Food Festival') ? 'selected' : ''; ?>>🍔 Food Festival</option>
                                        <option value="Art Festival" <?php echo ($edit_mode && $ticket_to_edit['category'] == 'Art Festival') ? 'selected' : ''; ?>>🎨 Art Festival</option>
                                        <option value="Sports Event" <?php echo ($edit_mode && $ticket_to_edit['category'] == 'Sports Event') ? 'selected' : ''; ?>>⚽ Sports Event</option>
                                        <option value="Conference" <?php echo ($edit_mode && $ticket_to_edit['category'] == 'Conference') ? 'selected' : ''; ?>>💼 Conference</option>
                                        <option value="Other" <?php echo ($edit_mode && $ticket_to_edit['category'] == 'Other') ? 'selected' : ''; ?>>📋 Other</option>
                                    </select>
                                </div>
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fas fa-toggle-on text-green-500 mr-2"></i>Status Event
                                    </label>
                                    <select name="status" class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-primary/20 focus:border-primary transition-all duration-300 bg-white">
                                        <option value="active" <?php echo ($edit_mode && $ticket_to_edit['status'] == 'active') ? 'selected' : ''; ?>>✅ Aktif</option>
                                        <option value="inactive" <?php echo ($edit_mode && $ticket_to_edit['status'] == 'inactive') ? 'selected' : ''; ?>>❌ Tidak Aktif</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="relative">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">
                                    <i class="fas fa-align-left text-purple-500 mr-2"></i>Deskripsi Event
                                </label>
                                <textarea name="description" rows="5" placeholder="Ceritakan tentang event Anda, apa yang menarik dan istimewa..." 
                                          class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-primary/20 focus:border-primary transition-all duration-300 resize-none"><?php echo $edit_mode ? htmlspecialchars($ticket_to_edit['description']) : ''; ?></textarea>
                                <div class="text-xs text-gray-500 mt-2">
                                    <i class="fas fa-lightbulb mr-1"></i>
                                    Deskripsi yang menarik akan membantu menarik lebih banyak peserta
                                </div>
                            </div>

                            <!-- Date, Time, Location -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-200/50">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-calendar-check text-blue-500 mr-2"></i>
                                    Waktu & Tempat
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-calendar mr-1"></i>Tanggal
                                        </label>
                                        <input type="date" name="event_date" value="<?php echo $edit_mode ? $ticket_to_edit['event_date'] : ''; ?>" 
                                               class="w-full px-3 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-clock mr-1"></i>Waktu
                                        </label>
                                        <input type="time" name="event_time" value="<?php echo $edit_mode ? $ticket_to_edit['event_time'] : ''; ?>" 
                                               class="w-full px-3 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-map-marker-alt mr-1"></i>Lokasi
                                        </label>
                                        <input type="text" name="location" value="<?php echo $edit_mode ? htmlspecialchars($ticket_to_edit['location']) : ''; ?>" 
                                               placeholder="Jakarta Convention Center" 
                                               class="w-full px-3 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Ticket Types -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-200/50">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                        <i class="fas fa-ticket-alt text-green-500 mr-2"></i>
                                        Jenis & Harga Tiket
                                    </h3>
                                    <button type="button" id="addTicketType" class="bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                                        <i class="fas fa-plus mr-1"></i>Tambah Jenis
                                    </button>
                                </div>
                                
                                <div class="space-y-4">
                                     <div id="ticket-types-container">
                                        <?php 
                                        $ticket_types_data = $edit_mode ? json_decode($ticket_to_edit['ticket_types'], true) : [];
                                        if (!empty($ticket_types_data)):
                                            foreach ($ticket_types_data as $index => $type): ?>
                                                <div class="ticket-type-entry">
                                                    <div><label>Jenis Tiket</label><input type="text" name="types[<?= $index ?>][type_name]" value="<?= htmlspecialchars($type['type_name']) ?>" required></div>
                                                    <div><label>Harga (Rp)</label><input type="number" name="types[<?= $index ?>][price]" value="<?= htmlspecialchars($type['price']) ?>" required></div>
                                                    <div><label>Kuota</label><input type="number" name="types[<?= $index ?>][quantity]" value="<?= htmlspecialchars($type['quantity']) ?>" required></div>
                                                    <div><button type="button" class="remove-btn" onclick="this.parentElement.parentElement.remove()">Hapus</button></div>
                                                </div>
                                            <?php endforeach; 
                                        endif; ?>
                                    </div>
                                </div>
                            </div>
                            <script>
                            document.getElementById('addTicketType').addEventListener('click', function() {
                                const container = document.getElementById('ticket-types-container');
                                const index = Date.now(); // Gunakan timestamp untuk index unik
                                const newEntry = document.createElement('div');
                                newEntry.classList.add('ticket-type-entry');
                                newEntry.innerHTML = `
                                    <div><label>Jenis Tiket</label><input type="text" name="types[${index}][type_name]" placeholder="e.g., VVIP" required></div>
                                    <div><label>Harga (Rp)</label><input type="number" name="types[${index}][price]" placeholder="e.g., 2000000" required></div>
                                    <div><label>Kuota</label><input type="number" name="types[${index}][quantity]" placeholder="e.g., 100" required></div>
                                    <div><button type="button" class="remove-btn" onclick="this.parentElement.parentElement.remove()">Hapus</button></div>
                                `;
                                container.appendChild(newEntry);
                            });
                            </script>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                                <button type="button" id="resetForm" class="px-6 py-3 text-gray-600 hover:text-gray-800 font-medium transition-colors rounded-xl hover:bg-gray-100">
                                    <i class="fas fa-undo mr-2"></i>Reset Form
                                </button>
                                <div class="flex space-x-4">
                                    <?php if ($edit_mode): ?>
                                    <a href="admin.php" class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-medium transition-all duration-300">
                                        <i class="fas fa-times mr-2"></i>Batal
                                    </a>
                                    <button type="submit" name="update_ticket" class="bg-gradient-to-r from-primary to-purple-600 hover:from-primary-dark hover:to-purple-700 text-white px-8 py-3 rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
                                        <i class="fas fa-save mr-2"></i>Update Event
                                    </button>
                                    <?php else: ?>
                                    <button type="submit" name="add_ticket" class="bg-gradient-to-r from-primary to-purple-600 hover:from-primary-dark hover:to-purple-700 text-white px-8 py-3 rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                                        <i class="fas fa-plus mr-2"></i>Tambah Event
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Side Panel -->
                <div class="space-y-6">
                    <!-- Poster Upload -->
                    <!-- Side Panel - Bagian yang perlu ditambahkan setelah div space-y-6 -->
                    <div class="space-y-6">
                        <!-- Poster Upload -->
                        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/50 p-6 hover-lift transition-all duration-300">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-image text-purple-500 mr-2"></i>
                                Poster Event
                            </h3>
                            
                            <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-primary transition-all duration-300 cursor-pointer bg-gradient-to-br from-gray-50 to-white" id="imageUploadArea">
                                <div id="imagePreview" class="<?php echo ($edit_mode && $ticket_to_edit['image']) ? '' : 'hidden'; ?>">
                                    <img id="previewImg" src="<?php echo ($edit_mode && $ticket_to_edit['image']) ? '../images/' . $ticket_to_edit['image'] : ''; ?>" alt="Preview" class="w-full h-48 object-cover rounded-lg mb-4 shadow-lg">
                                    <button type="button" id="changeImage" class="bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300">
                                        <i class="fas fa-edit mr-1"></i>Ganti Gambar
                                    </button>
                                </div>
            
            <div id="uploadPlaceholder" class="<?php echo ($edit_mode && $ticket_to_edit['image']) ? 'hidden' : ''; ?>">
                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-purple-100 to-pink-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-cloud-upload-alt text-2xl text-purple-500"></i>
                </div>
                <p class="text-gray-600 font-medium mb-2">Klik untuk upload poster</p>
                <p class="text-sm text-gray-500">PNG, JPG hingga 10MB</p>
            </div>
        </div>
        
        <input type="file" id="imageInput" name="image" accept="image/*" class="hidden" form="eventForm">
        
        <div class="mt-4 text-xs text-gray-500 bg-blue-50 p-3 rounded-lg border border-blue-200">
            <i class="fas fa-info-circle mr-2 text-blue-500"></i>
            <strong>Tips:</strong> Gunakan gambar dengan rasio 16:9 atau 4:3 untuk hasil terbaik. Ukuran optimal: 1920x1080px
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/50 p-6 hover-lift transition-all duration-300">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
            Statistik Event
        </h3>
        
        <div class="space-y-4">
            <div class="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg border border-blue-200/50">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-calendar-alt text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Total Events</p>
                        <p class="text-xs text-gray-500">Semua event</p>
                    </div>
                </div>
                <span class="text-xl font-bold text-blue-600"><?php echo count($all_tickets); ?></span>
            </div>
            
            <div class="flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200/50">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Events Aktif</p>
                        <p class="text-xs text-gray-500">Sedang berjalan</p>
                    </div>
                </div>
                <span class="text-xl font-bold text-green-600">
                    <?php echo count(array_filter($all_tickets, function($ticket) { return $ticket['status'] == 'active'; })); ?>
                </span>
            </div>
            
            <div class="flex items-center justify-between p-3 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg border border-yellow-200/50">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-pause-circle text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Events Inactive</p>
                        <p class="text-xs text-gray-500">Tidak aktif</p>
                    </div>
                </div>
                <span class="text-xl font-bold text-yellow-600">
                    <?php echo count(array_filter($all_tickets, function($ticket) { return $ticket['status'] == 'inactive'; })); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- TABEL ADMINN -->



<!-- JavaScript untuk Upload Poster -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const imageUploadArea = document.getElementById('imageUploadArea');
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const previewImg = document.getElementById('previewImg');
    const changeImageBtn = document.getElementById('changeImage');

    // Event listener untuk area upload
    imageUploadArea.addEventListener('click', function() {
        imageInput.click();
    });

    // Event listener untuk tombol ganti gambar
    if (changeImageBtn) {
        changeImageBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            imageInput.click();
        });
    }

    // Event listener untuk input file
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validasi ukuran file (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('Ukuran file terlalu besar! Maksimal 10MB.');
                imageInput.value = '';
                return;
            }

            // Validasi tipe file
            if (!file.type.match('image.*')) {
                alert('File harus berupa gambar!');
                imageInput.value = '';
                return;
            }

            // Preview gambar
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                imagePreview.classList.remove('hidden');
                uploadPlaceholder.classList.add('hidden');
                
                // Tambahkan efek animasi
                imagePreview.style.opacity = '0';
                setTimeout(() => {
                    imagePreview.style.transition = 'opacity 0.3s ease-in-out';
                    imagePreview.style.opacity = '1';
                }, 100);
            };
            reader.readAsDataURL(file);
        }
    });

    // Drag and drop functionality
    imageUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        imageUploadArea.classList.add('border-primary', 'bg-primary/5');
    });

    imageUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        imageUploadArea.classList.remove('border-primary', 'bg-primary/5');
    });

    imageUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        imageUploadArea.classList.remove('border-primary', 'bg-primary/5');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            imageInput.files = files;
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            imageInput.dispatchEvent(event);
        }
    });

    // Auto-hide success message
    const successMessage = document.getElementById('successMessage');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            successMessage.style.opacity = '0';
            successMessage.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                successMessage.remove();
            }, 500);
        }, 5000);
    }

    // Character counter for event name
    const eventNameInput = document.querySelector('input[name="event_name"]');
    const nameCount = document.getElementById('nameCount');
    if (eventNameInput && nameCount) {
        eventNameInput.addEventListener('input', function() {
            const length = this.value.length;
            nameCount.textContent = length;
            
            if (length < 5) {
                nameCount.parentElement.classList.add('text-red-500');
                nameCount.parentElement.classList.remove('text-gray-500');
            } else if (length > 90) {
                nameCount.parentElement.classList.add('text-yellow-500');
                nameCount.parentElement.classList.remove('text-gray-500', 'text-red-500');
            } else {
                nameCount.parentElement.classList.add('text-green-500');
                nameCount.parentElement.classList.remove('text-gray-500', 'text-red-500', 'text-yellow-500');
            }
        });
        
        // Initial count
        nameCount.textContent = eventNameInput.value.length;
    }

    // Form reset functionality
    const resetForm = document.getElementById('resetForm');
    if (resetForm) {
        resetForm.addEventListener('click', function() {
            if (confirm('Apakah Anda yakin ingin mengosongkan semua field?')) {
                document.getElementById('eventForm').reset();
                imagePreview.classList.add('hidden');
                uploadPlaceholder.classList.remove('hidden');
                previewImg.src = '';
                
                // Reset character counter
                if (nameCount) {
                    nameCount.textContent = '0';
                    nameCount.parentElement.classList.add('text-gray-500');
                    nameCount.parentElement.classList.remove('text-red-500', 'text-yellow-500', 'text-green-500');
                }
            }
        });
    }
});
</script>

<!-- Tambahan CSS untuk styling -->
<style>
.ticket-type-entry {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
    padding: 1rem;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.ticket-type-entry:hover {
    border-color: #6366f1;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
}

.ticket-type-entry label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.ticket-type-entry input {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.ticket-type-entry input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.ticket-type-entry .remove-btn {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.ticket-type-entry .remove-btn:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

@media (max-width: 768px) {
    .ticket-type-entry {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .ticket-type-entry .remove-btn {
        justify-self: start;
        width: auto;
    }
}
</style>