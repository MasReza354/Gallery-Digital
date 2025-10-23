<?php
header('Content-Type: application/json');
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit;
}

$karya_id = (int)($_POST['karya_id'] ?? 0);
$user_ip = getUserIP();

if ($karya_id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid artwork ID']);
  exit;
}

$conn = getDBConnection();

// Check if like already exists
$check_stmt = $conn->prepare("SELECT id FROM Suka WHERE karya_id = ? AND user_ip = ?");
$check_stmt->bind_param("is", $karya_id, $user_ip);
$check_stmt->execute();
$exists = $check_stmt->get_result()->num_rows > 0;
$check_stmt->close();

if ($exists) {
  // Unlike
  $delete_stmt = $conn->prepare("DELETE FROM Suka WHERE karya_id = ? AND user_ip = ?");
  $delete_stmt->bind_param("is", $karya_id, $user_ip);
  $delete_stmt->execute();
  $delete_stmt->close();
  $action = 'unliked';
} else {
  // Like
  $insert_stmt = $conn->prepare("INSERT INTO Suka (karya_id, user_ip) VALUES (?, ?)");
  $insert_stmt->bind_param("is", $karya_id, $user_ip);
  $insert_stmt->execute();
  $insert_stmt->close();
  $action = 'liked';
}

// Get new like count
$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM Suka WHERE karya_id = ?");
$count_stmt->bind_param("i", $karya_id);
$count_stmt->execute();
$result = $count_stmt->get_result();
$new_count = $result->fetch_assoc()['count'];
$count_stmt->close();

$conn->close();

echo json_encode([
  'success' => true,
  'action' => $action,
  'newCount' => $new_count
]);
