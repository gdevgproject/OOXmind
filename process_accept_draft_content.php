<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once 'model/pdo.php';

function copyAndRenameFile($oldFilePath, $folder) {
    if (!$oldFilePath || !file_exists($oldFilePath)) {
        return "";
    }

    $extension = pathinfo($oldFilePath, PATHINFO_EXTENSION);
    $newFileName = date('s_i_G_j_n_Y') . "_$folder.$extension";
    $newFilePath = "view/uploads/$folder/$newFileName";

    return copy($oldFilePath, $newFilePath) ? $newFilePath : "";
}

function insertDraftContent($conn, $row, $newImagePath, $newVideoPath, $newAudioPath) {
    // Sử dụng cùng logic mapping fields
    $fields = [
        'vocab', 'part_of_speech', 'ipa', 'def', 'ex', 'question', 'answer',  
        'level', 'correct_count', 'incorrect_count', 'create_time',
        'last_review', 'next_review', 'response_time', 'is_recovery'
    ];
    
    $values = array_map(function($field) use ($row) {
        return $row[$field] ?? null;  
    }, $fields);

    // Thêm media paths và trạng thái accepted
    $values[] = $newImagePath;
    $values[] = $newVideoPath;
    $values[] = $newAudioPath;
    $values[] = 0; // accepted status

    $sql = "INSERT INTO draft_content (" .
           implode(', ', $fields) .
           ", image_path, video_path, audio_path, accepted) VALUES (" . 
           str_repeat('?,', count($fields) + 3) . "?)";

    $stmt = $conn->prepare($sql); 
    $stmt->execute($values);
}

function processContentToDraft($contentId) {
    try {
        $conn = pdo_get_connection();
        
        // Fetch content data with all fields
        $stmt = $conn->prepare("SELECT * FROM content WHERE content_id = ?"); 
        $stmt->execute([$contentId]);
        $content = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$content) {
            throw new Exception("Content not found");
        }

        // Copy files with new names
        $newImagePath = copyAndRenameFile($content['image_path'], 'image');
        $newVideoPath = copyAndRenameFile($content['video_path'], 'video');
        $newAudioPath = copyAndRenameFile($content['audio_path'], 'audio');

        // Insert into draft_content including all fields
        insertDraftContent($conn, $content, $newImagePath, $newVideoPath, $newAudioPath);

        // Update content status
        $stmt = $conn->prepare("UPDATE content SET accepted = 1 WHERE content_id = ?");
        $stmt->execute([$contentId]);

        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        error_log("Error in processContentToDraft: " . $e->getMessage()); 
        header("Location: index.php?error=1");
        exit();
    }
}

if (isset($_GET['id'])) {
    processContentToDraft($_GET['id']);
} else {
    header("Location: index.php");
    exit(); 
}