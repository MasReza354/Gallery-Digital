<?php
$page_title = 'Edit Karya - Galeri Karya Siswa';
require_once 'includes/header.php';
requireLogin();
if (!hasRole('siswa')) {
  header('Location: /dashboard/siswa.php');
  exit;
}

$current_user = getCurrentUser();
$karya_id = (int)($_GET['id'] ?? 0);

if ($karya_id <= 0) {
  header('Location: /dashboard/siswa.php');
  exit;
}

$conn = getDBConnection();

// Get artwork data
$stmt = $conn->prepare("SELECT * FROM Karya WHERE id = ? AND siswa_id = ?");
$stmt->bind_param("ii", $karya_id, $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header('Location: /dashboard/siswa.php');
  exit;
}

$karya = $result->fetch_assoc();
$stmt->close();

// Check if artwork can be edited (only if not approved yet)
if ($karya['status'] === 'disetujui') {
  header('Location: /karya.php?id=' . $karya_id);
  exit;
}

// Get categories
$categories = $conn->query("SELECT * FROM Kategori ORDER BY nama");

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $judul = sanitize($_POST['judul'] ?? '');
  $deskripsi = sanitize($_POST['deskripsi'] ?? '');
  $kategori_id = (int)($_POST['kategori_id'] ?? 0);

  // Validate
  if (empty($judul)) {
    $errors[] = 'Judul karya harus diisi';
  } elseif (strlen($judul) < 3) {
    $errors[] = 'Judul minimal 3 karakter';
  }

  if (empty($deskripsi)) {
    $errors[] = 'Deskripsi karya harus diisi';
  } elseif (strlen($deskripsi) < 10) {
    $errors[] = 'Deskripsi minimal 10 karakter';
  }

  if ($kategori_id <= 0) {
    $errors[] = 'Kategori harus dipilih';
  }

  // Handle file replacement
  $media_url = $karya['media_url'];
  if (!empty($_FILES['media']['name'])) {
    $upload_result = uploadFile($_FILES['media'], 'uploads/karya');
    if ($upload_result['success']) {
      // Delete old file
      if (file_exists('uploads/karya/' . $media_url)) {
        unlink('uploads/karya/' . $media_url);
      }
      $media_url = $upload_result['filename'];
    } else {
      $errors[] = $upload_result['message'];
    }
  }

  if (empty($errors)) {
    // Update artwork
    $update_stmt = $conn->prepare("UPDATE Karya SET judul = ?, deskripsi = ?, kategori_id = ?, media_url = ?, diperbarui_pada = CURRENT_TIMESTAMP WHERE id = ? AND siswa_id = ?");
    $update_stmt->bind_param("ssiisi", $judul, $deskripsi, $kategori_id, $media_url, $karya_id, $current_user['id']);

    if ($update_stmt->execute()) {
      $success = true;
    } else {
      $errors[] = 'Terjadi kesalahan saat menyimpan perubahan';
    }
    $update_stmt->close();
  }
}

$conn->close();

$file_type = getFileType($media_url);
?>

<div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-4xl mx-auto">

    <!-- Header -->
    <div class="mb-8">
      <a href="/dashboard/siswa.php" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 mb-4">
        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard
      </a>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Karya</h1>
      <p class="text-gray-600 dark:text-gray-400 mt-2">Perbarui informasi karya Anda</p>
    </div>

    <?php if ($success): ?>
      <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-6 mb-8">
        <div class="flex items-center">
          <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl mr-3"></i>
          <div>
            <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Karya Berhasil Diperbarui!</h3>
            <p class="text-green-700 dark:text-green-300 mt-1">Perubahan Anda telah disimpan.</p>
          </div>
        </div>
        <div class="mt-4 flex gap-3">
          <a href="/karya.php?id=<?php echo $karya_id; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            Lihat Karya
          </a>
          <a href="/dashboard/siswa.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
            Dashboard
          </a>
        </div>
      </div>
    <?php endif; ?>

    <!-- Current Status Warning -->
    <?php if ($karya['status'] === 'ditolak' || $karya['status'] === 'revisi'): ?>
      <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
        <div class="flex items-center">
          <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mr-3"></i>
          <div>
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
              <strong>Karya perlu revisi:</strong> Karya ini <?php echo $karya['status'] === 'ditolak' ? 'ditolak' : 'perlu direvisi'; ?> oleh kurator.
              Perbarui karya Anda sesuai feedback yang diberikan sebelum mengirim ulang.
            </p>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
      <div class="p-8">
        <?php if (!empty($errors)): ?>
          <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
            <div class="flex items-center">
              <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
              <div>
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Ada beberapa kesalahan:</h3>
                <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                  <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-8">
          <!-- Title -->
          <div>
            <label for="judul" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Judul Karya <span class="text-red-500">*</span>
            </label>
            <input type="text" id="judul" name="judul" required minlength="3" maxlength="255"
              value="<?php echo htmlspecialchars($karya['judul']); ?>"
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
          </div>

          <!-- Category -->
          <div>
            <label for="kategori_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Kategori <span class="text-red-500">*</span>
            </label>
            <select id="kategori_id" name="kategori_id" required
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
              <option value="">Pilih kategori...</option>
              <?php while ($kategori = $categories->fetch_assoc()): ?>
                <option value="<?php echo $kategori['id']; ?>" <?php echo ($kategori['id'] == $karya['kategori_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($kategori['nama']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- Current Media Preview -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              File Karya Saat Ini
            </label>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
              <?php if ($file_type === 'image'): ?>
                <img src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" alt="Current media" class="max-w-full h-32 object-cover rounded-lg mx-auto">
              <?php elseif ($file_type === 'video'): ?>
                <video controls class="max-w-full h-32 rounded-lg mx-auto">
                  <source src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" type="video/<?php echo pathinfo($karya['media_url'], PATHINFO_EXTENSION); ?>">
                </video>
              <?php else: ?>
                <div class="flex items-center justify-center h-32 bg-gray-200 dark:bg-gray-600 rounded-lg">
                  <i class="fas fa-file-alt text-4xl text-gray-400"></i>
                </div>
              <?php endif; ?>

              <div class="mt-3 flex items-center justify-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                <span><?php echo ucfirst($file_type); ?></span>
                <span>•</span>
                <span><?php echo htmlspecialchars($karya['media_url']); ?></span>
              </div>
            </div>

            <!-- File Replacement -->
            <div>
              <label for="media" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Ganti File Karya (Opsional)
              </label>
              <input type="file" id="media" name="media" accept="image/*,video/*,.pdf,.doc,.docx,.txt"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-gray-300">
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kosongkan jika tidak ingin mengganti file. Format yang didukung: JPG, PNG, GIF, PDF, MP4, MOV</p>
            </div>
          </div>

          <!-- Description -->
          <div>
            <label for="deskripsi" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Deskripsi Karya <span class="text-red-500">*</span>
            </label>
            <textarea id="deskripsi" name="deskripsi" rows="6" required minlength="10"
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"><?php echo htmlspecialchars($karya['deskripsi']); ?></textarea>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Minimal 10 karakter. Jelaskan karya Anda secara detail.</p>
          </div>

          <!-- Submit Buttons -->
          <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t dark:border-gray-600">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
              <i class="fas fa-save mr-2"></i>Simpan Perubahan
            </button>
            <a href="/karya.php?id=<?php echo $karya_id; ?>" class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-center">
              Batal dan Lihat Karya
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>