<?php
$page_title = 'Galeri Karya - Galeri Karya Siswa';
require_once 'includes/header.php';

$conn = getDBConnection();

// Get query parameters
$search = sanitize($_GET['q'] ?? '');
$kategori_id = (int)($_GET['kategori'] ?? 0);
$sort = sanitize($_GET['sort'] ?? 'terbaru');

// Pagination
$page = (int)($_GET['p'] ?? 1);
$limit = 12;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = ["k.status = 'disetujui'"];
$bind_params = [];
$bind_types = "";

// Search condition
if (!empty($search)) {
  $where_conditions[] = "(k.judul LIKE ? OR k.deskripsi LIKE ? OR p.nama_lengkap LIKE ?)";
  $search_param = "%$search%";
  $bind_params[] = $search_param;
  $bind_params[] = $search_param;
  $bind_params[] = $search_param;
  $bind_types .= "sss";
}

// Category filter
if ($kategori_id > 0) {
  $where_conditions[] = "k.kategori_id = ?";
  $bind_params[] = $kategori_id;
  $bind_types .= "i";
}

// Order by
$order_by = match ($sort) {
  'penilaian' => "jumlah_suka DESC, k.dibuat_pada DESC",
  'populer' => "jumlah_suka DESC, k.dibuat_pada DESC",
  'terlama' => "k.dibuat_pada ASC",
  default => "k.dibuat_pada DESC"
};

