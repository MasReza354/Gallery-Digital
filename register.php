<?php
$page_title = 'Daftar - Galeri Karya Siswa';
require_once 'includes/header.php';

$error = '';
$success = '';

// Redirect if already logged in
if (isLoggedIn()) {
  header('Location: /index.php');
  exit;
}

// Get categories for display
$conn = getDBConnection();
$categories = $conn->query("SELECT * FROM Kategori ORDER BY nama");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = sanitize($_POST['username'] ?? '');
  $email = sanitize($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';
  $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
  $peran = sanitize($_POST['peran'] ?? '');
  $captcha_answer = $_POST['captcha_answer'] ?? '';

  // Validation
  if (empty($username) || empty($email) || empty($password) || empty($nama_lengkap) || empty($peran)) {
    $error = 'Semua field harus diisi';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Format email tidak valid';
  } elseif (strlen($username) < 4) {
    $error = 'Username minimal 4 karakter';
  } elseif (strlen($password) < 6) {
    $error = 'Password minimal 6 karakter';
  } elseif ($password !== $confirm_password) {
    $error = 'Password dan konfirmasi password tidak cocok';
  } elseif (!in_array($peran, ['siswa', 'guru'])) {
    $error = 'Peran tidak valid';
  } elseif (!verifyCaptcha($captcha_answer)) {
    $error = 'Jawaban captcha salah';
  } else {
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM Pengguna WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $error = 'Username atau email sudah terdaftar';
      $stmt->close();
    } else {
      $stmt->close();

      // Hash password
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);

      // Insert new user
      $stmt = $conn->prepare("INSERT INTO Pengguna (username, email, password, peran, nama_lengkap, status) VALUES (?, ?, ?, ?, ?, 'menunggu')");
      $stmt->bind_param("sssss", $username, $email, $hashed_password, $peran, $nama_lengkap);

      if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header('Location: /login.php?registered=success');
        exit;
      } else {
        $error = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
        $stmt->close();
      }
    }
  }
}

// Generate captcha
$captcha_question = generateCaptcha();

$conn->close();
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-2xl w-full space-y-8">
    <div class="text-center fade-in">
      <i class="fas fa-user-plus text-6xl text-blue-600 mb-4"></i>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Daftar Akun Baru</h2>
      <p class="mt-2 text-gray-600 dark:text-gray-400">Bergabunglah dengan komunitas kreatif kami</p>
    </div>

    <?php if ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
      </div>
    <?php endif; ?>

    <form class="mt-8 space-y-6 bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg" method="POST">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Username -->
        <div>
          <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Username <span class="text-red-500">*</span>
          </label>
          <input id="username" name="username" type="text" required minlength="4" class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Minimal 4 karakter" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>

        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Email <span class="text-red-500">*</span>
          </label>
          <input id="email" name="email" type="email" required class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="contoh@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <!-- Full Name -->
        <div class="md:col-span-2">
          <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Nama Lengkap <span class="text-red-500">*</span>
          </label>
          <input id="nama_lengkap" name="nama_lengkap" type="text" required class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nama lengkap Anda" value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>">
        </div>

        <!-- Role -->
        <div class="md:col-span-2">
          <label for="peran" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Daftar Sebagai <span class="text-red-500">*</span>
          </label>
          <select id="peran" name="peran" required class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">Pilih peran...</option>
            <option value="siswa" <?php echo (isset($_POST['peran']) && $_POST['peran'] === 'siswa') ? 'selected' : ''; ?>>Siswa</option>
            <option value="guru" <?php echo (isset($_POST['peran']) && $_POST['peran'] === 'guru') ? 'selected' : ''; ?>>Guru/Kurator</option>
          </select>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Siswa dapat mengunggah karya, Guru dapat meninjau dan memberikan feedback
          </p>
        </div>

        <!-- Password -->
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Password <span class="text-red-500">*</span>
          </label>
          <input id="password" name="password" type="password" required minlength="6" class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Minimal 6 karakter">
        </div>

        <!-- Confirm Password -->
        <div>
          <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Konfirmasi Password <span class="text-red-500">*</span>
          </label>
          <input id="confirm_password" name="confirm_password" type="password" required minlength="6" class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ulangi password">
        </div>

        <!-- Captcha -->
        <div class="md:col-span-2">
          <label for="captcha_answer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Verifikasi Keamanan <span class="text-red-500">*</span>
          </label>
          <div class="flex items-center space-x-4">
            <div class="flex-1">
              <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 rounded-lg text-center font-mono text-lg">
                <?php echo $captcha_question; ?>
              </div>
            </div>
            <div class="w-32">
              <input id="captcha_answer" name="captcha_answer" type="number" required class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Jawaban">
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center">
        <input id="terms" name="terms" type="checkbox" required class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
        <label for="terms" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
          Saya menyetujui <a href="#" class="text-blue-600 hover:text-blue-500">syarat dan ketentuan</a> yang berlaku
        </label>
      </div>

      <div>
        <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
          <i class="fas fa-user-plus mr-2"></i>
          Daftar Sekarang
        </button>
      </div>

      <div class="text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Sudah punya akun?
          <a href="/login.php" class="font-medium text-blue-600 hover:text-blue-500">
            Masuk di sini
          </a>
        </p>
      </div>
    </form>

    <div class="bg-blue-50 dark:bg-gray-800 border border-blue-200 dark:border-blue-900 rounded-lg p-4">
      <h3 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">
        <i class="fas fa-info-circle mr-2"></i>Informasi Penting
      </h3>
      <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1">
        <li>• Akun Anda akan diverifikasi oleh administrator sebelum dapat digunakan</li>
        <li>• Proses verifikasi biasanya memakan waktu 1-2 hari kerja</li>
        <li>• Anda akan menerima notifikasi email setelah akun diverifikasi</li>
      </ul>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>