<?php
require_once 'model/pdo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['total_time_spent'])) {
    $total_time_spent = (int) $_POST['total_time_spent'];
    // Cập nhật tổng thời gian
    pdo_execute("UPDATE activity_log SET total_time_spent = ? WHERE activity_date = CURDATE()", $total_time_spent);
  }

  if (isset($_POST['add_time'])) {
    $add_time = (int) $_POST['add_time'];
    // Cộng thêm thời gian
    pdo_execute("UPDATE activity_log SET total_time_spent = total_time_spent + ? WHERE activity_date = CURDATE()", $add_time);
  }

  if (isset($_POST['close_time'])) {
    $close_time = $_POST['close_time'];
    // Cập nhật close_time
    pdo_execute("UPDATE activity_log SET close_time = ? WHERE activity_date = CURDATE()", $close_time);
  }
}
