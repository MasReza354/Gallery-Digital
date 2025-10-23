<?php
$page_title = 'Dashboard Siswa - Galeri Karya Siswa';
require_once '../includes/header.php';
requireLogin();
if (!hasRole('siswa')) {
  header('Location: /dashboard/siswa.php');
  exit;
}

$current_user = getCurrentUser();
$conn = getDBConnection();

// Get user's artworks statistics
$stats_query = "
  SELECT
    COUNT(*) as total_karya,
    COUNT(CASE WHEN status = 'menunggu' THEN 1 END) as menunggu,
    COUNT(CASE WHEN status = 'disetujui' THEN 1 END) as disetujui,
    COUNT(CASE WHEN status = 'ditolak' THEN 1 END) as ditolak,
    COUNT(CASE WHEN status = 'revisi' THEN 1 END) as revisi,
    COUNT(CASE WHEN unggulan = TRUE AND status = 'disetujui' THEN 1 END) as unggulan,
    SUM((SELECT COUNT(*) FROM Suka s WHERE s.karya_id = k.id)) as total_suka
  FROM Karya k
  WHERE k.siswa_id = ?
";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $current_user['id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent artworks
$recent_artworks_query = "
  SELECT k.*, kat.nama as kategori_nama,
         COUNT(DISTINCT s.id) as jumlah_suka
  FROM Karya k
  LEFT JOIN Kategori kat ON k.kategori_id = kat.id
  LEFT JOIN Suka s ON k.id = s.karya_id
  WHERE k.siswa_id = ?
  GROUP BY k.id
  ORDER BY k.dibuat_pada DESC
  LIMIT 6
";

$stmt = $conn->prepare($recent_artworks_query);
$stmt->bind_param("i", $current_user['id']);
$stmt->execute();
$recent_artworks = $stmt->get_result();
$stmt->close();

// Get recent notifications
$notifications_query = "
  SELECT * FROM Notifikasi
  WHERE pengguna_id = ?
  ORDER BY dibuat_pada DESC
  LIMIT 10
";

$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("i", $current_user['id']);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();

$conn->close();

// Mark notifications as read
$conn = getDBConnection();
$conn->query("UPDATE Notifikasi SET status = 'dibaca' WHERE pengguna_id = " . $current_user['id'] . " AND status = 'belum dibaca'");
$conn->close();
?>

