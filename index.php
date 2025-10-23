<?php
$page_title = 'Galeri Karya Siswa';
require_once 'includes/header.php';

$conn = getDBConnection();

// Get featured artworks (unggulan = true)
$sql_featured = "
  SELECT k.*, p.nama_lengkap as nama_siswa, p.foto_profil,
         kat.nama as kategori_nama,
         COUNT(s.id) as jumlah_suka
  FROM Karya k
  LEFT JOIN Pengguna p ON k.siswa_id = p.id
  LEFT JOIN Kategori kat ON k.kategori_id = kat.id
  LEFT JOIN Suka s ON k.id = s.karya_id
  WHERE k.status = 'disetujui' AND k.unggulan = TRUE
  GROUP BY k.id
  ORDER BY k.dibuat_pada DESC
  LIMIT 6
";
$featured_artworks = $conn->query($sql_featured);

// Get recent artworks
$sql_recent = "
  SELECT k.*, p.nama_lengkap as nama_siswa, p.foto_profil,
         kat.nama as kategori_nama,
         COUNT(s.id) as jumlah_suka
  FROM Karya k
  LEFT JOIN Pengguna p ON k.siswa_id = p.id
  LEFT JOIN Kategori kat ON k.kategori_id = kat.id
  LEFT JOIN Suka s ON k.id = s.karya_id
  WHERE k.status = 'disetujui'
  GROUP BY k.id
  ORDER BY k.dibuat_pada DESC
  LIMIT 8
";
$recent_artworks = $conn->query($sql_recent);

// Get categories for filter
$categories = $conn->query("SELECT * FROM Kategori ORDER BY nama");

$conn->close();
?>

<!-- Hero Section -->
<section class="relative bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 py-20 lg:py-32 overflow-hidden">
  <div class="absolute inset-0 bg-black opacity-20"></div>
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <div class="fade-in">
      <h1 class="text-4xl lg:text-6xl font-bold text-white mb-6 leading-tight">
        Galeri <span class="text-yellow-300">Karya Kreatif</span><br>
        Siswa Indonesia
      </h1>
      <p class="text-xl lg:text-2xl text-white/90 mb-8 max-w-3xl mx-auto">
        Platform digital untuk menampilkan, mengapresiasi, dan menginspirasi karya-karya kreatif siswa dari seluruh Indonesia
      </p>

      <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-12">
        <a href="/galeri.php" class="bg-white text-gray-900 px-8 py-4 rounded-full font-semibold text-lg hover:bg-gray-100 transition-colors flex items-center">
          <i class="fas fa-images mr-2"></i>Jelajahi Galeri
        </a>
        <?php if (!isLoggedIn()): ?>
          <a href="/register.php" class="border-2 border-white text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-white hover:text-gray-900 transition-colors flex items-center">
            <i class="fas fa-user-plus mr-2"></i>Bergabung Sekarang
          </a>
        <?php else: ?>
          <a href="/upload-karya.php" class="border-2 border-white text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-white hover:text-gray-900 transition-colors flex items-center">
            <i class="fas fa-upload mr-2"></i>Unggah Karya
          </a>
        <?php endif; ?>
      </div>

      <!-- Statistics -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 max-w-2xl mx-auto">
        <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
          <div class="text-3xl font-bold text-white mb-2">100+</div>
          <div class="text-white/80 text-sm">Karya Kreatif</div>
        </div>
        <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
          <div class="text-3xl font-bold text-white mb-2">50+</div>
          <div class="text-white/80 text-sm">Siswa Aktif</div>
        </div>
        <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
          <div class="text-3xl font-bold text-white mb-2">8</div>
          <div class="text-white/80 text-sm">Kategori Seni</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Animated background elements -->
  <div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-10 -left-10 w-20 h-20 bg-white/10 rounded-full animate-bounce" style="animation-delay: 0s; animation-duration: 3s;"></div>
    <div class="absolute top-20 -right-10 w-16 h-16 bg-white/10 rounded-full animate-bounce" style="animation-delay: 1s; animation-duration: 4s;"></div>
    <div class="absolute -bottom-10 left-1/4 w-12 h-12 bg-white/10 rounded-full animate-bounce" style="animation-delay: 2s; animation-duration: 3.5s;"></div>
  </div>
</section>