// Where clause string
$where_clause = implode(" AND ", $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM Karya k
              LEFT JOIN Pengguna p ON k.siswa_id = p.id
              WHERE $where_clause";

$count_stmt = $conn->prepare($count_sql);
if (!empty($bind_params)) {
  $count_stmt->bind_param($bind_types, ...$bind_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get artworks
$sql = "SELECT k.*, p.nama_lengkap as nama_siswa, p.foto_profil,
               kat.nama as kategori_nama,
               COUNT(DISTINCT s.id) as jumlah_suka
        FROM Karya k
        LEFT JOIN Pengguna p ON k.siswa_id = p.id
        LEFT JOIN Kategori kat ON k.kategori_id = kat.id
        LEFT JOIN Suka s ON k.id = s.karya_id
        WHERE $where_clause
        GROUP BY k.id
        ORDER BY $order_by
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!empty($bind_params)) {
  $stmt->bind_param($bind_types . "ii", ...[...$bind_params, $limit, $offset]);
} else {
  $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Get categories for filter
$categories = $conn->query("SELECT * FROM Kategori ORDER BY nama");
$conn->close();
?>

<!-- Page Header -->
<section class="bg-gray-50 dark:bg-gray-900 py-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center">
      <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Galeri Karya</h1>
      <p class="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
        Jelajahi berbagai karya kreatif dari siswa-siswa berbakat Indonesia
      </p>
    </div>
  </div>
</section>

<!-- Filters Section -->
<section class="bg-white dark:bg-gray-800 py-8 border-b dark:border-gray-700">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <form method="GET" class="grid grid-cols-1 lg:grid-cols-4 gap-4">
      <!-- Search -->
      <div class="col-span-1 lg:col-span-2">
        <label for="q" class="sr-only">Cari karya</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-search text-gray-400"></i>
          </div>
          <input type="text" name="q" id="q" value="<?php echo htmlspecialchars($search); ?>"
            class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Cari berdasarkan judul, deskripsi, atau nama siswa...">
        </div>
      </div>

      <!-- Category Filter -->
      <div>
        <label for="kategori" class="sr-only">Kategori</label>
        <select name="kategori" id="kategori" class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="">Semua Kategori</option>
          <?php while ($kategori = $categories->fetch_assoc()): ?>
            <option value="<?php echo $kategori['id']; ?>" <?php echo ($kategori_id == $kategori['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($kategori['nama']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Sort -->
      <div>
        <label for="sort" class="sr-only">Urutkan</label>
        <select name="sort" id="sort" class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="terbaru" <?php echo ($sort === 'terbaru') ? 'selected' : ''; ?>>Terbaru</option>
          <option value="terlama" <?php echo ($sort === 'terlama') ? 'selected' : ''; ?>>Terlama</option>
          <option value="populer" <?php echo ($sort === 'populer') ? 'selected' : ''; ?>>Paling Disukai</option>
        </select>
      </div>

      <!-- Submit Button -->
      <div class="lg:col-span-4 flex justify-center">
        <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
          <i class="fas fa-filter mr-2"></i>Terapkan Filter
        </button>
        <?php if ($search || $kategori_id > 0 || $sort !== 'terbaru'): ?>
          <a href="/galeri.php" class="ml-4 bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
            <i class="fas fa-times mr-2"></i>Reset
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</section>

<!-- Gallery Grid -->
<section class="py-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Results Info -->
    <div class="mb-8 flex justify-between items-center flex-wrap gap-4">
      <div class="text-gray-600 dark:text-gray-400">
        Menampilkan <?php echo $offset + 1; ?> hingga <?php echo min($offset + $limit, $total_records); ?> dari <?php echo number_format($total_records); ?> karya
      </div>
      <?php if (isLoggedIn() && getCurrentUser()['peran'] === 'siswa'): ?>
        <a href="/upload-karya.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
          <i class="fas fa-plus mr-2"></i>Unggah Karya Baru
        </a>
      <?php endif; ?>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php while ($karya = $result->fetch_assoc()): ?>
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-all transform hover:scale-105 fade-in">
            <div class="relative">
              <?php
              $file_type = getFileType($karya['media_url']);
              if ($file_type === 'image'):
              ?>
                <img src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" alt="<?php echo htmlspecialchars($karya['judul']); ?>" class="w-full h-48 object-cover">
              <?php elseif ($file_type === 'video'): ?>
                <video class="w-full h-48 object-cover" muted>
                  <source src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" type="video/<?php echo pathinfo($karya['media_url'], PATHINFO_EXTENSION); ?>">
                  <i class="fas fa-play-circle absolute inset-0 flex items-center justify-center text-white text-4xl bg-black bg-opacity-50"></i>
                </video>
              <?php else: ?>
                <div class="w-full h-48 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                  <i class="fas fa-file-alt text-4xl text-gray-400"></i>
                </div>
              <?php endif; ?>

              <?php if ($karya['unggulan']): ?>
                <div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                  <i class="fas fa-star mr-1"></i> Unggulan
                </div>
              <?php endif; ?>

              <?php if ($file_type === 'video'): ?>
                <div class="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white px-2 py-1 rounded text-xs">
                  <i class="fas fa-video mr-1"></i> Video
                </div>
              <?php endif; ?>
            </div>

            <div class="p-4">
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center">
                  <img src="/uploads/profil/<?php echo htmlspecialchars($karya['foto_profil']); ?>" alt="Profile" class="w-6 h-6 rounded-full object-cover mr-2" onerror="this.style.display='none'; this.nextElementSibling.innerHTML='<i class=\'fas fa-user text-gray-400\'></i>';" style="width: 24px; height: 24px;"><i class="fas fa-user text-gray-400" style="display: none;"></i>
                  <span class="text-xs text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($karya['nama_siswa']); ?></span>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo getRelativeTime($karya['dibuat_pada']); ?></span>
              </div>

              <h3 class="font-bold text-gray-900 dark:text-white mb-2 line-clamp-1" title="<?php echo htmlspecialchars($karya['judul']); ?>">
                <?php echo htmlspecialchars(truncateText($karya['judul'], 50)); ?>
              </h3>

              <p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2">
                <?php echo truncateText(strip_tags($karya['deskripsi']), 80); ?>
              </p>

              <div class="flex items-center justify-between mb-3">
                <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">
                  <?php echo htmlspecialchars($karya['kategori_nama'] ?? 'Tanpa Kategori'); ?>
                </span>

                <div class="flex items-center text-gray-500 dark:text-gray-400 text-sm">
                  <form method="POST" action="/suka.php" class="inline mr-3" id="like-form-<?php echo $karya['id']; ?>">
                    <input type="hidden" name="karya_id" value="<?php echo $karya['id']; ?>">
                    <button type="submit" class="text-red-500 hover:text-red-600 <?php
                                                                                  $user_ip = getUserIP();
                                                                                  $conn_temp = getDBConnection();
                                                                                  $check_like = $conn_temp->prepare("SELECT id FROM Suka WHERE karya_id = ? AND user_ip = ?");
                                                                                  $check_like->bind_param("is", $karya['id'], $user_ip);
                                                                                  $check_like->execute();
                                                                                  $is_liked = $check_like->get_result()->num_rows > 0;
                                                                                  $conn_temp->close();
                                                                                  echo $is_liked ? 'text-red-600' : '';
                                                                                  ?>" onclick="handleLike(this, event)">
                      <i class="fas fa-heart"></i> <?php echo $karya['jumlah_suka']; ?>
                    </button>
                  </form>
                </div>
              </div>

              <a href="/karya.php?id=<?php echo $karya['id']; ?>" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors text-center block text-sm">
                Lihat Detail
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-16">
        <i class="fas fa-search text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <h3 class="text-2xl font-semibold text-gray-600 dark:text-gray-400 mb-2">Tidak ada karya ditemukan</h3>
        <p class="text-gray-500 dark:text-gray-500 mb-8 max-w-md mx-auto">
          <?php if ($search || $kategori_id > 0): ?>
            Coba ubah kata kunci pencarian atau filter kategori untuk menemukan karya yang Anda cari.
          <?php else: ?>
            Belum ada karya yang disetujui untuk ditampilkan. Jadilah yang pertama unggah karya kreatif Anda!
          <?php endif; ?>
        </p>

        <?php if (isLoggedIn() && getCurrentUser()['peran'] === 'siswa'): ?>
          <a href="/upload-karya.php" class="bg-blue-600 text-white px-8 py-4 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
            <i class="fas fa-upload mr-2"></i>Unggah Karya Pertama
          </a>
        <?php elseif (!isLoggedIn()): ?>
          <div class="space-x-4">
            <a href="/register.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">Daftar</a>
            <a href="/login.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors">Masuk</a>
          </div>
        <?php endif; ?>

        <?php if ($search || $kategori_id > 0): ?>
          <div class="mt-6">
            <a href="/galeri.php" class="text-blue-600 hover:text-blue-500 font-medium">Tampilkan semua karya</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <div class="mt-12 flex justify-center">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
          <!-- Previous -->
          <?php if ($page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page - 1])); ?>" class="relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">
              <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
            </a>
          <?php else: ?>
            <span class="relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-300 dark:text-gray-500 cursor-not-allowed">
              <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
            </span>
          <?php endif; ?>

          <!-- Page Numbers -->
          <?php
          $start_page = max(1, $page - 2);
          $end_page = min($total_pages, $page + 2);

          for ($i = $start_page; $i <= $end_page; $i++):
            $params = array_merge($_GET, ['p' => $i]);
            unset($params['p']); // Remove existing p
            $params['p'] = $i;
          ?>
            <?php if ($i === $page): ?>
              <span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 dark:bg-blue-900 text-sm font-medium text-blue-600 dark:text-blue-400">
                <?php echo $i; ?>
              </span>
            <?php else: ?>
              <a href="?<?php echo http_build_query($params); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <?php echo $i; ?>
              </a>
            <?php endif; ?>
          <?php endfor; ?>

          <!-- Next -->
          <?php if ($page < $total_pages): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page + 1])); ?>" class="relative inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">
              Selanjutnya <i class="fas fa-chevron-right ml-1"></i>
            </a>
          <?php else: ?>
            <span class="relative inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-300 dark:text-gray-500 cursor-not-allowed">
              Selanjutnya <i class="fas fa-chevron-right ml-1"></i>
            </span>
          <?php endif; ?>
        </nav>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
  // Handle like button without page refresh
  function handleLike(button, event) {
    event.preventDefault();

    const form = button.closest('form');
    const formData = new FormData(form);
    const currentIcon = button.querySelector('i');
    const currentCount = button.textContent.trim().match(/\d+$/)[0];
    const isLiked = button.classList.contains('text-red-600');

    // Disable button temporarily
    button.disabled = true;

    fetch('/suka.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          if (data.action === 'liked') {
            button.classList.add('text-red-600');
            button.innerHTML = '<i class="fas fa-heart"></i> ' + data.newCount;
          } else {
            button.classList.remove('text-red-600');
            button.innerHTML = '<i class="fas fa-heart"></i> ' + data.newCount;
          }
        } else {
          alert('Terjadi kesalahan saat memproses permintaan');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memproses permintaan');
      })
      .finally(() => {
        button.disabled = false;
      });
  }
</script>

<?php require_once 'includes/footer.php'; ?>