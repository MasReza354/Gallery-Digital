<?php
$page_title = 'Detail Karya - Galeri Karya Siswa';
require_once 'includes/header.php';

$artwork_id = (int)($_GET['id'] ?? 0);

if ($artwork_id <= 0) {
  header('Location: /galeri.php');
  exit;
}

$conn = getDBConnection();

// Get artwork details
$stmt = $conn->prepare("SELECT k.*, p.nama_lengkap as nama_siswa, p.foto_profil, p.email,
                               kat.nama as kategori_nama, kat.deskripsi as kategori_deskripsi,
                               COUNT(DISTINCT s.id) as jumlah_suka
                        FROM Karya k
                        LEFT JOIN Pengguna p ON k.siswa_id = p.id
                        LEFT JOIN Kategori kat ON k.kategori_id = kat.id
                        LEFT JOIN Suka s ON k.id = s.karya_id
                        WHERE k.id = ? AND k.status = 'disetujui'
                        GROUP BY k.id");
$stmt->bind_param("i", $artwork_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header('Location: /galeri.php');
  exit;
}

$karya = $result->fetch_assoc();
$stmt->close();

// Get feedback from teachers
$feedback_stmt = $conn->prepare("SELECT ub.*, p.nama_lengkap as nama_guru, p.foto_profil
                                FROM Umpan_Balik ub
                                LEFT JOIN Pengguna p ON ub.guru_id = p.id
                                WHERE ub.karya_id = ?
                                ORDER BY ub.dibuat_pada DESC");
$feedback_stmt->bind_param("i", $artwork_id);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();
$feedback_stmt->close();

// Check if current user liked this artwork
$user_ip = getUserIP();
$like_stmt = $conn->prepare("SELECT id FROM Suka WHERE karya_id = ? AND user_ip = ?");
$like_stmt->bind_param("is", $artwork_id, $user_ip);
$like_stmt->execute();
$is_liked = $like_stmt->get_result()->num_rows > 0;
$like_stmt->close();

// Get similar artworks
$similar_stmt = $conn->prepare("SELECT k2.*, p2.nama_lengkap as nama_siswa, p2.foto_profil,
                                       COUNT(DISTINCT s2.id) as jumlah_suka
                                FROM Karya k2
                                LEFT JOIN Pengguna p2 ON k2.siswa_id = p2.id
                                LEFT JOIN Suka s2 ON k2.id = s2.karya_id
                                WHERE k2.status = 'disetujui'
                                AND (k2.kategori_id = ? OR k2.siswa_id IN (
                                    SELECT DISTINCT k3.siswa_id FROM Karya k3
                                    WHERE k3.kategori_id = ? AND k3.status = 'disetujui'
                                ))
                                AND k2.id != ?
                                GROUP BY k2.id
                                ORDER BY k2.dibuat_pada DESC
                                LIMIT 6");
$similar_stmt->bind_param("iii", $karya['kategori_id'], $karya['kategori_id'], $artwork_id);
$similar_stmt->execute();
$similar_result = $similar_stmt->get_result();
$similar_stmt->close();

$conn->close();

$file_type = getFileType($karya['media_url']);
?>

<!-- Breadcrumb -->
<nav class="bg-gray-100 dark:bg-gray-800 py-3">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center space-x-2 text-sm">
      <a href="/index.php" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">Beranda</a>
      <i class="fas fa-chevron-right text-gray-400"></i>
      <a href="/galeri.php" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">Galeri</a>
      <i class="fas fa-chevron-right text-gray-400"></i>
      <span class="text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($karya['judul']); ?></span>
    </div>
  </div>
</nav>

<!-- Artwork Details -->
<section class="py-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Artwork Display -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden mb-6">
          <div class="relative">
            <?php if ($file_type === 'image'): ?>
              <img src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" alt="<?php echo htmlspecialchars($karya['judul']); ?>" class="w-full max-h-96 lg:max-h-[600px] object-contain bg-gray-50 dark:bg-gray-900">
            <?php elseif ($file_type === 'video'): ?>
              <video controls class="w-full max-h-96 lg:max-h-[600px] bg-black">
                <source src="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" type="video/<?php echo pathinfo($karya['media_url'], PATHINFO_EXTENSION); ?>">
                Browser Anda tidak mendukung pemutaran video.
              </video>
            <?php else: ?>
              <div class="w-full h-96 lg:h-[600px] bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
                <a href="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" target="_blank" class="bg-blue-600 text-white px-8 py-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                  <i class="fas fa-download mr-2"></i>Download File
                </a>
              </div>
            <?php endif; ?>

            <?php if ($karya['unggulan']): ?>
              <div class="absolute top-4 left-4 bg-yellow-500 text-white px-3 py-2 rounded-full text-sm font-semibold shadow-lg">
                <i class="fas fa-star mr-1"></i> Karya Unggulan
              </div>
            <?php endif; ?>

            <!-- Like Button -->
            <div class="absolute top-4 right-4">
              <form method="POST" action="/suka.php" id="like-form-main">
                <input type="hidden" name="karya_id" value="<?php echo $karya['id']; ?>">
                <button type="submit" class="bg-white/90 dark:bg-gray-800/90 text-gray-800 dark:text-white px-4 py-3 rounded-full hover:bg-white dark:hover:bg-gray-700 transition-colors shadow-lg <?php echo $is_liked ? 'text-red-600' : ''; ?>" onclick="handleLike(this, event)">
                  <i class="fas fa-heart <?php echo $is_liked ? 'text-red-600' : 'text-gray-400'; ?>"></i>
                  <span class="ml-2 font-semibold"><?php echo $karya['jumlah_suka']; ?></span>
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-4 mb-8">
          <button onclick="shareArtwork()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
            <i class="fas fa-share mr-2"></i>Bagikan
          </button>

          <?php if ($file_type !== 'video'): ?>
            <a href="/uploads/karya/<?php echo htmlspecialchars($karya['media_url']); ?>" download class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors flex items-center">
              <i class="fas fa-download mr-2"></i>Download
            </a>
          <?php endif; ?>

          <?php if (isLoggedIn() && (getCurrentUser()['peran'] === 'guru' || getCurrentUser()['peran'] === 'administrator')): ?>
            <a href="/review.php?id=<?php echo $karya['id']; ?>" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors flex items-center">
              <i class="fas fa-comment mr-2"></i>Beri Umpan Balik
            </a>
          <?php endif; ?>

          <?php if (isLoggedIn() && getCurrentUser()['id'] === $karya['siswa_id']): ?>
            <a href="/edit-karya.php?id=<?php echo $karya['id']; ?>" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors flex items-center">
              <i class="fas fa-edit mr-2"></i>Edit Karya
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Artwork Info Sidebar -->
      <div class="lg:col-span-1">
        <div class="space-y-6">
          <!-- Basic Info -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4"><?php echo htmlspecialchars($karya['judul']); ?></h1>

            <div class="space-y-3 mb-6">
              <div class="flex items-center">
                <i class="fas fa-user text-gray-400 w-5"></i>
                <span class="ml-3 text-gray-600 dark:text-gray-400">Oleh <strong><?php echo htmlspecialchars($karya['nama_siswa']); ?></strong></span>
              </div>

              <div class="flex items-center">
                <i class="fas fa-tag text-gray-400 w-5"></i>
                <span class="ml-3 text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($karya['kategori_nama'] ?? 'Tanpa Kategori'); ?></span>
              </div>

              <div class="flex items-center">
                <i class="fas fa-calendar text-gray-400 w-5"></i>
                <span class="ml-3 text-gray-600 dark:text-gray-400"><?php echo formatTanggal($karya['dibuat_pada']); ?></span>
              </div>

              <div class="flex items-center">
                <i class="fas fa-heart text-red-500 w-5"></i>
                <span class="ml-3 text-gray-600 dark:text-gray-400"><?php echo $karya['jumlah_suka']; ?> suka</span>
              </div>

              <div class="flex items-center">
                <i class="fas fa-file text-gray-400 w-5"></i>
                <span class="ml-3 text-gray-600 dark:text-gray-400"><?php echo ucfirst($file_type); ?></span>
              </div>
            </div>

            <!-- Creator Profile -->
            <div class="border-t dark:border-gray-600 pt-6">
              <a href="/profil.php?id=<?php echo $karya['siswa_id']; ?>" class="flex items-center hover:bg-gray-50 dark:hover:bg-gray-700 p-3 rounded-lg transition-colors">
                <img src="/uploads/profil/<?php echo htmlspecialchars($karya['foto_profil']); ?>" alt="Creator" class="w-12 h-12 rounded-full object-cover" onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\'fas fa-user text-gray-400\'></i>' + this.parentNode.innerHTML.substring(this.outerHTML.length);">
                <div class="ml-4">
                  <h3 class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($karya['nama_siswa']); ?></h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Lihat profil lengkap →</p>
                </div>
              </a>
            </div>
          </div>

          <!-- Description -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Deskripsi</h3>
            <div class="text-gray-600 dark:text-gray-400 whitespace-pre-line">
              <?php echo nl2br(htmlspecialchars($karya['deskripsi'] ?: 'Tidak ada deskripsi yang tersedia.')); ?>
            </div>
          </div>

          <!-- Tags/Categories -->
          <?php if ($karya['kategori_id']): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Kategori</h3>
              <div class="flex flex-wrap gap-2">
                <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-sm">
                  <?php echo htmlspecialchars($karya['kategori_nama']); ?>
                </span>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                <?php echo htmlspecialchars($karya['kategori_deskripsi'] ?: 'Kategori seni dan kreativitas'); ?>
              </p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Feedback Section -->
    <?php if ($feedback_result->num_rows > 0): ?>
      <div class="mt-12">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Umpan Balik dari Guru/Kurator</h3>
        <div class="space-y-4">
          <?php while ($feedback = $feedback_result->fetch_assoc()): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
              <div class="flex items-start space-x-4">
                <img src="/uploads/profil/<?php echo htmlspecialchars($feedback['foto_profil']); ?>" alt="Guru" class="w-10 h-10 rounded-full object-cover" onerror="this.src='/assets/default_avatar.png'">
                <div class="flex-1">
                  <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($feedback['nama_guru']); ?></h4>
                    <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo getRelativeTime($feedback['dibuat_pada']); ?></span>
                  </div>
                  <div class="text-gray-600 dark:text-gray-400 whitespace-pre-line">
                    <?php echo nl2br(htmlspecialchars($feedback['umpan_balik'])); ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Similar Artworks -->
    <?php if ($similar_result->num_rows > 0): ?>
      <div class="mt-16">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Karya Serupa</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php while ($similar = $similar_result->fetch_assoc()): ?>
            <a href="/karya.php?id=<?php echo $similar['id']; ?>" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
              <div class="relative">
                <?php
                $similar_file_type = getFileType($similar['media_url']);
                if ($similar_file_type === 'image'):
                ?>
                  <img src="/uploads/karya/<?php echo htmlspecialchars($similar['media_url']); ?>" alt="<?php echo htmlspecialchars($similar['judul']); ?>" class="w-full h-32 object-cover">
                <?php elseif ($similar_file_type === 'video'): ?>
                  <video class="w-full h-32 object-cover" muted>
                    <source src="/uploads/karya/<?php echo htmlspecialchars($similar['media_url']); ?>" type="video/<?php echo pathinfo($similar['media_url'], PATHINFO_EXTENSION); ?>">
                  </video>
                <?php else: ?>
                  <div class="w-full h-32 bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                    <i class="fas fa-file-alt text-xl text-gray-400"></i>
                  </div>
                <?php endif; ?>

                <?php if ($similar['unggulan']): ?>
                  <div class="absolute top-1 right-1 bg-yellow-500 text-white p-1 rounded-full text-xs">
                    <i class="fas fa-star"></i>
                  </div>
                <?php endif; ?>
              </div>

              <div class="p-4">
                <h4 class="font-semibold text-gray-900 dark:text-white text-sm mb-1"><?php echo htmlspecialchars($similar['judul']); ?></h4>
                <p class="text-gray-600 dark:text-gray-400 text-xs"><?php echo htmlspecialchars($similar['nama_siswa']); ?></p>
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                  <span><?php echo htmlspecialchars($similar['kategori_nama'] ?? 'Tanpa Kategori'); ?></span>
                  <span><i class="fas fa-heart mr-1"></i><?php echo $similar['jumlah_suka']; ?></span>
                </div>
              </div>
            </a>
          <?php endwhile; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
  // Handle like button
  function handleLike(button, event) {
    event.preventDefault();

    const form = button.closest('form');
    const formData = new FormData(form);

    fetch('/suka.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const icon = button.querySelector('i');
          const count = button.querySelector('span');

          if (data.action === 'liked') {
            button.classList.add('text-red-600');
            icon.classList.add('text-red-600');
            icon.classList.remove('text-gray-400');
          } else {
            button.classList.remove('text-red-600');
            icon.classList.remove('text-red-600');
            icon.classList.add('text-gray-400');
          }

          if (count) {
            count.textContent = data.newCount;
          }
        } else {
          alert('Terjadi kesalahan saat memproses permintaan');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memproses permintaan');
      });
  }

  // Share artwork function
  function shareArtwork() {
    if (navigator.share) {
      navigator.share({
        title: '<?php echo htmlspecialchars($karya['judul']); ?>',
        text: 'Lihat karya "<?php echo htmlspecialchars($karya['judul']); ?>" di Galeri Karya Siswa',
        url: window.location.href
      });
    } else {
      // Fallback: copy to clipboard
      navigator.clipboard.writeText(window.location.href).then(function() {
        alert('Link telah disalin ke clipboard!');
      });
    }
  }
</script>

<?php require_once 'includes/footer.php'; ?>