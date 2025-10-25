<?php
$page_title = 'Reset Password - Galeri Karya Siswa';
require_once 'includes/header.php'; // Pastikan path ini benar

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$error = '';
$success = '';
$token = sanitize($_GET['token'] ?? ''); // Token dari link email
$otp_required = isset($_GET['otp_required']); // Flag jika datang dari lupa-password.php
$otp_post = sanitize($_POST['otp'] ?? ''); // OTP dari form POST
$show_form = false; // Kontrol tampilan form
$email = $_SESSION['reset_email'] ?? ''; // Ambil email dari session jika ada (setelah dari lupa-password)
$form_stage = 'input_otp'; // Tahap form: 'input_otp' atau 'input_password'

// Ambil pesan sukses sementara jika ada (dari simulasi email)
$temp_success = $_SESSION['temp_success_message'] ?? '';
unset($_SESSION['temp_success_message']); // Hapus setelah dibaca

$conn = getDBConnection(); // Dapatkan koneksi

if ($conn) {
  // --- Logika Validasi Token atau OTP ---

  // Prioritas 1: Validasi OTP yang di-POST dari form OTP
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($otp_post)) {
    $stmt_otp = $conn->prepare("SELECT email FROM Password_Resets WHERE otp = ? AND expires > NOW() AND email = ?");
    if ($stmt_otp) {
      $stmt_otp->bind_param("ss", $otp_post, $email); // Gunakan email dari session
      $stmt_otp->execute();
      $result_otp = $stmt_otp->get_result();

      if ($result_otp->num_rows === 1) {
        $show_form = true;
        $form_stage = 'input_password'; // Lanjut ke input password
        // Email sudah ada dari session
      } else {
        $error = 'Kode OTP salah atau telah kedaluwarsa.';
        $form_stage = 'input_otp'; // Tetap di input OTP
        $show_form = true; // Tampilkan lagi form OTP
      }
      $stmt_otp->close();
    } else {
      $error = 'Gagal memvalidasi OTP.';
      error_log("Reset PW - Prepare OTP check failed: " . $conn->error);
    }
  }
  // Prioritas 2: Validasi Token dari link (GET request)
  elseif (!empty($token)) {
    $stmt_token = $conn->prepare("SELECT email FROM Password_Resets WHERE token = ? AND expires > NOW()");
    if ($stmt_token) {
      $stmt_token->bind_param("s", $token);
      $stmt_token->execute();
      $result_token = $stmt_token->get_result();

      if ($result_token->num_rows === 1) {
        $show_form = true;
        $form_stage = 'input_password'; // Lanjut ke input password
        $row = $result_token->fetch_assoc();
        $email = $row['email']; // Ambil email dari token
        $_SESSION['reset_email'] = $email; // Simpan email ke session untuk proses POST password
      } else {
        $error = 'Token reset password tidak valid atau telah kedaluwarsa.';
        $form_stage = 'invalid'; // Tandai sebagai tidak valid
        $show_form = false;
      }
      $stmt_token->close();
    } else {
      $error = 'Gagal memvalidasi token.';
      error_log("Reset PW - Prepare token check failed: " . $conn->error);
      $show_form = false;
    }
  }
  // Prioritas 3: Tampilkan form OTP jika diminta dari lupa-password.php
  elseif ($otp_required && !empty($email)) {
    // Cek apakah memang ada request reset aktif untuk email ini
    $stmt_check_active = $conn->prepare("SELECT id FROM Password_Resets WHERE email = ? AND expires > NOW()");
    if ($stmt_check_active) {
      $stmt_check_active->bind_param("s", $email);
      $stmt_check_active->execute();
      if ($stmt_check_active->get_result()->num_rows > 0) {
        $show_form = true;
        $form_stage = 'input_otp';
      } else {
        $error = "Tidak ada permintaan reset password aktif untuk email ini atau sudah kedaluwarsa.";
        $show_form = false;
      }
      $stmt_check_active->close();
    } else {
      $error = "Gagal memeriksa status reset.";
      $show_form = false;
    }
  }
  // Jika tidak ada token, tidak ada OTP POST, dan tidak diminta OTP -> Akses tidak valid
  else {
    if (!$success && empty($temp_success)) { // Jangan timpa pesan sukses
      $error = 'Akses tidak valid. Silakan minta reset password kembali.';
      $show_form = false;
    }
  }

  // --- Logika Update Password ---
  if ($form_stage === 'input_password' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email_hidden = $_POST['email'] ?? ''; // Ambil email dari hidden input

    // Pastikan email ada sebelum melanjutkan
    if (empty($email_hidden)) {
      $error = "Sesi reset tidak valid. Silakan ulangi proses lupa password.";
      $show_form = false; // Sembunyikan form
    } elseif (empty($password) || strlen($password) < 6) {
      $error = 'Password baru minimal 6 karakter.';
      $show_form = true; // Tetap tampilkan form password
      $form_stage = 'input_password'; // Pastikan stage tetap
      $email = $email_hidden; // Pertahankan email
    } elseif ($password !== $confirm_password) {
      $error = 'Password baru dan konfirmasi password tidak cocok.';
      $show_form = true; // Tetap tampilkan form password
      $form_stage = 'input_password'; // Pastikan stage tetap
      $email = $email_hidden; // Pertahankan email
    } else {
      // Semua validasi lolos, update password
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $update_stmt = $conn->prepare("UPDATE Pengguna SET password = ? WHERE email = ?");
      if ($update_stmt) {
        $update_stmt->bind_param("ss", $hashed_password, $email_hidden);

        if ($update_stmt->execute()) {
          // Hapus data reset dari database setelah berhasil
          $delete_stmt = $conn->prepare("DELETE FROM Password_Resets WHERE email = ?");
          if ($delete_stmt) {
            $delete_stmt->bind_param("s", $email_hidden);
            $delete_stmt->execute();
            $delete_stmt->close();
          } else {
            error_log("Gagal prepare delete reset setelah update: " . $conn->error);
          }

          // Hapus email dari session
          unset($_SESSION['reset_email']);

          $success = 'Password Anda telah berhasil diperbarui!';
          // Arahkan ke login dengan pesan sukses
          header("Location: /login.php?reset=success");
          exit;
        } else {
          $error = 'Gagal memperbarui password di database. Coba lagi nanti.';
          error_log("Gagal execute update password: " . $update_stmt->error);
          $show_form = true; // Tampilkan lagi form password
          $form_stage = 'input_password';
          $email = $email_hidden;
        }
        $update_stmt->close();
      } else {
        $error = 'Gagal mempersiapkan update password.';
        error_log("Gagal prepare update password: " . $conn->error);
        $show_form = true; // Tampilkan lagi form password
        $form_stage = 'input_password';
        $email = $email_hidden;
      }
    }
  }

  $conn->close(); // Tutup koneksi di akhir
} else {
  $error = 'Koneksi database gagal.';
  error_log("Reset Password DB connection failed.");
}
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900">
  <div class="max-w-md w-full space-y-8">
    <div class="text-center fade-in">
      <i class="fas fa-key text-5xl text-blue-600 mb-4"></i>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Reset Password</h2>
    </div>

    <!-- Tampilkan pesan sukses sementara (dari simulasi email) -->
    <?php if ($temp_success): ?>
      <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded" role="alert">
        <p class="font-bold">Informasi</p>
        <p><?php echo $temp_success; ?></p>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
        <p class="font-bold">Error</p>
        <p><?php echo $error; ?></p>
        <?php if ($form_stage === 'invalid' || $form_stage === 'input_otp'): ?>
          <p class="mt-2 text-sm">
            <a href="/lupa-password.php" class="font-medium text-blue-600 hover:text-blue-500">Minta reset password lagi?</a>
          </p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
        <p class="font-bold">Sukses!</p>
        <p><?php echo $success; // Pesan sukses setelah update 
            ?></p>
      </div>
    <?php endif; ?>

    <?php if ($show_form): ?>
      <form class="mt-8 space-y-6 bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg" method="POST" novalidate>

        <!-- Hidden input untuk email, diperlukan saat POST password -->
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <!-- Hidden input untuk token (jika ada), bisa digunakan untuk validasi ulang jika perlu -->
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <!-- Hidden input untuk OTP (jika ada), bisa digunakan untuk validasi ulang jika perlu -->
        <input type="hidden" name="otp_val" value="<?php echo htmlspecialchars($otp_post); ?>">


        <?php if ($form_stage === 'input_otp'): ?>
          <!-- === TAHAP 1: FORM INPUT OTP === -->
          <p class="text-gray-600 dark:text-gray-400 text-sm">
            Masukkan 6 digit kode OTP yang telah dikirim ke email <strong><?php echo htmlspecialchars($email); ?></strong>.
          </p>
          <div>
            <label for="otp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Kode OTP <span class="text-red-500">*</span>
            </label>
            <input id="otp" name="otp" type="number" inputmode="numeric" pattern="[0-9]*" maxlength="6" required
              class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm tracking-widest text-center"
              placeholder="------">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kode berlaku selama 30 menit.</p>
          </div>
          <div>
            <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors">
              <i class="fas fa-check-circle mr-2"></i>
              Verifikasi Kode OTP
            </button>
          </div>
          <div class="text-center text-sm">
            <a href="/lupa-password.php" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
              Kirim ulang kode?
            </a>
          </div>

        <?php elseif ($form_stage === 'input_password'): ?>
          <!-- === TAHAP 2: FORM INPUT PASSWORD BARU === -->
          <p class="text-gray-600 dark:text-gray-400 text-sm">
            Masukkan password baru untuk akun <strong><?php echo htmlspecialchars($email); ?></strong>.
          </p>
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Password Baru <span class="text-red-500">*</span>
            </label>
            <input id="password" name="password" type="password" required minlength="6"
              class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Minimal 6 karakter">
          </div>

          <div>
            <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Konfirmasi Password Baru <span class="text-red-500">*</span>
            </label>
            <input id="confirm_password" name="confirm_password" type="password" required minlength="6"
              class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Ulangi password baru">
          </div>

          <div>
            <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors">
              <i class="fas fa-save mr-2"></i>
              Simpan Password Baru
            </button>
          </div>
        <?php endif; ?>
      </form>
    <?php endif; // End if ($show_form) 
    ?>

    <?php if (!$show_form && !$success): // Tampilkan link kembali jika form tidak tampil & belum sukses 
    ?>
      <div class="text-center mt-6">
        <a href="/login.php" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
          Kembali ke Login
        </a>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once 'includes/footer.php'; // Pastikan path ini benar 
?>