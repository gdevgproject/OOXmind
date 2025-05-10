<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once 'model/pdo.php';

function deleteFile($filePath)
{
    if ($filePath) {
        unlink($filePath);
    }
}

if (isset($_POST['update'])) {
    $id = $_POST['editId'];
    $newVocab = $_POST['editVocab'];
    $newPartOfSpeech = $_POST['editPartOfSpeech'];
    $newIPA = $_POST['editIPA'];
    $newDef = $_POST['editDef'];
    $newExample = $_POST['editExample'];
    $newQuestion = $_POST['editQuestion'];
    $newAnswer = $_POST['editAnswer'];
    $isActive = isset($_POST['editIsActive']) ? 1 : 0;  // Check if the checkbox was checked
    // Nhận URL hiện tại từ form
    $currentUrl = $_POST['currentUrl'];

    // Kết nối cơ sở dữ liệu một lần
    $pdo = pdo_get_connection();

    // Lấy đường dẫn file hiện tại từ cơ sở dữ liệu
    $stmt = $pdo->prepare("SELECT image_path, video_path, audio_path FROM content WHERE content_id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Xử lý file mới được upload (nếu có)
    $timestamp = date('s_i_G_j_n_Y');

    // Image
    $newImagePath = $row['image_path']; // Mặc định giữ nguyên nếu không upload mới
    if ($_FILES['editImage']['size'] > 0) {
        deleteFile($row['image_path']);
        $newImagePath = "view/uploads/image/{$timestamp}_image." . pathinfo($_FILES['editImage']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['editImage']['tmp_name'], $newImagePath);
    }

    // Video
    $newVideoPath = $row['video_path'];
    if ($_FILES['editVideo']['size'] > 0) {
        deleteFile($row['video_path']);
        $newVideoPath = "view/uploads/video/{$timestamp}_video." . pathinfo($_FILES['editVideo']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['editVideo']['tmp_name'], $newVideoPath);
    }

    // Audio
    $newAudioPath = $row['audio_path'];
    if ($_FILES['editAudio']['size'] > 0) {
        deleteFile($row['audio_path']);
        $newAudioPath = "view/uploads/audio/{$timestamp}_audio." . pathinfo($_FILES['editAudio']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['editAudio']['tmp_name'], $newAudioPath);
    }

    // Cập nhật cơ sở dữ liệu, including is_active field
    $stmt = $pdo->prepare("UPDATE content SET vocab=?, part_of_speech=?, ipa=?, def=?, ex=?, question=?, answer=?, image_path=?, video_path=?, audio_path=?, is_active=? WHERE content_id=?");
    $stmt->execute([$newVocab, $newPartOfSpeech, $newIPA, $newDef, $newExample, $newQuestion, $newAnswer, $newImagePath, $newVideoPath, $newAudioPath, $isActive, $id]);

    // Chuyển hướng sau khi cập nhật về URL ban đầu
    header("Location: " . $currentUrl);
    exit();
}
