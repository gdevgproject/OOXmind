<!-- CREATE TABLE `global_memory_profile` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `memory_factor` FLOAT DEFAULT 1
);

Thêm `difficulty` FLOAT DEFAULT 0 vào content -->

<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once 'model/pdo.php';

function calculateNextReview($currentDateTime, $level, $difficulty, $memoryFactor, $responseTime, $correctCount, $incorrectCount)
{
    // Cơ sở thời gian ôn tập (tính bằng giây)
    $baseIntervals = [
        0 => 60,        // 60 giây (1 phút) - Lần đầu tiên học hoặc trả lời sai nhiều
        1 => 70 * 60,       // 70 phút
        2 => 12 * 3600,     // 12 giờ
        3 => 23 * 3600,     // 23 giờ
        4 => 47 * 3600,     // 47 giờ
        5 => 71 * 3600,     // 71 giờ
        6 => 359 * 3600,    // 359 giờ
        7 => 1103 * 3600,   // 1103 giờ
        8 => 3407 * 3600,   // 3407 giờ
        9 => 8567 * 3600    // 8567 giờ
    ];

    // Xác định base level dựa trên số lần trả lời đúng/sai và level hiện tại
    if ($correctCount == 0 && $incorrectCount > 0) {
        // Chưa từng trả lời đúng, trả lời sai nhiều: ôn tập ngay lập tức (level 0)
        $baseLevel = 0;
    } elseif ($correctCount > 0 && $incorrectCount == 0) {
        // Chỉ trả lời đúng, chưa sai lần nào: giữ nguyên level hiện tại
        $baseLevel = $level;
    }
    elseif ($correctCount > $incorrectCount) {
        // Số lần đúng nhiều hơn sai: tăng level
         $baseLevel = min($level + 1, 9);
    }
    elseif ($correctCount <= $incorrectCount){
         // Số lần sai lớn hơn hoặc bằng: giảm level, xuống thấp nhất là 0
        $baseLevel = max($level - 1, 0);
    }

    // Lấy cơ sở thời gian dựa trên base level
    $baseInterval = $baseIntervals[$baseLevel];

    // Điều chỉnh thời gian dựa trên độ khó và hệ số nhớ
    $adjustedInterval = $baseInterval * (1 + $difficulty) / $memoryFactor;

    // Điều chỉnh thời gian dựa trên thời gian phản hồi
    // Nếu trả lời nhanh (dưới 5s), tăng thời gian ôn tập lên,  ngược lại
    $adjustedInterval *= ($responseTime < 5 ? 1.2 : (1 + $responseTime / 60)); 

    // Trả về thời gian ôn tập tiếp theo
    return date("Y-m-d H:i:s", strtotime($currentDateTime) + $adjustedInterval);
}

