<?php
require_once 'php/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    // Validasi input
    if ($username === '' || $password === '' || $confirm === '') {
        $error = "Semua field harus diisi!";
    } elseif ($password !== $confirm) {
        $error = "Password dan konfirmasi tidak sama!";
    } else {
        // Cek username sudah ada atau belum
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username sudah terdaftar!";
        } else {
            // Hash password dan simpan user baru dengan role 'user'
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
            $stmt->execute([$username, $hashed]);
            $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
            header("Location: login.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register Pengguna</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Register Pengguna</h2>
        <form method="post" action="register.php">
            <label>Username: <input type="text" name="username" required></label><br>
            <label>Password: <input type="password" name="password" required></label><br>
            <label>Konfirmasi Password: <input type="password" name="confirm" required></label><br>
            <button type="submit">Register</button>
        </form>
        <?php
        if (isset($error)) {
            echo "<p style='color:red'>$error</p>";
        }
        ?>
        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</body>
</html>
