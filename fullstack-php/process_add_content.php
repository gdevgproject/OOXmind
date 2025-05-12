<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once 'model/pdo.php';

if (isset($_POST['save'])) {
    // Lấy dữ liệu từ form
    $vocab = $_POST['newVocab'];
    $part_of_speech = str_replace("()", "", $_POST['newPartOfSpeech']);  // Xử lý trực tiếp
    $ipa = $_POST['newIPA'];
    $def = $_POST['newDef'];
    $ex = $_POST['newExample'];
    $question = $_POST['newQuestion'];
    $answer = $_POST['newAnswer'];
    $is_active = isset($_POST['newIsActive']) ? 1 : 0;  // Check if the checkbox was checked

    // Get the filter to return to
    $returnFilter = $_POST['returnFilter'] ?? 'all';

    // Tạo tên file chung theo thời gian
    $timestamp = date('s_i_G_j_n_Y');

    // Lưu file (nếu có)
    $imagePath = !empty($_FILES['newImage']['name']) ? saveFile('newImage', 'image', $timestamp) : '';
    $videoPath = !empty($_FILES['newVideo']['name']) ? saveFile('newVideo', 'video', $timestamp) : '';
    $audioPath = !empty($_FILES['newAudio']['name']) ? saveFile('newAudio', 'audio', $timestamp) : '';

    // Thực hiện insert vào cơ sở dữ liệu với trường is_active
    $sql = "INSERT INTO content (vocab, part_of_speech, ipa, def, ex, question, answer, image_path, video_path, audio_path, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    pdo_execute($sql, $vocab, $part_of_speech, $ipa, $def, $ex, $question, $answer, $imagePath, $videoPath, $audioPath, $is_active);

    // Chuyển hướng về index.php với filter hiện tại
    header("Location: index.php?filter=$returnFilter");
    exit();
}

function saveFile($fileInputName, $folder, $timestamp)
{
    $targetDir = "view/uploads/$folder/";
    $extension = pathinfo($_FILES[$fileInputName]["name"], PATHINFO_EXTENSION);

    // Tạo tên file theo timestamp
    $newFileName = $timestamp . "_$folder.$extension";
    $targetFile = $targetDir . $newFileName;

    if (move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $targetFile)) {
        return $targetFile;
    } else {
        echo "Sorry, there was an error uploading your file.";
        return "";
    }
}
