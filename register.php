<?php
$page_title = 'Daftar Akun Baru - Galeri Karya Siswa';
require_once 'includes/header.php';

$error = '';
$success = ''; // Tidak digunakan di halaman ini, tapi didefinisikan

// Redirect jika sudah login
if (isLoggedIn()) {
  header('Location: /index.php'); // Atau ke dashboard jika relevan
  exit;
}

// Koneksi database hanya jika diperlukan
$conn = null;
function getRegDBConnection()
{
  global $conn;
  if ($conn === null) {
    $conn = getDBConnection(); // Asumsi fungsi ini ada di database.php
  }
  return $conn;
}

// Generate captcha
if (session_status() === PHP_SESSION_NONE) {
  session_start();
} // Pastikan session aktif
$captcha_question = generateCaptcha(); // Panggil fungsi generateCaptcha() dari functions.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = sanitize($_POST['username'] ?? '');
  $email = sanitize($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';
  $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
  $peran = sanitize($_POST['peran'] ?? '');
  $captcha_answer = $_POST['captcha_answer'] ?? '';
  $terms_accepted = isset($_POST['terms']); // Cek apakah checkbox dicentang

  // Validation
  if (empty($username) || empty($email) || empty($password) || empty($nama_lengkap) || empty($peran)) {
    $error = 'Semua kolom yang ditandai * harus diisi.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Format email tidak valid.';
  } elseif (strlen($username) < 4 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $error = 'Username minimal 4 karakter dan hanya boleh berisi huruf, angka, dan underscore (_).';
  } elseif (strlen($password) < 6) {
    $error = 'Password minimal 6 karakter.';
  } elseif ($password !== $confirm_password) {
    $error = 'Password dan konfirmasi password tidak cocok.';
  } elseif (!in_array($peran, ['siswa', 'guru'])) {
    $error = 'Peran yang dipilih tidak valid.';
  } elseif (!$terms_accepted) { // PERBAIKAN: Validasi checkbox S&K
    $error = 'Anda harus menyetujui syarat dan ketentuan.';
  } elseif (!verifyCaptcha($captcha_answer)) { // Cek Captcha
    $error = 'Jawaban verifikasi keamanan salah.';
  } else {
    // Lanjutkan jika validasi lolos
    $db = getRegDBConnection(); // Dapatkan koneksi
    if ($db) {
      // Check if username or email already exists
      $stmt_check = $db->prepare("SELECT id FROM Pengguna WHERE username = ? OR email = ?");
      if ($stmt_check) {
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
          $error = 'Username atau email sudah terdaftar. Silakan gunakan yang lain.';
        } else {
          // Hash password
          $hashed_password = password_hash($password, PASSWORD_DEFAULT);

          // Insert new user
          $stmt_insert = $db->prepare("INSERT INTO Pengguna (username, email, password, peran, nama_lengkap, status) VALUES (?, ?, ?, ?, ?, 'menunggu')");
          if ($stmt_insert) {
            $stmt_insert->bind_param("sssss", $username, $email, $hashed_password, $peran, $nama_lengkap);

            if ($stmt_insert->execute()) {
              // Berhasil mendaftar
              // Tidak perlu close connection di sini jika akan redirect
              header('Location: /login.php?registered=success');
              exit;
            } else {
              $error = 'Terjadi kesalahan saat menyimpan data pendaftaran. Silakan coba lagi.';
              error_log("Register insert failed: " . $stmt_insert->error); // Log error
            }
            $stmt_insert->close();
          } else {
            $error = 'Gagal mempersiapkan statement insert.';
            error_log("Register prepare insert failed: " . $db->error); // Log error
          }
        }
        $stmt_check->close();
      } else {
        $error = 'Gagal mempersiapkan statement check.';
        error_log("Register prepare check failed: " . $db->error); // Log error
      }
      // Jangan close connection di sini jika ada redirect di atas
      // if ($conn) $conn->close();
    } else {
      $error = 'Koneksi database gagal.';
      error_log("Register DB connection failed."); // Log error
    }
  }

  // Regenerate captcha jika gagal dan error bukan karena field kosong/S&K
  if ($error && $error !== 'Anda harus menyetujui syarat dan ketentuan.' && !str_starts_with($error, 'Semua kolom')) {
    $captcha_question = generateCaptcha(); // Buat pertanyaan baru
  }
}