<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">

    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-8 mb-8 text-white">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center space-x-4">
          <img src="/uploads/profil/<?php echo htmlspecialchars($current_user['foto_profil']); ?>" alt="Profile" class="w-16 h-16 rounded-full object-cover border-4 border-white" onerror="this.src='/assets/default_avatar.png'">
          <div>
            <h1 class="text-2xl font-bold">Selamat datang, <?php echo htmlspecialchars($current_user['nama_lengkap']); ?>!</h1>
            <p class="text-blue-100">Pantau perkembangan karya Anda di dashboard ini</p>
          </div>
        </div>
        <a href="/upload-karya.php" class="bg-white text-blue-600 px-6 py-3 rounded-lg hover:bg-blue-50 transition-colors font-semibold flex items-center">
          <i class="fas fa-plus mr-2"></i>Unggah Karya
        </a>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
            <i class="fas fa-images text-blue-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['total_karya']; ?></h3>
            <p class="text-gray-600 dark:text-gray-400">Total Karya</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['disetujui']; ?></h3>
            <p class="text-gray-600 dark:text-gray-400">Disetujui</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-lg">
            <i class="fas fa-clock text-yellow-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['menunggu']; ?></h3>
            <p class="text-gray-600 dark:text-gray-400">Menunggu Review</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-lg">
            <i class="fas fa-heart text-purple-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['total_suka']; ?></h3>
            <p class="text-gray-600 dark:text-gray-400">Total Suka</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

      <!-- Recent Artworks -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Karya Anda</h2>
            <a href="/galeri.php?q=&author=me" class="text-blue-600 hover:text-blue-500 font-medium">Lihat Semua</a>
          </div>

          <?php if ($recent_artworks->num_rows > 0): ?>
            <div class="space-y-4">
              <?php while ($karya = $recent_artworks->fetch_assoc()): ?>
                <div class="flex items-center space-x-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                  <div class="flex-shrink-0">
                    <?php
                    $file_type = getFileType($karya['media_url']);
                    if ($file_type === 'image'):
                    ?>
                      <img src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" alt="<?php echo htmlspecialchars($karya['judul']); ?>" class="w-16 h-16 object-cover rounded-lg">
                    <?php elseif ($file_type === 'video'): ?>
                      <video class="w-16 h-16 object-cover rounded-lg bg-gray-200">
                        <source src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" type="video/<?php echo pathinfo($karya['media_url'], PATHINFO_EXTENSION); ?>">
                      </video>
                    <?php else: ?>
                      <div class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-alt text-gray-400 text-xl"></i>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($karya['judul']); ?></h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($karya['kategori_nama'] ?? 'Tanpa Kategori'); ?> • <?php echo getRelativeTime($karya['dibuat_pada']); ?></p>

                    <div class="flex items-center mt-1">
                      <?php
                      $status_colors = [
                        'menunggu' => 'bg-yellow-100 text-yellow-800',
                        'disetujui' => 'bg-green-100 text-green-800',
                        'ditolak' => 'bg-red-100 text-red-800',
                        'revisi' => 'bg-orange-100 text-orange-800'
                      ];
                      $status_text = [
                        'menunggu' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'revisi' => 'Perlu Revisi'
                      ];
                      ?>
                      <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $status_colors[$karya['status']]; ?>">
                        <?php echo $status_text[$karya['status']]; ?>
                      </span>

                      <span class="ml-2 text-gray-500 dark:text-gray-400 text-xs">
                        <i class="fas fa-heart mr-1"></i><?php echo $karya['jumlah_suka']; ?>
                      </span>
                    </div>
                  </div>

                  <div class="flex space-x-2">
                    <a href="/karya.php?id=<?php echo $karya['id']; ?>" class="text-blue-600 hover:text-blue-500 p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                      <i class="fas fa-eye text-sm"></i>
                    </a>
                    <?php if ($karya['status'] === 'menunggu' || $karya['status'] === 'revisi'): ?>
                      <a href="/edit-karya.php?id=<?php echo $karya['id']; ?>" class="text-green-600 hover:text-green-500 p-2 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors">
                        <i class="fas fa-edit text-sm"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-12">
              <i class="fas fa-image text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
              <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">Belum ada karya</h3>
              <p class="text-gray-500 dark:text-gray-500 mb-6">Mulai bagikan kreativitas Anda dengan mengunggah karya pertama!</p>
              <a href="/upload-karya.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                Unggah Karya Pertama
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-8">
        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Aksi Cepat</h2>
          <div class="space-y-3">
            <a href="/upload-karya.php" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
              <i class="fas fa-plus mr-2"></i>Unggah Karya Baru
            </a>

            <a href="/profil.php?id=<?php echo $current_user['id']; ?>" class="w-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-center">
              <i class="fas fa-user mr-2"></i>Lihat Profil
            </a>

            <a href="/galeri.php" class="w-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-center">
              <i class="fas fa-images mr-2"></i>Jelajahi Galeri
            </a>

            <a href="/edit-profil.php" class="w-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-center">
              <i class="fas fa-cog mr-2"></i>Pengaturan Profil
            </a>
          </div>
        </div>

        <!-- Recent Notifications -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Notifikasi Terbaru</h2>

          <?php if ($notifications->num_rows > 0): ?>
            <div class="space-y-3">
              <?php while ($notif = $notifications->fetch_assoc()): ?>
                <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg <?php echo $notif['status'] === 'belum dibaca' ? 'border-l-4 border-blue-500' : ''; ?>">
                  <i class="fas fa-bell text-blue-500 mt-1 flex-shrink-0"></i>
                  <div class="flex-1">
                    <p class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($notif['pesan']); ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?php echo getRelativeTime($notif['dibuat_pada']); ?></p>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">Belum ada notifikasi</p>
          <?php endif; ?>

          <div class="mt-4">
            <a href="/notifikasi.php" class="text-blue-600 hover:text-blue-500 font-medium text-sm">Lihat semua notifikasi →</a>
          </div>
        </div>

        <!-- Account Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Status Akun</h2>
          <div class="flex items-center">
            <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
            <div>
              <p class="font-medium text-gray-900 dark:text-white">Akun Aktif</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">Terakhir login: <?php echo date('d M Y H:i', $_SESSION['login_time'] ?? time()); ?></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>