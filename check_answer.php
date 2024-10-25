<?php
require_once 'model/pdo.php';

// Kiểm tra đáp án
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $contentId = filter_input(INPUT_POST, 'contentId', FILTER_SANITIZE_NUMBER_INT);
    $userAnswer1 = filter_input(INPUT_POST, 'userAnswer1', FILTER_SANITIZE_STRING);
    $userAnswer2 = filter_input(INPUT_POST, 'userAnswer2', FILTER_SANITIZE_STRING);

    try {
        $conn = pdo_get_connection();

        // Sử dụng prepared statement
        $sql = "SELECT vocab, answer FROM content WHERE content_id = :contentId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':contentId', $contentId, PDO::PARAM_INT);
        $stmt->execute();

        // Lấy kết quả một lần
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra đáp án
        if ($row && $userAnswer1 === $row['vocab'] && $userAnswer2 === $row['answer']) {
            echo "<p>Đúng rồi! Tiếp tục với từ vựng tiếp theo.</p>";
        } else {
            echo "<p>Sai rồi! Hãy thử lại.</p>";
        }
    } catch (PDOException $e) {
        // Ghi log lỗi ra file thay vì hiển thị
        error_log("Database error: " . $e->getMessage());
        echo "<p>Lỗi hệ thống. Vui lòng thử lại sau.</p>";
    }
}
