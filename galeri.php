<?php
$page_title = 'Galeri Karya - Galeri Karya Siswa';
require_once 'includes/header.php';

$conn = getDBConnection();

// Get query parameters
$search = sanitize($_GET['q'] ?? '');
$kategori_id = (int)($_GET['kategori'] ?? 0);
$sort = sanitize($_GET['sort'] ?? 'terbaru');
$is_ajax = isset($_GET['ajax']); // Cek apakah ini request AJAX

// Pagination
$page = (int)($_GET['p'] ?? 1);
$limit = 12; // Jumlah karya per halaman
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = ["k.status = 'disetujui'"];
$bind_params = [];
$bind_types = "";

// Search condition
if (!empty($search)) {
  $where_conditions[] = "(k.judul LIKE ? OR k.deskripsi LIKE ? OR p.nama_lengkap LIKE ?)";
  $search_param = "%" . $search . "%"; // Tambahkan wildcard
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
  'populer' => "jumlah_suka DESC, k.dibuat_pada DESC", // 'penilaian' diganti 'populer' agar sesuai select
  'terlama' => "k.dibuat_pada ASC",
  'terbaru' => "k.dibuat_pada DESC", // Default
  default => "k.dibuat_pada DESC"
};

// Where clause string
$where_clause = !empty($where_conditions) ? implode(" AND ", $where_conditions) : "1"; // Handle jika tidak ada kondisi

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT k.id) as total FROM Karya k
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
$count_stmt->close(); // Tutup statement count

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
$current_bind_types = $bind_types . "ii"; // Tambah tipe untuk LIMIT dan OFFSET
$current_bind_params = array_merge($bind_params, [$limit, $offset]); // Gabungkan params

$stmt->bind_param($current_bind_types, ...$current_bind_params);

$stmt->execute();
$result = $stmt->get_result();
$artworks_data = []; // Simpan hasil query ke array
while ($row = $result->fetch_assoc()) {
  $artworks_data[] = $row;
}
$stmt->close(); // Tutup statement utama


