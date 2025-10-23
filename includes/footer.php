</main>

<!-- Footer -->
<footer class="bg-gray-800 dark:bg-gray-900 text-white mt-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      <!-- About -->
      <div>
        <h3 class="text-lg font-bold mb-4">Tentang Kami</h3>
        <p class="text-gray-400 text-sm">
          Platform galeri digital untuk menampilkan karya-karya kreatif siswa. Tempat di mana kreativitas bertemu dengan apresiasi.
        </p>
      </div>

      <!-- Quick Links -->
      <div>
        <h3 class="text-lg font-bold mb-4">Tautan Cepat</h3>
        <ul class="space-y-2 text-sm">
          <li><a href="/index.php" class="text-gray-400 hover:text-white">Beranda</a></li>
          <li><a href="/galeri.php" class="text-gray-400 hover:text-white">Galeri</a></li>
          <?php if (!isLoggedIn()): ?>
            <li><a href="/login.php" class="text-gray-400 hover:text-white">Masuk</a></li>
            <li><a href="/register.php" class="text-gray-400 hover:text-white">Daftar</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Categories -->
      <div>
        <h3 class="text-lg font-bold mb-4">Kategori</h3>
        <ul class="space-y-2 text-sm">
          <li><a href="/galeri.php?kategori=1" class="text-gray-400 hover:text-white">Seni Rupa</a></li>
          <li><a href="/galeri.php?kategori=2" class="text-gray-400 hover:text-white">Sains</a></li>
          <li><a href="/galeri.php?kategori=3" class="text-gray-400 hover:text-white">Teknologi</a></li>
          <li><a href="/galeri.php?kategori=4" class="text-gray-400 hover:text-white">Sastra</a></li>
        </ul>
      </div>

      <!-- Contact & Social -->
      <div>
        <h3 class="text-lg font-bold mb-4">Hubungi Kami</h3>
        <ul class="space-y-2 text-sm text-gray-400">
          <li><i class="fas fa-envelope mr-2"></i>info@galerikarya.com</li>
          <li><i class="fas fa-phone mr-2"></i>+62 123 4567 890</li>
        </ul>
        <div class="mt-4 flex space-x-4">
          <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook text-xl"></i></a>
          <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter text-xl"></i></a>
          <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram text-xl"></i></a>
          <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-youtube text-xl"></i></a>
        </div>
      </div>
    </div>

    <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
      <p>&copy; <?php echo date('Y'); ?> Galeri Karya Siswa. All rights reserved.</p>
    </div>
  </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="fixed bottom-8 right-8 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 hidden transition-all duration-300 z-30">
  <i class="fas fa-arrow-up"></i>
</button>

<script>
  // Back to top button
  window.addEventListener('scroll', function() {
    const backToTop = document.getElementById('backToTop');
    if (window.pageYOffset > 300) {
      backToTop.classList.remove('hidden');
    } else {
      backToTop.classList.add('hidden');
    }
  });

  document.getElementById('backToTop').addEventListener('click', function() {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });
</script>
</body>

</html>