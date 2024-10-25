<?php
include 'view/header.php';

// Hàm truy vấn từ vựng theo màu sắc
function getVocabularyByColor($conn, $color, $limit)
{
    $sql = "SELECT * FROM content WHERE FLOOR(response_time / 1000) " .
        ($color === 'red' ? "> 60" : ($color === 'yellow' ? "BETWEEN 41 AND 60" : "<= 40")) .
        " ORDER BY response_time DESC LIMIT " . intval($limit);
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy 15 từ cho mỗi màu theo thứ tự: đỏ, vàng, xanh
$redWords = getVocabularyByColor($conn, 'red', 15);
$yellowWords = getVocabularyByColor($conn, 'yellow', 15);
$greenWords = getVocabularyByColor($conn, 'green', 15);

// Kết hợp tất cả các từ vào một mảng
$contentData = array_merge($redWords, $yellowWords, $greenWords);

// Hàm định dạng thời gian
function formatTime($milliseconds)
{
    $seconds = floor($milliseconds / 1000);
    $minutes = floor($seconds / 60);
    $seconds %= 60; // Lấy số giây còn lại sau khi chia cho số phút
    return sprintf('%02d:%02d', $minutes, $seconds);
}

// Hàm xác định màu sắc dựa trên thời gian
function getTimeColor($milliseconds)
{
    $seconds = floor($milliseconds / 1000);
    return $seconds <= 40 ? 'green' : ($seconds <= 60 ? 'yellow' : 'red');
}
?>

<div class="mx-auto mt-3" style="width:90%">
    <!-- Content Table -->
    <table class="custom-table" id="contentTable">
        <thead>
            <tr>
                <th>Time</th>
                <th>Count</th>
                <th>Vocabulary</th>
                <th>Definition</th>
                <th>Example</th>
                <th>Question</th>
                <th>Answer</th>
                <th>Image</th>
                <th>Video</th>
                <th>Audio</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $count = 1;

            foreach ($contentData as $row) {
                $formattedTime = formatTime(htmlspecialchars($row['response_time']));
                $timeColor = getTimeColor($row['response_time']);
                echo "<tr>";
                echo "<td class='text-center' style='color:{$timeColor};'><strong>" . $formattedTime . "</strong></td>";
                echo "<td class='text-center'>{$count}</td>";
                echo "<td>" . htmlspecialchars($row['vocab']) . " " . htmlspecialchars($row['part_of_speech']) . "<br>" . htmlspecialchars($row['ipa']) . "</td>";
                echo "<td>" . htmlspecialchars($row['def']) . "</td>";
                echo "<td>" . htmlspecialchars($row['ex']) . "</td>";
                echo "<td>" . htmlspecialchars($row['question']) . "</td>";
                echo "<td>" . htmlspecialchars($row['answer']) . "</td>";

                // Hiển thị ảnh
                echo "<td>" . (!empty($row['image_path']) ? "<img src='" . htmlspecialchars($row['image_path']) . "' alt='Image' style='max-width:100px; max-height:100px;' ondblclick='enlargeImage(\"" . htmlspecialchars($row['image_path']) . "\")'>" : "") . "</td>";

                // Hiển thị video
                echo "<td>" . (!empty($row['video_path']) ? "<video width='150' height='100' controls><source src='" . htmlspecialchars($row['video_path']) . "' type='video/mp4'>Your browser does not support the video tag.</video>" : "") . "</td>";

                // Hiển thị audio
                echo "<td>" . (!empty($row['audio_path']) ? "<div class='custom-btn text-center'><img src='assets/audio.png' alt='Play Audio' style='cursor:pointer;' onclick='playAudio(\"" . htmlspecialchars($row['audio_path']) . "\")'></div>" : "") . "</td>";

                echo "</tr>";
                $count++; // Tăng biến đếm
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <img id="enlargedImage" src="" class="img-fluid" alt="Enlarged Image">
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.8/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    function playAudio(audioPath) {
        var audio = new Audio(audioPath);
        audio.play();
    }

    function enlargeImage(imagePath) {
        document.getElementById('enlargedImage').src = imagePath;
        $('#imageModal').modal('show');
    }
</script>
</body>

</html>