// --- PERBAIKAN: Pindahkan definisi fungsi ke luar blok if ---
// --- Mulai Fungsi untuk Render Konten Galeri ---
function renderGalleryContent($artworks_data, $offset, $limit, $total_records, $total_pages, $page, $search, $kategori_id, $sort)
{
  // 1. Tampilkan Info Hasil
  echo '<div class="mb-8 flex justify-between items-center flex-wrap gap-4">';
  echo '<div class="text-gray-600 dark:text-gray-400">';
  if ($total_records > 0) {
    echo "Menampilkan " . ($offset + 1) . " - " . min($offset + $limit, $total_records) . " dari " . number_format($total_records) . " karya";
  } else {
    echo "Tidak ada karya ditemukan.";
  }
  echo '</div>';
  // Tombol Unggah hanya jika user adalah siswa
  if (isLoggedIn() && ($user = getCurrentUser()) && $user['peran'] === 'siswa') {
    echo '<a href="/upload-karya.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center">';
    echo '<i class="fas fa-plus mr-2"></i>Unggah Karya Baru';
    echo '</a>';
  }
  echo '</div>';

  // 2. Tampilkan Grid Karya
  if (!empty($artworks_data)) {
    echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">';
    foreach ($artworks_data as $karya) {
      // --- Template Card Karya ---
      echo '<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-all transform hover:scale-[1.03] fade-in">';
      echo '<a href="/karya.php?id=' . $karya['id'] . '" class="block relative group">'; // Wrap card content in link

      echo '<div class="relative">';
      $file_type = getFileType($karya['media_url']);
      if ($file_type === 'image'):
        echo '<img src="/uploads/karya/' . htmlspecialchars($karya['media_url']) . '" alt="' . htmlspecialchars($karya['judul']) . '" class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-110">';
      elseif ($file_type === 'video'):
        echo '<div class="w-full h-48 bg-black flex items-center justify-center overflow-hidden">'; // Container for video
        echo '<video class="w-full h-full object-cover" muted>';
        echo '<source src="/uploads/karya/' . htmlspecialchars($karya['media_url']) . '" type="video/' . pathinfo($karya['media_url'], PATHINFO_EXTENSION) . '">';
        echo '</video>';
        echo '<i class="fas fa-play-circle absolute inset-0 flex items-center justify-center text-white text-4xl bg-black bg-opacity-30 opacity-0 group-hover:opacity-100 transition-opacity"></i>'; // Play icon on hover
        echo '</div>';
      else:
        echo '<div class="w-full h-48 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">';
        echo '<i class="fas fa-file-alt text-4xl text-gray-400"></i>';
        echo '</div>';
      endif;

      if ($karya['unggulan']):
        echo '<div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-semibold shadow">';
        echo '<i class="fas fa-star mr-1"></i> Unggulan';
        echo '</div>';
      endif;
      echo '</div>'; // end relative for image/video

      echo '<div class="p-4">';
      echo '<div class="flex items-center justify-between mb-2 text-xs">';
      echo '<div class="flex items-center text-gray-600 dark:text-gray-400 min-w-0">'; // min-w-0 for ellipsis
      echo '<img src="/uploads/profil/' . htmlspecialchars($karya['foto_profil']) . '" alt="Profile" class="w-5 h-5 rounded-full object-cover mr-1.5 flex-shrink-0" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'inline-block\';"><i class="fas fa-user text-gray-400 mr-1.5" style="display: none; width: 20px; height: 20px;"></i>';
      echo '<span class="truncate">' . htmlspecialchars($karya['nama_siswa']) . '</span>';
      echo '</div>';
      echo '<span class="text-gray-500 dark:text-gray-500 flex-shrink-0">' . getRelativeTime($karya['dibuat_pada']) . '</span>';
      echo '</div>';

      echo '<h3 class="font-bold text-gray-900 dark:text-white mb-2 line-clamp-1 group-hover:text-blue-600 transition-colors" title="' . htmlspecialchars($karya['judul']) . '">';
      echo htmlspecialchars(truncateText($karya['judul'], 45));
      echo '</h3>';

      echo '<p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2">';
      echo truncateText(strip_tags($karya['deskripsi']), 70);
      echo '</p>';

      echo '<div class="flex items-center justify-between text-xs">';
      echo '<span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-0.5 rounded">';
      echo htmlspecialchars($karya['kategori_nama'] ?? 'Lainnya');
      echo '</span>';

      // Like button di luar link utama card agar bisa diklik terpisah
      echo '<form method="POST" action="/suka.php" class="like-form z-10 relative" onclick="event.stopPropagation(); event.preventDefault(); handleLike(this.querySelector(\'button\'), event);">';
      echo '<input type="hidden" name="karya_id" value="' . $karya['id'] . '">';
      $user_ip = getUserIP();
      // Re-check like status for current user IP
      $conn_temp = getDBConnection(); // Need new connection inside loop if closed earlier
      $is_liked = false; // Default
      if ($conn_temp) {
        $check_like = $conn_temp->prepare("SELECT id FROM Suka WHERE karya_id = ? AND user_ip = ?");
        if ($check_like) {
          $check_like->bind_param("is", $karya['id'], $user_ip);
          $check_like->execute();
          $is_liked = $check_like->get_result()->num_rows > 0;
          $check_like->close();
        }
        $conn_temp->close();
      }
      $like_class = $is_liked ? 'text-red-500' : 'text-gray-500 dark:text-gray-400';
      echo '<button type="submit" class="' . $like_class . ' hover:text-red-500 transition-colors flex items-center">';
      echo '<i class="fas fa-heart mr-1"></i> <span class="like-count">' . $karya['jumlah_suka'] . '</span>';
      echo '</button>';
      echo '</form>';

      echo '</div>';
      echo '</div>'; // end p-4
      echo '</a>'; // End link wrapper
      echo '</div>'; // end card
      // --- Akhir Template Card Karya ---
    }
    echo '</div>'; // end grid
  } else {
    // --- Template "Tidak ada karya" ---
    echo '<div class="text-center py-16 col-span-1 md:col-span-2 lg:col-span-3 xl:col-span-4">'; // Span full width
    echo '<i class="fas fa-search text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>';
    echo '<h3 class="text-2xl font-semibold text-gray-600 dark:text-gray-400 mb-2">Tidak ada karya ditemukan</h3>';
    echo '<p class="text-gray-500 dark:text-gray-500 mb-8 max-w-md mx-auto">';
    if ($search || $kategori_id > 0) {
      echo 'Coba ubah kata kunci pencarian atau filter kategori Anda.';
    } else {
      echo 'Belum ada karya yang sesuai untuk ditampilkan di galeri ini.';
    }
    echo '</p>';
    echo '<a href="/galeri.php" class="text-blue-600 hover:text-blue-500 font-medium">Tampilkan semua karya</a>';
    echo '</div>';
    // --- Akhir Template "Tidak ada karya" ---
  }

  // 3. Tampilkan Paginasi
  echo '<div id="pagination-container" class="mt-12 flex justify-center">';
  if ($total_pages > 1) {
    echo '<nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">';

    // Previous Button
    $prev_page = $page - 1;
    $prev_disabled = $page <= 1;
    $prev_link = $prev_disabled ? '#' : '?' . http_build_query(array_merge($_GET, ['p' => $prev_page, 'ajax' => null])); // Hapus ajax
    $prev_class = $prev_disabled ? 'bg-gray-100 dark:bg-gray-700 text-gray-300 dark:text-gray-500 cursor-not-allowed' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700';
    echo '<a href="' . $prev_link . '" class="relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 text-sm font-medium ' . $prev_class . '">';
    echo '<span class="sr-only">Sebelumnya</span><i class="fas fa-chevron-left h-5 w-5"></i></a>';

    // Page Numbers Logic
    $links = [];
    $ellipsis = '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300">...</span>';

    if ($total_pages <= 5) { // Tampilkan semua jika <= 5 halaman
      for ($i = 1; $i <= $total_pages; $i++) $links[] = $i;
    } else {
      $links[] = 1; // Selalu tampilkan halaman 1
      if ($page > 3) $links[] = '...'; // Ellipsis kiri

      $start = max(2, $page - 1);
      $end = min($total_pages - 1, $page + 1);
      for ($i = $start; $i <= $end; $i++) $links[] = $i;

      if ($page < $total_pages - 2) $links[] = '...'; // Ellipsis kanan
      $links[] = $total_pages; // Selalu tampilkan halaman terakhir
    }

    foreach ($links as $link_page) {
      if ($link_page === '...') {
        echo $ellipsis;
      } else {
        $is_current = $link_page == $page;
        $page_link = '?' . http_build_query(array_merge($_GET, ['p' => $link_page, 'ajax' => null])); // Hapus ajax
        $page_class = $is_current ? 'z-10 bg-blue-50 dark:bg-blue-900 border-blue-500 text-blue-600 dark:text-blue-400' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700';
        echo '<a href="' . $page_link . '" aria-current="' . ($is_current ? 'page' : 'false') . '" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ' . $page_class . '">';
        echo $link_page;
        echo '</a>';
      }
    }

    // Next Button
    $next_page = $page + 1;
    $next_disabled = $page >= $total_pages;
    $next_link = $next_disabled ? '#' : '?' . http_build_query(array_merge($_GET, ['p' => $next_page, 'ajax' => null])); // Hapus ajax
    $next_class = $next_disabled ? 'bg-gray-100 dark:bg-gray-700 text-gray-300 dark:text-gray-500 cursor-not-allowed' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700';
    echo '<a href="' . $next_link . '" class="relative inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 text-sm font-medium ' . $next_class . '">';
    echo '<span class="sr-only">Selanjutnya</span><i class="fas fa-chevron-right h-5 w-5"></i></a>';

    echo '</nav>';
  }
  echo '</div>'; // end pagination-container
}
// --- Akhir Fungsi Render ---
// --- Akhir Perbaikan ---


