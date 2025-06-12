<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
require_once 'db.php';

// ===================================================================
// BAGIAN 1: CONTROLLER - Memproses semua Aksi (Add & Update)
// ===================================================================

// Cek jika ada request POST (dari submit form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
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
        $stmt->execute([':event_name' => $_POST['event_name'], ':description' => $_POST['description'], ':category' => $_POST['category'], ':location' => $_POST['location'], ':event_date' => $_POST['event_date'], ':event_time' => !empty($_POST['event_time']) ? $_POST['event_time'] : null, ':image' => $image_name, ':status' => $_POST['status'], ':ticket_types' => $ticket_types_json, ':id' => $id]);

        header("Location: admin.php?status=update_success");
        exit();
    }

    // --- Logika untuk ADD Event ---
    if (isset($_POST['add_ticket'])) {
        // (Logika ini sama persis seperti di file add_ticket.php sebelumnya)
        $image_name = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../images/";
            $image_name = basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image_name;
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        }

        $ticket_types_array = [];
        if (isset($_POST['types']) && is_array($_POST['types'])) {
            foreach ($_POST['types'] as $type) {
                if (!empty($type['type_name']) && !empty($type['price']) && !empty($type['quantity'])) {
                    $ticket_types_array[] = ['type_name' => trim($type['type_name']), 'price' => (float)$type['price'], 'quantity' => (int)$type['quantity']];
                }
            }
        }
        $ticket_types_json = json_encode($ticket_types_array);

        $sql = "INSERT INTO tickets (event_name, description, category, location, event_date, event_time, image, status, ticket_types) VALUES (:event_name, :description, :category, :location, :event_date, :event_time, :image, :status, :ticket_types)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':event_name' => $_POST['event_name'], ':description' => $_POST['description'], ':category' => $_POST['category'], ':location' => $_POST['location'], ':event_date' => $_POST['event_date'], ':event_time' => !empty($_POST['event_time']) ? $_POST['event_time'] : null, ':image' => $image_name, ':status' => $_POST['status'], ':ticket_types' => $ticket_types_json]);
        
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

// Selalu ambil semua data tiket untuk ditampilkan di tabel bawah
$all_tickets = $conn->query("SELECT * FROM tickets ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles.css">
    <title>Admin Panel</title>
    <style>
        form label { display: block; margin-top: 15px; font-weight: bold; }
        form input, form textarea, form select { width: 100%; padding: 8px; margin-top: 5px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
        form button { margin-top: 20px; padding: 12px 20px; }
        fieldset { border: 1px solid #ddd; padding: 20px; margin-top: 20px; border-radius: 5px; }
        legend { font-weight: bold; font-size: 1.2em; padding: 0 10px; }
        .table-container { overflow-x: auto; }
        .ticket-type-entry { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
        .ticket-type-entry > div { flex: 1; }
        .remove-btn { padding: 8px 12px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, Admin</h1>
        <a href="logout.php">Logout</a>

        <hr>
        <h2><?= $edit_mode ? 'Edit Event' : 'Add New Event' ?></h2>
        
        <form action="admin.php" method="post" enctype="multipart/form-data">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?= $ticket_to_edit['id'] ?>">
            <?php endif; ?>

            <label>Nama Festival:</label>
            <input type="text" name="event_name" value="<?= $edit_mode ? htmlspecialchars($ticket_to_edit['event_name']) : '' ?>" required>
            
            <label>Kategori:</label>
            <input type="text" name="category" value="<?= $edit_mode ? htmlspecialchars($ticket_to_edit['category']) : '' ?>">
            
            <label>Deskripsi:</label>
            <textarea name="description" rows="4"><?= $edit_mode ? htmlspecialchars($ticket_to_edit['description']) : '' ?></textarea>
            
            <label>Lokasi:</label>
            <input type="text" name="location" value="<?= $edit_mode ? htmlspecialchars($ticket_to_edit['location']) : '' ?>" required>
            
            <label>Tanggal:</label>
            <input type="date" name="event_date" value="<?= $edit_mode ? htmlspecialchars($ticket_to_edit['event_date']) : '' ?>" required>
            
            <label>Jam Mulai:</label>
            <input type="time" name="event_time" value="<?= $edit_mode ? htmlspecialchars($ticket_to_edit['event_time']) : '' ?>">

            <label>Status:</label>
            <select name="status">
                <option value="active" <?= ($edit_mode && $ticket_to_edit['status'] == 'active') ? 'selected' : '' ?>>Aktif</option>
                <option value="inactive" <?= ($edit_mode && $ticket_to_edit['status'] == 'inactive') ? 'selected' : '' ?>>Tidak Aktif</option>
            </select>
            
            <label><?= $edit_mode ? 'Ganti Gambar/Poster (Opsional)' : 'Gambar/Poster' ?></label>
            <?php if ($edit_mode && !empty($ticket_to_edit['image'])): ?>
                <p>Gambar saat ini: <img src="../images/<?= htmlspecialchars($ticket_to_edit['image']) ?>" width="100" alt="Current Image"></p>
                <input type="hidden" name="existing_image" value="<?= htmlspecialchars($ticket_to_edit['image']) ?>">
            <?php endif; ?>
            <input type="file" name="image">

            <fieldset>
                <legend>Jenis & Kuota Tiket</legend>
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
                <button type="button" id="add-type-btn">Tambah Jenis Tiket</button>
            </fieldset>
            
            <?php if ($edit_mode): ?>
                <button type="submit" name="update_ticket">Update Event</button>
                <a href="admin.php" style="margin-left: 10px;">Batal Edit</a>
            <?php else: ?>
                <button type="submit" name="add_ticket">Add Event</button>
            <?php endif; ?>
        </form>
        <hr>

        <h2>All Events</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>Event</th><th>Lokasi</th><th>Tanggal</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach($all_tickets as $ticket): ?>
                    <tr>
                        <td><?= htmlspecialchars($ticket['event_name']) ?></td>
                        <td><?= htmlspecialchars($ticket['location']) ?></td>
                        <td><?= date('d M Y', strtotime($ticket['event_date'])) ?></td>
                        <td><?= ucfirst($ticket['status']) ?></td>
                        <td>
                            <a href="admin.php?action=edit&id=<?= $ticket['id'] ?>">Edit</a>
                            <a href="delete_ticket.php?id=<?= $ticket['id'] ?>" onclick="return confirm('Yakin ingin menghapus event ini?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('add-type-btn').addEventListener('click', function() {
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
</body>
</html>