<?php
if (isset($_POST['count']) && is_numeric($_POST['count'])) {
    require_once 'model/pdo.php';

    try {
        $conn = pdo_get_connection();

        // Bắt đầu giao dịch
        $conn->beginTransaction();

        // Tăng giá trị count và cập nhật vào cơ sở dữ liệu
        $sqlUpdate = "UPDATE `count` SET `count` = `count` + :count WHERE `count_name` = 'count_draft'";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':count', $_POST['count'], PDO::PARAM_INT);
        $stmtUpdate->execute();

        // Lấy giá trị count mới sau khi cập nhật
        $sqlSelect = "SELECT `count` FROM `count` WHERE `count_name` = 'count_draft'";
        $stmtSelect = $conn->prepare($sqlSelect);
        $stmtSelect->execute();
        $newCount = $stmtSelect->fetchColumn();

        // Cam kết giao dịch
        $conn->commit();

        // Phản hồi với giá trị count mới
        echo $newCount;
    } catch (PDOException $e) {
        // Nếu có lỗi, hoàn tác giao dịch
        $conn->rollBack();
        echo 'Error: ' . htmlspecialchars($e->getMessage());
    }
} else {
    echo 'Invalid input.';
}