// Jika ini request AJAX, kirim data dan exit
if ($is_ajax) {
  // Buffer output
  ob_start();

  // Panggil fungsi render untuk request AJAX
  renderGalleryContent($artworks_data, $offset, $limit, $total_records, $total_pages, $page, $search, $kategori_id, $sort);

  // Ambil output, bersihkan buffer, kirim JSON
  $gallery_html = ob_get_clean();
  // Pastikan koneksi ditutup sebelum mengirim JSON
  if ($conn && $conn->ping()) {
    $conn->close();
  }
  header('Content-Type: application/json');
  echo json_encode(['html' => $gallery_html]);
  exit;
}
// Akhir AJAX Check

// Get categories for filter (non-ajax only)
$categories_result = $conn->query("SELECT id, nama FROM Kategori ORDER BY nama");
$categories = [];
if ($categories_result) {
  while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
  }
}
// Tidak perlu close connection di sini jika sudah ditutup di blok AJAX
if ($conn && $conn->ping()) {
  $conn->close();
} // Tutup jika belum tertutup

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
<section class="bg-white dark:bg-gray-800 py-6 border-b dark:border-gray-700 sticky top-16 z-30 shadow-sm">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Form ID dan hapus tombol submit -->
    <form method="GET" id="filter-form" action="/galeri.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
      <!-- Search -->
      <div class="col-span-1 md:col-span-2">
        <label for="q" class="sr-only">Cari karya</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-search text-gray-400"></i>
          </div>
          <input type="text" name="q" id="q" value="<?php echo htmlspecialchars($search); ?>"
            class="filter-input block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
            placeholder="Cari judul, deskripsi, siswa...">
        </div>
      </div>

      <!-- Category Filter -->
      <div>
        <label for="kategori" class="sr-only">Kategori</label>
        <select name="kategori" id="kategori" class="filter-input block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
          <option value="">Semua Kategori</option>
          <?php foreach ($categories as $kategori): ?>
            <option value="<?php echo $kategori['id']; ?>" <?php echo ($kategori_id == $kategori['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($kategori['nama']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Sort -->
      <div>
        <label for="sort" class="sr-only">Urutkan</label>
        <select name="sort" id="sort" class="filter-input block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
          <option value="terbaru" <?php echo ($sort === 'terbaru') ? 'selected' : ''; ?>>Terbaru</option>
          <option value="terlama" <?php echo ($sort === 'terlama') ? 'selected' : ''; ?>>Terlama</option>
          <option value="populer" <?php echo ($sort === 'populer') ? 'selected' : ''; ?>>Paling Disukai</option>
        </select>
      </div>

      <!-- Hapus Tombol Terapkan Filter, sisakan Reset jika filter aktif -->
      <?php if ($search || $kategori_id > 0 || $sort !== 'terbaru'): ?>
        <div class="col-span-1 md:col-span-4 flex justify-center mt-4 md:mt-0">
          <a href="/galeri.php" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm font-medium flex items-center">
            <i class="fas fa-times mr-1"></i>Reset Filter
          </a>
        </div>
      <?php endif; ?>
    </form>
  </div>
</section>

<!-- Gallery Grid -->
<section class="py-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Wrapper untuk AJAX -->
    <div id="gallery-wrapper">

      <?php
      // Panggil fungsi render untuk tampilan awal (non-AJAX)
      renderGalleryContent($artworks_data, $offset, $limit, $total_records, $total_pages, $page, $search, $kategori_id, $sort);
      ?>

    </div> <!-- Akhir #gallery-wrapper -->

  </div>
</section>

<!-- Script untuk AJAX Filter dan Like -->
<script>
  // Handle like button without page refresh
  // Delegasikan ke body agar bekerja setelah konten AJAX dimuat ulang
  document.body.addEventListener('click', function(event) {
    const likeButton = event.target.closest('.like-form button');
    if (likeButton) {
      event.preventDefault(); // Mencegah submit form biasa
      event.stopPropagation(); // Mencegah link card ter-klik
      handleLike(likeButton, event); // Panggil fungsi handleLike
    }
  });


  function handleLike(button, event) {
    // event.preventDefault(); // Sudah dicegah di event listener utama
    // event.stopPropagation(); // Sudah dicegah di event listener utama

    const form = button.closest('form');
    if (!form) return; // Pastikan form ditemukan

    const countSpan = button.querySelector('.like-count');
    const icon = button.querySelector('i');
    const formData = new FormData(form);
    const isLiked = button.classList.contains('text-red-500'); // Cek status sebelum request

    button.disabled = true; // Nonaktifkan tombol
    button.classList.add('opacity-50', 'cursor-not-allowed');

    fetch('/suka.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response error');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          if (data.action === 'liked') {
            button.classList.add('text-red-500');
            icon.classList.add('text-red-500'); // Pastikan ikon juga berubah
            icon.classList.remove('text-gray-500', 'dark:text-gray-400');
          } else {
            button.classList.remove('text-red-500');
            icon.classList.remove('text-red-500');
            icon.classList.add('text-gray-500', 'dark:text-gray-400'); // Kembalikan warna ikon
          }
          if (countSpan) {
            countSpan.textContent = data.newCount;
          }
        } else {
          alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
          // Kembalikan state tombol jika gagal
          if (isLiked) button.classList.add('text-red-500');
          else button.classList.remove('text-red-500');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan koneksi.');
        // Kembalikan state tombol jika gagal
        if (isLiked) button.classList.add('text-red-500');
        else button.classList.remove('text-red-500');
      })
      .finally(() => {
        button.disabled = false; // Aktifkan kembali
        button.classList.remove('opacity-50', 'cursor-not-allowed');
      });
  }


  // Script AJAX Filter
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filter-form');
    const inputs = form.querySelectorAll('.filter-input');
    const galleryWrapper = document.getElementById('gallery-wrapper');
    const loadingIndicator = document.createElement('div'); // Indikator loading
    loadingIndicator.innerHTML = '<div class="flex justify-center items-center py-10"><div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-12 w-12 mb-4"></div><span class="ml-3 text-gray-500 dark:text-gray-400">Memuat karya...</span></div>'; // Style loader
    loadingIndicator.style.display = 'none'; // Sembunyikan awal
    // Sisipkan indikator sebelum wrapper galeri
    if (galleryWrapper.parentNode) {
      galleryWrapper.parentNode.insertBefore(loadingIndicator, galleryWrapper);
    }


    let debounceTimer;

    function fetchGallery(page = 1, scrollToTop = false) {
      loadingIndicator.style.display = 'flex'; // Tampilkan loading (flex agar center)
      galleryWrapper.style.opacity = '0.5'; // Redupkan konten lama

      const formData = new FormData(form);
      formData.append('ajax', '1'); // Tandai sebagai request AJAX
      formData.set('p', page); // Set halaman yang diminta (set agar mengganti yang lama jika ada)

      const params = new URLSearchParams(formData).toString();

      // Update URL di browser tanpa reload
      const url = new URL(window.location);
      url.search = params.replace('&ajax=1', '').replace(/&?p=1$/, '').replace(/&p=1&/, '&'); // Hapus ajax=1 dan p=1 dari URL browser
      history.pushState({
        page: page
      }, '', url); // Simpan state halaman

      fetch('/galeri.php?' + params) // Kirim request ke galeri.php
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response error: ' + response.statusText);
          }
          return response.json();
        })
        .then(data => {
          if (data && data.html) {
            galleryWrapper.innerHTML = data.html; // Ganti konten galeri
          } else {
            // Cek jika HTML kosong karena tidak ada hasil
            if (data && data.html === '') {
              galleryWrapper.innerHTML = '<p class="text-center text-gray-500 dark:text-gray-400 py-10">Tidak ada karya yang cocok dengan filter Anda.</p>';
            } else {
              galleryWrapper.innerHTML = '<p class="text-center text-red-500 py-10">Gagal memuat data galeri. Respon tidak valid.</p>';
            }
          }
        })
        .catch(error => {
          console.error('Error fetching gallery:', error);
          galleryWrapper.innerHTML = `<p class="text-center text-red-500 py-10">Terjadi kesalahan saat memuat galeri (${error.message}). Coba lagi nanti.</p>`;
        })
        .finally(() => {
          loadingIndicator.style.display = 'none'; // Sembunyikan loading
          galleryWrapper.style.opacity = '1'; // Kembalikan opacity
          if (scrollToTop) {
            const filterSection = document.querySelector('section.sticky'); // Target section filter
            if (filterSection) {
              window.scrollTo({
                top: filterSection.offsetTop + filterSection.offsetHeight, // Scroll ke bawah filter
                behavior: 'smooth'
              });
            } else {
              window.scrollTo({
                top: 0,
                behavior: 'smooth'
              }); // Fallback scroll ke atas
            }
          }
        });
    }

    // Listener untuk input filter
    inputs.forEach(input => {
      input.addEventListener('change', function() {
        // Untuk <select>
        fetchGallery(1, true); // Selalu kembali ke halaman 1 dan scroll
      });

      if (input.type === 'text' && input.name === 'q') {
        // Untuk search bar (debounce)
        input.addEventListener('input', function() { // Gunakan 'input' agar lebih responsif
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(() => {
            fetchGallery(1, false); // Kembali ke halaman 1, tidak perlu scroll
          }, 450); // Tunggu 450ms (sedikit lebih lama)
        });
      }
    });

    // Handle klik paginasi via AJAX (delegasi ke body)
    document.body.addEventListener('click', function(e) {
      const link = e.target.closest('#pagination-container a');
      // Pastikan link ada, bukan ellipsis, dan bukan link yang disabled
      if (link && link.getAttribute('href') !== '#' && !link.querySelector('span.cursor-not-allowed')) {
        e.preventDefault();
        const url = new URL(link.href);
        const page = url.searchParams.get('p') || 1;
        fetchGallery(page, true); // Panggil fetchGallery dengan halaman baru dan scroll
      }
    });

    // Handle back/forward browser button
    window.addEventListener('popstate', function(event) {
      // Ambil halaman dari state atau default ke 1
      const page = event.state && event.state.page ? event.state.page : 1;
      // Ambil filter dari URL saat ini
      const urlParams = new URLSearchParams(window.location.search);
      document.getElementById('q').value = urlParams.get('q') || '';
      document.getElementById('kategori').value = urlParams.get('kategori') || '';
      document.getElementById('sort').value = urlParams.get('sort') || 'terbaru';

      fetchGallery(page, false); // Fetch tanpa scroll
    });

  });
</script>
<!-- Akhir Perbaikan -->


<?php require_once 'includes/footer.php'; ?>