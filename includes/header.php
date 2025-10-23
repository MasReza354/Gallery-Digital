<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$current_user = getCurrentUser();
$page_title = isset($page_title) ? $page_title : 'Galeri Karya Siswa';
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
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Dark mode */
    .dark {
      background-color: #1a202c;
      color: #e2e8f0;
    }

    .dark .bg-white {
      background-color: #2d3748;
    }

    .dark .text-gray-800 {
      color: #e2e8f0;
    }

    .dark .text-gray-600 {
      color: #cbd5e0;
    }

    .dark .border-gray-200 {
      border-color: #4a5568;
    }
  </style>
  <script>
    // Dark mode toggle
    function toggleDarkMode() {
      document.documentElement.classList.toggle('dark');
      const isDark = document.documentElement.classList.contains('dark');
      localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
      updateDarkModeIcon();
    }

    function updateDarkModeIcon() {
      const icon = document.getElementById('darkModeIcon');
      if (icon) {
        if (document.documentElement.classList.contains('dark')) {
          icon.className = 'fas fa-sun';
        } else {
          icon.className = 'fas fa-moon';
        }
      }
    }

    // Check for saved dark mode preference
    document.addEventListener('DOMContentLoaded', function() {
      const darkMode = localStorage.getItem('darkMode');
      if (darkMode === 'enabled') {
        document.documentElement.classList.add('dark');
      }
      updateDarkModeIcon();
    });

    // Mobile menu toggle
    function toggleMobileMenu() {
      const menu = document.getElementById('mobileMenu');
      menu.classList.toggle('hidden');
    }
  </script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
  <!-- Loading Screen -->
  <div id="loadingScreen" class="fixed inset-0 bg-white dark:bg-gray-900 z-50 flex items-center justify-center">
    <div class="text-center">
      <!-- Animated Logo -->
      <div class="logo-container mb-6">
        <div class="logo-pulse">
          <i class="fas fa-palette text-6xl text-blue-600 animate-bounce" style="animation-duration: 2s;"></i>
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
            <i class="fas fa-palette text-2xl text-blue-600"></i>
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
              $unread_count = getUnreadNotificationsCount();
              if ($unread_count > 0):
              ?>
                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $unread_count; ?></span>
              <?php endif; ?>
            </a>

            <!-- User Menu -->
            <div class="relative group">
              <button class="flex items-center space-x-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
                <img src="/uploads/profil/<?php echo $current_user['foto_profil']; ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover" onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\'fas fa-user text-gray-400\'></i>'">
                <span><?php echo sanitize($current_user['nama_lengkap']); ?></span>
                <i class="fas fa-chevron-down text-sm"></i>
              </button>
              <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 hidden group-hover:block">
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
          <button onclick="toggleDarkMode()" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
            <i id="darkModeIcon" class="fas fa-moon text-xl"></i>
          </button>
        </div>

        <!-- Mobile Menu Button -->
        <div class="md:hidden flex items-center space-x-4">
          <button onclick="toggleDarkMode()" class="text-gray-700 dark:text-gray-300">
            <i id="darkModeIconMobile" class="fas fa-moon text-xl"></i>
          </button>
          <button onclick="toggleMobileMenu()" class="text-gray-700 dark:text-gray-300">
            <i class="fas fa-bars text-2xl"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden md:hidden bg-white dark:bg-gray-800 border-t dark:border-gray-700">
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
            <?php if ($unread_count > 0): ?>
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
      setTimeout(function() {
        document.getElementById('loadingScreen').style.display = 'none';
      }, 500);
    });
  </script>

  <main class="min-h-screen">