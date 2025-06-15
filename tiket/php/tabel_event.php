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
                // Get event data for image deletion
                $sql_select = "SELECT image FROM tickets WHERE id = :id";
                $stmt_select = $conn->prepare($sql_select);
                $stmt_select->execute([':id' => $id]);
                $event = $stmt_select->fetch(PDO::FETCH_ASSOC);
                
                if ($event) {
                    // Delete image file if exists
                    if (!empty($event['image'])) {
                        $target_dir = "../images/";
                        $image_path = $target_dir . $event['image'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    
                    // Delete from database
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
    
    // --- UPDATE EVENT ---
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if ($id) {
            try {
                // Handle image upload
                $image_name = $_POST['existing_image'] ?? '';
                
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $target_dir = "../images/";
                    
                    // Delete old image
                    if (!empty($image_name) && file_exists($target_dir . $image_name)) {
                        unlink($target_dir . $image_name);
                    }
                    
                    // Upload new image
                    $new_image_name = time() . '_' . basename($_FILES["image"]["name"]);
                    $target_file = $target_dir . $new_image_name;
                    
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $image_name = $new_image_name;
                    }
                }
                
                // Process ticket types
                $ticket_types_array = [];
                if (isset($_POST['ticket_types']) && is_array($_POST['ticket_types'])) {
                    foreach ($_POST['ticket_types'] as $type) {
                        if (!empty($type['type_name']) && !empty($type['price']) && !empty($type['quantity'])) {
                            $ticket_types_array[] = [
                                'type_name' => trim($type['type_name']),
                                'price' => (float)$type['price'],
                                'quantity' => (int)$type['quantity']
                            ];
                        }
                    }
                }
                $ticket_types_json = json_encode($ticket_types_array);
                
                // Update query
                $sql = "UPDATE tickets SET 
                        event_name = :event_name, 
                        description = :description, 
                        category = :category, 
                        location = :location, 
                        event_date = :event_date, 
                        event_time = :event_time, 
                        image = :image, 
                        status = :status, 
                        ticket_types = :ticket_types 
                        WHERE id = :id";
                
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
                
                $_SESSION['flash_message'] = 'Event berhasil diperbarui!';
                $_SESSION['flash_type'] = 'success';
                
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'error';
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    
}

// === GET EVENTS DATA ===
try {
    $sql = "SELECT * FROM tickets ORDER BY event_date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = [];
    $error_message = "Error fetching events: " . $e->getMessage();
}

// Get flash messages
$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Event - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#64748b'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-calendar-alt text-primary mr-2"></i>
                    Kelola Event
                </h1>
                <div class="flex items-center space-x-4">
                    <button onclick="location.href='admin.php'" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <!-- Flash Message -->
        <?php if ($flash_message): ?>
        <div id="flashMessage" class="mb-6 p-4 rounded-lg <?php echo $flash_type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas <?php echo $flash_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($flash_message); ?>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-current opacity-70 hover:opacity-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Events Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Daftar Event</h2>
                <p class="text-gray-600 mt-1">Total: <?php echo count($events); ?> event</p>
            </div>

            <?php if (empty($events)): ?>
                echo = "event belum tersedia";
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiket</th>
                            <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($events as $event): ?>
                        <?php 
                        $ticket_types = json_decode($event['ticket_types'], true) ?? [];
                        $total_tickets = array_sum(array_column($ticket_types, 'quantity'));
                        $date_formatted = date('d M Y', strtotime($event['event_date']));
                        $time_formatted = $event['event_time'] ? date('H:i', strtotime($event['event_time'])) : '-';
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <?php if ($event['image']): ?>
                                        <img class="h-12 w-12 rounded-lg object-cover" src="../images/<?php echo htmlspecialchars($event['image']); ?>" alt="Event Image">
                                        <?php else: ?>
                                        <div class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($event['event_name']); ?></div>
                                        <div class="text-sm text-gray-500 truncate max-w-xs">
                                            <?php echo htmlspecialchars(substr($event['description'] ?? '', 0, 60)); ?>
                                            <?php if (strlen($event['description'] ?? '') > 60): ?>...<?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($event['category']): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($event['category']); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo $date_formatted; ?></div>
                                <div class="text-sm text-gray-500"><?php echo $time_formatted; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?php echo $event['location'] ? htmlspecialchars($event['location']) : '-'; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $event['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <span class="w-1.5 h-1.5 mr-1.5 rounded-full <?php echo $event['status'] === 'active' ? 'bg-green-400' : 'bg-red-400'; ?>"></span>
                                    <?php echo $event['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo count($ticket_types); ?> jenis</div>
                                <div class="text-sm text-gray-500"><?php echo number_format($total_tickets); ?> tiket</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($event)); ?>)" 
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white p-2 rounded-lg transition-colors" 
                                            title="Edit Event">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['event_name']); ?>')" 
                                            class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg transition-colors" 
                                            title="Hapus Event">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button onclick="viewTicketTypes(<?php echo htmlspecialchars(json_encode($ticket_types)); ?>, '<?php echo htmlspecialchars($event['event_name']); ?>')" 
                                            class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-lg transition-colors" 
                                            title="Lihat Jenis Tiket">
                                        <i class="fas fa-ticket-alt"></i>
                                    </button>
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

    <!-- Add/Edit Event Modal -->
    <div id="eventModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-screen overflow-y-auto">
                <form id="eventForm" method="POST" enctype="multipart/form-data">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Tambah Event</h3>
                            <button type="button" onclick="closeEventModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="eventId">
                        <input type="hidden" name="existing_image" id="existingImage">
                        
                        <!-- Basic Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Event *</label>
                                <input type="text" name="event_name" id="eventName" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                                <input type="text" name="category" id="eventCategory" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                            <textarea name="description" id="eventDescription" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi</label>
                                <input type="text" name="location" id="eventLocation" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal *</label>
                                <input type="date" name="event_date" id="eventDate" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Waktu</label>
                                <input type="time" name="event_time" id="eventTime" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Gambar</label>
                                <input type="file" name="image" id="eventImage" accept="image/*" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                <div id="currentImage" class="mt-2 hidden">
                                    <img id="currentImagePreview" class="h-20 w-20 object-cover rounded-lg" alt="Current Image">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" id="eventStatus" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Ticket Types -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <label class="block text-sm font-medium text-gray-700">Jenis Tiket</label>
                                <button type="button" onclick="addTicketType()" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                    <i class="fas fa-plus mr-1"></i>Tambah
                                </button>
                            </div>
                            <div id="ticketTypesContainer" class="space-y-3">
                                <!-- Ticket types will be added here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200 flex space-x-3">
                        <button type="button" onclick="closeEventModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-lg transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="flex-1 bg-primary hover:bg-blue-600 text-white py-2 px-4 rounded-lg transition-colors">
                            <span id="submitText">Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 text-center mb-2">Konfirmasi Hapus</h3>
                <p class="text-sm text-gray-500 text-center mb-6">
                    Apakah Anda yakin ingin menghapus event "<span id="deleteEventName" class="font-medium"></span>"? 
                    Tindakan ini tidak dapat dibatalkan.
                </p>
                <div class="flex space-x-3">
                    <button onclick="closeDeleteModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-lg transition-colors">
                        Batal
                    </button>
                    <form id="deleteForm" method="POST" class="flex-1">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteEventId">
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition-colors">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Ticket Types View Modal -->
    <div id="ticketModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-lg w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Jenis Tiket</h3>
                    <button onclick="closeTicketModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <h4 id="ticketEventName" class="text-sm font-medium text-gray-600 mb-4"></h4>
                <div id="ticketTypesList" class="space-y-3"></div>
            </div>
        </div>
    </div>


<script>

// Global variables
let ticketTypeCounter = 0;

// === MODAL FUNCTIONS ===

// Open Add Event Modal
// function openAddModal() {
//     document.getElementById('modalTitle').textContent = 'Tambah Event';
//     document.getElementById('formAction').value = 'add';
//     document.getElementById('submitText').textContent = 'Simpan';
    
//     // Reset form
//     document.getElementById('eventForm').reset();
//     document.getElementById('eventId').value = '';
//     document.getElementById('existingImage').value = '';
//     document.getElementById('currentImage').classList.add('hidden');
    
//     // Clear ticket types
//     document.getElementById('ticketTypesContainer').innerHTML = '';
//     ticketTypeCounter = 0;
    
//     // Add one ticket type by default
//     addTicketType();
    
//     // Show modal
//     document.getElementById('eventModal').classList.remove('hidden');
// }

// Open Edit Event Modal
function openEditModal(eventData) {
    document.getElementById('modalTitle').textContent = 'Edit Event';
    document.getElementById('formAction').value = 'update';
    document.getElementById('submitText').textContent = 'Perbarui';
    
    // Fill form with event data
    document.getElementById('eventId').value = eventData.id;
    document.getElementById('eventName').value = eventData.event_name || '';
    document.getElementById('eventCategory').value = eventData.category || '';
    document.getElementById('eventDescription').value = eventData.description || '';
    document.getElementById('eventLocation').value = eventData.location || '';
    document.getElementById('eventDate').value = eventData.event_date || '';
    document.getElementById('eventTime').value = eventData.event_time || '';
    document.getElementById('eventStatus').value = eventData.status || 'active';
    document.getElementById('existingImage').value = eventData.image || '';
    
    // Show current image if exists
    if (eventData.image) {
        document.getElementById('currentImagePreview').src = '../images/' + eventData.image;
        document.getElementById('currentImage').classList.remove('hidden');
    } else {
        document.getElementById('currentImage').classList.add('hidden');
    }
    
    // Clear and populate ticket types
    document.getElementById('ticketTypesContainer').innerHTML = '';
    ticketTypeCounter = 0;
    
    try {
        const ticketTypes = JSON.parse(eventData.ticket_types || '[]');
        if (ticketTypes.length > 0) {
            ticketTypes.forEach(ticketType => {
                addTicketType(ticketType);
            });
        } else {
            addTicketType(); // Add empty ticket type if none exist
        }
    } catch (e) {
        console.error('Error parsing ticket types:', e);
        addTicketType(); // Add empty ticket type on error
    }
    
    // Show modal
    document.getElementById('eventModal').classList.remove('hidden');
}

// Close Event Modal
function closeEventModal() {
    document.getElementById('eventModal').classList.add('hidden');
    document.getElementById('eventForm').reset();
}

// === TICKET TYPE FUNCTIONS ===

// Add Ticket Type Row
function addTicketType(ticketData = null) {
    ticketTypeCounter++;
    const container = document.getElementById('ticketTypesContainer');
    
    const ticketTypeHtml = `
        <div class="ticket-type-row bg-gray-50 p-4 rounded-lg" id="ticketType_${ticketTypeCounter}">
            <div class="flex items-center justify-between mb-3">
                <h5 class="text-sm font-medium text-gray-700">Jenis Tiket #${ticketTypeCounter}</h5>
                <button type="button" onclick="removeTicketType(${ticketTypeCounter})" 
                        class="text-red-500 hover:text-red-700 text-sm">
                    <i class="fas fa-trash mr-1"></i>Hapus
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Tiket</label>
                    <input type="text" name="ticket_types[${ticketTypeCounter}][type_name]" 
                           value="${ticketData ? ticketData.type_name || '' : ''}"
                           placeholder="contoh: VIP, Regular"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga (Rp)</label>
                    <input type="number" name="ticket_types[${ticketTypeCounter}][price]" 
                           value="${ticketData ? ticketData.price || '' : ''}"
                           placeholder="0" min="0" step="1000"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah</label>
                    <input type="number" name="ticket_types[${ticketTypeCounter}][quantity]" 
                           value="${ticketData ? ticketData.quantity || '' : ''}"
                           placeholder="0" min="1"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', ticketTypeHtml);
}

// Remove Ticket Type Row
function removeTicketType(id) {
    const element = document.getElementById(`ticketType_${id}`);
    if (element) {
        element.remove();
    }
    
    // Ensure at least one ticket type remains
    const container = document.getElementById('ticketTypesContainer');
    if (container.children.length === 0) {
        addTicketType();
    }
}

// === DELETE FUNCTIONS ===

// Delete Event Confirmation
function deleteEvent(eventId, eventName) {
    document.getElementById('deleteEventId').value = eventId;
    document.getElementById('deleteEventName').textContent = eventName;
    document.getElementById('deleteModal').classList.remove('hidden');
}

// Close Delete Modal
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// === TICKET TYPES VIEW FUNCTIONS ===

// View Ticket Types
function viewTicketTypes(ticketTypes, eventName) {
    document.getElementById('ticketEventName').textContent = eventName;
    const container = document.getElementById('ticketTypesList');
    
    container.innerHTML = '';
    
    if (ticketTypes && ticketTypes.length > 0) {
        ticketTypes.forEach((ticket, index) => {
            const ticketHtml = `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <h5 class="font-medium text-gray-900">${ticket.type_name || 'Tidak ada nama'}</h5>
                        <span class="text-sm text-gray-500">#${index + 1}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Harga:</span>
                            <div class="font-medium text-green-600">Rp ${formatNumber(ticket.price || 0)}</div>
                        </div>
                        <div>
                            <span class="text-gray-600">Jumlah:</span>
                            <div class="font-medium">${formatNumber(ticket.quantity || 0)} tiket</div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', ticketHtml);
        });
    } else {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-ticket-alt text-3xl mb-2"></i>
                <p>Belum ada jenis tiket</p>
            </div>
        `;
    }
    
    document.getElementById('ticketModal').classList.remove('hidden');
}

// Close Ticket Types Modal
function closeTicketModal() {
    document.getElementById('ticketModal').classList.add('hidden');
}

// === UTILITY FUNCTIONS ===

// Format number with thousand separators
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

// Form validation before submit
function validateEventForm() {
    const eventName = document.getElementById('eventName').value.trim();
    const eventDate = document.getElementById('eventDate').value;
    
    if (!eventName) {
        alert('Nama event harus diisi!');
        return false;
    }
    
    if (!eventDate) {
        alert('Tanggal event harus diisi!');
        return false;
    }
    
    // Validate ticket types
    const ticketRows = document.querySelectorAll('.ticket-type-row');
    let hasValidTicket = false;
    
    ticketRows.forEach(row => {
        const typeName = row.querySelector('input[name*="[type_name]"]').value.trim();
        const price = row.querySelector('input[name*="[price]"]').value;
        const quantity = row.querySelector('input[name*="[quantity]"]').value;
        
        if (typeName && price && quantity) {
            hasValidTicket = true;
        }
    });
    
    if (!hasValidTicket) {
        alert('Minimal harus ada satu jenis tiket yang lengkap (nama, harga, dan jumlah)!');
        return false;
    }
    
    return true;
}

// === EVENT LISTENERS ===

// Add form validation on submit
document.addEventListener('DOMContentLoaded', function() {
    const eventForm = document.getElementById('eventForm');
    if (eventForm) {
        eventForm.addEventListener('submit', function(e) {
            if (!validateEventForm()) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Auto-hide flash messages after 5 seconds
    const flashMessage = document.getElementById('flashMessage');
    if (flashMessage) {
        setTimeout(() => {
            flashMessage.style.transition = 'opacity 0.5s ease-out';
            flashMessage.style.opacity = '0';
            setTimeout(() => {
                flashMessage.remove();
            }, 500);
        }, 5000);
    }
    
    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('fixed') && e.target.classList.contains('inset-0')) {
            if (e.target.id === 'eventModal') closeEventModal();
            if (e.target.id === 'deleteModal') closeDeleteModal();
            if (e.target.id === 'ticketModal') closeTicketModal();
        }
    });
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEventModal();
            closeDeleteModal();
            closeTicketModal();
        }
    });
});

// Image preview function
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('currentImagePreview');
            const container = document.getElementById('currentImage');
            
            preview.src = e.target.result;
            container.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Add image preview event listener
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('eventImage');
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            previewImage(this);
        });
    }
});

// === ADDITIONAL HELPER FUNCTIONS ===

// Confirm action with custom message
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Show loading state
function showLoading(buttonElement, loadingText = 'Loading...') {
    buttonElement.disabled = true;
    buttonElement.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>${loadingText}`;
}

// Hide loading state
function hideLoading(buttonElement, originalText) {
    buttonElement.disabled = false;
    buttonElement.innerHTML = originalText;
}

// Sanitize HTML to prevent XSS
function sanitizeHTML(str) {
    const temp = document.createElement('div');
    temp.textContent = str;
    return temp.innerHTML;
}

// Export functions for potential external use
window.EventManager = {
    openAddModal,
    openEditModal,
    closeEventModal,
    deleteEvent,
    closeDeleteModal,
    viewTicketTypes,
    closeTicketModal,
    addTicketType,
    removeTicketType,
    validateEventForm,
    formatNumber
};

</script>
