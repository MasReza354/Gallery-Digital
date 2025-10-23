<?php
$page_title = 'Review Karya - Galeri Karya Siswa';
require_once 'includes/header.php';
requireLogin();
if (!hasRole('guru')) {
  header('Location: /dashboard/guru.php');
  exit;
}

$current_user = getCurrentUser();
$karya_id = (int)($_GET['id'] ?? 0);
$status_filter = sanitize($_GET['status'] ?? 'menunggu');

$conn = getDBConnection();

// If specific artwork ID is provided
if ($karya_id > 0) {
  $stmt = $conn->prepare("SELECT k.*, p.nama_lengkap as nama_siswa, p.email, p.foto_profil,
                                 kat.nama as kategori_nama, kat.deskripsi as kategori_deskripsi
                          FROM Karya k
                          LEFT JOIN Pengguna p ON k.siswa_id = p.id
                          LEFT JOIN Kategori kat ON k.kategori_id = kat.id
                          WHERE k.id = ?");
  $stmt->bind_param("i", $karya_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    header('Location: /review.php');
    exit;
  }

  $karya = $result->fetch_assoc();
  $stmt->close();

  // Get existing feedback if any
  $feedback_stmt = $conn->prepare("SELECT * FROM Umpan_Balik WHERE karya_id = ? AND guru_id = ?");
  $feedback_stmt->bind_param("ii", $karya_id, $current_user['id']);
  $feedback_stmt->execute();
  $existing_feedback = $feedback_stmt->get_result()->fetch_assoc();
  $feedback_stmt->close();

  $conn->close();

  $file_type = getFileType($karya['media_url']);
} else {
  // Show list of artworks for review
  $where_clause = "k.status IN ('menunggu'";
  if ($status_filter === 'all') {
    $where_clause .= ", 'disetujui', 'ditolak', 'revisi'";
  } elseif ($status_filter === 'disetujui') {
    $where_clause .= ", 'disetujui'";
  } elseif ($status_filter === 'ditolak') {
    $where_clause .= ", 'ditolak'";
  } elseif ($status_filter === 'revisi') {
    $where_clause .= ", 'revisi'";
  }
  $where_clause .= ")";

  $page = (int)($_GET['page'] ?? 1);
  $limit = 12;
  $offset = ($page - 1) * $limit;

  $total_query = "SELECT COUNT(*) as total FROM Karya k WHERE $where_clause";
  $total_result = $conn->query($total_query);
  $total_records = $total_result->fetch_assoc()['total'];
  $total_pages = ceil($total_records / $limit);

  $query = "
    SELECT k.*, p.nama_lengkap as nama_siswa, p.foto_profil,
           kat.nama as kategori_nama,
           COUNT(DISTINCT s.id) as jumlah_suka,
           CASE WHEN ub.id IS NOT NULL THEN 1 ELSE 0 END as sudah_review
    FROM Karya k
    LEFT JOIN Pengguna p ON k.siswa_id = p.id
    LEFT JOIN Kategori kat ON k.kategori_id = kat.id
    LEFT JOIN Suka s ON k.id = s.karya_id
    LEFT JOIN Umpan_Balik ub ON k.id = ub.karya_id AND ub.guru_id = {$current_user['id']}
    WHERE $where_clause
    GROUP BY k.id
    ORDER BY
      CASE
        WHEN k.status = 'menunggu' THEN 1
        WHEN sudah_review = 0 THEN 2
        ELSE 3
      END,
      k.dibuat_pada ASC
    LIMIT $limit OFFSET $offset
  ";

  $artworks = $conn->query($query);
  $conn->close();

  // Handle bulk actions
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = sanitize($_POST['bulk_action']);
    $selected_ids = $_POST['selected_artworks'] ?? [];

    if (!empty($selected_ids) && in_array($action, ['approve', 'reject', 'feature'])) {
      $conn = getDBConnection();

      foreach ($selected_ids as $id) {
        if ($action === 'approve') {
          $conn->query("UPDATE Karya SET status = 'disetujui' WHERE id = " . (int)$id);
        } elseif ($action === 'reject') {
          $conn->query("UPDATE Karya SET status = 'ditolak' WHERE id = " . (int)$id);
        } elseif ($action === 'feature') {
          $conn->query("UPDATE Karya SET unggulan = TRUE WHERE id = " . (int)$id);
        }

        // Create notification if not exists
        $siswa_query = $conn->query("SELECT siswa_id FROM Karya WHERE id = " . (int)$id);
        if ($siswa = $siswa_query->fetch_assoc()) {
          $message = match ($action) {
            'approve' => "Karya Anda telah disetujui oleh kurator.",
            'reject' => "Karya Anda perlu diperbaiki sesuai feedback dari kurator.",
            'feature' => "Selamat! Karya Anda telah ditandai sebagai karya unggulan.",
            default => ""
          };

          if ($message) {
            createNotification($siswa['siswa_id'], $message, "/karya.php?id=" . $id);
          }
        }
      }

      $conn->close();
      header('Location: ' . $_SERVER['REQUEST_URI']);
      exit;
    }
  }
}

