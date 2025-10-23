<?php
$page_title = 'Masuk - Galeri Karya Siswa';
require_once 'includes/header.php';

$error = '';
$success = '';

// Redirect if already logged in
if (isLoggedIn()) {
  $user = getCurrentUser();
  if ($user['peran'] === 'siswa') {
    header('Location: /dashboard/siswa.php');
  } elseif ($user['peran'] === 'guru') {
    header('Location: /dashboard/guru.php');
  } elseif ($user['peran'] === 'administrator') {
    header('Location: /dashboard/admin.php');
  }
  exit;
}

// Captcha disabled for better user experience

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = sanitize($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($username) || empty($password)) {
    $error = 'Username dan password harus diisi';
  } else {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, password, peran, status FROM Pengguna WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();

      if ($user['status'] === 'menunggu') {
        $error = 'Akun Anda masih menunggu verifikasi dari administrator';
      } elseif ($user['status'] === 'tidak aktif') {
        $error = 'Akun Anda telah dinonaktifkan';
      } elseif (password_verify($password, $user['password'])) {
        loginUser($user['id']);

        // Redirect to intended page or dashboard
        if (isset($_SESSION['redirect_after_login'])) {
          $redirect = $_SESSION['redirect_after_login'];
          unset($_SESSION['redirect_after_login']);
          header('Location: ' . $redirect);
        } else {
          if ($user['peran'] === 'siswa') {
            header('Location: /dashboard/siswa.php');
          } elseif ($user['peran'] === 'guru') {
            header('Location: /dashboard/guru.php');
          } elseif ($user['peran'] === 'administrator') {
            header('Location: /dashboard/admin.php');
          }
        }
        exit;
      } else {
        $error = 'Username atau password salah';
      }
    } else {
      $error = 'Username atau password salah';
    }

    $stmt->close();
    $conn->close();
  }
}
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-md w-full space-y-8">
    <div class="text-center fade-in">
      <i class="fas fa-palette text-6xl text-blue-600 mb-4"></i>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Selamat Datang</h2>
      <p class="mt-2 text-gray-600 dark:text-gray-400">Masuk ke akun Anda</p>
    </div>

    <?php if ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['registered']) && $_GET['registered'] === 'success'): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">Registrasi berhasil! Silakan tunggu verifikasi dari administrator.</span>
      </div>
    <?php endif; ?>

    <form class="mt-8 space-y-6 bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg" method="POST">
      <div class="space-y-4">
        <div>
          <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Username atau Email
          </label>
          <input id="username" name="username" type="text" required class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Masukkan username atau email">
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Password
          </label>
          <input id="password" name="password" type="password" required class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Masukkan password">
        </div>


      </div>

      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
          <label for="remember" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
            Ingat saya
          </label>
        </div>

        <div class="text-sm">
          <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
            Lupa password?
          </a>
        </div>
      </div>

      <div>
        <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
          <i class="fas fa-sign-in-alt mr-2"></i>
          Masuk
        </button>
      </div>

      <div class="text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Belum punya akun?
          <a href="/register.php" class="font-medium text-blue-600 hover:text-blue-500">
            Daftar sekarang
          </a>
        </p>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>