<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. You must be an admin.");
}

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Proses Upload Gambar ---
    $image_name = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../images/";
        $image_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $image_name = uniqid('ticket_', true) . '.' . $image_extension;
        $target_file = $target_dir . $image_name;
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($image_extension, $allowed_types)) {
            die("Error: Invalid file type.");
        }
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            die("Error: Failed to upload image.");
        }
    }

    // --- Proses Data Jenis Tiket ---
    $ticket_types_array = [];
    if (isset($_POST['types']) && is_array($_POST['types'])) {
        foreach ($_POST['types'] as $type) {
            // Hanya masukkan ke array jika harga dan kuota diisi
            if (!empty($type['price']) && !empty($type['quantity'])) {
                $ticket_types_array[] = [
                    'type_name' => $type['type_name'],
                    'price' => (float)$type['price'],
                    'quantity' => (int)$type['quantity']
                ];
            }
        }
    }
    // Ubah array PHP menjadi string JSON untuk disimpan di database
    $ticket_types_json = json_encode($ticket_types_array);


    // --- Simpan semua data ke tabel 'tickets' ---
    try {
        $sql = "INSERT INTO tickets (event_name, description, category, location, event_date, event_time, image, status, ticket_types) 
                VALUES (:event_name, :description, :category, :location, :event_date, :event_time, :image, :status, :ticket_types)";
        
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
            ':ticket_types' => $ticket_types_json // Simpan data tiket sebagai JSON
        ]);

        header("Location: admin.php?status=add_success");
        exit();

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: admin.php");
    exit();
}
?>