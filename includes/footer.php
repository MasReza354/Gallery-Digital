</main>

<!-- Footer -->
<footer class="bg-gray-800 dark:bg-gray-900 text-white mt-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <!-- PERBAIKAN: Grid Wrapper Baru untuk Logo + Konten -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-8">

      <!-- Kolom Logo -->
      <!-- Tampil di atas di mobile (< md), di kiri di desktop (>= md) -->
      <div class="md:col-span-1 flex flex-row md:flex-col justify-center items-center md:items-start gap-2 text-center md:text-left mb-8 md:mb-0">
        <!-- Pastikan Anda memiliki logo di /assets/logo-universitas.png dan /assets/logo-mitra.png -->
        <!-- TODO: Ganti path logo sesuai kebutuhan -->
        <img src="../assets/images/logo.png" alt="Logo Universitas" class="h-20 w-auto object-contain" title="Logo Universitas" onerror="this.style.display='none'">
        <img src="../assets/images/logo.png" alt="Logo Mitra" class="h-20 w-auto object-contain" title="Logo Mitra" onerror="this.style.display='none'">
      </div>

      <!-- Kolom Konten (Grid Nested) -->
      <!-- PERBAIKAN: text-center di mobile (< md), md:text-left di desktop (>= md) -->
      <div class="md:col-span-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 text-center md:text-left">

        <!-- Tentang Kami -->
        <div>
          <h3 class="text-lg font-bold mb-4">Tentang Kami</h3>
          <p class="text-gray-400 text-sm">
            Platform galeri digital untuk menampilkan karya-karya kreatif siswa. Tempat di mana kreativitas bertemu dengan apresiasi.
          </p>
        </div>

        <!-- Tautan Cepat -->
        <div>
          <h3 class="text-lg font-bold mb-4">Tautan Cepat</h3>
          <ul class="space-y-2 text-sm">
            <li><a href="/index.php" class="text-gray-400 hover:text-white transition-colors">Beranda</a></li>
            <li><a href="/galeri.php" class="text-gray-400 hover:text-white transition-colors">Galeri</a></li>
            <?php if (!isLoggedIn()): ?>
              <li><a href="/login.php" class="text-gray-400 hover:text-white transition-colors">Masuk</a></li>
              <li><a href="/register.php" class="text-gray-400 hover:text-white transition-colors">Daftar</a></li>
            <?php else: ?>
              <?php $user = getCurrentUser(); ?>
              <li><a href="/dashboard/<?php echo $user['peran']; ?>.php" class="text-gray-400 hover:text-white transition-colors">Dashboard</a></li>
              <li><a href="/profil.php?id=<?php echo $user['id']; ?>" class="text-gray-400 hover:text-white transition-colors">Profil Saya</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Kategori (Sesuai file asli Anda) -->
        <div>
          <h3 class="text-lg font-bold mb-4">Kategori</h3>
          <ul class="space-y-2 text-sm">
            <li><a href="/galeri.php?kategori=1" class="text-gray-400 hover:text-white transition-colors">Seni Rupa</a></li>
            <li><a href="/galeri.php?kategori=2" class="text-gray-400 hover:text-white transition-colors">Sains</a></li>
            <li><a href="/galeri.php?kategori=3" class="text-gray-400 hover:text-white transition-colors">Teknologi</a></li>
            <li><a href="/galeri.php?kategori=4" class="text-gray-400 hover:text-white transition-colors">Sastra</a></li>
            <li><a href="/galeri.php" class="text-gray-400 hover:text-white transition-colors">Lainnya...</a></li>
          </ul>
        </div>

        <!-- Hubungi Kami -->
        <div>
          <h3 class="text-lg font-bold mb-4">Hubungi Kami</h3>
          <ul class="space-y-2 text-sm text-gray-400">
            <li class="flex items-center justify-center md:justify-start">
              <i class="fas fa-envelope w-4 mr-2 text-center"></i>
              <a href="mailto:info@galerikarya.com" class="hover:text-white transition-colors">info@galerikarya.com</a>
            </li>
            <li class="flex items-center justify-center md:justify-start">
              <i class="fas fa-phone w-4 mr-2 text-center"></i>
              <span>+62 123 4567 890</span>
            </li>
          </ul>
          <!-- PERBAIKAN: justify-center di mobile (< md), md:justify-start di desktop (>= md) -->
          <div class="mt-4 flex space-x-4 justify-center md:justify-start">
            <a href="#" aria-label="Facebook" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-facebook text-xl"></i></a>
            <a href="#" aria-label="Twitter" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-twitter text-xl"></i></a>
            <a href="#" aria-label="Instagram" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-instagram text-xl"></i></a>
            <a href="#" aria-label="YouTube" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-youtube text-xl"></i></a>
          </div>
        </div>
      </div>
      <!-- Akhir Perbaikan Grid Wrapper -->
    </div>

    <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
      <p>&copy; <?php echo date('Y'); ?> Galeri Karya Siswa. All rights reserved.</p>
    </div>
  </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" aria-label="Kembali ke atas" class="fixed bottom-8 right-8 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 hidden transition-all duration-300 z-30 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
  <i class="fas fa-arrow-up"></i>
</button>

<script>
  // Back to top button
  const backToTopButton = document.getElementById('backToTop');

  window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
      backToTopButton.classList.remove('hidden');
      backToTopButton.classList.add('opacity-100'); // Add fade-in effect if desired
    } else {
      backToTopButton.classList.add('hidden');
      backToTopButton.classList.remove('opacity-100'); // Add fade-out effect if desired
    }
  });

  backToTopButton.addEventListener('click', function() {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });
</script>
</body>

</html>