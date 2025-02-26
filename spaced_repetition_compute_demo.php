<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once 'model/pdo.php';

/**
 * Lấy thông tin content từ database
 */
function getContentInfo($conn, $contentId)
{
    $sql = "SELECT correct_count, incorrect_count, is_recovery, level FROM content WHERE content_id = :content_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':content_id', $contentId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Cập nhật số lần trả lời đúng
 */
function updateCorrectCount($conn, $contentId, $incrementValue = 1)
{
    $sql = "UPDATE content SET correct_count = correct_count + :increment_value WHERE content_id = :content_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':increment_value', $incrementValue, PDO::PARAM_INT);
    $stmt->bindParam(':content_id', $contentId, PDO::PARAM_INT);
    $stmt->execute();
}

/**
 * Cập nhật số lần trả lời sai
 */
function updateIncorrectCount($conn, $contentId, $incrementValue = 1)
{
    $sql = "UPDATE content SET incorrect_count = incorrect_count + :increment_value WHERE content_id = :content_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':increment_value', $incrementValue, PDO::PARAM_INT);
    $stmt->bindParam(':content_id', $contentId, PDO::PARAM_INT);
    $stmt->execute();
}

/**
 * Cập nhật trạng thái phục hồi
 */
function updateRecoveryState($conn, $contentId, $isRecovery)
{
    $sql = "UPDATE content SET is_recovery = :is_recovery WHERE content_id = :content_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':is_recovery', $isRecovery, PDO::PARAM_INT);
    $stmt->bindParam(':content_id', $contentId, PDO::PARAM_INT);
    $stmt->execute();
}

/**
 * Tính toán khoảng thời gian cho lần ôn tập tiếp theo dựa trên level
 */
function calculateNextReviewInterval($level)
{
    $intervals = [
        1 => "70 MINUTE",
        2 => "12 HOUR",
        3 => "23 HOUR",
        4 => "47 HOUR",
        5 => "71 HOUR",
        6 => "359 HOUR",
        7 => "1103 HOUR",
        8 => "3407 HOUR"
    ];

    if ($level <= 0) {
        return "last_review"; // Giữ nguyên thời gian ôn tập cuối
    } elseif ($level > 8) {
        return "8567 HOUR";
    } elseif (isset($intervals[$level])) {
        return $intervals[$level];
    }

    return "60 MINUTE"; // Mặc định cho trường hợp phục hồi
}

/**
 * Cập nhật level và thời gian ôn tập tiếp theo cho nội dung
 */
function updateLevelAndNextReview($conn, $contentId, $level, $currentDateTime, $responseTime, $useRecoveryMode = false)
{
    if ($useRecoveryMode) {
        // Chế độ phục hồi: luôn là 60 phút
        $sql = "UPDATE content SET 
            level = :level,
            last_review = :currentDateTime,
            response_time = :responseTime,
            next_review = DATE_ADD(:currentDateTime, INTERVAL 60 MINUTE) 
            WHERE content_id = :content_id";
    } else {
        // Chế độ bình thường: dựa theo level
        $interval = calculateNextReviewInterval($level);

        if ($interval === "last_review") {
            $sql = "UPDATE content SET 
                level = :level,
                last_review = :currentDateTime,
                response_time = :responseTime,
                next_review = last_review 
                WHERE content_id = :content_id";
        } else {
            $sql = "UPDATE content SET 
                level = :level,
                last_review = :currentDateTime,
                response_time = :responseTime,
                next_review = DATE_ADD(:currentDateTime, INTERVAL $interval) 
                WHERE content_id = :content_id";
        }
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':content_id', $contentId, PDO::PARAM_INT);
    $stmt->bindParam(':currentDateTime', $currentDateTime, PDO::PARAM_STR);
    $stmt->bindParam(':level', $level, PDO::PARAM_INT);
    $stmt->bindParam(':responseTime', $responseTime, PDO::PARAM_INT);
    $stmt->execute();
}

/**
 * Xử lý trường hợp trả lời đúng
 */
function handleCorrectResponse($conn, $contentId, $currentDateTime, $responseTime)
{
    // Lấy thông tin nội dung
    $contentInfo = getContentInfo($conn, $contentId);
    $isRecovery = $contentInfo['is_recovery'];

    if ($isRecovery == 0) {
        // Không phải phục hồi: tăng số lần trả lời đúng
        updateCorrectCount($conn, $contentId);
    } else {
        // Đang phục hồi: đặt lại trạng thái phục hồi
        updateRecoveryState($conn, $contentId, 0);
    }

    // Lấy thông tin sau khi cập nhật để tính level mới
    $updatedInfo = getContentInfo($conn, $contentId);
    $level = $updatedInfo['correct_count'] - $updatedInfo['incorrect_count'];

    // Cập nhật level và thời gian ôn tập tiếp theo
    updateLevelAndNextReview($conn, $contentId, $level, $currentDateTime, $responseTime);
}

/**
 * Xử lý trường hợp trả lời sai
 */
function handleIncorrectResponse($conn, $contentId, $currentDateTime, $responseTime)
{
    // Lấy thông tin nội dung
    $contentInfo = getContentInfo($conn, $contentId);
    $isRecovery = $contentInfo['is_recovery'];
    $level = $contentInfo['level'];
    $correctCount = $contentInfo['correct_count'];
    $incorrectCount = $contentInfo['incorrect_count'];

    if ($isRecovery == 0) {
        // Không phải phục hồi
        if ($level < 6) {
            // Level thấp: tăng số lần sai nếu cần và cập nhật level
            if ($incorrectCount < $correctCount) {
                updateIncorrectCount($conn, $contentId);
            }

            // Lấy thông tin mới sau khi cập nhật
            $updatedInfo = getContentInfo($conn, $contentId);
            $level = $updatedInfo['correct_count'] - $updatedInfo['incorrect_count'];

            // Cập nhật level và thời gian ôn tập tiếp theo
            updateLevelAndNextReview($conn, $contentId, $level, $currentDateTime, $responseTime);
        } else {
            // Level cao: chuyển sang chế độ phục hồi
            updateRecoveryState($conn, $contentId, 1);

            // Tăng số lần trả lời sai nếu cần
            if ($incorrectCount < $correctCount) {
                updateIncorrectCount($conn, $contentId);
            }

            // Lấy thông tin mới sau khi cập nhật
            $updatedInfo = getContentInfo($conn, $contentId);
            $level = $updatedInfo['correct_count'] - $updatedInfo['incorrect_count'];

            // Cập nhật level và thời gian ôn tập tiếp theo với chế độ phục hồi
            updateLevelAndNextReview($conn, $contentId, $level, $currentDateTime, $responseTime, true);
        }
    } else {
        // Đang phục hồi: tăng số lần sai nếu cần
        if ($incorrectCount < $correctCount) {
            updateIncorrectCount($conn, $contentId);
        }

        // Lấy thông tin mới sau khi cập nhật
        $updatedInfo = getContentInfo($conn, $contentId);
        $level = $updatedInfo['correct_count'] - $updatedInfo['incorrect_count'];

        // Cập nhật level và thời gian ôn tập tiếp theo với chế độ phục hồi
        updateLevelAndNextReview($conn, $contentId, $level, $currentDateTime, $responseTime, true);
    }
}

// Xử lý request POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $contentId = $_POST["content_id"];
    $isCorrect = $_POST["is_correct"];
    $responseTime = $_POST["response_time"];

    try {
        $conn = pdo_get_connection();
        $currentDateTime = date("Y-m-d H:i:s");

        if ($isCorrect == 1) {
            // Xử lý trường hợp trả lời đúng
            handleCorrectResponse($conn, $contentId, $currentDateTime, $responseTime);
        } else {
            // Xử lý trường hợp trả lời sai
            handleIncorrectResponse($conn, $contentId, $currentDateTime, $responseTime);
        }

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    } finally {
        unset($conn);
    }
}
