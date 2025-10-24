<?php
session_start();
require_once 'db.php';

$db = new Database();
$conn = $db->connect();

// ✅ Cek login
if (!isset($_SESSION['user_id'])) {
  echo "<script>
    alert('Silakan login terlebih dahulu untuk melakukan pemesanan.');
    window.location.href = 'login.php';
  </script>";
  exit;
}

// ✅ Cek ID kamar
if (!isset($_GET['id'])) {
  header("Location: index.php");
  exit;
}

$id = $_GET['id'];

// ✅ Ambil detail kamar
$stmt = $conn->prepare("SELECT k.*, t.nama_tipe 
                        FROM kamar k 
                        JOIN tipe_kamar t ON k.id_tipe = t.id_tipe
                        WHERE k.id_kamar = ?");
$stmt->execute([$id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
  echo "<script>alert('Kamar tidak ditemukan!'); window.location='index.php';</script>";
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking | Azel Hotel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    :root { --primary: #8b0000; }
    .bg-primary { background-color: var(--primary); }
    .text-primary { color: var(--primary); }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">

<!-- 🌹 NAVBAR -->
<nav class="fixed top-0 left-0 w-full bg-white shadow z-40">
  <div class="flex items-center justify-between px-10 py-4 w-full">
    <div class="flex items-center gap-3">
      <i class="fas fa-bed text-2xl text-primary"></i>
      <a href="index.php" class="text-2xl font-bold text-primary tracking-wide">AZEL HOTEL</a>
    </div>

    <div class="hidden md:flex items-center gap-8">
      <a href="index.php#rooms" class="hover:text-primary font-semibold">Kamar</a>
      <a href="index.php#fasilitas" class="hover:text-primary font-semibold">Fasilitas</a>
      <a href="index.php#kontak" class="hover:text-primary font-semibold">Kontak</a>
      <a href="logout.php" class="text-red-600 hover:text-red-800 font-semibold">Logout</a>
    </div>
  </div>
</nav>

<!-- 🌸 HERO SECTION -->
<header class="mt-20 bg-gray-100 py-12">
  <div class="max-w-5xl mx-auto text-center px-6">
    <h1 class="text-4xl font-extralight mb-2 text-primary">Formulir Pemesanan Kamar</h1>
    <p class="text-gray-600 text-lg">Lengkapi detail di bawah untuk memesan kamar pilihan Anda.</p>
  </div>
</header>

<!-- 🛏️ DETAIL KAMAR + FORM -->
<section class="max-w-6xl mx-auto px-6 py-16 grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

  <!-- 📷 FOTO KAMAR -->
  <div class="rounded-2xl overflow-hidden shadow-lg">
    <img src="uploads/<?php echo htmlspecialchars($room['foto']); ?>" 
         alt="<?php echo htmlspecialchars($room['nama_kamar']); ?>" 
         class="w-full h-[420px] object-cover">
  </div>

  <!-- 🧾 FORM PEMESANAN -->
  <div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-3xl font-semibold mb-3 text-gray-800">
      <?php echo htmlspecialchars($room['nama_kamar']); ?>
    </h2>
    <p class="text-gray-600 mb-1">Tipe: <span class="font-medium"><?php echo htmlspecialchars($room['nama_tipe']); ?></span></p>
    <p class="text-lg text-primary font-semibold mb-4">Rp <?php echo number_format($room['harga_per_malam'], 0, ',', '.'); ?> / malam</p>
    <p class="text-gray-600 mb-6 leading-relaxed"><?php echo nl2br(htmlspecialchars($room['deskripsi'])); ?></p>

    <form action="action_booking.php" method="POST" class="space-y-4">
      <input type="hidden" name="id_kamar" value="<?php echo $room['id_kamar']; ?>">

      <div>
        <label class="block font-medium text-gray-700 mb-1">Tanggal Check-In</label>
        <input type="date" name="checkin" required class="border rounded-lg w-full px-3 py-2 focus:ring-2 focus:ring-primary outline-none">
      </div>

      <div>
        <label class="block font-medium text-gray-700 mb-1">Tanggal Check-Out</label>
        <input type="date" name="checkout" required class="border rounded-lg w-full px-3 py-2 focus:ring-2 focus:ring-primary outline-none">
      </div>

      <div>
        <label class="block font-medium text-gray-700 mb-1">Jumlah Tamu</label>
        <input type="number" name="jumlah_tamu" min="1" value="1" required class="border rounded-lg w-full px-3 py-2 focus:ring-2 focus:ring-primary outline-none">
      </div>

      <button type="submit" class="bg-primary hover:bg-red-900 text-white font-semibold py-3 px-6 rounded-lg w-full transition duration-200 shadow">
        Konfirmasi Pesanan
      </button>
    </form>
  </div>
</section>

<!-- 🌙 FOOTER -->
<footer class="bg-gray-900 text-gray-300 py-8 text-center">
  <p>&copy; <?php echo date('Y'); ?> Azel Hotel. Semua Hak Dilindungi.</p>
</footer>

</body>
</html>
