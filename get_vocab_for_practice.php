<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once 'model/pdo.php';

// Kiểm tra nếu yêu cầu là POST và nút practice được nhấp
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["practice"])) {
    // Kết nối cơ sở dữ liệu
    $conn = pdo_get_connection();

    // Truy vấn dữ liệu từ cơ sở dữ liệu
    $sql = "SELECT vocab, def FROM content WHERE response_time > 40000";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // Lấy kết quả từ truy vấn
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Trả về dữ liệu dưới dạng JSON
    header('Content-Type: application/json');
    echo json_encode($results);
    exit();
} else {
    // Nếu không phải yêu cầu POST hoặc không có tham số "practice", trả về lỗi
    http_response_code(400);
    echo json_encode(array("message" => "Bad request"));
    exit();
}
