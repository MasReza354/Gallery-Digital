<?php
$page_title = 'Lupa Password - Galeri Karya Siswa';
require_once 'includes/header.php'; // Pastikan path ini benar

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$error = '';
$success = '';

// Jika user sudah login, arahkan ke dashboard
if (isLoggedIn()) {
  $user = getCurrentUser();
  if ($user) {
    header('Location: /dashboard/' . $user['peran'] . '.php');
    exit;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL); // Sanitasi email

  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Masukkan alamat email yang valid.';
  } else {
    $conn = getDBConnection(); // Dapatkan koneksi
    if ($conn) {
      // Cek apakah email terdaftar dan akun aktif
      $stmt = $conn->prepare("SELECT id, nama_lengkap FROM Pengguna WHERE email = ? AND status = 'aktif'");
      if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
          $user = $result->fetch_assoc();

          // Buat OTP (6 digit angka acak)
          $otp = rand(100000, 999999);
          // Buat Token (string acak yang lebih aman untuk link)
          $token = bin2hex(random_bytes(32));
          $expires = date('Y-m-d H:i:s', time() + 1800); // OTP/Token berlaku 30 menit

          // Hapus data reset lama untuk email ini
          $delete_stmt = $conn->prepare("DELETE FROM Password_Resets WHERE email = ?");
          if ($delete_stmt) {
            $delete_stmt->bind_param("s", $email);
            $delete_stmt->execute();
            $delete_stmt->close();
          } else {
            error_log("Gagal prepare delete reset: " . $conn->error);
          }


          // Simpan OTP dan Token baru ke database
          $insert_stmt = $conn->prepare("INSERT INTO Password_Resets (email, token, otp, expires) VALUES (?, ?, ?, ?)");
          if ($insert_stmt) {
            $insert_stmt->bind_param("ssss", $email, $token, $otp, $expires);

            if ($insert_stmt->execute()) {
              // --- Pengiriman Email ---
              // Ganti bagian ini dengan implementasi pengiriman email sesungguhnya
              // menggunakan library seperti PHPMailer atau layanan email API.

              $subject = "Reset Password Akun Galeri Karya Siswa Anda";
              $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
              $message = "Halo " . htmlspecialchars($user['nama_lengkap']) . ",\n\n";
              $message .= "Anda menerima email ini karena ada permintaan reset password untuk akun Anda.\n\n";
              $message .= "Kode OTP Anda adalah: " . $otp . "\n";
              $message .= "Kode ini berlaku selama 30 menit.\n\n";
              $message .= "Atau, Anda bisa klik link berikut untuk mereset password:\n";
              $message .= $reset_link . "\n\n";
              $message .= "Jika Anda tidak meminta reset password, abaikan email ini.\n\n";
              $message .= "Terima kasih,\nTim Galeri Karya Siswa";
              $headers = 'From: noreply@galerikarya.com' . "\r\n" . // Ganti dengan email Anda
                'Reply-To: noreply@galerikarya.com' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

              // Coba kirim email
              // if (mail($email, $subject, $message, $headers)) {
              //     // Jika berhasil kirim, arahkan ke input OTP
              //     $_SESSION['reset_email'] = $email; // Simpan email untuk halaman berikutnya
              //     header("Location: /reset-password.php?otp_required=1");
              //     exit;
              // } else {
              //     $error = 'Gagal mengirim email reset password. Coba lagi nanti.';
              //     error_log("Gagal mengirim email reset ke: " . $email);
              // }

              // --- Simulasi Pengiriman Email (Untuk Development) ---
              $_SESSION['reset_email'] = $email; // Simpan email untuk halaman berikutnya
              // Set pesan sukses yang menyertakan OTP untuk dilihat developer
              $_SESSION['temp_success_message'] = "Instruksi reset password (termasuk OTP: $otp) telah dikirim ke email Anda. Silakan cek inbox (atau spam).";
              header("Location: /reset-password.php?otp_required=1");
              exit;
              // --- Akhir Simulasi ---

            } else {
              $error = 'Gagal menyimpan data reset password. Coba lagi nanti.';
              error_log("Gagal insert reset data: " . $insert_stmt->error);
            }
            $insert_stmt->close();
          } else {
            $error = 'Gagal mempersiapkan penyimpanan data reset.';
            error_log("Gagal prepare insert reset: " . $conn->error);
          }
        } else {
          // Email tidak ditemukan atau akun tidak aktif
          // Tampilkan pesan generik untuk keamanan
          $error = 'Jika email Anda terdaftar dan aktif, instruksi reset password akan dikirim.';
          // Sebenarnya tidak ada email yang dikirim, tapi ini mencegah orang menebak email valid.
        }
        $stmt->close();
      } else {
        $error = 'Gagal mempersiapkan query database.';
        error_log("Gagal prepare select user: " . $conn->error);
      }
      $conn->close();
    } else {
      $error = 'Koneksi database gagal.';
      error_log("Lupa Password DB connection failed.");
    }
  }
}
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900">
  <div class="max-w-md w-full space-y-8">
    <div class="text-center fade-in">
      <i class="fas fa-key text-5xl text-blue-600 mb-4"></i>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Lupa Password Anda?</h2>
      <p class="mt-2 text-gray-600 dark:text-gray-400">Jangan khawatir. Masukkan email Anda di bawah ini.</p>
    </div>

    <?php if ($error): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
        <p><?php echo $error; ?></p>
      </div>
    <?php endif; ?>
    <?php if ($success): // Pesan sukses ini mungkin tidak akan terlihat karena redirect 
    ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
        <p><?php echo $success; ?></p>
      </div>
    <?php endif; ?>

    <form class="mt-8 space-y-6 bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg" method="POST" novalidate>
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Alamat Email Terdaftar <span class="text-red-500">*</span>
        </label>
        <input id="email" name="email" type="email" autocomplete="email" required
          class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          placeholder="contoh@email.com">
      </div>

      <div>
        <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors">
          <i class="fas fa-paper-plane mr-2"></i>
          Kirim Instruksi Reset
        </button>
      </div>

      <div class="text-center text-sm">
        <a href="/login.php" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
          Kembali ke Halaman Login
        </a>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; // Pastikan path ini benar 
?>