// Handle individual review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && $karya_id > 0) {
  $status = sanitize($_POST['status']);
  $feedback = sanitize($_POST['feedback'] ?? '');
  $make_featured = isset($_POST['make_featured']);

  if (!in_array($status, ['disetujui', 'ditolak', 'revisi'])) {
    $error = 'Status tidak valid';
  } else {
    $conn = getDBConnection();

    // Update artwork status
    $update_stmt = $conn->prepare("UPDATE Karya SET status = ?, unggulan = ?, diperbarui_pada = CURRENT_TIMESTAMP WHERE id = ?");
    $unggulan = $make_featured ? 1 : $karya['unggulan'];
    $update_stmt->bind_param("sii", $status, $unggulan, $karya_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Insert or update feedback
    if (!empty($feedback)) {
      if ($existing_feedback) {
        // Update existing feedback
        $feedback_stmt = $conn->prepare("UPDATE Umpan_Balik SET umpan_balik = ?, diperbarui_pada = CURRENT_TIMESTAMP WHERE id = ?");
        $feedback_stmt->bind_param("si", $feedback, $existing_feedback['id']);
      } else {
        // Insert new feedback
        $feedback_stmt = $conn->prepare("INSERT INTO Umpan_Balik (karya_id, guru_id, umpan_balik) VALUES (?, ?, ?)");
        $feedback_stmt->bind_param("iis", $karya_id, $current_user['id'], $feedback);
      }
      $feedback_stmt->execute();
      $feedback_stmt->close();
    }

    // Create notification for student
    $status_text = [
      'disetujui' => 'disetujui',
      'ditolak' => 'ditolak',
      'revisi' => 'memerlukan revisi'
    ];

    $link = "/karya.php?id=$karya_id";
    $message = "Karya '{$karya['judul']}' telah {$status_text[$status]} oleh kurator.";

    if ($make_featured && !$karya['unggulan']) {
      $message .= " Selamat! Karya Anda juga ditandai sebagai karya unggulan.";
    }

    createNotification($karya['siswa_id'], $message, $link);

    $conn->close();
    header('Location: /review.php?success=1');
    exit;
  }
}
?>

