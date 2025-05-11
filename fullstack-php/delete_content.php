<?php
require_once 'model/pdo.php';

// Kiểm tra nếu có tham số 'id' trong URL
if (isset($_GET['id'])) {
    $contentId = $_GET['id'];

    // Lấy thông tin content từ cơ sở dữ liệu dựa trên ID
    $sql = "SELECT * FROM content WHERE content_id = ?";
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
        $sqlDelete = "DELETE FROM content WHERE content_id = ?";
        pdo_execute($sqlDelete, $contentId);
    }

    // Chuyển hướng về trang index.php với filter hiện tại
    $returnFilter = isset($_GET['returnFilter']) ? $_GET['returnFilter'] : 'all';
    header('Location: index.php?filter=' . $returnFilter);
    exit();
}
