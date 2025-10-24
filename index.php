<?php
require_once 'db.php';
session_start();

// Cek status login
$isLoggedIn = isset($_SESSION['user_id']);

// Koneksi database (support class Database versi lama/baru)
$db = new Database();
if (method_exists($db, 'connect')) {
    $conn = $db->connect();
} elseif (property_exists($db, 'conn') && $db->conn) {
    $conn = $db->conn;
} else {
    die('Koneksi database tidak ditemukan.');
}

// Ambil data kamar + tipe
try {
    $sql = "SELECT k.id_kamar, k.id_tipe, k.nama_kamar, k.deskripsi, k.harga_per_malam, k.foto, t.nama_tipe
            FROM kamar k
            LEFT JOIN tipe_kamar t ON k.id_tipe = t.id_tipe
            ORDER BY k.id_kamar DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query gagal: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Azel Hotel — Reservasi</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    :root { --primary: #8b0000; }
    .bg-primary { background-color: var(--primary); }
    .text-primary { color: var(--primary); }
    .hero-slider { height: 78vh; background-position:center; background-size:cover; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">

<nav class="fixed top-0 left-0 w-full bg-white shadow z-40">
  <div class="flex items-center justify-between px-10 py-4 w-full">
    
    <div class="flex items-center gap-3">
      <i class="fas fa-bed text-2xl text-primary"></i>
      <a href="index.php" class="text-2xl font-bold text-primary tracking-wide">AZEL HOTEL</a>
    </div>

    <div class="hidden md:flex items-center gap-8">
      <a href="#rooms" class="hover:text-primary font-semibold">Kamar</a>
      <a href="#fasilitas" class="hover:text-primary font-semibold">Fasilitas</a>
      <a href="#kontak" class="hover:text-primary font-semibold">Kontak</a>

      <a 
        href="<?php echo $isLoggedIn ? 'booking.php' : 'login.php?redirect=booking.php'; ?>" 
        class="bg-primary hover:bg-red-900 text-white px-5 py-2 rounded-lg transition">
        Book Now
      </a>

      <?php if ($isLoggedIn): ?>
        <a href="history.php" class="text-primary hover:text-red-800 font-semibold">Riwayat Pesanan</a>
        <a href="logout.php" class="text-red-600 hover:text-red-800 font-semibold">Logout</a>
      <?php else: ?>
        <a href="login.php" class="text-primary hover:text-red-800 font-semibold">Login</a>
      <?php endif; ?>
    </div>

    <button class="md:hidden" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')">
      <i class="fas fa-bars text-xl"></i>
    </button>
  </div>

  <div id="mobileMenu" class="hidden md:hidden bg-white border-t">
    <div class="px-6 py-4 flex flex-col gap-3 text-center">
      <a href="#rooms" class="font-medium py-1">Kamar</a>
      <a href="#fasilitas" class="font-medium py-1">Fasilitas</a>
      <a href="#kontak" class="font-medium py-1">Kontak</a>
      <a 
        href="<?php echo $isLoggedIn ? 'booking.php' : 'login.php?redirect=booking.php'; ?>" 
        class="bg-primary text-white px-4 py-2 rounded-lg text-center mt-2">
        Book Now
      </a>
      <?php if ($isLoggedIn): ?>
        <a href="history.php" class="text-primary font-semibold">Riwayat Pesanan</a>
        <a href="logout.php" class="text-red-600 font-semibold">Logout</a>
      <?php else: ?>
        <a href="login.php" class="text-primary font-semibold">Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<header class="mt-20">
  <div class="hero-slider flex items-center justify-center text-white" 
       style="background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.35)), url('uploads/hero1.jpg');">
    <div class="text-center px-6">
      <h1 class="text-5xl md:text-6xl font-light mb-4">Kenyamanan Bintang Lima</h1>
      <p class="max-w-2xl mx-auto text-lg mb-6">Nikmati pengalaman menginap mewah, pelayanan personal, dan lokasi strategis di jantung kota.</p>
    </div>
  </div>
</header>

<section id="rooms" class="max-w-7xl mx-auto px-6 py-20">
  <h2 class="text-4xl font-extralight text-center mb-10">Pilihan Kamar Kami</h2>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php if (!empty($rooms)): ?>
      <?php foreach ($rooms as $r): 
        $imgPath = 'uploads/' . ($r['foto'] ?? '');
        if (empty($r['foto']) || !is_file($imgPath)) {
            $imgPath = 'uploads/noimage.jpg';
        }
      ?>
      <article class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition">
        <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($r['nama_kamar']); ?>" class="w-full h-56 object-cover">
        <div class="p-6">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($r['nama_kamar']); ?></h3>
            <span class="text-sm text-gray-500"><?php echo htmlspecialchars($r['nama_tipe'] ?? '—'); ?></span>
          </div>
          <p class="text-gray-600 text-sm mb-4">
            <?php echo nl2br(htmlspecialchars(substr($r['deskripsi'],0,140))); ?><?php echo (strlen($r['deskripsi'])>140)?'...':''; ?>
          </p>
          <div class="flex items-center justify-between">
            <div class="text-lg font-bold text-primary">Rp <?php echo number_format($r['harga_per_malam'],0,',','.'); ?>/malam</div>
            <a 
              href="<?php echo $isLoggedIn 
                ? 'booking.php?id='.(int)$r['id_kamar'] 
                : 'login.php?redirect=' . urlencode('booking.php?id='.(int)$r['id_kamar']); ?>" 
              class="bg-primary text-white px-4 py-2 rounded-lg">
              Pesan
            </a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="col-span-3 text-center text-gray-500">Belum ada kamar tersedia.</div>
    <?php endif; ?>
  </div>
</section>

<section id="fasilitas" class="bg-gray-100 py-20">
  <div class="max-w-7xl mx-auto px-6">
    <h2 class="text-4xl font-extralight text-center mb-10">Fasilitas Unggulan</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div class="bg-white p-8 rounded-xl shadow-md text-center">
        <i class="fas fa-swimming-pool text-4xl text-primary mb-4"></i>
        <h4 class="font-semibold mb-2">Kolam Renang</h4>
        <p class="text-sm text-gray-500">Kolam rooftop dengan view kota.</p>
      </div>
      <div class="bg-white p-8 rounded-xl shadow-md text-center">
        <i class="fas fa-utensils text-4xl text-primary mb-4"></i>
        <h4 class="font-semibold mb-2">Restoran</h4>
        <p class="text-sm text-gray-500">Hidangan internasional & lokal.</p>
      </div>
      <div class="bg-white p-8 rounded-xl shadow-md text-center">
        <i class="fas fa-spa text-4xl text-primary mb-4"></i>
        <h4 class="font-semibold mb-2">Spa & Wellness</h4>
        <p class="text-sm text-gray-500">Perawatan profesional untuk relaksasi.</p>
      </div>
    </div>
  </div>
</section>

<section id="kontak" class="py-20">
  <div class="max-w-5xl mx-auto px-6">
    <h2 class="text-4xl font-extralight text-center mb-8">Hubungi Kami</h2>
    <div class="grid md:grid-cols-2 gap-10">
      <div>
        <h3 class="text-2xl font-semibold mb-4">Azel Hotel Jakarta</h3>
        <p class="text-gray-600 mb-2"><i class="fas fa-map-marker-alt text-primary mr-2"></i>Jl. Jend. Sudirman No.12, Jakarta</p>
        <p class="text-gray-600 mb-2"><i class="fas fa-phone text-primary mr-2"></i>+62 21 1234 5678</p>
        <p class="text-gray-600"><i class="fas fa-envelope text-primary mr-2"></i>reservasi@azelhotel.com</p>
      </div>
      <form action="action_kontak.php" method="POST" class="space-y-4">
        <input type="text" name="nama" placeholder="Nama Anda" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-primary outline-none" required>
        <input type="email" name="email" placeholder="Email Anda" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-primary outline-none" required>
        <textarea name="pesan" rows="4" placeholder="Pesan Anda" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-primary outline-none" required></textarea>
        <button type="submit" class="bg-primary text-white px-6 py-3 rounded-lg">Kirim Pesan</button>
      </form>
    </div>
  </div>
</section>

<footer class="bg-gray-900 text-gray-300 py-8">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <p>&copy; <?php echo date('Y'); ?> Azel Hotel. All rights reserved.</p>
  </div>
</footer>

<?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
  icon: '<?php echo isset($_SESSION['success']) ? 'success' : 'error'; ?>',
  title: '<?php echo isset($_SESSION['success']) ? $_SESSION['success'] : $_SESSION['error']; ?>',
  showConfirmButton: false,
  timer: 1800
});
</script>
<?php
unset($_SESSION['success']);
unset($_SESSION['error']);
endif;
?>
</body>
</html>