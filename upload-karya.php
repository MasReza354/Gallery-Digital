<?php
$page_title = 'Unggah Karya - Galeri Karya Siswa';
require_once 'includes/header.php';

// Check if user is logged in and has student role
if (!isLoggedIn()) {
  $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
  header('Location: /login.php');
  exit;
}

$current_user = getCurrentUser();
if (!$current_user || $current_user['peran'] !== 'siswa') {
  header('Location: /index.php');
  exit;
}

$current_user = getCurrentUser();
$conn = getDBConnection();

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM Kategori ORDER BY nama");

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get form data
  $judul = sanitize($_POST['judul'] ?? '');
  $deskripsi = sanitize($_POST['deskripsi'] ?? '');
  $kategori_id = (int)($_POST['kategori_id'] ?? 0);

  // Validate input
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

  // Handle file upload
  if (empty($_FILES['media']['name'])) {
    $errors[] = 'File karya harus diunggah';
  } else {
    $upload_result = uploadFile($_FILES['media'], 'uploads/karya');

    if (!$upload_result['success']) {
      $errors[] = $upload_result['message'];
    }
  }

  // If no errors, save to database
  if (empty($errors)) {
    $media_url = $upload_result['filename'];

    // Insert karya into database
    $stmt = $conn->prepare("INSERT INTO Karya (judul, deskripsi, siswa_id, kategori_id, media_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $judul, $deskripsi, $current_user['id'], $kategori_id, $media_url);

    if ($stmt->execute()) {
      $karya_id = $conn->insert_id;
      $success = true;

      // Create notification for user
      createNotification($current_user['id'], "Karya '{$judul}' telah berhasil diunggah dan sedang menunggu persetujuan dari kurator.", "/dashboard/siswa.php");

      // Optionally notify administrators (you can uncomment this if needed)
      /*
      $admin_query = $conn->query("SELECT id FROM Pengguna WHERE peran = 'administrator'");
      while ($admin = $admin_query->fetch_assoc()) {
        createNotification($admin['id'], "Ada karya baru dari {$current_user['nama_lengkap']} yang perlu ditinjau.", "/dashboard/admin.php");
      }
      */

      $stmt->close();
    } else {
      $errors[] = 'Terjadi kesalahan saat menyimpan karya';
      $stmt->close();
    }
  }
}

$conn->close();
?>

