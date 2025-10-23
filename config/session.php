<?php
// Session configuration before starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Check if user is logged in
function isLoggedIn()
{
  return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser()
{
  if (!isLoggedIn()) {
    return null;
  }

  require_once __DIR__ . '/database.php';
  $conn = getDBConnection();

  $stmt = $conn->prepare("SELECT id, username, email, peran, nama_lengkap, foto_profil, status FROM Pengguna WHERE id = ?");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();

  $stmt->close();
  $conn->close();

  return $user;
}

// Check if user has specific role
function hasRole($role)
{
  $user = getCurrentUser();
  return $user && $user['peran'] === $role;
}

// Require login
function requireLogin()
{
  if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /login.php');
    exit;
  }
}

// Require specific role
function requireRole($role)
{
  requireLogin();
  if (!hasRole($role)) {
    header('Location: /index.php');
    exit;
  }
}

// Login user
function loginUser($user_id)
{
  $_SESSION['user_id'] = $user_id;
  $_SESSION['login_time'] = time();
}

// Logout user
function logoutUser()
{
  session_unset();
  session_destroy();
  header('Location: /index.php');
  exit;
}

// Get unread notifications count
function getUnreadNotificationsCount()
{
  if (!isLoggedIn()) {
    return 0;
  }

  require_once __DIR__ . '/database.php';
  $conn = getDBConnection();

  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Notifikasi WHERE pengguna_id = ? AND status = 'belum dibaca'");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();

  $stmt->close();
  $conn->close();

  return $row['count'];
}
