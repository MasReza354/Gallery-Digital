<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$current_user = getCurrentUser();
$page_title = isset($page_title) ? $page_title : 'Galeri Karya Siswa';

// --- PERBAIKAN: Path logo dinamis ---
// TODO: Ganti path ini jika logo Anda ada di tempat lain
$logo_path = "/assets/images/logo.png";
// --- Akhir Perbaikan ---

?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Custom animations */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .fade-in {
      animation: fadeIn 0.6s ease-out;
    }

    /* Loading spinner */
    .loader {
      border: 3px solid #f3f3f3;
      border-top: 3px solid #3498db;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    /* Smooth transitions */
    * {
      transition: background-color 0.3s ease, color 0.3s ease, transform 0.3s ease, opacity 0.3s ease;
    }

    /* Dark mode */
    html.dark {
      color-scheme: dark;
    }

    html:not(.dark) {
      color-scheme: light;
    }

    .dark body {
      background-color: #1a202c;
      /* gray-900 */
      color: #e2e8f0;
      /* gray-200 */
    }

    .dark .bg-white {
      background-color: #2d3748;
      /* gray-800 */
    }

    .dark .bg-gray-50 {
      background-color: #1a202c;
      /* gray-900 */
    }

    .dark .bg-gray-100 {
      background-color: #2d3748;
      /* gray-800 */
    }

    .dark .text-gray-900 {
      color: #ffffff;
      /* white */
    }

    .dark .text-gray-800 {
      color: #e2e8f0;
      /* gray-200 */
    }

    .dark .text-gray-700 {
      color: #a0aec0;
      /* gray-400 */
    }

    .dark .text-gray-600 {
      color: #cbd5e0;
      /* gray-400 */
    }

    .dark .text-gray-500 {
      color: #a0aec0;
      /* gray-500 */
    }

    .dark .text-gray-400 {
      color: #718096;
      /* gray-600 */
    }

    .dark .border-gray-200 {
      border-color: #4a5568;
      /* gray-700 */
    }

    .dark .border-gray-300 {
      border-color: #4a5568;
      /* gray-600 */
    }

    .dark .border-gray-700 {
      border-color: #2d3748;
      /* gray-800 */
    }

    .dark .shadow-lg {
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.25);
    }

    .dark .placeholder-gray-500::placeholder {
      color: #a0aec0;
      /* gray-500 */
    }

    .dark .hover\:bg-gray-50:hover {
      background-color: #4a5568;
      /* gray-700 */
    }

    .dark .hover\:bg-gray-100:hover {
      background-color: #4a5568;
      /* gray-700 */
    }

    /* PERBAIKAN: CSS Animasi Burger-to-X */
    .burger-icon span {
      display: block;
      width: 24px;
      /* Lebar garis burger */
      height: 2px;
      /* Tinggi garis burger */
      background-color: currentColor;
      /* Warna garis mengikuti teks */
      transform-origin: center;
      transition: all 0.3s ease-in-out;
    }

    #mobileMenuButton.active .burger-icon span:nth-child(1) {
      /* Garis atas: pindah ke tengah, rotasi 45 derajat */
      transform: translateY(6px) rotate(45deg);
    }

    #mobileMenuButton.active .burger-icon span:nth-child(2) {
      /* Garis tengah: hilang (opacity 0) dan geser ke kiri */
      opacity: 0;
      transform: translateX(-10px);
    }

    #mobileMenuButton.active .burger-icon span:nth-child(3) {
      /* Garis bawah: pindah ke tengah, rotasi -45 derajat */
      transform: translateY(-6px) rotate(-45deg);
    }

    /* Akhir Perbaikan */
  </style>
  <script>
    // Dark mode toggle
    function toggleDarkMode() {
      const htmlElement = document.documentElement;
      htmlElement.classList.toggle('dark');
      const isDark = htmlElement.classList.contains('dark');
      localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
      updateDarkModeIcon();
    }

    // PERBAIKAN: Update kedua ikon (desktop dan mobile)
    function updateDarkModeIcon() {
      const icon = document.getElementById('darkModeIcon');
      const iconMobile = document.getElementById('darkModeIconMobile'); // Target ikon mobile

      let iconClass = 'fas fa-moon text-xl'; // Default: ikon bulan
      // Periksa apakah class 'dark' ada di elemen <html>
      if (document.documentElement.classList.contains('dark')) {
        iconClass = 'fas fa-sun text-xl'; // Mode gelap: ikon matahari
      }

      if (icon) {
        icon.className = iconClass;
      }
      if (iconMobile) {
        iconMobile.className = iconClass;
      }
    }
    // Akhir Perbaikan

    // Apply dark mode on initial load
    function applyInitialDarkMode() {
      const storedMode = localStorage.getItem('darkMode');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

      // Terapkan 'dark' jika tersimpan 'enabled' ATAU (tidak tersimpan DAN user prefer dark)
      if (storedMode === 'enabled' || (storedMode === null && prefersDark)) {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
      updateDarkModeIcon(); // Update ikon setelah menerapkan tema
    }

    applyInitialDarkMode(); // Jalankan saat script dimuat

    // PERBAIKAN: Listener untuk perubahan preferensi sistem
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
      // Hanya update jika tidak ada preferensi tersimpan di localStorage
      if (localStorage.getItem('darkMode') === null) {
        if (event.matches) {
          document.documentElement.classList.add('dark');
        } else {
          document.documentElement.classList.remove('dark');
        }
        updateDarkModeIcon();
      }
    });
    // Akhir Perbaikan

    // Mobile menu toggle
    // PERBAIKAN: Toggle class 'active' pada tombol
    function toggleMobileMenu() {
      const menu = document.getElementById('mobileMenu');
      const button = document.getElementById('mobileMenuButton');
      menu.classList.toggle('hidden');
      button.classList.toggle('active'); // Menambah/menghapus class 'active'
    }
    // Akhir Perbaikan
  </script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
  <!-- Loading Screen -->
  <div id="loadingScreen" class="fixed inset-0 bg-white dark:bg-gray-900 z-50 flex items-center justify-center transition-opacity duration-500 ease-out">
    <div class="text-center">
      <!-- Animated Logo -->
      <div class="logo-container mb-6">
        <div class="logo-pulse">
          <!-- PERBAIKAN: Ganti ikon dengan <img> -->
          <!-- Pastikan Anda memiliki logo di /assets/logo.png -->
          <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo" class="h-16 w-16 mx-auto animate-bounce" style="animation-duration: 2s;">
          <!-- Akhir Perbaikan -->
        </div>
        <div class="logo-glow"></div>
      </div>
      <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 animate-pulse">Galeri Karya Siswa</h3>
      <p class="text-gray-600 dark:text-gray-400 animate-pulse" style="animation-delay: 0.5s;">Memuat konten kreatif...</p>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Logo -->
        <div class="flex items-center">
          <a href="/index.php" class="flex items-center space-x-2">
            <!-- PERBAIKAN: Ganti ikon dengan img -->
            <!-- Pastikan Anda memiliki logo di /assets/logo.png -->
            <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo Galeri" class="h-8 w-auto">
            <!-- Akhir Perbaikan -->
            <span class="font-bold text-xl text-gray-800 dark:text-white">Galeri Karya</span>
          </a>
        </div>

        <!-- Desktop Navigation -->
        <div class="hidden md:flex items-center space-x-6">
          <a href="/index.php" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Beranda</a>
          <a href="/galeri.php" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Galeri</a>

          <?php if ($current_user): ?>
            <?php if ($current_user['peran'] === 'siswa'): ?>
              <a href="/dashboard/siswa.php" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Dashboard</a>
            <?php elseif ($current_user['peran'] === 'guru'): ?>
              <a href="/dashboard/guru.php" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Dashboard</a>
            <?php elseif ($current_user['peran'] === 'administrator'): ?>
              <a href="/dashboard/admin.php" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Admin</a>
            <?php endif; ?>

            <!-- Notifications -->
            <a href="/notifikasi.php" class="relative text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
              <i class="fas fa-bell text-xl"></i>
              <?php
              // Pastikan fungsi getUnreadNotificationsCount() ada dan bekerja
              if (function_exists('getUnreadNotificationsCount')) {
                $unread_count = getUnreadNotificationsCount();
                if ($unread_count > 0):
              ?>
                  <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $unread_count; ?></span>
              <?php endif;
              } else {
                // Handle jika fungsi tidak ditemukan
                error_log("Fungsi getUnreadNotificationsCount() tidak ditemukan.");
              }
              ?>
            </a>

            <!-- User Menu -->
            <div class="relative group">
              <button class="flex items-center space-x-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none">
                <img src="/uploads/profil/<?php echo htmlspecialchars($current_user['foto_profil']); ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover" onerror="this.onerror=null; this.src='/assets/default_avatar.png';"> <!-- Fallback ke default avatar -->
                <span><?php echo sanitize($current_user['nama_lengkap']); ?></span>
                <i class="fas fa-chevron-down text-sm transition-transform duration-200 group-hover:rotate-180"></i>
              </button>
              <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 hidden group-hover:block transition-opacity duration-200 opacity-0 group-hover:opacity-100">
                <a href="/profil.php?id=<?php echo $current_user['id']; ?>" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Profil Saya</a>
                <a href="/edit-profil.php" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Edit Profil</a>
                <a href="/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Keluar</a>
              </div>
            </div>
          <?php else: ?>
            <a href="/login.php" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Masuk</a>
            <a href="/register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Daftar</a>
          <?php endif; ?>

          <!-- Dark Mode Toggle -->
          <button onclick="toggleDarkMode()" aria-label="Toggle dark mode" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
            <i id="darkModeIcon" class="fas fa-moon text-xl"></i>
          </button>
        </div>

        <!-- Mobile Menu Button -->
        <div class="md:hidden flex items-center space-x-4">
          <button onclick="toggleDarkMode()" aria-label="Toggle dark mode" class="text-gray-700 dark:text-gray-300">
            <i id="darkModeIconMobile" class="fas fa-moon text-xl"></i>
          </button>

          <!-- PERBAIKAN: Tombol Burger Animasi -->
          <button id="mobileMenuButton" onclick="toggleMobileMenu()" aria-label="Toggle menu" class="text-gray-700 dark:text-gray-300 relative h-8 w-8 flex items-center justify-center focus:outline-none">
            <span class="sr-only">Buka menu</span>
            <div class="burger-icon space-y-1.5">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </button>
          <!-- Akhir Perbaikan -->
        </div>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden md:hidden bg-white dark:bg-gray-800 border-t dark:border-gray-700 absolute w-full shadow-lg">
      <div class="px-4 py-3 space-y-3">
        <a href="/index.php" class="block text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Beranda</a>
        <a href="/galeri.php" class="block text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Galeri</a>

        <?php if ($current_user): ?>
          <?php if ($current_user['peran'] === 'siswa'): ?>
            <a href="/dashboard/siswa.php" class="block text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Dashboard</a>
          <?php elseif ($current_user['peran'] === 'guru'): ?>
            <a href="/dashboard/guru.php" class="block text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Dashboard</a>
          <?php elseif ($current_user['peran'] === 'administrator'): ?>
            <a href="/dashboard/admin.php" class="block text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Admin</a>
          <?php endif; ?>

          <a href="/notifikasi.php" class="block text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
            Notifikasi
            <?php if (isset($unread_count) && $unread_count > 0): ?>
              <span class="ml-2 bg-red-500 text-white text-xs rounded-full px-2 py-1"><?php echo $unread_count; ?></span>
            <?php endif; ?>
          </a>
          <a href="/profil.php?id=<?php echo $current_user['id']; ?>" class="block text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Profil Saya</a>
          <a href="/edit-profil.php" class="block text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Edit Profil</a>
          <a href="/logout.php" class="block text-red-600 hover:text-red-700">Keluar</a>
        <?php else: ?>
          <a href="/login.php" class="block text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Masuk</a>
          <a href="/register.php" class="block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-center">Daftar</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <script>
    // Hide loading screen after page load
    window.addEventListener('load', function() {
      const loadingScreen = document.getElementById('loadingScreen');
      if (loadingScreen) {
        loadingScreen.classList.add('opacity-0');
        // Hapus elemen setelah transisi selesai agar tidak mengganggu interaksi
        setTimeout(function() {
          loadingScreen.style.display = 'none';
        }, 500); // Sesuaikan dengan durasi transisi di CSS
      }
    });
  </script>

  <main class="min-h-screen">