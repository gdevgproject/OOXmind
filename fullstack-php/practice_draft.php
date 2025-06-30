<?php
include 'view/header.php';

// Kiểm tra và lấy giá trị các tham số def và vocab từ URL
$def = $_GET['def'] ?? null;
$vocab = $_GET['vocab'] ?? null;

// Kiểm tra xem các tham số có giá trị không và kết hợp chúng
if ($def && $vocab) {
    $combinedString = trim($def . ' ' . $vocab);
    echo "<script>console.log('def:', " . json_encode($def) . ");</script>";
    echo "<script>console.log('vocab:', " . json_encode($vocab) . ");</script>";
    echo "<script>var combinedString = " . json_encode($combinedString) . ";</script>";
}

// Truy vấn số từ trường "count" của bản ghi "count_draft"
try {
    $conn = pdo_get_connection();

    // Truy vấn để kiểm tra sự tồn tại của count_draft
    $sql = "SELECT `count` FROM `count` WHERE `count_name` = 'count_draft'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Nếu không tìm thấy bản ghi, chèn bản ghi mới
    if ($row) {
        $countValue = $row['count'];
    } else {
        $insertSql = "INSERT INTO `count` (`count_name`, `count`) VALUES ('count_draft', 0)";
        $conn->exec($insertSql);
        $countValue = 0; // Gán giá trị countValue là 0
    }
} catch (PDOException $e) {
    // Xử lý lỗi nếu cần
    error_log("Database error: " . $e->getMessage()); // Ghi lại lỗi vào log thay vì hiển thị
    echo "An error occurred. Please try again later.";
}
?>


<style>
    #continue-button:disabled {
        opacity: 0.5;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.2);
        }

        100% {
            transform: scale(1);
        }
    }

    .pulsate {
        animation: pulse 0.5s;
    }

    .correct-answer {
        color: green;
    }

    .incorrect-answer {
        color: red;
    }

    /* Màu chữ cho trạng thái theory */
    .theory-text-green {
        color: green;
    }
