<?php
include 'koneksi.php';

$result = mysqli_query($conn, "SELECT * FROM tickets WHERE status = 'active' ORDER BY event_date ASC");

$tickets = [];
while($row = mysqli_fetch_assoc($result)) {
    $tickets[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Festival Event</title>
  <!-- <script src="https://cdn.tailwindcss.com"></script> -->
   <link rel="stylesheet" href="src\output.css">
</head>
<body class="bg-gradient-to-r from-purple-200 to-indigo-200 font-sans min-h-screen text-gray-800">

  <!-- Header -->
  <header class="flex justify-between items-center px-8 py-6">
    <div>
      <h1 class="text-2xl font-bold">Festival Event</h1>
      <p class="text-sm text-gray-600">Temukan Event menarik</p>
    </div>
    <!-- <a href="#" class="text-pink-500 font-semibold">HOME</a> -->
    <a href="login.php" class="text-pink-500 font-semibold">LOGIN</a>
  </header>

  <!-- Search Bar -->
  <div class="flex justify-center px-4 mb-6">
  <input type="text" placeholder="Cari event yang kamu inginkan"
         class="w-full sm:w-2/3 lg:w-1/2 px-6 py-3 rounded-xl shadow-sm border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 transition bg-white">
</div>

  <!-- Kategori -->
  <div class="flex justify-center gap-4 mb-10">
    <?php
      $kategori = ['music.png', 'culture.png', 'food.png', 'festival.png', 'cinema.png'];
      foreach ($kategori as $k) {
        echo "<img src='icons/$k' class='w-14 h-14 rounded-xl bg-white p-2 shadow-md' />";
      }
    ?>
  </div>

<!-- Section Event -->
<section class="px-4 sm:px-6 md:px-12">
  <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-2 sm:gap-0">
    <h2 class="text-xl font-semibold text-center sm:text-left">Acara Festival Event</h2>
    <a href="#" class="text-orange-500 text-sm font-medium">Lihat Semua</a>
  </div>

  <!-- Responsive Card Grid -->
  <div class="flex overflow-x-auto space-x-6 px-6 py-6">
    <?php
    include 'koneksi.php';
    $result = mysqli_query($conn, "SELECT * FROM tickets WHERE status = 'active' ORDER BY event_date ASC");
    while($row = mysqli_fetch_assoc($result)):
      $image = $row['image'];
      $event = $row['event_name'];
      $date = date("d F Y", strtotime($row['event_date']));
      $price = $row['price'] > 0 ? "Rp. " . number_format($row['price'], 0, ',', '.') : "Gratis";
    ?>
    <div class="flex-shrink-0 w-64 h-[360px] bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col">
        <div class="h-40 w-full overflow-hidden rounded-t-2xl">
            <img src="images/<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($event) ?>" class="w-full h-full object-cover">
        </div>
      
      <div class="flex-grow p-4 flex flex-col justify-between">
        <div>
          <h4 class="font-bold text-base mb-2 leading-tight text-gray-900"><?= htmlspecialchars($event) ?></h4>
          <div class="flex items-center text-sm text-gray-600 mb-1">
            <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
            </svg>
            <?= $date ?>
          </div>
          <div class="flex items-center text-sm text-gray-600 mb-3">
            <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
            </svg>
            <?= isset($row['location']) && $row['location'] ? htmlspecialchars($row['location']) : 'Lokasi TBA' ?>
          </div>
        </div>
        
        <div class="flex justify-between items-center mt-3">
          <p class="text-lg font-bold text-orange-500"><?= $price ?></p>
          <a href="#" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors duration-200 shadow-sm">Beli Tiket</a>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</section>

</body>
</html>