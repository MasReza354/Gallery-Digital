<?php
$page_title = 'Dashboard Administrator - Galeri Karya Siswa';
require_once '../includes/header.php';
requireLogin();
if (!hasRole('administrator')) {
  header('Location: /dashboard/admin.php');
  exit;
}

$current_user = getCurrentUser();
$conn = getDBConnection();

// Get system statistics
$stats = $conn->query("
  SELECT
    (SELECT COUNT(*) FROM Pengguna WHERE peran = 'administrator') as total_admin,
    (SELECT COUNT(*) FROM Pengguna WHERE peran = 'guru') as total_guru,
    (SELECT COUNT(*) FROM Pengguna WHERE peran = 'siswa') as total_siswa,
    (SELECT COUNT(*) FROM Karya) as total_karya,
    (SELECT COUNT(*) FROM Karya WHERE status = 'menunggu') as karya_menunggu,
    (SELECT COUNT(*) FROM Karya WHERE status = 'disetujui') as karya_disetujui,
    (SELECT COUNT(*) FROM Karya WHERE unggulan = TRUE) as karya_unggulan
")->fetch_assoc();

// Get recent users
$recent_users = $conn->query("
  SELECT id, nama_lengkap, peran, status, dibuat_pada
  FROM Pengguna
  ORDER BY dibuat_pada DESC
  LIMIT 5
");

// Get recent artworks
$recent_artworks = $conn->query("
  SELECT k.id, k.judul, k.status, k.dibuat_pada, p.nama_lengkap as nama_siswa, kat.nama as kategori_nama
  FROM Karya k
  LEFT JOIN Pengguna p ON k.siswa_id = p.id
  LEFT JOIN Kategori kat ON k.kategori_id = kat.id
  ORDER BY k.dibuat_pada DESC
  LIMIT 5
");

// Handle user status changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = sanitize($_POST['action']);
  $user_id = (int)($_POST['user_id'] ?? 0);

  if ($user_id > 0) {
    if ($action === 'activate') {
      $conn->query("UPDATE Pengguna SET status = 'aktif' WHERE id = $user_id");
      createNotification($user_id, "Akun Anda telah diaktifkan oleh administrator.", "/dashboard/" . ($current_user['peran'] === 'siswa' ? 'siswa' : 'guru') . ".php");
    } elseif ($action === 'deactivate') {
      $conn->query("UPDATE Pengguna SET status = 'tidak aktif' WHERE id = $user_id");
      createNotification($user_id, "Akun Anda telah dinonaktifkan oleh administrator.", "/dashboard/" . ($current_user['peran'] === 'siswa' ? 'siswa' : 'guru') . ".php");
    } elseif ($action === 'delete') {
      $conn->query("DELETE FROM Pengguna WHERE id = $user_id AND id != 1"); // Don't delete admin account
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
  }
}

// Handle artwork management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['artwork_action'])) {
  $action = sanitize($_POST['artwork_action']);
  $artwork_id = (int)($_POST['artwork_id'] ?? 0);

  if ($artwork_id > 0) {
    if ($action === 'feature') {
      $conn->query("UPDATE Karya SET unggulan = TRUE WHERE id = $artwork_id");
    } elseif ($action === 'unfeature') {
      $conn->query("UPDATE Karya SET unggulan = FALSE WHERE id = $artwork_id");
    } elseif ($action === 'delete') {
      $conn->query("DELETE FROM Karya WHERE id = $artwork_id");
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
  }
}

$conn->close();
?>

<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">

    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg shadow-lg p-8 mb-8 text-white">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center space-x-4">
          <img src="/uploads/profil/<?php echo htmlspecialchars($current_user['foto_profil']); ?>" alt="Profile" class="w-16 h-16 rounded-full object-cover border-4 border-white" onerror="this.src='/assets/default_avatar.png'">
          <div>
            <h1 class="text-2xl font-bold">Selamat datang, Admin!</h1>
            <p class="text-purple-100">Kelola sistem galeri karya siswa dari dashboard administrator</p>
          </div>
        </div>
        <div class="flex gap-3">
          <a href="#users" class="bg-white text-purple-600 px-4 py-2 rounded-lg hover:bg-purple-50 transition-colors font-semibold text-sm flex items-center">
            <i class="fas fa-users mr-1"></i><?php echo $stats['total_siswa'] + $stats['total_guru'] + $stats['total_admin']; ?> Users
          </a>
          <a href="#artworks" class="bg-white text-purple-600 px-4 py-2 rounded-lg hover:bg-purple-50 transition-colors font-semibold text-sm flex items-center">
            <i class="fas fa-images mr-1"></i><?php echo $stats['total_karya']; ?> Karya
          </a>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
            <i class="fas fa-users text-green-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['total_siswa'] + $stats['total_guru'] + $stats['total_admin']; ?></h3>
            <p class="text-gray-600 dark:text-gray-400">Total Pengguna</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
            <i class="fas fa-paint-brush text-blue-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['total_karya']; ?></h3>
            <p class="text-gray-600 dark:text-gray-400">Total Karya</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-lg">
            <i class="fas fa-clock text-yellow-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['karya_menunggu']; ?></h3>
            <p class="text-gray-600 dark:text-gray-400">Menunggu Review</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-lg">
            <i class="fas fa-star text-purple-600 text-2xl"></i>
          </div>
          <div class="ml-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['karya_unggulan']; ?></h3>
            <p class="text-gray-600 dark:text-gray-400">Karya Unggulan</p>
          </div>
        </div>
      </div>
    </div>

    <!-- User Management Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" id="users">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Administrator</h3>
            <p class="text-gray-600 dark:text-gray-400"><?php echo $stats['total_admin']; ?> akun aktif</p>
          </div>
          <i class="fas fa-user-shield text-3xl text-red-500"></i>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Guru/Kurator</h3>
            <p class="text-gray-600 dark:text-gray-400"><?php echo $stats['total_guru']; ?> akun aktif</p>
          </div>
          <i class="fas fa-chalkboard-teacher text-3xl text-green-500"></i>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Siswa</h3>
            <p class="text-gray-600 dark:text-gray-400"><?php echo $stats['total_siswa']; ?> akun aktif</p>
          </div>
          <i class="fas fa-graduation-cap text-3xl text-blue-500"></i>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

      <!-- Recent Users -->
      <div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Pengguna Terbaru</h2>
            <a href="/admin/users.php" class="text-blue-600 hover:text-blue-500 font-medium">Kelola Semua</a>
          </div>

          <?php if ($recent_users->num_rows > 0): ?>
            <div class="space-y-4">
              <?php while ($user = $recent_users->fetch_assoc()): ?>
                <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                      <i class="fas fa-user text-gray-500"></i>
                    </div>
                    <div>
                      <h3 class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['nama_lengkap']); ?></h3>
                      <p class="text-sm text-gray-600 dark:text-gray-400 capitalize"><?php echo $user['peran']; ?> • <?php echo getRelativeTime($user['dibuat_pada']); ?></p>
                    </div>
                  </div>

                  <div class="flex items-center space-x-2">
                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $user['status'] === 'aktif' ? 'bg-green-100 text-green-800' : ($user['status'] === 'menunggu' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                      <?php echo ucfirst($user['status']); ?>
                    </span>

                    <form method="POST" class="inline">
                      <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                      <?php if ($user['status'] === 'menunggu'): ?>
                        <button type="submit" name="action" value="activate" class="text-green-600 hover:text-green-500 p-2" title="Aktifkan">
                          <i class="fas fa-check text-sm"></i>
                        </button>
                      <?php elseif ($user['status'] === 'aktif'): ?>
                        <button type="submit" name="action" value="deactivate" class="text-yellow-600 hover:text-yellow-700 p-2" title="Nonaktifkan">
                          <i class="fas fa-ban text-sm"></i>
                        </button>
                      <?php elseif ($user['status'] === 'tidak aktif'): ?>
                        <button type="submit" name="action" value="activate" class="text-green-600 hover:text-green-500 p-2" title="Aktifkan">
                          <i class="fas fa-check text-sm"></i>
                        </button>
                      <?php endif; ?>
                      <?php if ($user['id'] != 1): // Don't allow deleting main admin 
                      ?>
                        <button type="submit" name="action" value="delete" class="text-red-600 hover:text-red-700 p-2 ml-2" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">
                          <i class="fas fa-trash text-sm"></i>
                        </button>
                      <?php endif; ?>
                    </form>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">Belum ada pengguna terdaftar</p>
          <?php endif; ?>

          <div class="mt-6">
            <a href="/admin/users.php" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors text-center block">
              <i class="fas fa-users mr-2"></i>Kelola Semua Pengguna
            </a>
          </div>
        </div>
      </div>

      <!-- Recent Artworks -->
      <div id="artworks">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Karya Terbaru</h2>
            <a href="/review.php?status=all" class="text-blue-600 hover:text-blue-500 font-medium">Kelola Semua</a>
          </div>

          <?php if ($recent_artworks->num_rows > 0): ?>
            <div class="space-y-4">
              <?php while ($karya = $recent_artworks->fetch_assoc()): ?>
                <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 dark:text-white line-clamp-1"><?php echo htmlspecialchars($karya['judul']); ?></h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Oleh <?php echo htmlspecialchars($karya['nama_siswa']); ?> • <?php echo htmlspecialchars($karya['kategori_nama'] ?? 'Tanpa Kategori'); ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?php echo getRelativeTime($karya['dibuat_pada']); ?></p>

                    <div class="flex items-center mt-2">
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
                    </div>
                  </div>

                  <div class="ml-4 flex space-x-2">
                    <a href="/karya.php?id=<?php echo $karya['id']; ?>" class="text-blue-600 hover:text-blue-500 p-2">
                      <i class="fas fa-eye text-sm"></i>
                    </a>

                    <?php
                    $conn = getDBConnection();
                    $is_featured = $conn->query("SELECT unggulan FROM Karya WHERE id = " . $karya['id'])->fetch_assoc()['unggulan'];
                    $conn->close();
                    ?>

                    <form method="POST" class="inline">
                      <input type="hidden" name="artwork_id" value="<?php echo $karya['id']; ?>">
                      <?php if (!$is_featured): ?>
                        <button type="submit" name="artwork_action" value="feature" class="text-yellow-600 hover:text-yellow-700 p-2" title="Tandai Unggulan">
                          <i class="fas fa-star text-sm"></i>
                        </button>
                      <?php else: ?>
                        <button type="submit" name="artwork_action" value="unfeature" class="text-yellow-600 hover:text-yellow-700 p-2" title="Hapus dari Unggulan">
                          <i class="fas fa-star-half-alt text-sm"></i>
                        </button>
                      <?php endif; ?>
                    </form>

                    <button type="button" onclick="deleteArtwork(<?php echo $karya['id']; ?>)" class="text-red-600 hover:text-red-700 p-2" title="Hapus">
                      <i class="fas fa-trash text-sm"></i>
                    </button>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">Belum ada karya diunggah</p>
          <?php endif; ?>

          <div class="mt-6">
            <a href="/review.php?status=all" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors text-center block">
              <i class="fas fa-list mr-2"></i>Kelola Semua Karya
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- System Management -->
    <div class="mt-8">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Manajemen Sistem</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="/admin/users.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow block">
          <div class="flex items-center">
            <i class="fas fa-users text-3xl text-blue-500 mr-4"></i>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white">Kelola Pengguna</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Aktivasi, edit, dan hapus akun</p>
            </div>
          </div>
        </a>

        <a href="/review.php?status=menunggu" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow block">
          <div class="flex items-center">
            <i class="fas fa-gavel text-3xl text-green-500 mr-4"></i>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white">Review Manual</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Review karya secara manual</p>
            </div>
          </div>
        </a>

        <a href="/galeri.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow block">
          <div class="flex items-center">
            <i class="fas fa-images text-3xl text-purple-500 mr-4"></i>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white">Galeri Umum</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Pantau galeri publik</p>
            </div>
          </div>
        </a>

        <a href="/admin/reports.php" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow block">
          <div class="flex items-center">
            <i class="fas fa-chart-bar text-3xl text-orange-500 mr-4"></i>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white">Laporan</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Statistik dan analisis</p>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
</div>

<script>
  function deleteArtwork(artworkId) {
    if (confirm('Apakah Anda yakin ingin menghapus karya ini? Tindakan ini tidak dapat dibatalkan.')) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.innerHTML = `
      <input type="hidden" name="artwork_id" value="${artworkId}">
      <input type="hidden" name="artwork_action" value="delete">
    `;
      document.body.appendChild(form);
      form.submit();
    }
  }
</script>

<?php require_once '../includes/footer.php'; ?>