<?php if ($karya_id > 0): ?>
  <!-- Individual Review Page -->
  <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">

      <!-- Breadcrumb -->
      <nav class="mb-8">
        <div class="flex items-center space-x-2 text-sm">
          <a href="/dashboard/guru.php" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">Dashboard</a>
          <i class="fas fa-chevron-right text-gray-400"></i>
          <a href="/review.php" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">Review</a>
          <i class="fas fa-chevron-right text-gray-400"></i>
          <span class="text-gray-900 dark:text-white font-medium">Review: <?php echo htmlspecialchars($karya['judul']); ?></span>
        </div>
      </nav>

      <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-6 mb-8">
          <div class="flex items-center">
            <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl mr-3"></i>
            <div>
              <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Review Berhasil!</h3>
              <p class="text-green-700 dark:text-green-300 mt-1">Penilaian karya telah disimpan dan siswa telah diberitahu.</p>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Artwork Display -->
        <div>
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="aspect-video bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden mb-6">
              <?php if ($file_type === 'image'): ?>
                <img src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" alt="<?php echo htmlspecialchars($karya['judul']); ?>" class="w-full h-full object-contain">
              <?php elseif ($file_type === 'video'): ?>
                <video controls class="w-full h-full">
                  <source src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" type="video/<?php echo pathinfo($karya['media_url'], PATHINFO_EXTENSION); ?>">
                </video>
              <?php else: ?>
                <div class="w-full h-full flex items-center justify-center">
                  <a href="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" target="_blank" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-download mr-2"></i>Download File
                  </a>
                </div>
              <?php endif; ?>
            </div>

            <div class="space-y-4">
              <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($karya['judul']); ?></h2>
                <div class="flex items-center mt-2">
                  <img src="/uploads/profil/<?php echo htmlspecialchars($karya['foto_profil']); ?>" alt="Student" class="w-8 h-8 rounded-full mr-3" onerror="this.src='/assets/default_avatar.png'">
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($karya['nama_siswa']); ?></p>
                    <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo formatTanggal($karya['dibuat_pada']); ?> • <?php echo htmlspecialchars($karya['kategori_nama'] ?? 'Tanpa Kategori'); ?></p>
                  </div>
                </div>
              </div>

              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Deskripsi</h3>
                <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($karya['deskripsi'] ?: 'Tidak ada deskripsi.')); ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Review Form -->
        <div>
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Beri Penilaian</h3>

            <?php if (isset($error)): ?>
              <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
                <p class="text-red-700 dark:text-red-300"><?php echo $error; ?></p>
              </div>
            <?php endif; ?>

            <form method="POST">
              <!-- Status Selection -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                  Keputusan Review <span class="text-red-500">*</span>
                </label>
                <div class="space-y-3">
                  <label class="flex items-center">
                    <input type="radio" name="status" value="disetujui" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300" <?php echo ($existing_feedback['status'] ?? 'disetujui') === 'disetujui' ? 'checked' : ''; ?>>
                    <span class="ml-3 flex items-center">
                      <i class="fas fa-check-circle text-green-500 mr-2"></i>
                      <span class="font-medium text-gray-900 dark:text-white">Setujui Karya</span>
                      <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">- Publikasikan di galeri</span>
                    </span>
                  </label>

                  <label class="flex items-center">
                    <input type="radio" name="status" value="revisi" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300" <?php echo ($existing_feedback['status'] ?? '') === 'revisi' ? 'checked' : ''; ?>>
                    <span class="ml-3 flex items-center">
                      <i class="fas fa-edit text-yellow-500 mr-2"></i>
                      <span class="font-medium text-gray-900 dark:text-white">Perlu Revisi</span>
                      <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">- Berikan saran perbaikan</span>
                    </span>
                  </label>

                  <label class="flex items-center">
                    <input type="radio" name="status" value="ditolak" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300" <?php echo ($existing_feedback['status'] ?? '') === 'ditolak' ? 'checked' : ''; ?>>
                    <span class="ml-3 flex items-center">
                      <i class="fas fa-times-circle text-red-500 mr-2"></i>
                      <span class="font-medium text-gray-900 dark:text-white">Tolak Karya</span>
                      <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">- Tidak memenuhi kriteria</span>
                    </span>
                  </label>
                </div>
              </div>

              <!-- Featured Checkbox -->
              <div class="mb-6">
                <label class="flex items-center">
                  <input type="checkbox" name="make_featured" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded" <?php echo $karya['unggulan'] ? 'checked' : ''; ?>>
                  <span class="ml-3 flex items-center">
                    <i class="fas fa-star text-yellow-500 mr-2"></i>
                    <span class="text-gray-900 dark:text-white">Tandai sebagai Karya Unggulan</span>
                  </span>
                </label>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 ml-7">Karya ini akan ditampilkan di halaman utama sebagai karya terbaik</p>
              </div>

              <!-- Feedback Text -->
              <div class="mb-6">
                <label for="feedback" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Umpan Balik untuk Siswa <span class="text-red-500">*</span>
                </label>
                <textarea id="feedback" name="feedback" rows="6" required class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400" placeholder="Berikan feedback yang konstruktif dan mendukung perkembangan siswa..."><?php echo htmlspecialchars($existing_feedback['umpan_balik'] ?? ''); ?></textarea>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Minimal 10 karakter. Jelaskan kekuatan karya, saran perbaikan, dan motivasi untuk siswa.</p>
              </div>

              <!-- Submit Buttons -->
              <div class="flex flex-col sm:flex-row gap-4">
                <button type="submit" name="submit_review" class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                  <i class="fas fa-paper-plane mr-2"></i>Simpan Penilaian
                </button>
                <a href="/review.php" class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-center">
                  Batal
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- Review List Page -->
  <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Review Karya Siswa</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Tinjau dan beri penilaian pada karya siswa yang menunggu approval</p>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="mb-8">
        <nav class="flex space-x-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
          <?php
          $tabs = [
            'menunggu' => ['label' => 'Menunggu Review', 'icon' => 'fas fa-clock'],
            'all' => ['label' => 'Semua', 'icon' => 'fas fa-list'],
            'disetujui' => ['label' => 'Disetujui', 'icon' => 'fas fa-check'],
            'revisi' => ['label' => 'Perlu Revisi', 'icon' => 'fas fa-edit'],
            'ditolak' => ['label' => 'Ditolak', 'icon' => 'fas fa-times']
          ];

          foreach ($tabs as $tab => $config):
          ?>
            <a href="?status=<?php echo $tab; ?>" class="flex-1 flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md transition-colors <?php echo $status_filter === $tab ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'; ?>">
              <i class="<?php echo $config['icon']; ?> mr-2"></i><?php echo $config['label']; ?>
            </a>
          <?php endforeach; ?>
        </nav>
      </div>

      <!-- Bulk Actions -->
      <form method="POST" id="bulk-form">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
          <div class="px-6 py-4 border-b dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <label class="flex items-center">
                  <input type="checkbox" id="select-all" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                  <span class="ml-2 text-gray-900 dark:text-white font-medium">Pilih Semua</span>
                </label>
                <span class="text-sm text-gray-600 dark:text-gray-400"><?php echo $total_records; ?> karya ditemukan</span>
              </div>

              <?php if ($total_records > 0): ?>
                <div class="flex space-x-2">
                  <select name="bulk_action" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white dark:bg-gray-700">
                    <option value="">Aksi Massal...</option>
                    <option value="approve">Setujui Terpilih</option>
                    <option value="reject">Tolak Terpilih</option>
                    <option value="feature">Tandai Unggulan</option>
                  </select>
                  <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Terapkan
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Artworks Grid -->
          <?php if ($artworks->num_rows > 0): ?>
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($karya = $artworks->fetch_assoc()): ?>
                  <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start space-x-3 mb-4">
                      <input type="checkbox" name="selected_artworks[]" value="<?php echo $karya['id']; ?>" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded artwork-checkbox">
                      <div class="flex-1">
                        <div class="relative">
                          <?php
                          $file_type = getFileType($karya['media_url']);
                          if ($file_type === 'image'):
                          ?>
                            <img src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" alt="" class="w-full h-32 object-cover rounded-lg">
                          <?php elseif ($file_type === 'video'): ?>
                            <video class="w-full h-32 object-cover rounded-lg bg-gray-200">
                              <source src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" type="video/<?php echo pathinfo($karya['media_url'], PATHINFO_EXTENSION); ?>">
                            </video>
                          <?php else: ?>
                            <div class="w-full h-32 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                              <i class="fas fa-file-alt text-gray-400 text-xl"></i>
                            </div>
                          <?php endif; ?>

                          <?php if ($karya['unggulan']): ?>
                            <div class="absolute top-2 right-2 bg-yellow-500 text-white p-1 rounded-full text-xs">
                              <i class="fas fa-star"></i>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>

                    <div class="space-y-2">
                      <h3 class="font-semibold text-gray-900 dark:text-white line-clamp-1" title="<?php echo htmlspecialchars($karya['judul']); ?>">
                        <?php echo htmlspecialchars(truncateText($karya['judul'], 40)); ?>
                      </h3>
                      <p class="text-sm text-gray-600 dark:text-gray-400">Oleh <?php echo htmlspecialchars($karya['nama_siswa']); ?></p>

                      <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($karya['kategori_nama'] ?? 'Tanpa Kategori'); ?></span>
                        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                          <i class="fas fa-heart mr-1"></i><?php echo $karya['jumlah_suka']; ?>
                        </div>
                      </div>

                      <div class="flex items-center justify-between mt-3">
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

                        <div class="flex space-x-2">
                          <a href="/karya.php?id=<?php echo $karya['id']; ?>" class="text-blue-600 hover:text-blue-500 p-1">
                            <i class="fas fa-eye text-sm"></i>
                          </a>
                          <a href="/review.php?id=<?php echo $karya['id']; ?>" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition-colors">
                            <?php echo $karya['sudah_review'] ? 'Edit' : 'Review'; ?>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>
          <?php else: ?>
            <div class="p-12 text-center">
              <i class="fas fa-search text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
              <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">Tidak ada karya untuk direview</h3>
              <p class="text-gray-500 dark:text-gray-500 mb-6">Semua karya sudah direview atau tidak ada karya yang sesuai filter.</p>
              <a href="?status=all" class="text-blue-600 hover:text-blue-500 font-medium">Lihat semua karya</a>
            </div>
          <?php endif; ?>
        </div>
      </form>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <div class="mt-8 flex justify-center">
          <nav class="relative z-0 inline-flex rounded-md shadow-sm">
            <?php if ($page > 1): ?>
              <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50">
                <i class="fas fa-chevron-left mr-1"></i>Sebelumnya
              </a>
            <?php else: ?>
              <span class="relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-300 cursor-not-allowed">
                <i class="fas fa-chevron-left mr-1"></i>Sebelumnya
              </span>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
              <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'bg-blue-50 dark:bg-blue-900 border-blue-500 text-blue-600' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                <?php echo $i; ?>
              </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
              <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50">
                Selanjutnya<i class="fas fa-chevron-right ml-1"></i>
              </a>
            <?php else: ?>
              <span class="relative inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-300 cursor-not-allowed">
                Selanjutnya<i class="fas fa-chevron-right ml-1"></i>
              </span>
            <?php endif; ?>
          </nav>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<script>
  // Select all checkbox functionality
  document.getElementById('select-all')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.artwork-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
  });
</script>

<?php require_once 'includes/footer.php'; ?>