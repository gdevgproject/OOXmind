<?php
// Kiểm tra xem file pdo.php đã được include hay chưa
if (!in_array("model/pdo.php", get_included_files())) {
    // Nếu chưa, include nó
    require_once 'model/pdo.php';
}

// Kiểm tra nếu có tham số 'id' trong URL
if (isset($_GET['id'])) {
    $contentId = $_GET['id'];

    // Lấy thông tin draft content từ cơ sở dữ liệu
    $sql = "SELECT * FROM draft_content WHERE draft_id = ?";
    $stmt = pdo_execute($sql, $contentId);
    $content = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

    // Kiểm tra nếu tìm thấy content
    if ($content) {
        // Xóa các file liên quan nếu chúng tồn tại
        if (file_exists($content['image_path'])) {
            unlink($content['image_path']);
        }
        if (file_exists($content['video_path'])) {
            unlink($content['video_path']);
        }
        if (file_exists($content['audio_path'])) {
            unlink($content['audio_path']);
        }

        // Xóa dữ liệu từ cơ sở dữ liệu
        $sqlDelete = "DELETE FROM draft_content WHERE draft_id = ?";
        pdo_execute($sqlDelete, $contentId);
    }

    // Chuyển hướng về trang draft_content.php
    header('Location: draft_content.php');
    exit();
}
