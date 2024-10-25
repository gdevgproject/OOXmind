<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once 'model/pdo.php';

function calculateAndUpdateLevel($contentId, $correctCount, $incorrectCount, $currentDateTime, $responseTime, $conn)
{
    // Lấy ra correct_count và incorrect_count sau khi update để tính toán level
    $sqlLevel = "SELECT correct_count, incorrect_count FROM content WHERE content_id = :content_id";
    $stmtLevel = $conn->prepare($sqlLevel);
    $stmtLevel->bindParam(':content_id', $contentId, PDO::PARAM_INT);
    $stmtLevel->execute();
    $counts = $stmtLevel->fetch(PDO::FETCH_ASSOC);

    $correctCount = $counts['correct_count'];
    $incorrectCount = $counts['incorrect_count'];
    // Tính toán level mới dựa trên số lần trả lời đúng và sai, đảm bảo level trong khoảng từ 0 đến 7
    $level = $correctCount - $incorrectCount;

    // Cập nhật level mới và các trường khác
    $sqlUpdate = "UPDATE content SET 
        level = :level,
        last_review = :currentDateTime, 
        response_time = :responseTime,
        next_review = CASE
            WHEN correct_count - incorrect_count = 1 THEN DATE_ADD(:currentDateTime, INTERVAL 70 MINUTE)
            WHEN correct_count - incorrect_count = 2 THEN DATE_ADD(:currentDateTime, INTERVAL 12 HOUR)
            WHEN correct_count - incorrect_count = 3 THEN DATE_ADD(:currentDateTime, INTERVAL 23 HOUR)
            WHEN correct_count - incorrect_count = 4 THEN DATE_ADD(:currentDateTime, INTERVAL 47 HOUR)
            WHEN correct_count - incorrect_count = 5 THEN DATE_ADD(:currentDateTime, INTERVAL 71 HOUR)
            WHEN correct_count - incorrect_count = 6 THEN DATE_ADD(:currentDateTime, INTERVAL 359 HOUR)
            WHEN correct_count - incorrect_count = 7 THEN DATE_ADD(:currentDateTime, INTERVAL 1103 HOUR)
            WHEN correct_count - incorrect_count = 8 THEN DATE_ADD(:currentDateTime, INTERVAL 3407 HOUR)
            WHEN correct_count - incorrect_count > 8 THEN DATE_ADD(:currentDateTime, INTERVAL 8567 HOUR)
            ELSE last_review
        END 
        WHERE content_id = :content_id";

    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':content_id', $contentId, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':currentDateTime', $currentDateTime, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':level', $level, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':responseTime', $responseTime, PDO::PARAM_INT);
    $stmtUpdate->execute();
}

