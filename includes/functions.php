<?php
// Helper functions for the application

// Sanitize input
function sanitize($data)
{
  return htmlspecialchars(strip_tags(trim($data)));
}

// Format date to Indonesian format
function formatTanggal($datetime)
{
  $bulan = [
    1 => 'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
  ];

  $timestamp = strtotime($datetime);
  $hari = date('d', $timestamp);
  $bulanNum = date('n', $timestamp);
  $tahun = date('Y', $timestamp);

  return $hari . ' ' . $bulan[$bulanNum] . ' ' . $tahun;
}

// Get relative time (e.g., "2 jam yang lalu")
function getRelativeTime($datetime)
{
  $timestamp = strtotime($datetime);
  $diff = time() - $timestamp;

  if ($diff < 60) {
    return 'Baru saja';
  } elseif ($diff < 3600) {
    $minutes = floor($diff / 60);
    return $minutes . ' menit yang lalu';
  } elseif ($diff < 86400) {
    $hours = floor($diff / 3600);
    return $hours . ' jam yang lalu';
  } elseif ($diff < 604800) {
    $days = floor($diff / 86400);
    return $days . ' hari yang lalu';
  } else {
    return formatTanggal($datetime);
  }
}

// Upload file with validation
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'mp4', 'mov'])
{
  if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
    return ['success' => false, 'message' => 'Error uploading file'];
  }

  $fileName = $file['name'];
  $fileTmpName = $file['tmp_name'];
  $fileSize = $file['size'];
  $fileError = $file['error'];

  // Get file extension
  $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

  // Validate file type
  if (!in_array($fileExt, $allowedTypes)) {
    return ['success' => false, 'message' => 'File type not allowed'];
  }

  // Validate file size (max 10MB)
  if ($fileSize > 10485760) {
    return ['success' => false, 'message' => 'File size too large (max 10MB)'];
  }

  // Generate unique file name
  $newFileName = uniqid('', true) . '.' . $fileExt;
  $targetPath = $targetDir . '/' . $newFileName;

  // Create directory if not exists
  if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
  }

  // Move uploaded file
  if (move_uploaded_file($fileTmpName, $targetPath)) {
    return ['success' => true, 'filename' => $newFileName, 'path' => $targetPath];
  }

  return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Create notification
function createNotification($pengguna_id, $pesan, $link = '#')
{
  require_once __DIR__ . '/../config/database.php';
  $conn = getDBConnection();

  $stmt = $conn->prepare("INSERT INTO Notifikasi (pengguna_id, pesan, link_tujuan) VALUES (?, ?, ?)");
  $stmt->bind_param("iss", $pengguna_id, $pesan, $link);
  $result = $stmt->execute();

  $stmt->close();
  $conn->close();

  return $result;
}

// Get file type from extension
function getFileType($filename)
{
  $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

  $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
  $videoTypes = ['mp4', 'mov', 'avi', 'wmv', 'flv'];
  $documentTypes = ['pdf', 'doc', 'docx', 'txt'];

  if (in_array($ext, $imageTypes)) {
    return 'image';
  } elseif (in_array($ext, $videoTypes)) {
    return 'video';
  } elseif (in_array($ext, $documentTypes)) {
    return 'document';
  }

  return 'unknown';
}

// Truncate text
function truncateText($text, $length = 100)
{
  if (strlen($text) <= $length) {
    return $text;
  }

  return substr($text, 0, $length) . '...';
}

// Generate simple math captcha
function generateCaptcha()
{
  $num1 = rand(1, 10);
  $num2 = rand(1, 10);
  $answer = $num1 + $num2;

  $_SESSION['captcha_answer'] = $answer;

  return "$num1 + $num2 = ?";
}

// Verify captcha
function verifyCaptcha($userAnswer)
{
  if (!isset($_SESSION['captcha_answer'])) {
    return false;
  }

  $correct = (int)$_SESSION['captcha_answer'] === (int)$userAnswer;

  // Only unset if the answer is correct, so user can retry with wrong answers
  if ($correct) {
    unset($_SESSION['captcha_answer']);
  }

  return $correct;
}

// Get user IP address
function getUserIP()
{
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    return $_SERVER['HTTP_CLIENT_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    return $_SERVER['HTTP_X_FORWARDED_FOR'];
  } else {
    return $_SERVER['REMOTE_ADDR'];
  }
}
