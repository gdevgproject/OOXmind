<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once 'model/pdo.php';


function copyAndRenameFile($oldFilePath, $folder)
{
    if (!$oldFilePath || !file_exists($oldFilePath)) {
        return ""; // Trả về rỗng nếu đường dẫn không tồn tại hoặc không hợp lệ
    }

    $extension = pathinfo($oldFilePath, PATHINFO_EXTENSION);
    $newFileName = date('s_i_G_j_n_Y') . "_$folder.$extension"; // Đặt tên file mới
    $newFilePath = "view/uploads/$folder/$newFileName"; // Tạo đường dẫn mới

    return copy($oldFilePath, $newFilePath) ? $newFilePath : ""; // Sao chép và trả về đường dẫn mới hoặc rỗng
}

function processDraftContent($draftId, $page)
{
    $conn = pdo_get_connection(); // Kết nối CSDL
    $row = fetchDraftContent($conn, $draftId); // Lấy nội dung nháp

    // Xử lý và sao chép các tệp
    $newImagePath = copyAndRenameFile($row['image_path'], 'image');
    $newVideoPath = copyAndRenameFile($row['video_path'], 'video');
    $newAudioPath = copyAndRenameFile($row['audio_path'], 'audio');

    insertContent($conn, $row, $newImagePath, $newVideoPath, $newAudioPath); // Chèn nội dung
    updateDraftContentStatus($conn, $draftId); // Cập nhật trạng thái nháp

    header("Location: draft_content.php?page=$page");
    exit();
}

function fetchDraftContent($conn, $draftId)
{
    $sql = "SELECT * FROM draft_content WHERE draft_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $draftId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function insertContent($conn, $row, $newImagePath, $newVideoPath, $newAudioPath) {
    // Đảm bảo tất cả các trường đều được map đúng
    $fields = [
        'vocab', 'part_of_speech', 'ipa', 'def', 'ex', 'question', 'answer',
        'level', 'correct_count', 'incorrect_count', 'create_time', 
        'last_review', 'next_review', 'response_time', 'is_recovery'
    ];
    
    $values = array_map(function($field) use ($row) {
        return $row[$field] ?? null;
    }, $fields);
    
    // Thêm các path media mới
    $values[] = $newImagePath;
    $values[] = $newVideoPath; 
    $values[] = $newAudioPath;

    $sql = "INSERT INTO content (" . 
           implode(', ', $fields) . 
           ", image_path, video_path, audio_path) VALUES (" .
           str_repeat('?,', count($fields) + 2) . "?)";

    $stmt = $conn->prepare($sql);
    $stmt->execute($values);
}

function updateDraftContentStatus($conn, $draftId)
{
    $sql = "UPDATE draft_content SET accepted = 1 WHERE draft_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $draftId, PDO::PARAM_INT);
    $stmt->execute();
}

if (isset($_GET['id']) && isset($_GET['page'])) {
    processDraftContent($_GET['id'], $_GET['page']);
} else {
    header("Location: draft_content.php");
    exit();
}
