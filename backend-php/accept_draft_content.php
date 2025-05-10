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

function processDraftContent($draftId)
{
    $conn = pdo_get_connection(); // Kết nối CSDL
    $row = fetchDraftContent($conn, $draftId); // Lấy nội dung nháp

    // Xử lý và sao chép các tệp
    $newImagePath = copyAndRenameFile($row['image_path'], 'image');
    $newVideoPath = copyAndRenameFile($row['video_path'], 'video');
    $newAudioPath = copyAndRenameFile($row['audio_path'], 'audio');

    insertContent($conn, $row, $newImagePath, $newVideoPath, $newAudioPath); // Chèn nội dung
    updateDraftContentStatus($conn, $draftId); // Cập nhật trạng thái nháp

    redirect('home.php');
}

function fetchDraftContent($conn, $draftId)
{
    $sql = "SELECT * FROM draft_content WHERE draft_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $draftId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function insertContent($conn, $row, $newImagePath, $newVideoPath, $newAudioPath)
{
    $sql = "INSERT INTO content (vocab, part_of_speech, ipa, def, ex, question, answer, image_path, video_path, audio_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $row['vocab'],
        $row['part_of_speech'],
        $row['ipa'],
        $row['def'],
        $row['ex'],
        $row['question'],
        $row['answer'],
        $newImagePath,
        $newVideoPath,
        $newAudioPath
    ]);
}

function updateDraftContentStatus($conn, $draftId)
{
    $sql = "UPDATE draft_content SET accepted = 1 WHERE draft_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $draftId, PDO::PARAM_INT);
    $stmt->execute();
}

function redirect($location)
{
    echo "<script>window.location.href = '$location';</script>";
}


if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
    processDraftContent($_GET["id"]); // Xử lý nội dung nếu có tham số GET
}
