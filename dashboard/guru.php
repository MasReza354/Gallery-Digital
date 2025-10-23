<?php
$page_title = 'Dashboard Guru/Kurator - Galeri Karya Siswa';
require_once '../includes/header.php';
requireLogin();
if (!hasRole('guru')) {
  header('Location: /dashboard/guru.php');
  exit;
}

$current_user = getCurrentUser();
$conn = getDBConnection();

// Get pending artworks count
$pending_count = $conn->query("SELECT COUNT(*) as count FROM Karya WHERE status = 'menunggu'")->fetch_assoc()['count'];

// Get recent reviews count
$recent_reviews = $conn->query("SELECT COUNT(*) as count FROM Umpan_Balik WHERE guru_id = {$current_user['id']} AND dibuat_pada >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];

// Get featured artworks count made by this teacher
$featured_count = $conn->query("SELECT COUNT(*) as count FROM Karya k INNER JOIN Umpan_Balik ub ON k.id = ub.karya_id WHERE ub.guru_id = {$current_user['id']} AND k.unggulan = TRUE")->fetch_assoc()['count'];

// Get pending artworks for review
$pending_artworks = $conn->query("
  SELECT k.*, p.nama_lengkap as nama_siswa, p.foto_profil,
         kat.nama as kategori_nama, COUNT(DISTINCT s.id) as jumlah_suka
  FROM Karya k
  LEFT JOIN Pengguna p ON k.siswa_id = p.id
  LEFT JOIN Kategori kat ON k.kategori_id = kat.id
  LEFT JOIN Suka s ON k.id = s.karya_id
  WHERE k.status = 'menunggu'
  GROUP BY k.id
  ORDER BY k.dibuat_pada ASC
  LIMIT 10
");

// Get recent reviews made by this teacher
$recent_feedback = $conn->query("
  SELECT ub.*, k.judul, k.media_url, p.nama_lengkap as nama_siswa
  FROM Umpan_Balik ub
  LEFT JOIN Karya k ON ub.karya_id = k.id
  LEFT JOIN Pengguna p ON k.siswa_id = p.id
  WHERE ub.guru_id = {$current_user['id']}
  ORDER BY ub.dibuat_pada DESC
  LIMIT 5
");

$conn->close();
?>

<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">

    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-green-600 to-blue-600 rounded-lg shadow-lg p-8 mb-8 text-white">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center space-x-4">
          <img src="/uploads/profil/<?php echo htmlspecialchars($current_user['foto_profil']); ?>" alt="Profile" class="w-16 h-16 rounded-full object-cover border-4 border-white" onerror="this.src='/assets/default_avatar.png'">
          <div>
            <h1 class="text-2xl font-bold">Selamat datang, <?php echo htmlspecialchars($current_user['nama_lengkap']); ?>!</h1>
            <p class="text-green-100">Dashboard Guru/Kurator - Tinjau dan beri penilaian karya siswa</p>
          </div>
        </div>
        <div class="flex gap-3">
          <a href="#pending" class="bg-white text-green-600 px-4 py-2 rounded-lg hover:bg-green-50 transition-colors font-semibold text-sm">
            <i class="fas fa-clock mr-1"></i><?php echo $pending_count; ?> Pending
          </a>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-lg">
            <i class="fas fa-clock text-yellow-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $pending_count; ?></h3>
            <p class="text-gray-600 dark:text-gray-400">Karya Menunggu</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
            <i class="fas fa-comment text-blue-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $recent_reviews; ?></h3>
            <p class="text-gray-600 dark:text-gray-400">Review Minggu Ini</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-lg">
            <i class="fas fa-star text-purple-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $featured_count; ?></h3>
            <p class="text-gray-600 dark:text-gray-400">Karya Unggulan</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
              <?php
              $conn = getDBConnection();
              $approved_this_month = $conn->query("SELECT COUNT(DISTINCT k.id) as count FROM Karya k INNER JOIN Umpan_Balik ub ON k.id = ub.karya_id WHERE ub.guru_id = {$current_user['id']} AND MONTH(k.diperbarui_pada) = MONTH(CURRENT_DATE()) AND YEAR(k.diperbarui_pada) = YEAR(CURRENT_DATE()) AND k.status = 'disetujui'");
              echo $approved_this_month->fetch_assoc()['count'];
              $conn->close();
              ?>
            </h3>
            <p class="text-gray-600 dark:text-gray-400">Disetujui Bulan Ini</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

      <!-- Pending Reviews -->
      <div class="lg:col-span-2" id="pending">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Karya Menunggu Penilaian</h2>
            <a href="/review.php?status=menunggu" class="text-blue-600 hover:text-blue-500 font-medium">Lihat Semua</a>
          </div>

          <?php if ($pending_artworks->num_rows > 0): ?>
            <div class="space-y-4">
              <?php while ($karya = $pending_artworks->fetch_assoc()): ?>
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
                    <p class="text-sm text-gray-600 dark:text-gray-400">Oleh <?php echo htmlspecialchars($karya['nama_siswa']); ?> • <?php echo htmlspecialchars($karya['kategori_nama'] ?? 'Tanpa Kategori'); ?></p>
                    <div class="flex items-center mt-1">
                      <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                        Menunggu Review
                      </span>
                      <span class="ml-2 text-gray-500 dark:text-gray-400 text-xs">
                        <?php echo getRelativeTime($karya['dibuat_pada']); ?>
                      </span>
                    </div>
                  </div>

                  <div class="flex space-x-2">
                    <a href="/karya.php?id=<?php echo $karya['id']; ?>" class="text-blue-600 hover:text-blue-500 p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                      <i class="fas fa-eye text-sm"></i>
                    </a>
                    <a href="/review.php?id=<?php echo $karya['id']; ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm">
                      Review
                    </a>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-12">
              <i class="fas fa-check-circle text-6xl text-green-300 dark:text-green-600 mb-4"></i>
              <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">Tidak ada karya menunggu</h3>
              <p class="text-gray-500 dark:text-gray-500 mb-6">Semua karya sudah direview. Bagus!</p>
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
            <a href="/review.php?status=menunggu" class="w-full bg-yellow-600 text-white py-3 px-4 rounded-lg hover:bg-yellow-700 transition-colors flex items-center justify-center">
              <i class="fas fa-list mr-2"></i>Review Karya
            </a>

            <a href="/galeri.php?status=disetujui" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
              <i class="fas fa-images mr-2"></i>Lihat Galeri
            </a>

            <a href="/edit-profil.php" class="w-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-center">
              <i class="fas fa-cog mr-2"></i>Pengaturan Profil
            </a>
          </div>
        </div>

        <!-- Recent Reviews -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Review Terbaru</h2>

          <?php if ($recent_feedback->num_rows > 0): ?>
            <div class="space-y-3">
              <?php while ($feedback = $recent_feedback->fetch_assoc()): ?>
                <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div class="flex-shrink-0">
                    <?php
                    $file_type = getFileType($feedback['media_url']);
                    if ($file_type === 'image'):
                    ?>
                      <img src="/uploads/karya/<?php echo htmlspecialchars($feedback['media_url']); ?>" alt="" class="w-10 h-10 object-cover rounded">
                    <?php else: ?>
                      <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center">
                        <i class="fas fa-file-alt text-gray-400 text-sm"></i>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2"><?php echo htmlspecialchars($feedback['judul']); ?></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($feedback['nama_siswa']); ?> • <?php echo getRelativeTime($feedback['dibuat_pada']); ?></p>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">Belum ada review</p>
          <?php endif; ?>

          <div class="mt-4">
            <a href="/review.php?status=all" class="text-blue-600 hover:text-blue-500 font-medium text-sm">Lihat semua review →</a>
          </div>
        </div>

        <!-- Guidelines -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-3">
            <i class="fas fa-info-circle mr-2"></i>Panduan Review
          </h3>
          <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
            <li>• Berikan feedback yang konstruktif</li>
            <li>• Nilai berdasarkan kreativitas dan originality</li>
            <li>• Centang "Unggulan" untuk karya terbaik</li>
            <li>• Siswa akan menerima notifikasi hasil review</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>