// Tutup koneksi jika terbuka dan tidak ada redirect
if ($conn && !$success) {
  $conn->close();
}
?>
<!-- PERBAIKAN: CSS Tambahan untuk Checkbox -->
<style>
  .custom-checkbox-label {
    position: relative;
    padding-left: 28px;
    /* Ruang untuk checkbox kustom */
    cursor: pointer;
    user-select: none;
  }

  .custom-checkbox-label input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
  }

  .checkmark {
    position: absolute;
    top: 0;
    left: 0;
    height: 20px;
    width: 20px;
    background-color: #eee;
    /* Warna dasar checkbox */
    border: 1px solid #ccc;
    border-radius: 4px;
    transition: background-color 0.2s ease, border-color 0.2s ease;
  }

  .dark .checkmark {
    background-color: #4a5568;
    /* gray-700 */
    border-color: #718096;
    /* gray-600 */
  }

  .custom-checkbox-label input:checked~.checkmark {
    background-color: #3b82f6;
    /* Warna biru saat dicentang */
    border-color: #3b82f6;
  }

  .dark .custom-checkbox-label input:checked~.checkmark {
    background-color: #60a5fa;
    /* Warna biru lebih terang di dark mode */
    border-color: #60a5fa;
  }

  .checkmark:after {
    content: "";
    position: absolute;
    display: none;
    left: 6px;
    top: 2px;
    width: 6px;
    height: 11px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
  }

  .custom-checkbox-label input:checked~.checkmark:after {
    display: block;
  }

  /* Efek focus */
  .custom-checkbox-label input:focus~.checkmark {
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4);
    /* Ring biru */
  }

  /* Animasi Kelap-kelip */
  @keyframes blink {

    0%,
    100% {
      opacity: 1;
    }

    50% {
      opacity: 0.6;
    }
  }

  .blinking-info {
    animation: blink 1.5s linear infinite;
  }
</style>
<!-- Akhir Perbaikan CSS -->


<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900">
  <div class="max-w-2xl w-full space-y-8">
    <div class="text-center fade-in">
      <i class="fas fa-user-plus text-5xl text-blue-600 mb-4"></i>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Buat Akun Baru</h2>
      <p class="mt-2 text-gray-600 dark:text-gray-400">Isi formulir di bawah untuk mendaftar</p>
    </div>

    <?php if ($error): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
        <p class="font-bold">Pendaftaran Gagal</p>
        <p><?php echo $error; ?></p>
      </div>
    <?php endif; ?>

    <form class="mt-8 space-y-6 bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg" method="POST" novalidate>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
        <!-- Username -->
        <div>
          <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Username <span class="text-red-500">*</span>
          </label>
          <input id="username" name="username" type="text" required minlength="4" pattern="^[a-zA-Z0-9_]+$" title="Hanya huruf, angka, dan underscore"
            class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Min. 4 karakter (a-z, 0-9, _)" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>

        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Email <span class="text-red-500">*</span>
          </label>
          <input id="email" name="email" type="email" required
            class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="contoh@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <!-- Full Name -->
        <div class="md:col-span-2">
          <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Nama Lengkap <span class="text-red-500">*</span>
          </label>
          <input id="nama_lengkap" name="nama_lengkap" type="text" required
            class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Nama sesuai identitas" value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>">
        </div>

        <!-- Role -->
        <div class="md:col-span-2">
          <label for="peran" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Daftar Sebagai <span class="text-red-500">*</span>
          </label>
          <select id="peran" name="peran" required
            class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="" disabled <?php echo !isset($_POST['peran']) ? 'selected' : ''; ?>>-- Pilih Peran --</option>
            <option value="siswa" <?php echo (isset($_POST['peran']) && $_POST['peran'] === 'siswa') ? 'selected' : ''; ?>>Siswa (Mengunggah Karya)</option>
            <option value="guru" <?php echo (isset($_POST['peran']) && $_POST['peran'] === 'guru') ? 'selected' : ''; ?>>Guru/Kurator (Meninjau Karya)</option>
          </select>
        </div>

        <!-- Password -->
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Password <span class="text-red-500">*</span>
          </label>
          <input id="password" name="password" type="password" required minlength="6"
            class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Minimal 6 karakter">
        </div>

        <!-- Confirm Password -->
        <div>
          <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Konfirmasi Password <span class="text-red-500">*</span>
          </label>
          <input id="confirm_password" name="confirm_password" type="password" required minlength="6"
            class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Ulangi password">
        </div>

        <!-- Captcha -->
        <div class="md:col-span-2">
          <label for="captcha_answer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Verifikasi: <span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded"><?php echo $captcha_question; ?></span>
            <span class="text-red-500">*</span>
          </label>
          <input id="captcha_answer" name="captcha_answer" type="number" required
            class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Jawaban">
        </div>
      </div>

      <!-- PERBAIKAN: Checkbox Syarat & Ketentuan dengan Styling Kustom -->
      <div class="flex items-start pt-4">
        <label class="custom-checkbox-label text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center" id="termsLabel">
          <input id="terms" name="terms" type="checkbox" required>
          <span class="checkmark mr-2"></span>
          Saya telah membaca dan menyetujui
          <button type="button" id="openTermsModal" class="ml-1 font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 underline focus:outline-none">
            Syarat & Ketentuan
          </button>
          <span class="text-red-500 ml-1">*</span>
        </label>
      </div>
      <!-- Akhir Perbaikan Checkbox -->

      <div class="pt-4">
        <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors">
          <i class="fas fa-user-plus mr-2"></i>
          Daftar Sekarang
        </button>
      </div>

      <div class="text-center text-sm text-gray-600 dark:text-gray-400">
        Sudah punya akun?
        <a href="/login.php" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
          Masuk di sini
        </a>
      </div>
    </form>

    <!-- PERBAIKAN: Informasi Penting Ngejreng -->
    <div class="bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 text-yellow-800 dark:text-yellow-200 p-4 mt-8 rounded-r-lg shadow blinking-info">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-xl"></i>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-bold mb-1">Informasi Penting!</h3>
          <ul class="text-xs list-disc list-inside space-y-1">
            <li>Akun Anda memerlukan <strong>verifikasi administrator</strong> sebelum dapat digunakan.</li>
            <li>Proses verifikasi biasanya memakan waktu <strong>1-2 hari kerja</strong>.</li>
            <li>Anda akan menerima notifikasi jika akun sudah aktif.</li>
          </ul>
        </div>
      </div>
    </div>
    <!-- Akhir Perbaikan -->

  </div>