<div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-4xl mx-auto">

    <?php if ($success): ?>
      <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-6 mb-8">
        <div class="flex items-center">
          <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl mr-3"></i>
          <div>
            <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Karya Berhasil Diunggah!</h3>
            <p class="text-green-700 dark:text-green-300 mt-1">Karya Anda telah dikirim dan sedang menunggu persetujuan dari kurator.</p>
          </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-4">
          <a href="/dashboard/siswa.php" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
            Lihat Dashboard
          </a>
          <a href="/galeri.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
            Lihat Galeri
          </a>
          <a href="/upload-karya.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
            Unggah Karya Lain
          </a>
        </div>
      </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
      <div class="flex items-center mb-8">
        <i class="fas fa-upload text-3xl text-blue-600 mr-4"></i>
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Unggah Karya Baru</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Bagikan kreativitas Anda dengan komunitas</p>
        </div>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
          <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
            <div>
              <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Ada beberapa kesalahan yang perlu diperbaiki:</h3>
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
        <!-- Artwork Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Title -->
          <div>
            <label for="judul" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Judul Karya <span class="text-red-500">*</span>
            </label>
            <input type="text" id="judul" name="judul" required minlength="3" maxlength="255"
              value="<?php echo isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : ''; ?>"
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
              placeholder="Masukkan judul karya yang menarik">
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
                <option value="<?php echo $kategori['id']; ?>" <?php echo (isset($_POST['kategori_id']) && $_POST['kategori_id'] == $kategori['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($kategori['nama']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label for="deskripsi" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Deskripsi Karya <span class="text-red-500">*</span>
          </label>
          <textarea id="deskripsi" name="deskripsi" rows="4" required minlength="10"
            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
            placeholder="Jelaskan tentang proses pembuatan, inspirasi, teknik yang digunakan, atau ceritakan tentang karyamu..."><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Minimum 10 karakter. Deskripsikan karyamu secara lengkap untuk membantu penilaian kurator.
          </p>
        </div>

        <!-- File Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            File Karya <span class="text-red-500">*</span>
          </label>

          <!-- Drag & Drop Upload Area -->
          <div id="dropzone" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-blue-500 transition-colors cursor-pointer bg-gray-50 dark:bg-gray-700">
            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
            <div class="text-gray-600 dark:text-gray-400">
              <p class="font-medium mb-2">Klik untuk memilih file atau drag & drop</p>
              <p class="text-sm">Format yang didukung: JPG, PNG, GIF, PDF, MP4, MOV</p>
              <p class="text-sm">Ukuran maksimal: 10MB</p>
            </div>
            <input type="file" id="media" name="media" accept="image/*,video/*,.pdf,.doc,.docx,.txt" hidden required>
          </div>

          <!-- File Preview -->
          <div id="file-preview" class="(hidden) mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg flex items-center">
            <i class="fas fa-file text-blue-500 mr-3"></i>
            <div class="flex-1">
              <p class="text-sm font-medium text-blue-800 dark:text-blue-200" id="file-name"></p>
              <p class="text-xs text-blue-600 dark:text-blue-300" id="file-size"></p>
            </div>
            <button type="button" onclick="clearFile()" class="text-red-500 hover:text-red-700 p-2">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>

        <!-- Preview Section -->
        <div id="image-preview" class="hidden">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Pratinjau Gambar
          </label>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-white dark:bg-gray-700 rounded-lg p-4">
              <img id="preview-thumb" alt="Thumbnail" class="w-full h-32 object-cover rounded-lg cursor-pointer" onclick="showFullPreview()">
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 text-center">Klik untuk memperbesar</p>
            </div>
          </div>
        </div>

        <!-- Guidelines -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-3">
            <i class="fas fa-info-circle mr-2"></i>Pedoman Mengunggah Karya
          </h3>
          <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-2">
            <li>• Pastikan karya Anda original dan bukan hasil plagiarisme</li>
            <li>• Berikan judul yang menarik dan deskripsi yang informatif</li>
            <li>• Gunakan file dengan resolusi tinggi untuk hasil terbaik</li>
            <li>• Karya akan ditinjau oleh kurator sebelum dipublikasikan</li>
            <li>• Proses review biasanya memakan waktu 1-3 hari</li>
            <li>• Anda akan menerima notifikasi hasil review via sistem</li>
          </ul>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t dark:border-gray-600">
          <button type="submit" class="flex-1 bg-blue-600 text-white py-4 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            <i class="fas fa-upload mr-2"></i>Unggah Karya
          </button>
          <a href="/dashboard/siswa.php" class="px-6 py-4 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-center">
            Batal dan Kembali
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Full Image Preview Modal -->
<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center hidden z-50">
  <div class="max-w-4xl max-h-screen p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-2">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pratinjau Gambar</h3>
        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-100 p-2">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      <img id="modal-image" src="" alt="Full Preview" class="max-w-full max-h-96 lg:max-h-[70vh] object-contain mx-auto rounded-lg">
    </div>
  </div>
</div>

<script>
  // File upload handling
  document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('media');
    const filePreview = document.getElementById('file-preview');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const imagePreview = document.getElementById('image-preview');
    const previewThumb = document.getElementById('preview-thumb');

    // Click to open file dialog
    dropzone.addEventListener('click', function() {
      fileInput.click();
    });

    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      dropzone.addEventListener(eventName, preventDefaults, false);
      document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
      e.preventDefault();
      e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
      dropzone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropzone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
      dropzone.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
    }

    function unhighlight(e) {
      dropzone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
    }

    dropzone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
      const dt = e.dataTransfer;
      const files = dt.files;
      handleFiles(files);
    }

    fileInput.addEventListener('change', function() {
      handleFiles(fileInput.files);
    });

    function handleFiles(files) {
      if (files.length > 0) {
        const file = files[0];
        fileInput.files = files; // Update file input

        // Show file preview
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        filePreview.classList.remove('hidden');

        // Show image preview if it's an image
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function(e) {
            previewThumb.src = e.target.result;
            imagePreview.classList.remove('hidden');
          };
          reader.readAsDataURL(file);
        } else {
          imagePreview.classList.add('hidden');
        }

        // Update dropzone appearance
        dropzone.classList.remove('border-gray-300', 'dark:border-gray-600');
        dropzone.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
      }
    }

    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
  });

  function clearFile() {
    document.getElementById('media').value = '';
    document.getElementById('file-preview').classList.add('hidden');
    document.getElementById('image-preview').classList.add('hidden');
    document.getElementById('dropzone').classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
    document.getElementById('dropzone').classList.add('border-gray-300', 'dark:border-gray-600');
  }

  function showFullPreview() {
    const previewThumb = document.getElementById('preview-thumb');
    const modalImage = document.getElementById('modal-image');
    const modal = document.getElementById('image-modal');

    modalImage.src = previewThumb.src;
    modal.classList.remove('hidden');
  }

  function closeModal() {
    document.getElementById('image-modal').classList.add('hidden');
  }

  // Close modal when clicking outside
  document.getElementById('image-modal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeModal();
    }
  });
</script>

<?php require_once 'includes/footer.php'; ?>