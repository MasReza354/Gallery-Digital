<?php
$page_title = 'Profil Pengguna - Galeri Karya Siswa';
require_once 'includes/header.php';

$user_id = (int)($_GET['id'] ?? 0);

if ($user_id <= 0) {
  header('Location: /index.php');
  exit;
}

$conn = getDBConnection();

// Get user info
$user_stmt = $conn->prepare("SELECT id, nama_lengkap, foto_profil, dibuat_pada FROM Pengguna WHERE id = ? AND status = 'aktif'");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$result = $user_stmt->get_result();

if ($result->num_rows === 0) {
  header('Location: /index.php');
  exit;
}

$user = $result->fetch_assoc();
$user_stmt->close();

// Get user's artworks
$artworks_stmt = $conn->prepare("
  SELECT k.*, COUNT(DISTINCT s.id) as jumlah_suka
  FROM Karya k
  LEFT JOIN Suka s ON k.id = s.karya_id
  WHERE k.siswa_id = ? AND k.status = 'disetujui'
  GROUP BY k.id
  ORDER BY k.dibuat_pada DESC
");
$artworks_stmt->bind_param("i", $user_id);
$artworks_stmt->execute();
$artworks_result = $artworks_stmt->get_result();
$artworks_stmt->close();

// Get featured artworks only
$featured_stmt = $conn->prepare("SELECT COUNT(*) as count FROM Karya WHERE siswa_id = ? AND status = 'disetujui' AND unggulan = TRUE");
$featured_stmt->bind_param("i", $user_id);
$featured_stmt->execute();
$featured_count = $featured_stmt->get_result()->fetch_assoc()['count'];
$featured_stmt->close();

// Get total likes
$total_likes_stmt = $conn->prepare("SELECT SUM(jumlah_suka) as total FROM (
  SELECT COUNT(DISTINCT s.id) as jumlah_suka
  FROM Karya k
  LEFT JOIN Suka s ON k.id = s.karya_id
  WHERE k.siswa_id = ? AND k.status = 'disetujui'
  GROUP BY k.id
) as likes");
$total_likes_stmt->bind_param("i", $user_id);
$total_likes_stmt->execute();
$total_likes = $total_likes_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_likes_stmt->close();

$current_user = getCurrentUser();
$is_own_profile = $current_user && $current_user['id'] === $user_id;

$conn->close();
?>

<!-- Breadcrumb -->
<nav class="bg-gray-100 dark:bg-gray-800 py-3">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center space-x-2 text-sm">
      <a href="/index.php" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">Beranda</a>
      <i class="fas fa-chevron-right text-gray-400"></i>
      <span class="text-gray-900 dark:text-white font-medium">Profil <?php echo htmlspecialchars($user['nama_lengkap']); ?></span>
    </div>
  </div>
</nav>

<!-- Profile Header -->
<section class="py-12 bg-white dark:bg-gray-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-8">
      <div class="flex flex-col md:flex-row items-center md:items-start space-y-6 md:space-y-0 md:space-x-8">
        <div class="flex-shrink-0">
          <img src="/uploads/profil/<?php echo htmlspecialchars($user['foto_profil']); ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg" onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\'fas fa-user text-6xl text-gray-400\'></i>'">
        </div>

        <div class="flex-1 text-center md:text-left">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($user['nama_lengkap']); ?></h1>
          <p class="text-gray-600 dark:text-gray-400 mb-4">Bergabung sejak <?php echo formatTanggal($user['dibuat_pada']); ?></p>

          <!-- Stats -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600"><?php echo $artworks_result->num_rows; ?></div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Karya</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-purple-600"><?php echo $featured_count; ?></div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Unggulan</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600"><?php echo $total_likes; ?></div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Suka</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-orange-600"><?php echo $artworks_result->num_rows > 0 ? round($total_likes / $artworks_result->num_rows, 1) : 0; ?></div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Rata-rata</div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-wrap gap-3 justify-center md:justify-start">
            <a href="/galeri.php?q=&author=<?php echo $user['id']; ?>" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
              <i class="fas fa-eye mr-2"></i>Lihat Semua Karya
            </a>

            <?php if ($is_own_profile): ?>
              <a href="/edit-profil.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center">
                <i class="fas fa-edit mr-2"></i>Edit Profil
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Artworks Grid -->
<section class="py-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-8">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Karya <?php echo htmlspecialchars($user['nama_lengkap']); ?></h2>

      <?php if ($artworks_result->num_rows > 8): ?>
        <a href="/galeri.php?q=&author=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-500 font-medium">Lihat Semua</a>
      <?php endif; ?>
    </div>

    <?php if ($artworks_result->num_rows > 0): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $count = 0;
        while ($karya = $artworks_result->fetch_assoc() && $count < 8):
          $count++;
        ?>
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-all transform hover:scale-105 fade-in">
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

              <?php if ($karya['unggulan']): ?>
                <div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                  <i class="fas fa-star mr-1"></i> Unggulan
                </div>
              <?php endif; ?>
            </div>

            <div class="p-4">
              <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2 line-clamp-1" title="<?php echo htmlspecialchars($karya['judul']); ?>">
                <?php echo htmlspecialchars(truncateText($karya['judul'], 30)); ?>
              </h3>

              <p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2">
                <?php echo truncateText(strip_tags($karya['deskripsi']), 60); ?>
              </p>

              <div class="flex items-center justify-between">
                <a href="/karya.php?id=<?php echo $karya['id']; ?>" class="text-blue-600 hover:text-blue-500 font-medium text-sm">
                  Lihat Detail
                </a>

                <div class="flex items-center text-gray-500 dark:text-gray-400">
                  <i class="fas fa-heart mr-1"></i>
                  <span class="text-sm"><?php echo $karya['jumlah_suka']; ?></span>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-16">
        <i class="fas fa-palette text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">Belum ada karya</h3>
        <p class="text-gray-500 dark:text-gray-500 mb-6"><?php echo $is_own_profile ? 'Mulai bagikan kreativitas Anda' : 'Pengguna ini belum mengunggah karya apapun'; ?></p>

        <?php if ($is_own_profile): ?>
          <a href="/upload-karya.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
            Unggah Karya Pertama
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>