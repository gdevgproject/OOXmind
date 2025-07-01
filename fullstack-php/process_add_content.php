<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once 'model/pdo.php';

// Function to get 5 most recent images
function getRecentImages($limit = 5)
{
    $imageFolder = "view/uploads/image/";
    if (!is_dir($imageFolder)) {
        return [];
    }

    $images = [];
    $files = scandir($imageFolder);

    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($imageFolder . $file)) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $images[] = [
                    'name' => $file,
                    'path' => $imageFolder . $file,
                    'time' => filemtime($imageFolder . $file)
                ];
            }
        }
    }

    // Sort by modification time (newest first)
    usort($images, function ($a, $b) {
        return $b['time'] - $a['time'];
    });

    return array_slice($images, 0, $limit);
}

// Function to copy recent image with new timestamp naming
function copyRecentImage($sourcePath, $timestamp)
{
    if (!file_exists($sourcePath)) {
        return '';
    }

    $targetDir = "view/uploads/image/";
    $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
    $newFileName = $timestamp . "_image.$extension";
    $targetFile = $targetDir . $newFileName;

    if (copy($sourcePath, $targetFile)) {
        return $targetFile;
    }

    return '';
}

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

    // Handle recent image selection
    $imagePath = '';
    if (!empty($_POST['selectedRecentImage'])) {
        // Copy selected recent image with new naming
        $imagePath = copyRecentImage($_POST['selectedRecentImage'], $timestamp);
    } elseif (!empty($_FILES['newImage']['name'])) {
        // Use uploaded image
        $imagePath = saveFile('newImage', 'image', $timestamp);
    }

    // Lưu file (nếu có)
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