<!-- Featured Artworks Section -->
<section class="py-16 bg-gray-50 dark:bg-gray-900">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Karya Unggulan</h2>
      <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
        Kumpulan karya terbaik yang telah dipilih secara khusus untuk menginspirasi kreativitas Anda
      </p>
    </div>

    <?php if ($featured_artworks->num_rows > 0): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        <?php while ($karya = $featured_artworks->fetch_assoc()): ?>
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow fade-in">
            <div class="relative">
              <?php
              $file_type = getFileType($karya['media_url']);
              if ($file_type === 'image'):
              ?>
                <img src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" alt="<?php echo htmlspecialchars($karya['judul']); ?>" class="w-full h-48 object-cover">
              <?php elseif ($file_type === 'video'): ?>
                <video class="w-full h-48 object-cover" muted>
                  <source src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" type="video/<?php echo pathinfo($karya['media_url'], PATHINFO_EXTENSION); ?>">
                </video>
              <?php else: ?>
                <div class="w-full h-48 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                  <i class="fas fa-file-alt text-4xl text-gray-400"></i>
                </div>
              <?php endif; ?>

              <div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                <i class="fas fa-star mr-1"></i> Unggulan
              </div>
            </div>

            <div class="p-6">
              <div class="flex items-center mb-3">
                <img src="/uploads/profil/<?php echo htmlspecialchars($karya['foto_profil']); ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover mr-3" onerror="this.style.display='none'; this.nextElementSibling.innerHTML='<i class=\'fas fa-user text-gray-400\'></i>';" style="width: 32px; height: 32px;"><i class="fas fa-user text-gray-400" style="display: none;"></i>
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($karya['nama_siswa']); ?></h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo getRelativeTime($karya['dibuat_pada']); ?></p>
                </div>
              </div>

              <h4 class="font-bold text-lg text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($karya['judul']); ?></h4>

              <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-3">
                <?php echo truncateText(strip_tags($karya['deskripsi']), 100); ?>
              </p>

              <div class="flex items-center justify-between">
                <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded-full text-xs">
                  <?php echo htmlspecialchars($karya['kategori_nama'] ?? 'Tanpa Kategori'); ?>
                </span>

                <div class="flex items-center text-gray-500 dark:text-gray-400">
                  <i class="fas fa-heart mr-1"></i>
                  <span class="text-sm"><?php echo $karya['jumlah_suka']; ?></span>
                </div>
              </div>

              <a href="/karya.php?id=<?php echo $karya['id']; ?>" class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors text-center block">
                Lihat Detail
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-12">
        <i class="fas fa-palette text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">Belum ada karya unggulan</h3>
        <p class="text-gray-500 dark:text-gray-500 mb-6">Karya unggulan akan segera ditampilkan di sini</p>
        <?php if (isLoggedIn() && getCurrentUser()['peran'] === 'siswa'): ?>
          <a href="/upload-karya.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
            Unggah Karya Pertama Anda
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="text-center">
      <a href="/galeri.php" class="bg-blue-600 text-white px-8 py-4 rounded-full font-semibold hover:bg-blue-700 transition-colors">
        Jelajahi Semua Karya
      </a>
    </div>
  </div>
</section>

<!-- Recent Artworks Section -->
<section class="py-16 bg-white dark:bg-gray-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Karya Terbaru</h2>
      <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
        Karya-karya terbaru dari siswa kreatif yang baru saja diunggah
      </p>
    </div>

    <?php if ($recent_artworks->num_rows > 0): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php while ($karya = $recent_artworks->fetch_assoc()): ?>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow fade-in">
            <div class="relative">
              <?php
              $file_type = getFileType($karya['media_url']);
              if ($file_type === 'image'):
              ?>
                <img src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" alt="<?php echo htmlspecialchars($karya['judul']); ?>" class="w-full h-32 object-cover">
              <?php elseif ($file_type === 'video'): ?>
                <video class="w-full h-32 object-cover" muted>
                  <source src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" type="video/<?php echo pathinfo($karya['media_url'], PATHINFO_EXTENSION); ?>">
                </video>
              <?php else: ?>
                <div class="w-full h-32 bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                  <i class="fas fa-file-alt text-xl text-gray-400"></i>
                </div>
              <?php endif; ?>
            </div>

            <div class="p-4">
              <h4 class="font-semibold text-gray-900 dark:text-white text-sm mb-1"><?php echo htmlspecialchars($karya['judul']); ?></h4>
              <p class="text-gray-600 dark:text-gray-400 text-xs mb-2"><?php echo htmlspecialchars($karya['nama_siswa']); ?></p>

              <div class="flex items-center justify-between text-xs">
                <span class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-2 py-1 rounded">
                  <?php echo htmlspecialchars($karya['kategori_nama'] ?? 'Tanpa Kategori'); ?>
                </span>
                <div class="flex items-center text-gray-500 dark:text-gray-400">
                  <i class="fas fa-heart mr-1"></i>
                  <span><?php echo $karya['jumlah_suka']; ?></span>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-12">
        <i class="fas fa-image text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">Belum ada karya</h3>
        <p class="text-gray-500 dark:text-gray-500 mb-6">Jadilah siswa pertama yang mengunggah karya Anda</p>
        <?php if (isLoggedIn() && getCurrentUser()['peran'] === 'siswa'): ?>
          <a href="/upload-karya.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
            Unggah Karya Pertama
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Categories Section -->
<section class="py-16 bg-gray-50 dark:bg-gray-900">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Kategori Karya</h2>
      <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
        Jelajahi berbagai kategori karya kreatif
      </p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
      <?php $colors = ['bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500']; ?>
      <?php $i = 0;
      while ($kategori = $categories->fetch_assoc()): ?>
        <a href="/galeri.php?kategori=<?php echo $kategori['id']; ?>" class="bg-white dark:bg-gray-800 <?php echo $colors[$i % count($colors)]; ?> p-6 rounded-lg shadow-md hover:shadow-lg transition-all hover:scale-105 text-center">
          <i class="fas fa-palette text-2xl text-white mb-2"></i>
          <h3 class="font-semibold text-white text-sm"><?php echo htmlspecialchars($kategori['nama']); ?></h3>
        </a>
        <?php $i++; ?>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- Call to Action Section -->
<section class="py-16 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <h2 class="text-3xl font-bold mb-6">Siap Berkreasi dan Berbagi?</h2>
    <p class="text-xl mb-8 opacity-90">
      Bergabunglah dengan komunitas kreatif kami dan tampilkan karya Anda kepada dunia
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <?php if (!isLoggedIn()): ?>
        <a href="/register.php" class="bg-white text-gray-900 px-8 py-4 rounded-full font-semibold text-lg hover:bg-gray-100 transition-colors">
          Daftar Sekarang
        </a>
        <a href="/galeri.php" class="border-2 border-white text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-white/10 transition-colors">
          Jelajahi Galeri
        </a>
      <?php else: ?>
        <a href="/upload-karya.php" class="bg-white text-gray-900 px-8 py-4 rounded-full font-semibold text-lg hover:bg-gray-100 transition-colors">
          Unggah Karya
        </a>
        <a href="/galeri.php" class="border-2 border-white text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-white/10 transition-colors">
          Lihat Semua Karya
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>