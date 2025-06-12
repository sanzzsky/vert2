<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
require_once 'db.php';
// Mengambil data dari tabel 'tickets' yang sudah dimodifikasi
$tickets = $conn->query("SELECT * FROM tickets ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);
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
        form input[type="text"], form input[type="date"], form input[type="time"], form input[type="number"], form textarea, form select {
            width: 100%; padding: 8px; margin-top: 5px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box;
        }
        form button { margin-top: 20px; padding: 12px 20px; }
        fieldset { border: 1px solid #ddd; padding: 20px; margin-top: 20px; border-radius: 5px; }
        legend { font-weight: bold; font-size: 1.2em; padding: 0 10px; }
        .table-container { overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, Admin</h1>
        <a href="logout.php">Logout</a>

        <h2>Add New Ticket / Event</h2>
        <form action="add_ticket.php" method="post" enctype="multipart/form-data">
            
            <label for="event_name">Nama Festival:</label>
            <input type="text" id="event_name" name="event_name" required>
            
            <label for="kategori">Kategori:</label>
            <input type="text" id="kategori" name="category" placeholder="e.g., Music Festival, Art Exhibition">
            
            <label for="deskripsi">Deskripsi:</label>
            <textarea id="deskripsi" name="description" rows="4"></textarea>
            
            <label for="lokasi">Lokasi:</label>
            <input type="text" id="lokasi" name="location" required>
            
            <label for="tanggal">Tanggal:</label>
            <input type="date" id="tanggal" name="event_date" required>
            
            <label for="jam">Jam Mulai:</label>
            <input type="time" id="jam" name="event_time">

            <label for="status">Status:</label>
            <select id="status" name="status">
                <option value="active">Aktif (Ditampilkan)</option>
                <option value="inactive">Tidak Aktif (Disembunyikan)</option>
            </select>
            
            <label for="gambar">Gambar/Poster:</label>
            <input type="file" id="gambar" name="image">

            <fieldset>
                <legend>Jenis & Kuota Tiket</legend>

                <h4>Tiket Regular</h4>
                <input type="hidden" name="types[0][type_name]" value="Regular">
                <label for="price_regular">Harga Regular (Rp):</label>
                <input type="number" id="price_regular" name="types[0][price]" placeholder="e.g., 500000" required>
                <label for="quantity_regular">Batas/Kuota Tiket Regular:</label>
                <input type="number" id="quantity_regular" name="types[0][quantity]" placeholder="e.g., 1000" required>

                <h4>Tiket VIP</h4>
                <input type="hidden" name="types[1][type_name]" value="VIP">
                <label for="price_vip">Harga VIP (Rp):</label>
                <input type="number" id="price_vip" name="types[1][price]" placeholder="e.g., 1500000">
                <label for="quantity_vip">Batas/Kuota Tiket VIP:</label>
                <input type="number" id="quantity_vip" name="types[1][quantity]" placeholder="e.g., 200">
            </fieldset>
            
            <button type="submit">Add Ticket</button>
        </form>

        <h2>All Tickets</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Lokasi</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tickets as $ticket): ?>
                    <tr>
                        <td><?= htmlspecialchars($ticket['event_name']) ?></td>
                        <td><?= htmlspecialchars($ticket['location']) ?></td>
                        <td><?= date('d M Y', strtotime($ticket['event_date'])) ?></td>
                        <td><?= isset($ticket['event_time']) ? date('H:i', strtotime($ticket['event_time'])) : 'N/A' ?></td>
                        <td><?= ucfirst($ticket['status']) ?></td>
                        <td>
                            <a href="edit_ticket.php?id=<?= $ticket['id'] ?>">Edit</a>
                            <a href="delete_ticket.php?id=<?= $ticket['id'] ?>" onclick="return confirm('Yakin ingin menghapus event ini?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>