</style>
<div class="mt-3">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="row mb-3">
                <div class="col-md-1">
                    <div class="d-flex justify-content-md-start align-items-center h-100">
                        <h1 id="count-display" class="mt-3" style="padding-left: 35px;"><?php echo $countValue; ?> TIMES
                        </h1>
                    </div>
                </div>
                <div class="col-md-11">
                    <div class="d-flex justify-content-md-end align-items-center h-100">
                        <button class="custom-big-btn" id="theoryButton" onclick="toggleTheoryMode()">THEORY</button>
                    </div>
                </div>
            </div>
            <div class="custom-div text-center">
                <div class="input-group">
                    <textarea id="patternInput" class="input-box mb-3" rows="1" placeholder="Theory"></textarea>
                </div>
                <div class="input-group">
                    <textarea id="history-field" class="input-box mb-3" rows="7" placeholder="Practice History"
                        readonly></textarea>
                </div>
                <div class="input-group">
                    <textarea id="input-field" autocomplete="off" onkeyup="checkInput()" class="input-box" rows="1"
                        placeholder="Practice" autofocus></textarea>
                </div>
                <div class="text-center mt-3">
                    <button class="custom-big-btn" id="continue-button" onclick="checkFunction()"
                        disabled>CHECK</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Biến để lưu trữ lịch sử trả lời và trạng thái
    let answerHistory = '';
    const audio = new Audio('assets/audio/ngamphaohoa.mp3');
    audio.loop = true;
    let isTheoryMode = false;

    // Bắt đầu phát audio nếu play là true
    if (true) {
        audio.play();
    }

    let pattern = ""; // Biến để lưu trữ theory

    // Lấy các phần tử DOM để tiết kiệm thời gian truy cập
    const inputField = document.getElementById('input-field');
    const continueButton = document.getElementById('continue-button');

    function checkInput(event) {
        const input = inputField.value.trim();
        continueButton.disabled = input === '';

        // Chỉ xử lý khi phím Enter được nhấn
        if (event.key === "Enter" && input) {
            incrementCount();
        }
    }

    function clearInput() {
        inputField.value = '';
        inputField.focus();
        continueButton.disabled = true;
    }

    function incrementCount() {
        // Lấy các phần tử cần thiết từ DOM
        const countDisplay = document.getElementById('count-display');
        const input = document.getElementById('input-field');
        const theoryTextarea = document.getElementById('patternInput');
        const historyTextarea = document.getElementById('history-field');

        const userInput = input.value.trim(); // Lưu trữ giá trị đầu vào

        if (userInput) { // Kiểm tra nếu input không rỗng
            // So sánh câu mẫu với câu người dùng vừa nhập
            const isCorrect = userInput === pattern;

            // Xử lý hiển thị màu sắc và hiệu ứng dựa trên độ chính xác
            countDisplay.style.color = isCorrect ? 'green' : 'red';
            if (!isCorrect) {
                countDisplay.classList.add("pulsate");
                new Audio('assets/audio/incorrect_sound.mp3').play();
            } else {
                setTimeout(() => {
                    countDisplay.style.color = 'black'; // Đổi lại màu chữ thành đen sau 0.5 giây
                }, 500);
                new Audio('assets/audio/correct_sound.mp3').play();
            }

            // Cập nhật textarea theory
            theoryTextarea.innerHTML = pattern;

            // Hiển thị textarea "Practice History"
            historyTextarea.style.display = 'block';

            // Cập nhật lịch sử trả lời
            answerHistory += userInput + '\n';
            historyTextarea.value = answerHistory;
            historyTextarea.scrollTop = historyTextarea.scrollHeight;

            // Xóa bỏ nội dung của ô practice
            input.value = '';

            // Gửi yêu cầu Ajax để cập nhật giá trị count trên máy chủ
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_count.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = () => {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        // Kiểm tra phản hồi từ máy chủ
                        const response = xhr.responseText;
                        if (!isNaN(response)) {
                            countDisplay.innerText = response; // Cập nhật nội dung tổng số lần hiển thị
                        } else {
                            console.error('Lỗi khi cập nhật count trên máy chủ.');
                        }
                    }
                }
            };
            xhr.send('count=1');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Focus vào ô nhập câu mẫu ngay khi trang được tải
        const patternInput = document.getElementById('patternInput');
        const inputField = document.getElementById('input-field');
        const hideButton = document.getElementById('hideButton');
        const showButton = document.getElementById('showButton');

        patternInput.focus();

        // Lắng nghe sự kiện khi ô textarea mất focus
        patternInput.addEventListener('blur', function() {
            // Lưu nội dung của ô textarea vào biến pattern
            pattern = this.value;
        });

        function hidePattern() {
            patternInput.style.display = 'none';
            hideButton.style.display = 'none';
            showButton.style.display = 'block';
            inputField.focus(); // Focus vào ô input practice
        }

        function showPattern() {
            patternInput.style.display = 'block';
            showButton.style.display = 'none';
            hideButton.style.display = 'block';
            patternInput.focus(); // Focus vào ô nhập câu mẫu
        }

        function adjustTextareaHeight() {
            patternInput.style.height = 'auto';
            patternInput.style.height = patternInput.scrollHeight + 'px';
        }

        // Gọi hàm adjustTextareaHeight() mỗi khi có sự thay đổi trong textarea
        patternInput.addEventListener('input', adjustTextareaHeight);

        // Gắn các hàm vào sự kiện click của nút
        if (hideButton) hideButton.addEventListener('click', hidePattern);
        if (showButton) showButton.addEventListener('click', showPattern);
    });

    // Hàm xử lý sự kiện khi ấn nút Enter trong ô textarea
    function handleEnter(event, targetField) {
        // Kiểm tra xem phím Enter được ấn và không phải là ký tự mở rộng (shift, ctrl, alt)
        if (event.key === 'Enter' && !event.shiftKey && !event.ctrlKey && !event.altKey) {
            // Ngăn chặn hành vi mặc định của phím Enter (xuống dòng)
            event.preventDefault();

            if (targetField === 'practice') {
                // Tương đương với việc click vào nút CHECK
                checkFunction();
            } else if (targetField === 'theory') {
                // Chuyển focus sang ô textarea "Practice"
                document.getElementById('input-field').focus();
            }
        }
    }

    // Gán hàm handleEnter cho các sự kiện input
    document.getElementById('input-field').addEventListener('keydown', (event) => handleEnter(event, 'practice'));
    document.getElementById('patternInput').addEventListener('keydown', (event) => handleEnter(event, 'theory'));


    // Hàm điều chỉnh chiều cao của ô input
    function adjustInputFieldHeight() {
        const inputField = document.getElementById('input-field');
        inputField.style.height = 'auto'; // Đặt chiều cao thành tự động
        inputField.style.height = `${inputField.scrollHeight}px`; // Đặt chiều cao mới
    }

    // Hàm xử lý sự kiện cho ô input
    function handleInputChange() {
        const inputField = document.getElementById('input-field');
        const historyTextarea = document.getElementById('history-field');

        adjustInputFieldHeight(); // Điều chỉnh chiều cao ô input
        historyTextarea.style.display = inputField.value.trim() ? 'none' : 'block'; // Ẩn/hiện textarea "Practice History"
    }

    // Đăng ký sự kiện input cho ô input-field
    document.getElementById('input-field').addEventListener('input', handleInputChange);

    // Hàm xử lý khi nhấp vào nút Theory
    function toggleTheoryMode() {
        // Đảo ngược trạng thái theory
        isTheoryMode = !isTheoryMode;

        // Lấy tham chiếu đến các phần tử cần thiết
        const theoryButton = document.getElementById('theoryButton');
        const theoryTextarea = document.getElementById('patternInput');
        const practiceTextarea = document.getElementById('input-field');

        // Cập nhật màu sắc và trạng thái hiển thị của textarea
        theoryButton.classList.toggle('theory-text-green', isTheoryMode);
        theoryTextarea.style.display = isTheoryMode ? 'block' : 'none';

        // Focus vào ô input practice
        practiceTextarea.focus();
    }

    function checkFunction() {
        incrementCount();
    }

    // Kiểm tra xem biến combinedString đã được tạo ra từ PHP chưa
    if (typeof combinedString !== 'undefined') {
        document.getElementById('patternInput').value = '<?php echo $vocab; ?>';
    }

    window.onload = function() {
        toggleTheoryMode();
    };
</script>

</html>