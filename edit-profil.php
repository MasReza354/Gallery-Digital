<?php
$page_title = 'Edit Profil - Galeri Karya Siswa';
require_once 'includes/header.php';
requireLogin();
if (!hasRole('siswa')) {
  header('Location: /dashboard/siswa.php');
  exit;
}

$current_user = getCurrentUser();
$conn = getDBConnection();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
  $current_password = $_POST['current_password'] ?? '';
  $new_password = $_POST['new_password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  // Validate name
  if (empty($nama_lengkap)) {
    $errors[] = 'Nama lengkap harus diisi';
  } elseif (strlen($nama_lengkap) < 2) {
    $errors[] = 'Nama lengkap minimal 2 karakter';
  }

  // Password change validation
  if (!empty($new_password)) {
    if (empty($current_password)) {
      $errors[] = 'Kata sandi saat ini harus diisi untuk mengubah kata sandi';
    } elseif (!password_verify($current_password, $current_user['password'])) {
      $errors[] = 'Kata sandi saat ini salah';
    } elseif (strlen($new_password) < 6) {
      $errors[] = 'Kata sandi baru minimal 6 karakter';
    } elseif ($new_password !== $confirm_password) {
      $errors[] = 'Konfirmasi kata sandi tidak cocok';
    }
  }

  // Profile picture upload
  $foto_profil = $current_user['foto_profil'];
  if (!empty($_FILES['foto_profil']['name'])) {
    $upload_result = uploadFile($_FILES['foto_profil'], 'uploads/profil', ['jpg', 'jpeg', 'png', 'gif']);
    if ($upload_result['success']) {
      // Delete old profile picture if not default
      if ($foto_profil !== 'default_avatar.png' && file_exists('uploads/profil/' . $foto_profil)) {
        unlink('uploads/profil/' . $foto_profil);
      }
      $foto_profil = $upload_result['filename'];
    } else {
      $errors[] = 'Error mengunggah foto profil: ' . $upload_result['message'];
    }
  }

  if (empty($errors)) {
    // Update user data
    if (!empty($new_password)) {
      $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE Pengguna SET nama_lengkap = ?, password = ?, foto_profil = ?, diperbarui_pada = CURRENT_TIMESTAMP WHERE id = ?");
      $stmt->bind_param("sssi", $nama_lengkap, $hashed_password, $foto_profil, $current_user['id']);
    } else {
      $stmt = $conn->prepare("UPDATE Pengguna SET nama_lengkap = ?, foto_profil = ?, diperbarui_pada = CURRENT_TIMESTAMP WHERE id = ?");
      $stmt->bind_param("ssi", $nama_lengkap, $foto_profil, $current_user['id']);
    }

    if ($stmt->execute()) {
      $success = true;
      // Update session data
      $_SESSION['user_nama'] = $nama_lengkap;
      $current_user['nama_lengkap'] = $nama_lengkap;
      $current_user['foto_profil'] = $foto_profil;
    } else {
      $errors[] = 'Terjadi kesalahan saat menyimpan data';
    }
    $stmt->close();
  }
}

$conn->close();
?>

<div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-4xl mx-auto">

    <!-- Header -->
    <div class="mb-8">
      <a href="/dashboard/siswa.php" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 mb-4">
        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard
      </a>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Profil</h1>
      <p class="text-gray-600 dark:text-gray-400 mt-2">Kelola informasi pribadi Anda</p>
    </div>

    <?php if ($success): ?>
      <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-6 mb-8">
        <div class="flex items-center">
          <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl mr-3"></i>
          <div>
            <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Profil Berhasil Diperbarui!</h3>
            <p class="text-green-700 dark:text-green-300 mt-1">Perubahan Anda telah disimpan.</p>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Profile Form -->
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
          <!-- Profile Picture Section -->
          <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Foto Profil</h2>
            <div class="flex items-center space-x-6">
              <div class="flex-shrink-0">
                <img id="preview-image" src="/uploads/profil/<?php echo htmlspecialchars($current_user['foto_profil']); ?>" alt="Profile Preview" class="w-24 h-24 rounded-full object-cover border-4 border-gray-200 dark:border-gray-600" onerror="this.src='/assets/default_avatar.png'">
              </div>
              <div class="flex-1">
                <label for="foto_profil" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ubah Foto Profil</label>
                <input type="file" id="foto_profil" name="foto_profil" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-gray-300">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Format: JPG, PNG, GIF. Ukuran maksimal: 2MB</p>
              </div>
            </div>
          </div>

          <!-- Personal Information -->
          <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Informasi Pribadi</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" required minlength="2" maxlength="255"
                  value="<?php echo htmlspecialchars($current_user['nama_lengkap']); ?>"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
              </div>

              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Email
                </label>
                <input type="email" readonly
                  value="<?php echo htmlspecialchars($current_user['email']); ?>"
                  class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Email tidak dapat diubah</p>
              </div>

              <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Username
                </label>
                <input type="text" readonly
                  value="<?php echo htmlspecialchars($current_user['username']); ?>"
                  class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Username tidak dapat diubah</p>
              </div>
            </div>
          </div>

          <!-- Password Change -->
          <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Ubah Kata Sandi</h2>
            <div class="space-y-4">
              <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Kata Sandi Saat Ini
                </label>
                <input type="password" id="current_password" name="current_password"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Diperlukan hanya jika Anda ingin mengubah kata sandi</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Kata Sandi Baru
                  </label>
                  <input type="password" id="new_password" name="new_password" minlength="6"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
                </div>

                <div>
                  <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Konfirmasi Kata Sandi Baru
                  </label>
                  <input type="password" id="confirm_password" name="confirm_password" minlength="6"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
                </div>
              </div>

              <div class="text-sm text-gray-500 dark:text-gray-400">
                <p>• Kata sandi minimal 6 karakter</p>
                <p>• Kosongkan field kata sandi jika tidak ingin mengubah</p>
              </div>
            </div>
          </div>

          <!-- Submit Buttons -->
          <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t dark:border-gray-600">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
              <i class="fas fa-save mr-2"></i>Simpan Perubahan
            </button>
            <a href="/profil.php?id=<?php echo $current_user['id']; ?>" class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-center">
              Batal
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  // Profile picture preview
  document.getElementById('foto_profil').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('preview-image').src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });
</script>

<?php require_once 'includes/footer.php'; ?>