</div>

<!-- Modal Syarat dan Ketentuan (Kode Asli dari versi sebelumnya) -->
<div id="termsModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center hidden z-50 p-4 transition-opacity duration-300 ease-out opacity-0" aria-labelledby="modal-title" role="dialog" aria-modal="true">
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] flex flex-col transform transition-all duration-300 ease-out scale-95 opacity-0">
    <!-- Header Modal -->
    <div class="flex justify-between items-center p-4 border-b dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
      <h3 id="modal-title" class="text-lg font-bold text-gray-900 dark:text-white">Syarat dan Ketentuan Penggunaan</h3>
      <button type="button" id="closeTermsModal" aria-label="Tutup modal" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 rounded-full p-1 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <i class="fas fa-times text-xl"></i>
      </button>
    </div>

    <!-- Konten Modal (Scrollable) -->
    <div id="termsContent" class="p-6 space-y-4 overflow-y-auto text-gray-700 dark:text-gray-300 flex-grow">
      <h4 class="font-semibold text-gray-800 dark:text-gray-100">1. Penerimaan Ketentuan</h4>
      <p class="text-sm">Selamat datang di Galeri Karya Siswa ("Platform"). Dengan mendaftar, mengakses, atau menggunakan Platform ini, Anda ("Pengguna") setuju untuk mematuhi dan terikat oleh Syarat dan Ketentuan ("S&K") ini. Jika Anda tidak menyetujui S&K ini, Anda tidak diizinkan menggunakan Platform.</p>

      <h4 class="font-semibold text-gray-800 dark:text-gray-100">2. Pendaftaran dan Akun Pengguna</h4>
      <p class="text-sm">2.1. Anda harus berusia minimal 13 tahun atau memiliki izin dari orang tua/wali untuk mendaftar.</p>
      <p class="text-sm">2.2. Anda setuju untuk memberikan informasi yang akurat, terkini, dan lengkap saat pendaftaran.</p>
      <p class="text-sm">2.3. Anda bertanggung jawab penuh atas keamanan akun dan kata sandi Anda.</p>
      <p class="text-sm">2.4. Akun baru (Siswa dan Guru/Kurator) memerlukan verifikasi oleh Administrator sebelum dapat digunakan sepenuhnya.</p>

      <h4 class="font-semibold text-gray-800 dark:text-gray-100">3. Pengunggahan Karya (Untuk Siswa)</h4>
      <p class="text-sm">3.1. Siswa hanya boleh mengunggah karya yang merupakan hasil kreasi original mereka sendiri.</p>
      <p class="text-sm">3.2. Dilarang keras mengunggah karya yang:</p>
      <ul class="list-disc list-inside text-sm space-y-1 pl-4">
        <li>Melanggar hak cipta, merek dagang, paten, atau hak kekayaan intelektual pihak lain.</li>
        <li>Mengandung unsur pornografi, kekerasan eksplisit, perjudian, atau aktivitas ilegal lainnya.</li>
        <li>Mengandung ujaran kebencian, diskriminasi (SARA), perundungan, atau pencemaran nama baik.</li>
        <li>Mengandung informasi pribadi sensitif milik orang lain tanpa izin.</li>
        <li>Merupakan spam atau promosi komersial yang tidak relevan.</li>
      </ul>
      <p class="text-sm">3.3. Siswa memahami bahwa semua karya yang diunggah akan melalui proses peninjauan oleh Guru/Kurator sebelum dipublikasikan.</p>
      <p class="text-sm">3.4. Siswa dapat mengedit atau menghapus karya mereka selama statusnya masih 'Menunggu' atau 'Revisi'.</p>

      <h4 class="font-semibold text-gray-800 dark:text-gray-100">4. Peninjauan Karya (Untuk Guru/Kurator)</h4>
      <p class="text-sm">4.1. Guru/Kurator wajib meninjau karya yang diajukan secara objektif, adil, dan sesuai dengan pedoman yang mungkin ditetapkan.</p>
      <p class="text-sm">4.2. Umpan balik yang diberikan harus bersifat konstruktif dan bertujuan membantu pengembangan kreativitas siswa.</p>
      <p class="text-sm">4.3. Keputusan (Disetujui, Ditolak, Revisi) harus didasarkan pada kualitas, originalitas, dan kepatuhan karya terhadap S&K.</p>

      <h4 class="font-semibold text-gray-800 dark:text-gray-100">5. Hak Kekayaan Intelektual</h4>
      <p class="text-sm">5.1. Hak cipta atas karya yang diunggah tetap sepenuhnya menjadi milik Siswa sebagai kreator.</p>
      <p class="text-sm">5.2. Dengan mengunggah karya ke Platform, Siswa memberikan lisensi non-eksklusif, bebas royalti, berlaku di seluruh dunia kepada Platform untuk menggunakan, menampilkan, mereproduksi (dalam konteks Platform), mendistribusikan, dan mempromosikan karya tersebut semata-mata untuk keperluan operasional dan promosi Platform Galeri Karya Siswa.</p>
      <p class="text-sm">5.3. Platform tidak mengklaim kepemilikan atas karya yang diunggah.</p>

      <h4 class="font-semibold text-gray-800 dark:text-gray-100">6. Penggunaan oleh Publik</h4>
      <p class="text-sm">Pengunjung publik dapat melihat karya yang telah disetujui dan memberikan 'Like'. Pengumpulan alamat IP atau mekanisme identifikasi perangkat lainnya hanya digunakan untuk mencegah pemberian 'Like' berulang pada karya yang sama.</p>

      <h4 class="font-semibold text-gray-800 dark:text-gray-100">7. Pembatasan dan Penghentian Akun</h4>
      <p class="text-sm">Platform berhak, atas kebijaksanaannya sendiri, untuk menangguhkan, membatasi, atau menghentikan akses Pengguna ke Platform jika terjadi pelanggaran S&K ini, tanpa pemberitahuan sebelumnya.</p>

      <h4 class="font-semibold text-gray-800 dark:text-gray-100">8. Batasan Tanggung Jawab</h4>
      <p class="text-sm">Platform disediakan "sebagaimana adanya". Kami tidak menjamin bahwa Platform akan selalu bebas dari kesalahan atau gangguan. Kami tidak bertanggung jawab atas konten yang diunggah Pengguna atau tindakan Pengguna lain.</p>

      <h4 class="font-semibold text-gray-800 dark:text-gray-100">9. Perubahan Syarat dan Ketentuan</h4>
      <p class="text-sm">Kami dapat mengubah S&K ini dari waktu ke waktu. Perubahan akan diinformasikan melalui Platform. Dengan terus menggunakan Platform setelah perubahan, Anda dianggap menyetujui S&K yang baru.</p>

      <h4 class="font-semibold text-gray-800 dark:text-gray-100">10. Kontak</h4>
      <p class="text-sm">Jika ada pertanyaan mengenai S&K ini, silakan hubungi kami melalui [Alamat Email Kontak Anda].</p>

      <p class="font-semibold text-center mt-6 text-gray-500 dark:text-gray-400">--- Akhir Syarat dan Ketentuan ---</p>
    </div>

    <!-- Footer Modal -->
    <div class="p-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800 sticky bottom-0 z-10">
      <button type="button" id="acceptTerms" class="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-gray-400 dark:disabled:bg-gray-600 disabled:cursor-not-allowed text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800" disabled>
        Saya Mengerti dan Menyetujui Syarat & Ketentuan
      </button>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('termsModal');
    const modalContentWrapper = modal.querySelector(':scope > div'); // Wrapper utama modal
    const openButton = document.getElementById('openTermsModal');
    const closeButton = document.getElementById('closeTermsModal');
    const acceptButton = document.getElementById('acceptTerms');
    const termsContent = document.getElementById('termsContent');
    const termsCheckbox = document.getElementById('terms');
    const termsLabel = document.getElementById('termsLabel'); // Ambil labelnya

    // Fungsi untuk membuka modal
    function openModal() {
      modal.classList.remove('hidden');
      // Force reflow
      void modal.offsetWidth;
      modal.classList.add('opacity-100');
      modalContentWrapper.classList.add('scale-100', 'opacity-100');
      modalContentWrapper.classList.remove('scale-95', 'opacity-0');
      // Reset state tombol 'Setuju'
      acceptButton.disabled = true;
      acceptButton.classList.add('disabled:bg-gray-400', 'dark:disabled:bg-gray-600', 'disabled:cursor-not-allowed');
      termsContent.scrollTop = 0; // Scroll ke atas
    }

    // Fungsi untuk menutup modal
    function closeModal() {
      modal.classList.remove('opacity-100');
      modalContentWrapper.classList.remove('scale-100', 'opacity-100');
      modalContentWrapper.classList.add('scale-95', 'opacity-0');
      setTimeout(() => {
        modal.classList.add('hidden');
      }, 300); // Sesuaikan durasi transisi
    }

    // PERBAIKAN: Buka modal saat KLIK PADA LABEL (termasuk checkbox)
    termsLabel.addEventListener('click', function(e) {
      // Hanya buka modal jika target BUKAN link di dalam label
      if (!e.target.matches('#openTermsModal')) {
        e.preventDefault(); // Mencegah checkbox tercentang langsung
        openModal();
      }
      // Jika yang diklik adalah link S&K, biarkan event default (atau buka modal juga)
      if (e.target.matches('#openTermsModal')) {
        e.preventDefault(); // Mencegah link default jika mau buka modal saja
        openModal();
      }
    });
    // Akhir Perbaikan

    closeButton.addEventListener('click', closeModal);

    // Tutup jika klik backdrop
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        closeModal();
      }
    });

    // Tutup dengan tombol Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
        closeModal();
      }
    });

    // Cek scroll untuk mengaktifkan tombol 'Setuju'
    termsContent.addEventListener('scroll', function() {
      const isScrolledToBottom = termsContent.scrollHeight - termsContent.scrollTop <= termsContent.clientHeight + 20;
      if (isScrolledToBottom && acceptButton.disabled) { // Hanya aktifkan jika sebelumnya disable
        acceptButton.disabled = false;
        acceptButton.classList.remove('disabled:bg-gray-400', 'dark:disabled:bg-gray-600', 'disabled:cursor-not-allowed');
      } else if (!isScrolledToBottom && !acceptButton.disabled) {
        // Optional: Disable lagi jika scroll ke atas (sesuai permintaan awal)
        // acceptButton.disabled = true;
        // acceptButton.classList.add('disabled:bg-gray-400', 'dark:disabled:bg-gray-600', 'disabled:cursor-not-allowed');
      }
    });

    // Tombol Setuju: Centang checkbox dan tutup modal
    acceptButton.addEventListener('click', function() {
      termsCheckbox.checked = true;
      // Trigger event 'change' agar validasi form (jika ada) terpicu
      termsCheckbox.dispatchEvent(new Event('change'));
      closeModal();
    });
  });
</script>
<!-- Akhir Perbaikan -->


<?php require_once 'includes/footer.php'; ?>