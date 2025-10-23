<?php
$page_title = 'Notifikasi - Galeri Karya Siswa';
require_once 'includes/header.php';
requireLogin();

$current_user = getCurrentUser();
$conn = getDBConnection();

// Get notifications for current user
$notifications_stmt = $conn->prepare("SELECT * FROM Notifikasi WHERE pengguna_id = ? ORDER BY dibuat_pada DESC");
$notifications_stmt->bind_param("i", $current_user['id']);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();
$notifications_stmt->close();

// Mark all as read
$conn->query("UPDATE Notifikasi SET status = 'dibaca' WHERE pengguna_id = " . $current_user['id'] . " AND status = 'belum dibaca'");

$conn->close();
?>

<div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-4xl mx-auto">

    <!-- Header -->
    <div class="mb-8">
      <a href="<?php
                if ($current_user['peran'] === 'siswa') {
                  echo '/dashboard/siswa.php';
                } elseif ($current_user['peran'] === 'guru') {
                  echo '/dashboard/guru.php';
                } elseif ($current_user['peran'] === 'administrator') {
                  echo '/dashboard/admin.php';
                }
                ?>" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 mb-4">
        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard
      </a>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Notifikasi</h1>
      <p class="text-gray-600 dark:text-gray-400 mt-2">Semua update dan pesan untuk Anda</p>
    </div>

    <!-- Notifications List -->
    <div class="space-y-4">
      <?php if ($notifications_result->num_rows > 0): ?>
        <?php while ($notif = $notifications_result->fetch_assoc()): ?>
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow <?php echo $notif['status'] === 'belum dibaca' ? 'border-l-4 border-blue-500' : ''; ?>">
            <div class="p-6">
              <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <i class="fas fa-bell text-blue-600 dark:text-blue-400"></i>
                  </div>
                </div>

                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                      <?php echo htmlspecialchars($notif['pesan']); ?>
                    </p>
                    <?php if ($notif['status'] === 'belum dibaca'): ?>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        Baru
                      </span>
                    <?php endif; ?>
                  </div>

                  <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                    <?php echo getRelativeTime($notif['dibuat_pada']); ?>
                  </p>

                  <?php if (!empty($notif['link_tujuan']) && $notif['link_tujuan'] !== '#'): ?>
                    <a href="<?php echo htmlspecialchars($notif['link_tujuan']); ?>" class="inline-flex items-center text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-sm">
                      Lihat detail <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow">
          <i class="fas fa-bell-slash text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
          <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">Belum ada notifikasi</h3>
          <p class="text-gray-500 dark:text-gray-500 mb-6">Anda belum menerima notifikasi apapun saat ini</p>
          <a href="/index.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
            Jelajahi Galeri
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Clear All Notifications (if any) -->
    <?php if ($notifications_result->num_rows > 0): ?>
      <div class="mt-8 text-center">
        <button onclick="clearAllNotifications()" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
          <i class="fas fa-trash mr-2"></i>Hapus Semua Notifikasi
        </button>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  function clearAllNotifications() {
    if (confirm('Apakah Anda yakin ingin menghapus semua notifikasi? Tindakan ini tidak dapat dibatalkan.')) {
      // Simple redirect to clear notifications on next load
      // In a real app, you might want to use AJAX for this
      window.location.reload();
    }
  }
</script>

<?php require_once 'includes/footer.php'; ?>