function calculateAndUpdateLevel($contentId, $currentDateTime, $responseTime, $conn, $isCorrect)
{
    // Lấy thông tin từ vựng và memory_factor từ bảng global_memory_profile
    $sqlContent = "SELECT correct_count, incorrect_count, difficulty, level, is_recovery FROM content WHERE content_id = :content_id";
    $stmtContent = $conn->prepare($sqlContent);
    $stmtContent->bindParam(':content_id', $contentId, PDO::PARAM_INT);
    $stmtContent->execute();
    $contentData = $stmtContent->fetch(PDO::FETCH_ASSOC);

    // Lấy memory_factor từ bảng global_memory_profile
    $sqlMemory = "SELECT memory_factor FROM global_memory_profile WHERE id = 1";
    $stmtMemory = $conn->prepare($sqlMemory);
    $stmtMemory->execute();
    $memoryData = $stmtMemory->fetch(PDO::FETCH_ASSOC);

    $correctCount = $contentData['correct_count'];
    $incorrectCount = $contentData['incorrect_count'];
    $difficulty = $contentData['difficulty'];
    $memoryFactor = $memoryData['memory_factor'] ?? 1; // Mặc định memoryFactor là 1 nếu không tồn tại
    $level = $contentData['level'];
    $isRecovery = $contentData['is_recovery'];

    // Xử lý logic khi trả lời sai:
    if (!$isCorrect) {
        if ($isRecovery == 0) {
            if ($level < 6) {
                if ($incorrectCount < $correctCount) {
                    $incorrectCount += 1;
                }
            } else {
                // Level > 6:
                // Đặt lại is_recovery thành 1 để xử lý ở phía front-end, vẫn lưu kết quả incorrect nhưng đợi người dùng xem lại ở chế độ "recovery" và không ảnh hưởng level hiện tại
                $isRecovery = 1;

                //update incorrect_count +1
                $incorrectCount += 1;

                // cho next_review chỉ 1 phút sau
                $currentDateTime = date("Y-m-d H:i:s", strtotime('+1 minutes', strtotime($currentDateTime)));

                $sqlUpdate = "UPDATE content SET 
                    is_recovery = :is_recovery,
                    incorrect_count = :incorrect_count,
                    last_review = :currentDateTime, 
                    next_review = :currentDateTime
                    WHERE content_id = :content_id";
                
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->bindParam(':content_id', $contentId, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':is_recovery', $isRecovery, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':incorrect_count', $incorrectCount, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':currentDateTime', $currentDateTime, PDO::PARAM_STR);
                $stmtUpdate->execute();
                
                return; // Kết thúc sớm, không cần tính toán level và next_review phức tạp trong trường hợp này

            }
        }
        // is_recovery == 1 thì thực hiện như trường hợp level < 6 phía trên nhưng bỏ qua so sánh incorrect < correct
        if($isRecovery == 1) {
            $incorrectCount += 1;
        }
        
    }else { // xử lý logic khi trả lời đúng:
        if ($isRecovery == 0) {
            $correctCount += 1;
        }
        // Nếu đang trong trạng thái phục hồi (is_recovery == 1), đặt lại thành 0 và giữ nguyên số lần trả lời đúng
         else {
            $isRecovery = 0;
        }
    }

    // Tính toán level mới: Không dùng $level = $correctCount - $incorrectCount; vì có nhiều logic phức tạp hơn
    $newLevel = $level; // Gán mặc định bằng level cũ

    if ($correctCount == 0 && $incorrectCount > 0) {
        // Chưa từng trả lời đúng, trả lời sai nhiều: level 0
        $newLevel = 0;
    } elseif ($correctCount > 0 && $incorrectCount == 0) {
        // Chỉ trả lời đúng, chưa sai lần nào: giữ nguyên level
        $newLevel = $level;
    }
    elseif ($correctCount > $incorrectCount) {
        // Số lần đúng nhiều hơn sai: tăng level
        $newLevel = min($level + 1, 9);
    }
    elseif ($correctCount <= $incorrectCount){
         // Số lần sai lớn hơn hoặc bằng: giảm level, xuống thấp nhất là 0
        $newLevel = max($level - 1, 0);
    }
    
    // Tính toán độ khó mới: nhanh thì dễ, chậm thì khó,  đúng thì giảm độ khó, sai thì tăng độ khó
    // is_recovery == 1 thì không cập nhật difficulty 
    if ($isRecovery == 0){
        $newDifficulty = min(1, max(-1, $difficulty + ($responseTime > 30 ? 0.1 : -0.1) + ($isCorrect ? -0.15 : 0.15))); // isCorrect đúng thì -0.1 ngược lại
    }

    // Tính toán thời gian ôn tập tiếp theo
    $nextReview = calculateNextReview($currentDateTime, $newLevel, $newDifficulty, $memoryFactor, $responseTime, $correctCount, $incorrectCount);

    // Cập nhật thông tin từ vựng
    $sqlUpdate = "UPDATE content SET 
        level = :level,
        correct_count = :correct_count,
        incorrect_count = :incorrect_count,
        difficulty = :difficulty,
        last_review = :currentDateTime, 
        response_time = :responseTime,
        next_review = :nextReview,
        is_recovery = :is_recovery
        WHERE content_id = :content_id";

    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':content_id', $contentId, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':currentDateTime', $currentDateTime, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':level', $newLevel, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':correct_count', $correctCount, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':incorrect_count', $incorrectCount, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':difficulty', $newDifficulty, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':responseTime', $responseTime, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':nextReview', $nextReview, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':is_recovery', $isRecovery, PDO::PARAM_INT);
    $stmtUpdate->execute();

    // Cập nhật hệ số nhớ trong bảng global_memory_profile: Phản hồi nhanh thì tăng hệ số nhớ (tức là học tốt hơn), phản hồi chậm thì cho thấy học kém hơn, cần giảm hệ số nhớ xuống
    $newMemoryFactor = min(2, max(0.5, $memoryFactor + ($responseTime < 5 ? 0.05 : -0.05)));
    $sqlUpdateMemory = "INSERT INTO global_memory_profile (id, memory_factor) 
                        VALUES (1, :memory_factor)
                        ON DUPLICATE KEY UPDATE memory_factor = :memory_factor";
    $stmtUpdateMemory = $conn->prepare($sqlUpdateMemory);
    $stmtUpdateMemory->bindParam(':memory_factor', $newMemoryFactor, PDO::PARAM_STR);
    $stmtUpdateMemory->execute();
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
        $conn->beginTransaction();
        $currentDateTime = date("Y-m-d H:i:s");

        // Xử lý logic cập nhật level và các thông số liên quan
        calculateAndUpdateLevel($contentId, $currentDateTime, $responseTime, $conn, $isCorrect);

        $conn->commit();
        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    } finally {
        unset($conn);
    }
}
?>