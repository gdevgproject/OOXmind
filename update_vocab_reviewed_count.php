<?php
require_once 'model/pdo.php';
date_default_timezone_set('Asia/Ho_Chi_Minh'); // Set timezone

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['increment_vocab'])) {
  $today = date('Y-m-d');
  pdo_execute("UPDATE activity_log SET vocab_reviewed_count = vocab_reviewed_count + 1 WHERE activity_date = ?", $today);
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
