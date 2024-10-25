<?php
require_once 'model/pdo.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
$currentDateTime = date("Y-m-d H:i:s");

function getCountNextReview($conn, $currentDateTime)
{
    $sql = "SELECT COUNT(*) FROM content WHERE next_review <= :currentDateTime AND next_review IS NOT NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':currentDateTime', $currentDateTime, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getTotalVocabCount($conn)
{
    $sql = "SELECT COUNT(*) FROM content";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getTotalDraftCount($conn)
{
    $sql = "SELECT COUNT(*) FROM draft_content";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function gettotalCountPracticeDraft($conn)
{
    $sql = "SELECT `count` FROM `count` WHERE `count_name` = 'count_draft'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function homeGetUpcomingWords($conn, $currentDateTime)
{
    $sql = "SELECT * FROM content WHERE next_review IS NOT NULL AND next_review > :currentDateTime ORDER BY next_review ASC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':currentDateTime', $currentDateTime, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function homeGetCounts($conn)
{
    $sqlVocabCount = "SELECT COUNT(*) FROM content WHERE vocab IS NOT NULL AND vocab != ''";
    $sqlOtherCount = "SELECT COUNT(*) FROM content WHERE vocab IS NULL OR vocab = ''";

    $stmtVocab = $conn->prepare($sqlVocabCount);
    $stmtVocab->execute();
    $vocabCount = $stmtVocab->fetchColumn();

    $stmtOther = $conn->prepare($sqlOtherCount);
    $stmtOther->execute();
    $otherCount = $stmtOther->fetchColumn();

    return ['vocabCount' => $vocabCount, 'otherCount' => $otherCount];
}

function homeGetCount($conn, $currentDateTime)
{
    $sqlCount = "SELECT COUNT(*) as count FROM content WHERE next_review IS NOT NULL AND next_review <= :currentDateTime";
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->bindParam(':currentDateTime', $currentDateTime, PDO::PARAM_STR);
    $stmtCount->execute();
    return $stmtCount->fetchColumn();
}

function getFolderSize($folderPath)
{
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath)) as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    return $size;
}

function getOldestCreateTime($conn)
{
    $sql = "SELECT MIN(create_time) FROM content";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getTotalStats($conn)
{
    // Tính tổng level của từ vựng
    $sqlLevels = "SELECT SUM(level) FROM content";
    $stmtLevels = $conn->prepare($sqlLevels);
    $stmtLevels->execute();
    $totalLevels = $stmtLevels->fetchColumn();

    // Tính tổng số từ vựng
    $sqlVocabCount = "SELECT COUNT(*) FROM content";
    $stmtVocabCount = $conn->prepare($sqlVocabCount);
    $stmtVocabCount->execute();
    $totalVocab = $stmtVocabCount->fetchColumn();

    // Tính tổng số ngày học từ bảng activity_log
    $sqlTotalDays = "SELECT COUNT(*) FROM activity_log";
    $stmtTotalDays = $conn->prepare($sqlTotalDays);
    $stmtTotalDays->execute();
    $totalDays = $stmtTotalDays->fetchColumn();

    // Trả về tổng level, tổng từ vựng và tổng số ngày học
    return [
        'totalLevels' => $totalLevels,
        'totalVocab' => $totalVocab,
        'totalDays' => $totalDays
    ];
}

function initializeActivityLog()
{
    $today = date('Y-m-d'); // Get current date
    $existingLog = pdo_query_one("SELECT * FROM activity_log WHERE activity_date = ?", $today);

    // Insert a new record if not present for today
    if (!$existingLog) {
        pdo_execute(
            "INSERT INTO activity_log (activity_date, total_time_spent, open_time) VALUES (?, ?, ?)",
            $today,
            0,
            date('H:i:s')
        );
        return 0; // Return 0 if a new entry is created
    }

    return $existingLog['total_time_spent']; // Return total_time_spent if record exists
}

function getVocabReviewedCountToday($conn) {
    $currentDate = date("Y-m-d");
    $sql = "SELECT vocab_reviewed_count FROM activity_log WHERE activity_date = :currentDate";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getVocabCreatedCountToday($conn) {
    $currentDate = date("Y-m-d");
    $sql = "SELECT COUNT(*) FROM content WHERE DATE(create_time) = :currentDate";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchColumn();
}



// Call the function to initialize activity log and get total_time_spent
$timeSpentToday = initializeActivityLog();


try {
    $conn = pdo_get_connection();

    // Truy vấn lấy 7 cấp độ cao nhất cùng với số lượng từ vựng của mỗi cấp độ
    $sql = "SELECT level, COUNT(*) as vocab_count FROM content GROUP BY level ORDER BY level DESC LIMIT 7";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // Lấy dữ liệu và chuyển sang JSON
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $jsonData = json_encode($data, JSON_NUMERIC_CHECK);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $jsonData = json_encode([]);
} finally {
    unset($conn);
}


$conn = pdo_get_connection();
$countNextReview = getCountNextReview($conn, $currentDateTime);
$totalVocabCount = getTotalVocabCount($conn);
$totalDraftCount = getTotalDraftCount($conn);
$totalCountPracticeDraft = gettotalCountPracticeDraft($conn);
$upcomingWords = homeGetUpcomingWords($conn, $currentDateTime);
// Lấy số lượng từ vựng và khác
$counts = homeGetCounts($conn);
$vocabCount = $counts['vocabCount'];
$otherCount = $counts['otherCount'];
// ĐẾM tổng số từ để tự động ẩn hiện nút ôn tập ngay
$count = homeGetCount($conn, $currentDateTime);

$oldestCreateTime = getOldestCreateTime($conn);
$date1 = new DateTime($oldestCreateTime);
$date2 = new DateTime($currentDateTime);
$interval = $date1->diff($date2);
$days = $interval->days;


// Tính toán cho progress Word Per Day
// total WPD 8.764
$averageVocabPerDay = $days > 0 ? $totalVocabCount / $days : 0;
// WPD level 8
$WPDLevels = floor($averageVocabPerDay);
// Lấy phần thập phân của cấp độ WPD 0.764
$fractionalPartWPD = $averageVocabPerDay - $WPDLevels;
// WPD Percent 76.4
$WPDPercent = $fractionalPartWPD * 100;
// Định dạng WPDPercent với 2 chữ số sau dấu phẩy 76.400
$levelWPDPercent = number_format($WPDPercent, 2);


// Gọi hàm và lấy dữ liệu
$stats = getTotalStats($conn);
$totalLevels = $stats['totalLevels'];
$totalVocab = $stats['totalVocab'];
$totalDays = $stats['totalDays'];

// Tính cấp độ dựa theo tổng level của các từ vựng
$userLevels = floor(($totalLevels + $totalVocab + $totalDays) / 1000);

// Lấy phần thập phân của cấp độ
$fractionalPart = ($totalLevels + $totalVocab + $totalDays) / 1000 - $userLevels;

// Level Percent
$levelPercent = $fractionalPart * 100;
// Định dạng levelPercent với 2 chữ số sau dấu phẩy
$levelPercent = number_format($levelPercent, 2);



// Calculate the folder size
$projectFolderPath = 'D:\myproject\project\OOXmind';
$folderSizeBytes = getFolderSize($projectFolderPath);
$folderSizeGB = $folderSizeBytes / (1024 ** 3);

$vocabReviewedCount = getVocabReviewedCountToday($conn);
$vocabCreatedCountToday = getVocabCreatedCountToday($conn);


// Image Background Feature
$imageFolder = "assets/picture_background/";
$images = [];
$files = scandir($imageFolder);

foreach ($files as $file) {
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    if (in_array($extension, ["jpg", "jpeg", "png", "gif"])) {
        $images[] = $imageFolder . $file;
    }
}

$randomImage = $images[array_rand($images)];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>OOXmind</title>

    <link rel="icon" type="image/png" href="assets/minh.png" sizes="32x32">

    <link rel="stylesheet" href="view/css/custom-btn.css">
    <link rel="stylesheet" href="view/css/video_background.css">
    <link rel="stylesheet" href="view/css/header.css">
    <link rel="stylesheet" href="view/css/scrollbar.css">
    <link rel="stylesheet" href="view/css/custom-big-btn.css">
    <link rel="stylesheet" href="view/css/custom-div.css">
    <link rel="stylesheet" href="view/css/input-box.css">
    <link rel="stylesheet" href="view/css/custom-table.css">
    <link rel="stylesheet" href="view/css/color-full-line.css">
    <link rel="stylesheet" href="view/css/ui.css">
    <link rel="stylesheet" href="view/css/ginto-font-family.css">

    <!-- <link rel="stylesheet" href="view/css/font-open-sans.css"> -->
    <!-- <link rel="stylesheet" href="view/css/edit-form.css"> -->

    <link rel="stylesheet" href="./sql/bootstrap.min.css">

    <style>
        #myImage {
            transition: opacity 0.6s ease-in-out;
            width: 100%;
            height: 100vh;
            object-fit: cover;
            object-position: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: -1;
        }

        #myImage.transitioning {
            opacity: 0.2;
        }

        /* Progress Level Bar */

        .progress-container {
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            height: 11px;
            background: #2c3e50;  /* Soft dark blue-gray */
            overflow: hidden;
            position: relative;
            border: 1px solid #34495e;  /* Slightly lighter dark blue-gray */
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #e57373, #ffb74d, #fff176, #64b5f6, #ba68c8);
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
            transition: width 0.5s ease, background-color 0.3s ease, transform 0.3s ease;
        }


        .divider {
            position: absolute;
            width: 2px;
            height: 100%;
            background: black;
            z-index: 2;
        }

        .divider:nth-child(2) {
            left: 0%;
        }

        .divider:nth-child(2) {
            left: 10%;
        }

        .divider:nth-child(3) {
            left: 20%;
        }

        .divider:nth-child(4) {
            left: 30%;
        }

        .divider:nth-child(5) {
            left: 40%;
        }

        .divider:nth-child(6) {
            left: 50%;
        }

        .divider:nth-child(7) {
            left: 60%;
        }

        .divider:nth-child(8) {
            left: 70%;
        }

        .divider:nth-child(9) {
            left: 80%;
        }

        .divider:nth-child(10) {
            left: 90%;
        }

        .marker-divider {
            position: absolute;
            width: 1.2px;
            height: 100%;
            background: red;
            z-index: 3;
        }


        /* Progress WordPerDay Bar */

        /* .progress-container-wpd {
            width: 121px;
            height: 15px;
            background-color: rgb(0, 0, 0, 0.7);
            position: absolute;
            z-index: -1;
            top: 50px;
            left: 116px;
        }

        .progress-bar-wpd {
            height: 100%;
            background-color: #0055e4;
            transition: width 0.5s ease;
        }

        .wpd-number {
            color: white;
            font-size: 14px;
            position: absolute;
            top: -3.5px;
            left: 18px;
            font-weight: bold;
        } */
    </style>
    <script src="./sql/d3.v6.min.js"></script>
    <script src="./sql/jquery-3.5.1.slim.min.js"></script>
    <script src="./sql/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var images = <?php echo json_encode($images); ?>;
            var imageElement = document.getElementById('myImage');
            var currentImageIndex = 0;

            function changeImageWithTransition() {
                var newImage = images[currentImageIndex];
                imageElement.classList.add('transitioning');

                setTimeout(function () {
                    imageElement.src = newImage;
                    imageElement.classList.remove('transitioning');
                    currentImageIndex++;

                    if (currentImageIndex >= images.length) {
                        currentImageIndex = 0;
                    }
                }, 700);
            }

            setInterval(changeImageWithTransition, 10000);
        });
    </script>
</head>

<body>
    <span id="onlineTimeCounter" class="text-shadow"></span>
    <img id="myImage" src="<?php echo $randomImage; ?>" alt="Background Image">

    <!-- Audio player -->
    <audio id="backgroundMusic" autoplay loop>
        <source src="assets/audio/mixisoundbg.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>

    <div class="progress-container">
        <div class="progress-bar color-full-line" id="progressBar" style="width: <?php echo $levelPercent; ?>%;">
        </div>
        <div class="divider"></div>
        <div class="divider"></div>
        <div class="divider"></div>
        <div class="divider"></div>
        <div class="divider"></div>
        <div class="divider"></div>
        <div class="divider"></div>
        <div class="divider"></div>
        <div class="divider"></div>
        <div class="divider"></div>
    </div>

    <header>
        <section class="top-bar" id="topbar">
            <section class="color-full-line wrap-level-up" onclick="window.location.href='./home.php';">
                <section class="level-up">
                    <div>
                        <p class="level"><?php echo $userLevels; ?></p>
                        <p class="levelPercent"><?php echo $levelPercent; ?>%</p>
                    </div>
                </section>
                <section class="wrap-name">
                    <p class="user-name">Vu Duc Minh</p>
                    <p class="premium">Premium</p>
                </section>
            </section>
            <section class="humburgerBtn">
                <img src="./assets/menu.png" alt="menu" id="humburger-icon">
            </section>
        </section>

        <a href="./index.php" class="profile">
            <img src="./assets/minh.jpg" alt="user image">
        </a>
        <ul class="avt-list">
            <li style="background-color: #e57373; padding: 1px 6px; border-radius: 6px;">
                <?php echo number_format($folderSizeGB, 3); ?> GB
            </li>
            <li style="background-color: #ffb74d; padding: 1px 6px; border-radius: 6px; margin-top: 2px;">
                created <?php echo $vocabCreatedCountToday; ?>
            </li>
            <li style="background-color: #81c784; padding: 1px 6px; border-radius: 6px; margin-top: 2px;">
                reviewed <?php echo $vocabReviewedCount; ?>
            </li>
        </ul>

        <nav class="afteHumburgerBtn" id="menu">
            <ul>
                <li id="reviewnow" class="custom-btn">
                    <a href="./home.php">
                        <img src="<?php echo $countNextReview == 0 ? 'assets/perfection.png' : 'assets/brainred.png'; ?>"
                            alt="Home">
                        <p><strong><?php echo $countNextReview == 0 ? 'GOOD' : 'REVIEW NOW'; ?></strong></p>
                    </a>
                </li>
                <li id="notebook" class="custom-btn">
                    <a href="./index.php">
                        <img src="assets/writing.png" alt="Notebook">
                        <p><strong>NOTEBOOK <?php echo $totalVocabCount; ?></strong></p>
                    </a>
                </li>
                <li id="sketchbook" class="custom-btn">
                    <a href="./draft_content.php">
                        <img src="assets/draft.png" alt="Draft">
                        <p><strong>SKETCHBOOK <?php echo $totalDraftCount; ?></strong></p>
                    </a>
                </li>
                <li id="mastery" class="custom-btn">
                    <a href="./mastery.php">
                        <img src="assets/statistic.png" alt="Draft">
                        <p><strong>MASTERY</strong></p>
                    </a>
                </li>
                <li id="practice" class="custom-btn">
                    <a href="./practice_draft.php">
                        <img src="assets/practice_draft.png" alt="Draft">
                        <p><strong>PRACTICE <?php echo $totalCountPracticeDraft; ?></strong></p>
                    </a>
                </li>
                <li id="statistic" class="custom-btn">
                    <a href="./statistic.php">
                        <img src="assets/graph.png" alt="graph">
                        <p><strong>STATISTIC</strong></p>
                    </a>

                </li>
                <li id="toggle-music" class="custom-btn">
                    <a href="#">
                        <img src="assets/sound.png" alt="Toggle Music" id="toggle-music-icon">
                        <p><strong>TOGGLE MUSIC</strong></p>
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const audio = document.getElementById('backgroundMusic');
            const toggleMusicButton = document.getElementById('toggle-music');
            const toggleMusicIcon = document.getElementById('toggle-music-icon');
            const humburgerIcon = document.getElementById('humburger-icon');
            const menu = document.getElementById('menu');

            // Set initial volume
            audio.volume = 0.2;

            // Check localStorage for music setting
            const musicSetting = localStorage.getItem('musicEnabled');
            if (musicSetting === 'false') {
                audio.pause();
                toggleMusicIcon.src = 'assets/mute.png'; // Set to mute icon
            } else {
                audio.play().catch(() => { }); // Handle potential play errors
                toggleMusicIcon.src = 'assets/audio.png'; // Set to sound icon
            }

            // Toggle music on button click
            toggleMusicButton.addEventListener('click', function () {
                if (audio.paused) {
                    audio.play().catch(() => { }); // Handle potential play errors
                    toggleMusicIcon.src = 'assets/audio.png'; // Set to sound icon
                    localStorage.setItem('musicEnabled', 'true');
                } else {
                    audio.pause();
                    toggleMusicIcon.src = 'assets/mute.png'; // Set to mute icon
                    localStorage.setItem('musicEnabled', 'false');
                }
            });

            // Toggle menu on hamburger icon click
            humburgerIcon.addEventListener('click', function () {
                menu.classList.toggle('show');
            });

            // Hide menu when clicking outside of it
            document.addEventListener('click', function (event) {
                if (!menu.contains(event.target) && !humburgerIcon.contains(event.target)) {
                    menu.classList.remove('show');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Chỉ yêu cầu quyền thông báo nếu chưa yêu cầu trước đó
            if (Notification.permission === "default") {
                requestNotificationPermission();
            }

            // Thiết lập kiểm tra thông báo mỗi 5 giây thay vì mỗi giây
            setInterval(checkAndNotify, 5000);
            document.addEventListener('keydown', handleEnterPress);
        });

        function requestNotificationPermission() {
            if (!("Notification" in window)) {
                alert("Trình duyệt này không hỗ trợ thông báo đẩy");
            } else {
                Notification.requestPermission().then(permission => {
                    if (permission === "granted") {
                        console.log("Quyền thông báo được chấp nhận");
                    }
                });
            }
        }

        var notifiedWords = [];

        function checkAndNotify() {
            var upcomingWords = <?php echo json_encode($upcomingWords); ?>;
            var count = <?php echo $count; ?>;
            var currentTime = Date.now();

            // Lưu trữ thông báo chỉ nếu quyền đã được cấp
            if (Notification.permission !== "granted") {
                return; // Thoát nếu không có quyền
            }

            // Xử lý thông báo cho từng từ
            upcomingWords.forEach(function (word) {
                var nextReviewTime = new Date(word.next_review).getTime();
                if (nextReviewTime <= currentTime && !notifiedWords.includes(word.content_id)) {
                    // Tạo thông báo và âm thanh một lần duy nhất
                    var notificationOptions = {
                        body: "Có " + (count + 1) + " Từ Chờ Ôn Tập",
                        icon: 'assets/bot.png',
                        sound: 'assets/audio/yuno_say_yuki.mp3',
                        volume: 0.2
                    };

                    // Khởi tạo thông báo và âm thanh
                    var notification = new Notification("IT'S TIME TO REVIEW", notificationOptions);
                    var audio = new Audio(notificationOptions.sound);

                    // Phát âm thanh
                    audio.volume = notificationOptions.volume; // Đặt âm lượng trước khi phát
                    audio.play();

                    // Thiết lập sự kiện onclick cho thông báo
                    notification.onclick = function () {
                        window.location.href = 'practice.php';
                    };

                    // Đóng thông báo sau 4 giây
                    setTimeout(function () {
                        notification.close();
                    }, 4000);

                    count++;
                    notifiedWords.push(word.content_id);
                }
            });
        }

        // Đếm giờ online
        let totalElapsedSeconds = <?php echo $timeSpentToday; ?>;

        // Function to format time in hh:mm:ss format
        const formatElapsedTime = (seconds) => {
            const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
            const secondsFormatted = String(seconds % 60).padStart(2, '0');
            return `${hours}:${minutes}:${secondsFormatted}`;
        };

        // Function to send data to the server via AJAX
        const sendUpdateRequest = (urlEndpoint, requestData) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', urlEndpoint, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(requestData);
        };

        // Function to update the timer every second
        const updateElapsedTime = () => {
            totalElapsedSeconds++;
            document.getElementById('onlineTimeCounter').textContent = formatElapsedTime(totalElapsedSeconds);

            // Send total_time_spent update to the server every second
            sendUpdateRequest('update_time.php', `total_time_spent=${totalElapsedSeconds}`);

            // Add 10 seconds to total_time_spent every 10 seconds
            if (totalElapsedSeconds % 10 === 0) {
                sendUpdateRequest('update_time.php', `add_time=10`);
            }

            // Update close_time every second
            sendUpdateRequest('update_time.php', `close_time=${new Date().toLocaleTimeString('en-GB')}`);
        };

        // Function to start the timer and update every second
        const startElapsedTimeCounter = () => setInterval(updateElapsedTime, 1000);

        // Initialize the timer display and start the counter on page load
        window.onload = () => {
            document.getElementById('onlineTimeCounter').textContent = formatElapsedTime(totalElapsedSeconds);
            startElapsedTimeCounter();
        };
        // ================
    </script>

    <body>