function updateIncorrectCount($conn, $contentId, $incrementValue = 1)
{
    $sqlUpdateIncorrect = "UPDATE content SET incorrect_count = incorrect_count + :increment_value WHERE content_id = :content_id";
    $stmtUpdateIncorrect = $conn->prepare($sqlUpdateIncorrect);
    $stmtUpdateIncorrect->bindParam(':increment_value', $incrementValue, PDO::PARAM_INT);
    $stmtUpdateIncorrect->bindParam(':content_id', $contentId, PDO::PARAM_INT);
    $stmtUpdateIncorrect->execute();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $contentId = $_POST["content_id"];
    $isCorrect = $_POST["is_correct"];
    $responseTime = $_POST["response_time"];

    try {
        $conn = pdo_get_connection();
        $currentDateTime = date("Y-m-d H:i:s");

        if ($isCorrect == 1) {
            // Trả lời đúng
            $sqlGetRecovery = "SELECT is_recovery FROM content WHERE content_id = :content_id";
            $stmtGetRecovery = $conn->prepare($sqlGetRecovery);
            $stmtGetRecovery->bindParam(':content_id', $contentId, PDO::PARAM_INT);
            $stmtGetRecovery->execute();
            $isRecovery = $stmtGetRecovery->fetchColumn();

            if ($isRecovery == 0) {
                // Không phục hồi

                // update correct_count thêm 1
                $sqlUpdateCorrect = "UPDATE content SET correct_count = correct_count + 1 WHERE content_id = :content_id";
                $stmtUpdateCorrect = $conn->prepare($sqlUpdateCorrect);
                $stmtUpdateCorrect->bindParam(':content_id', $contentId, PDO::PARAM_INT);
                $stmtUpdateCorrect->execute();

                // Sau khi tính toán correct_count và incorrect_count
                calculateAndUpdateLevel($contentId, $correctCount, $incorrectCount, $currentDateTime, $responseTime, $conn);

            } else {
                // Phục hồi

                // update is_recovery == 0
                $sqlUpdateRecovery = "UPDATE content SET is_recovery = 0 WHERE content_id = :content_id";
                $stmtUpdateRecovery = $conn->prepare($sqlUpdateRecovery);
                $stmtUpdateRecovery->bindParam(':content_id', $contentId, PDO::PARAM_INT);
                $stmtUpdateRecovery->execute();

                // Sau khi tính toán correct_count và incorrect_count
                calculateAndUpdateLevel($contentId, $correctCount, $incorrectCount, $currentDateTime, $responseTime, $conn);

            }
        } else {
            // Trả lời sai
            $sqlGetRecovery = "SELECT is_recovery, level, correct_count, incorrect_count FROM content WHERE content_id = :content_id";
            $stmtGetRecovery = $conn->prepare($sqlGetRecovery);
            $stmtGetRecovery->bindParam(':content_id', $contentId, PDO::PARAM_INT);
            $stmtGetRecovery->execute();
            $result = $stmtGetRecovery->fetch(PDO::FETCH_ASSOC);
            $isRecovery = $result['is_recovery'];
            $level = $result['level'];
            $correctCount = $result['correct_count'];
            $incorrectCount = $result['incorrect_count'];

            if ($isRecovery == 0) {
                if ($level < 6) {
                    if ($incorrectCount < $correctCount) {
                        $sqlUpdateIncorrect = "UPDATE content SET incorrect_count = incorrect_count + 1 WHERE content_id = :content_id";
                        $stmtUpdateIncorrect = $conn->prepare($sqlUpdateIncorrect);
                        $stmtUpdateIncorrect->bindParam(':content_id', $contentId, PDO::PARAM_INT);
                        $stmtUpdateIncorrect->execute();
                    }

                    // Lấy ra correct_count và incorrect_count sau khi update để tính toán level
                    $sqlLevel = "SELECT correct_count, incorrect_count FROM content WHERE content_id = :content_id";
                    $stmtLevel = $conn->prepare($sqlLevel);
                    $stmtLevel->bindParam(':content_id', $contentId, PDO::PARAM_INT);
                    $stmtLevel->execute();
                    $counts = $stmtLevel->fetch(PDO::FETCH_ASSOC);

                    $correctCount = $counts['correct_count'];
                    $incorrectCount = $counts['incorrect_count'];

                    // Sau khi tính toán correct_count và incorrect_count
                    calculateAndUpdateLevel($contentId, $correctCount, $incorrectCount, $currentDateTime, $responseTime, $conn);

                } else {

                    // update is_recovery == 1
                    $sqlUpdateRecovery = "UPDATE content SET is_recovery = 1 WHERE content_id = :content_id";
                    $stmtUpdateRecovery = $conn->prepare($sqlUpdateRecovery);
                    $stmtUpdateRecovery->bindParam(':content_id', $contentId, PDO::PARAM_INT);
                    $stmtUpdateRecovery->execute();

                    // update incorrect_count thêm 1
                    updateIncorrectCount($conn, $contentId);


                    // Lấy ra correct_count và incorrect_count sau khi update để tính toán level
                    $sqlLevel = "SELECT correct_count, incorrect_count FROM content WHERE content_id = :content_id";
                    $stmtLevel = $conn->prepare($sqlLevel);
                    $stmtLevel->bindParam(':content_id', $contentId, PDO::PARAM_INT);
                    $stmtLevel->execute();
                    $counts = $stmtLevel->fetch(PDO::FETCH_ASSOC);

                    $correctCount = $counts['correct_count'];
                    $incorrectCount = $counts['incorrect_count'];

                    // Tính toán level mới dựa trên số lần trả lời đúng và sai, và Đảm bảo giá trị level nằm trong khoảng từ 0 đến 7
                    $level = $correctCount - $incorrectCount;

                    // Cập nhật level mới và last_review, response_time, và next_review - 60 phút
                    $sqlUpdate = "UPDATE content SET 
                    level = :level,
last_review = :currentDateTime,
response_time = :responseTime,
next_review = DATE_ADD(:currentDateTime, INTERVAL 60 MINUTE) WHERE content_id = :content_id";

                    $stmtUpdate = $conn->prepare($sqlUpdate);
                    $stmtUpdate->bindParam(':content_id', $contentId, PDO::PARAM_INT);
                    $stmtUpdate->bindParam(':currentDateTime', $currentDateTime, PDO::PARAM_STR);
                    $stmtUpdate->bindParam(':level', $level, PDO::PARAM_INT);
                    $stmtUpdate->bindParam(':responseTime', $responseTime, PDO::PARAM_INT);
                    $stmtUpdate->execute();
                }
            } else {
                if ($incorrectCount < $correctCount) {
                    updateIncorrectCount($conn, $contentId);
                }

                // Tính toán level mới dựa trên số lần trả lời đúng và sai, và Đảm bảo giá trị level nằm trong khoảng từ 0 đến 7
                $level = $correctCount - $incorrectCount;

                // Cập nhật level mới và last_review, response_time, và next_review - 60 phút
                $sqlUpdate = "UPDATE content SET 
                level = :level,
last_review = :currentDateTime,
response_time = :responseTime,
next_review = DATE_ADD(:currentDateTime, INTERVAL 60 MINUTE) WHERE content_id = :content_id";

                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->bindParam(':content_id', $contentId, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':currentDateTime', $currentDateTime, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':level', $level, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':responseTime', $responseTime, PDO::PARAM_INT);
                $stmtUpdate->execute();
            }
        }
        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    } finally {
        unset($conn);
    }
}