<?php
include 'view/header.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

$currentDateTime = date("Y-m-d H:i:s");
$conn = pdo_get_connection();

// $sql = "SELECT * FROM content WHERE next_review <= :currentDateTime ORDER BY level ASC";
$sql = "SELECT * FROM content WHERE next_review <= :currentDateTime AND is_active = 1 ORDER BY create_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':currentDateTime', $currentDateTime, PDO::PARAM_STR);
$stmt->execute();
$contentData = $stmt->fetchAll(PDO::FETCH_ASSOC);


if (empty($contentData)) {
    header('Location: home.php');
    exit;
}

$totalVocabulary = count($contentData);
?>


<style>
    #correctList li {
        color: green;
    }

    #incorrectList li {
        color: red;
    }

    .incorrect-answer {
        color: red;
    }

    .pseudo-class-level {
        position: relative;
    }

    .level-badge {
        position: absolute;
        top: 5px;
        left: 20px;
        color: black;
        font-weight: bold;
        padding: 0 8px;
        border-radius: 10px;
        z-index: 1;
        background: transparent;
        backdrop-filter: blur(3px);
        border: 2px solid rgba(255, 255, 255, .2);
        box-shadow: 0 0 10px rgba(0, 0, 0, .2);
    }

    .edit-this-vocab {
        position: absolute;
        top: 5px;
        right: 20px;
        color: black;
        font-weight: bold;
        padding: 0 8px;
        border-radius: 10px;
        z-index: 1;
        background: transparent;
        backdrop-filter: blur(3px);
        border: 2px solid rgba(255, 255, 255, .2);
        box-shadow: 0 0 10px rgba(0, 0, 0, .2);
    }
</style>

<!-- style cho form -->
<style>
    /* Styles cho modal */
    .modal-dialog {
        max-width: 90%;
    }

    .modal-content {
        background-color: rgba(255, 255, 255, 0.7);
        padding: 20px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.9);
        box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        backdrop-filter: blur(15px);
    }

    /* Form layout styles */
    .container-form {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .left-form,
    .right-form {
        flex: 1;
        min-width: 300px;
    }

    .upload-container {
        display: flex;
        justify-content: space-between;
        gap: 20px;
    }

    /* Input styles */
    .soft-input {
        border: 1px solid rgba(209, 207, 226, 0.7);
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 8px;
        padding: 10px;
        transition: background-color 0.3s ease;
        color: #4A4A4A;
        font-weight: bold;
    }

    .soft-input:focus {
        background-color: rgba(235, 230, 255, 0.9);
        outline: none;
    }

    /* Button styles */
    .btn-save,
    .btn-close,
    .custom-file-upload {
        padding: 10px 20px;
        border-radius: 8px;
        color: #333;
        transition: background-color 0.3s ease;
    }

    .save-close {
        display: flex;
        justify-content: space-between;
        align-items: end;
    }

    .btn-save {
        background-color: rgba(163, 196, 243, 0.9);
        border: none;
    }

    .btn-save:hover {
        background-color: rgba(111, 159, 231, 0.9);
    }

    .btn-close {
        background-color: rgba(255, 179, 179, 0.9);
        border: none;
    }

    .btn-close:hover {
        background-color: rgba(255, 128, 128, 0.9);
    }

    /* Label styles */
    label {
        font-weight: bold;
        color: #333;
    }

    /* Custom file upload button styles */
    .custom-file-upload {
        display: inline-block;
        padding: 10px 20px;
        cursor: pointer;
        background-color: rgba(163, 196, 243, 0.9);
        border-radius: 8px;
        color: #333;
        font-size: 14px;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }

    .custom-file-upload:hover {
        background-color: rgba(111, 159, 231, 0.9);
    }

    /* Hide default file input */
    input[type="file"] {
        display: none;
    }

    /* Upload preview styles */
    .upload-preview {
        position: relative;
        border: 2px solid rgba(209, 207, 226, 0.7);
        background-color: rgba(243, 240, 255, 0.9);
        border-radius: 8px;
        padding: 10px;
        height: 200px;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    /* Preview element styles */
    .preview-box {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        display: none;
        /* Ẩn mặc định, sẽ hiển thị khi có tệp tải lên */
        animation: fadeIn 0.5s ease;
    }

    /* Fade-in animation */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }
</style>
<div class="mt-5">
    <div class="row justify-content-center pseudo-class-level text-shadow-white">
        <div class="col-md-8">
            <span id="levelBadge" class="level-badge"></span>
            <button id="editThisVocab" class="edit-this-vocab text-shadow-white" onclick="editCurrentVocab()">Edit</button>
            <div class="custom-div text-center">
                <h3 class="my-3 text-justify" id="definition"></h3>
                <div class="input-group">
                    <input type="text" class="input-box text-shadow-white" id="vocabularyInput" placeholder="Vocabulary"
                        autocomplete="off">
                </div>
                <div class="input-group mt-3">
                    <input type="text" class="input-box text-shadow-white" id="partOfSpeechInput" placeholder="Part of Speech"
                        autocomplete="off">
                </div>
                <h3 class="my-3 text-justify" id="question"></h3>
                <div class="input-group">
                    <input type="text" class="input-box text-shadow-white" id="answerInput" placeholder="Answer" autocomplete="off">
                </div>
                <div class="text-center mt-5">
                    <button type="button" class="custom-big-btn text-shadow-white" onclick="checkAnswer()">CHECK</button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- checked -->
<div class="modal" id="resultModal" tabindex="-1" role="dialog" aria-labelledby="resultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-custom" role="document">
        <div class="modal-content" style="max-height: 1000px;">
            <div class="row justify-content-between">
                <div class="col-sm-6">
                    <p id="resultMessage"></p>
                    <p><strong><span id="resultVocab"></span></strong><span id="resultPartOfSpeech"></span></p>
                    <!-- Sử dụng d-flex của Bootstrap để căn chỉnh các phần tử -->
                    <div class="d-flex align-items-center mb-3">
                        <div class="custom-btn mr-2">
                            <img id="resultSlowAudioIcon" src="assets/slowaudio.png" class="custom-btn"
                                alt="Play Slow Audio">
                            <audio id="resultAudio" class="d-none"></audio>
                        </div>
                        <div class="custom-btn mr-2">
                            <img id="resultAudioIcon" src="assets/audio.png" class="custom-btn" alt="Play Audio"
                                onclick="playAudio()">
                            <audio id="resultAudio" class="d-none"></audio>
                        </div>
                        <p class="mb-0"><span id="resultIPA"></span></p> <!-- mb-0 loại bỏ margin dưới cùng -->
                    </div>

                    <p><span id="resultDef"></span></p>

                    <p><span id="resultEx"></span></p>
                    <p><strong><span id="resultQuestion"></span></strong></p>
                    <p><span id="resultAnswer"></span></p>
                    <video id="resultVideo" class="w-100 d-none mt-2" controls></video>
                </div>
                <div class="col-sm-6">
                    <img id="resultImage" class="img-fluid d-none" alt="Result Image" stlye="height: 375px;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="nextContent()">Continue</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Phóng To Hình Ảnh -->
<div class="modal" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">PICTURE</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <img id="enlargedImage" src="" class="img-fluid" alt="Hình Ảnh Phóng To">
            </div>
        </div>
    </div>
</div>

<!-- Thống kê -->
<div class="modal" id="statisticsModal" tabindex="-1" role="dialog" aria-labelledby="statisticsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-custom-2" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <p class="text-center"><span class="text-success" id="correctCount"></span> / <span
                        id="totalCount"></span></p>
                <p class="text-center">Total Time: <span id="totalTime"></span></p>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="mr-2">
                        <div id="incorrectListContainer">
                            <h5 class="text-danger text-center">Incorrect Answers</h5>
                            <ul id="incorrectList" class="list-unstyled"></ul>
                        </div>
                    </div>
                    <div class="mr-2">
                        <div id="correctListContainer">
                            <h5 class="text-success text-center">Correct Answers</h5>
                            <ul id="correctList" class="list-unstyled"></ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="redirectToHome()">CONTINUE</button>
            </div>
        </div>
    </div>
</div>


<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="process_edit_content.php" method="post" enctype="multipart/form-data">
                <input type="hidden" id="editId" name="editId" value="">
                <input type="hidden" id="currentUrl" name="currentUrl" value="">
                <div class="container-form">
                    <div class="left-form">
                        <div class="form-group">
                            <label for="editVocab">Vocabulary</label>
                            <input type="text" class="form-control soft-input" id="editVocab" name="editVocab">
                        </div>
                        <div class="form-group">
                            <label for="editPartOfSpeech">Part of Speech</label>
                            <input type="text" class="form-control soft-input" id="editPartOfSpeech"
                                name="editPartOfSpeech" value="()">
                        </div>
                        <div class="form-group">
                            <label for="editIPA">IPA</label>
                            <input type="text" class="form-control soft-input" id="editIPA" name="editIPA">
                        </div>
                        <div class="form-group">
                            <label for="editDef">Definition</label>
                            <textarea class="form-control soft-input" id="editDef" name="editDef" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="editExample">Example</label>
                            <textarea class="form-control soft-input" id="editExample" name="editExample"
                                rows="2"></textarea>
                        </div>
                    </div>
                    <div class="right-form">
                        <div class="form-group">
                            <label for="editQuestion">Question</label>
                            <textarea class="form-control soft-input" id="editQuestion" name="editQuestion"
                                rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="editAnswer">Answer</label>
                            <textarea class="form-control soft-input" id="editAnswer" name="editAnswer"
                                rows="2"></textarea>
                        </div>
                        <div class="upload-container">
                            <div class="form-group">
                                <label for="editImage">Image</label>
                                <div class="upload-preview">
                                    <label class="custom-file-upload">
                                        <input type="file" id="editImage" name="editImage" accept="image/*"
                                            onchange="previewFile(this, 'editImagePreview')">
                                        Choose Image
                                    </label>
                                    <img id="editImagePreview" class="preview-box" alt="Image Preview">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="editVideo">Video</label>
                                <div class="upload-preview">
                                    <label class="custom-file-upload">
                                        <input type="file" id="editVideo" name="editVideo" accept="video/*"
                                            onchange="previewFile(this, 'editVideoPreview')">
                                        Choose Video
                                    </label>
                                    <video id="editVideoPreview" class="preview-box" controls></video>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="editAudio">Audio</label>
                                <div class="upload-preview">
                                    <label class="custom-file-upload">
                                        <input type="file" id="editAudio" name="editAudio" accept="audio/*"
                                            onchange="previewFile(this, 'editAudioPreview')">
                                        Choose Audio
                                    </label>
                                    <audio id="editAudioPreview" class="preview-box" controls></audio>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center save-close">
                    <button type="button" class="btn btn-close" data-dismiss="modal">Close</button>
                    <h6>Enhance your skills with our resources!</h6>
                    <button type="submit" class="btn btn-save" name="update">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    let totalResponseTime = 0;
    let currentIndex = 0;
    let correctCount = 0;
    let incorrectItems = []; // Mảng mới để lưu trữ các câu sai
    let correctItems = [];
    let contentData = <?php echo json_encode($contentData); ?>;
    console.log(contentData);
    let mainContentDiv = document.querySelector('.mt-3');
    let resultModal = document.getElementById('resultModal');
    let statisticsModal = document.getElementById('statisticsModal');
    let totalLevels = <?php echo json_encode($totalLevels); ?>;

    let userLevels = <?php echo ($userLevels); ?>;
    let fractionalPart = <?php echo ($fractionalPart); ?>;

    console.log(totalLevels, userLevels, fractionalPart);

    // Lấy URL hiện tại và đặt vào input ẩn
    document.getElementById('currentUrl').value = window.location.href;

    let englishVoices = []; // Mảng lưu trữ các giọng đọc tiếng Anh
    let currentVoiceIndex = 0; // Biến theo dõi index giọng đọc hiện tại

    function populateEnglishVoices() {
        if (speechSynthesis.getVoices().length > 0) {
            englishVoices = speechSynthesis.getVoices().filter(voice => voice.lang.startsWith('en'));
        } else {
            // Nếu danh sách giọng đọc chưa sẵn sàng, thử lại sau một chút
            setTimeout(populateEnglishVoices, 100);
        }
    }

    populateEnglishVoices(); // Gọi hàm này khi trang load để lấy danh sách giọng đọc

    function getRandomEnglishVoice() {
        if (englishVoices.length > 0) {
            currentVoiceIndex = Math.floor(Math.random() * englishVoices.length);
            return englishVoices[currentVoiceIndex];
        }
        return null;
    }


    function displayContent() {
        var currentContent = contentData[currentIndex];
        var definition = document.getElementById('definition');
        var question = document.getElementById('question');
        var vocabInput = document.getElementById('vocabularyInput');
        var partOfSpeechInput = document.getElementById('partOfSpeechInput');
        var answerInput = document.getElementById('answerInput');
        var imageContainer = document.getElementById('imageContainer');
        var levelBadge = document.getElementById('levelBadge');
        var resultAudioIcon = document.getElementById('resultAudioIcon');

        levelBadge.textContent = currentContent.level;

        // Kiểm tra và hiển thị hình ảnh
        if (currentContent.image_path && currentContent.image_path.trim() !== '') {
            if (!imageContainer) {
                // Tạo container cho hình ảnh nếu chưa có
                imageContainer = document.createElement('div');
                imageContainer.id = 'imageContainer';
                definition.parentNode.insertBefore(imageContainer, definition.nextSibling);
            }
            imageContainer.innerHTML = '<img src="' + currentContent.image_path +
                '" class="img-fluid" alt="Vocabulary Image" style="border-radius: 20px; height: 350px;">';
            imageContainer.style.display = 'block';
        } else if (imageContainer) {
            imageContainer.style.display = 'none';
        }

        if (currentContent.def) {
            definition.innerText = currentContent.def;
            definition.style.display = 'block';
            vocabInput.style.display = 'block';
        } else {
            definition.style.display = 'none';
            vocabInput.style.display = 'none';
        }

        // Xử lý cho trường part_of_speech
        if (currentContent.part_of_speech) {
            partOfSpeechInput.style.display = 'block';
        } else {
            partOfSpeechInput.style.display = 'none';
        }

        if (currentContent.question) {
            question.innerText = currentContent.question;
            question.style.display = 'block';
            answerInput.style.display = 'block';
        } else {
            question.style.display = 'none';
            answerInput.style.display = 'none';
        }

        vocabInput.value = "";
        partOfSpeechInput.value = "";
        answerInput.value = "";

        if (!currentContent.def && currentContent.question) {
            answerInput.focus();
        } else if (currentContent.def) {
            vocabInput.focus();
        }

        // Ẩn/hiện nút audio dựa trên trường vocab
        if (currentContent.vocab === null || currentContent.vocab === "") {
            resultAudioIcon.classList.add('d-none');
            resultSlowAudioIcon.classList.add('d-none');
        } else {
            resultAudioIcon.classList.remove('d-none');
            resultSlowAudioIcon.classList.remove('d-none');
        }

        // Reset audio source and state for new vocabulary
        var resultAudio = document.getElementById('resultAudio');
        resultAudio.src = ''; // Clear previous audio source
        resultAudio.pause(); // Ensure any playing audio is stopped

        if (currentContent.audio_path) {
            resultAudio.src = currentContent.audio_path;
        }

        startTimer();
    }

    function checkAnswer() {
        var responseTime = endTimer();
        var userVocabulary = document.getElementById('vocabularyInput').value.trim().toLowerCase();
        var userPartOfSpeech = document.getElementById('partOfSpeechInput').value.trim().toLowerCase();
        var userAnswer = document.getElementById('answerInput').value.trim(); // Giữ nguyên định dạng cho đáp án
        var currentContent = contentData[currentIndex];
        var resultAudioIcon = document.getElementById('resultAudioIcon');


        // Loại bỏ dấu chấm chỉ từ đáp án người dùng nhập vào ô Vocabulary
        userVocabulary = userVocabulary.replace(/\./g, '');
        var correctVocabulary = currentContent.vocab.toLowerCase().replace(/\./g, '');

        // Chuyển đổi các viết tắt sang dạng đầy đủ
        var partOfSpeechMap = {
            'n': '(noun)',
            'adj': '(adjective)',
            'v': '(verb)',
            'exc': '(exclamation)',
            'con': '(conjunction)',
            'adv': '(adverb)',
            'pre': '(preposition)',
            'pro': '(pronoun)',
            'int': '(interjection)',
            'art': '(article)',
            'num': '(numeral)',
            'det': '(determiner)',
            'mod': '(modal verb)',
            'pv': '(phrasal verb)',
            'p': '(phrase)',
            'col': '(collocation)',
            'idi': '(idiom)',
            'ord': '(ordinal number)'
        };

        userPartOfSpeech = partOfSpeechMap[userPartOfSpeech] || userPartOfSpeech;
        var correctPartOfSpeech = partOfSpeechMap[currentContent.part_of_speech.toLowerCase()] || currentContent
            .part_of_speech.toLowerCase();

        var correctHtml = correctVocabulary + " " + correctPartOfSpeech;
        var incorrectHtml = "";

        if (userVocabulary !== correctVocabulary || userPartOfSpeech !== correctPartOfSpeech) {
            incorrectHtml = `<span class="incorrect-answer"><br>${userVocabulary} ${userPartOfSpeech}</span>`;
        }

        var correctAnswer = currentContent.answer;

        var resultMessage;
        var isCorrect = userVocabulary === correctVocabulary && userAnswer === correctAnswer && userPartOfSpeech ===
            correctPartOfSpeech;


        if (isCorrect) {
            resultMessage = "CORRECT!";
            document.getElementById('resultMessage').classList.remove('text-danger');
            document.getElementById('resultMessage').classList.add('text-success');
            correctCount++;
            correctItems.push(currentContent);
            playAudioCheck('onisama_short.mp3');
            // Cập nhật totalLevels
            ++totalLevels;
            userLevels = Math.trunc(totalLevels / 1000);
            fractionalPart = totalLevels / 1000 - userLevels;
            updateProgressBarWhenCorrect();
        } else {
            resultMessage = "INCORRECT!";
            document.getElementById('resultMessage').classList.remove('text-success');
            document.getElementById('resultMessage').classList.add('text-danger');
            incorrectItems.push(currentContent);
            playAudioCheck('ara_ara_sound.mp3');
            // Cập nhật totalLevels
            --totalLevels;
            userLevels = Math.trunc(totalLevels / 1000);
            fractionalPart = totalLevels / 1000 - userLevels;
            updateProgressBarWhenIncorrect();

        }

        // Cập nhật giao diện người dùng với thông tin chi tiết
        document.getElementById('resultVocab').innerHTML = correctHtml + incorrectHtml;
        document.getElementById('resultIPA').innerText = " " + currentContent.ipa;
        document.getElementById('resultEx').innerText = " " + currentContent.ex;
        document.getElementById('resultEx').classList.add('font-italic');
        document.getElementById('resultDef').innerText = " " + currentContent.def;

        // Kiểm tra xem question và answer có trống không
        if (currentContent.question) {
            document.getElementById('resultQuestion').innerText = " " + currentContent.question;
            document.getElementById('resultQuestion').parentNode.style.display = 'block';
        } else {
            document.getElementById('resultQuestion').parentNode.style.display = 'none';
        }

        if (currentContent.answer) {
            var incorrectAnswerHtml = userAnswer !== correctAnswer ?
                `<span class="incorrect-answer"><br>${userAnswer}</span>` : '';
            document.getElementById('resultAnswer').innerHTML = currentContent.answer + incorrectAnswerHtml;
            document.getElementById('resultAnswer').parentNode.style.display = 'block';
        } else {
            document.getElementById('resultAnswer').parentNode.style.display = 'none';
        }

        if (currentContent.image_path) {
            document.getElementById('resultImage').src = currentContent.image_path;
            document.getElementById('resultImage').classList.remove('d-none');
        } else {
            document.getElementById('resultImage').classList.add('d-none');
        }

        if (currentContent.video_path) {
            document.getElementById('resultVideo').src = currentContent.video_path;
            document.getElementById('resultVideo').classList.remove('d-none');
        } else {
            document.getElementById('resultVideo').classList.add('d-none');
        }

        if (currentContent.audio_path) {
            document.getElementById('resultAudio').src = currentContent.audio_path;
            // Icons audio luôn hiển thị
            // Đặt lại tốc độ phát âm thanh và tự động phát
            document.getElementById('resultAudio').playbackRate = 1.0;
            document.getElementById('resultAudio').play();
        } else {
            // Icons audio luôn hiển thị
        }

        // Tự động phát âm thanh TTS sau khi hiển thị modal kết quả (nếu không có audio file và vocab không rỗng)
        if (!currentContent.audio_path && currentContent.vocab && currentContent.vocab.trim() !== "") {
            speakVocabulary();
        }


        document.getElementById('resultMessage').innerText = resultMessage;

        // Hiển thị modal với kết quả
        $('#resultModal').modal('show');

        // Gửi thông tin cập nhật đến máy chủ
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var response = JSON.parse(this.responseText);
                if (!response.success) {
                    console.error("Error updating content status:", response.error);
                }
            }
        };

        // Gửi thông tin cập nhật đến máy chủ, bao gồm thời gian trả lời
        var xhttp = new XMLHttpRequest();
        xhttp.open("POST", "update_spaced_repetition.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("content_id=" + currentContent.content_id + "&is_correct=" + (isCorrect ? 1 : 0) + "&response_time=" +
            responseTime);

        // Increment vocab_reviewed_count
        var vocabHttp = new XMLHttpRequest();
        vocabHttp.open("POST", "update_vocab_reviewed_count.php", true);
        vocabHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        vocabHttp.send("increment_vocab=1"); // Adjust as needed for your backend logic

        // Tính lại userLevels và fractionalPart
        userLevels = Math.trunc(totalLevels / 1000);
        fractionalPart = (totalLevels / 1000) - userLevels;


        showResultModal();
    }

    function editCurrentVocab() {
        // Lấy nội dung hiện tại từ mảng contentData
        let currentContent = contentData[currentIndex];

        // Điền dữ liệu vào form chỉnh sửa
        fillEditModal(
            currentContent.content_id, // Lấy id của từ vựng hiện tại
            currentContent.vocab,
            currentContent.part_of_speech,
            currentContent.ipa,
            currentContent.def,
            currentContent.ex,
            currentContent.question,
            currentContent.answer,
            currentContent.image_path,
            currentContent.video_path,
            currentContent.audio_path
        );
    }

    function fillEditModal(id, vocab, partOfSpeech, ipa, def, ex, question, answer, imagePath, videoPath, audioPath) {
        document.getElementById('editId').value = id;
        document.getElementById('editVocab').value = vocab;
        document.getElementById('editPartOfSpeech').value = partOfSpeech;
        document.getElementById('editIPA').value = ipa;
        document.getElementById('editDef').value = def;
        document.getElementById('editExample').value = ex;
        document.getElementById('editQuestion').value = question;
        document.getElementById('editAnswer').value = answer;

        // Hiển thị bản xem trước của file nếu có
        displayFilePreview(imagePath, 'editImagePreview');
        displayFilePreview(videoPath, 'editVideoPreview');
        displayFilePreview(audioPath, 'editAudioPreview');

        // Mở modal chỉnh sửa
        $('#editModal').modal('show');
    }

    function displayFilePreview(filePath, previewElementId) {
        const previewElement = document.getElementById(previewElementId);
        if (filePath) {
            previewElement.src = filePath;
            previewElement.style.display = 'block';
        } else {
            previewElement.style.display = 'none';
        }
    }


    function playAudioCheck(audioPath) {
        var audio = new Audio('assets/audio/' + audioPath);
        audio.play();
    }

    function playAudio() {
        var audio = document.getElementById('resultAudio');
        var currentContent = contentData[currentIndex];

        if (currentContent.audio_path) {
            audio.currentTime = 0;
            audio.playbackRate = 1;
            audio.play();
        } else {
            speakVocabulary(1.0);
        }
    }

    function playSlowAudio() {
        var audio = document.getElementById('resultAudio');
        var currentContent = contentData[currentIndex];

        if (currentContent.audio_path) {
            audio.currentTime = 0;
            audio.playbackRate = 0.5;
            audio.play();
        } else {
            speakVocabulary(0.7);
        }
    }

    function speakVocabulary(rate = 1.0) {
        speechSynthesis.cancel(); // Hủy bỏ bất kỳ TTS đang chạy trước đó để tránh xung đột
        const vocabText = contentData[currentIndex].vocab;
        if ('speechSynthesis' in window) {
            const utterance = new SpeechSynthesisUtterance(vocabText);
            utterance.text = vocabText; // Set text explicitly
            utterance.rate = rate; // Set rate for slow/normal speed
            let selectedVoice = getRandomEnglishVoice(); // Get random English voice
            if (selectedVoice) {
                utterance.voice = selectedVoice; // Set voice to utterance
            }
            speechSynthesis.speak(utterance);
        } else {
            console.error("Trình duyệt của bạn không hỗ trợ Text-to-Speech API");
            alert("Tính năng đọc từ vựng không được hỗ trợ trên trình duyệt này.");
        }
    }


    // Gán hàm cho nút slowaudio
    var slowAudioButton = document.getElementById('resultSlowAudioIcon');
    if (slowAudioButton) {
        slowAudioButton.onclick = playSlowAudio;
    }


    // Hàm phát video
    function playVideo() {
        var video = document.getElementById('resultVideo');
        if (video.src) {
            video.play();
        }
    }


    function nextContent() {
        // Tăng currentIndex trước khi kiểm tra
        currentIndex++;

        if (currentIndex >= contentData.length) {
            // Hiển thị thống kê khi đã hoàn thành toàn bộ từ vựng
            document.getElementById('correctCount').innerText = correctCount;
            document.getElementById('totalCount').innerText = contentData.length;
            $('#statisticsModal').modal('show');

            var correctList = document.getElementById('correctList');
            correctList.innerHTML = ''; // Xóa danh sách cũ
            correctItems.forEach(function(item) {
                var li = document.createElement('li');
                li.innerText = item.vocab + " | " + item.question;
                li.style.color = 'green'; // Thêm màu sắc cho các từ đúng
                correctList.appendChild(li);
            });

            var incorrectList = document.getElementById('incorrectList');
            incorrectList.innerHTML = ''; // Xóa danh sách cũ
            incorrectItems.forEach(function(item) {
                var li = document.createElement('li');
                li.innerText = item.vocab + " | " + item.question;
                li.style.color = 'red'; // Thêm màu sắc cho các từ sai
                incorrectList.appendChild(li);
            });

            showStatisticsModal(); // Hiển thị thống kê và ẩn các khối khác
        } else {
            // Đặt timeout để chắc chắn modal đã đóng và giao diện được cập nhật
            setTimeout(function() {
                displayContent();
            }, 100); // Độ trễ 100ms, có thể điều chỉnh nếu cần
            $('#resultModal').modal('hide');
            showMainContent(); // Quay trở lại nội dung chính và ẩn các khối khác
        }
    }

    // Hiển thị nội dung ban đầu
    displayContent();

    document.addEventListener('keydown', function(event) {
        if (event.code === 'Enter') {
            enterFullScreenMode();
            event.preventDefault(); // Ngăn chặn hành động mặc định của phím Enter
            var statisticsModal = document.getElementById('statisticsModal');
            var resultModal = document.getElementById('resultModal');
            var vocabInput = document.getElementById('vocabularyInput');
            var partOfSpeechInput = document.getElementById('partOfSpeechInput');
            var answerInput = document.getElementById('answerInput');
            var currentContent = contentData[currentIndex];

            // Kiểm tra xem modal thống kê có đang hiển thị hay không
            if (statisticsModal.classList.contains('show')) {
                redirectToHome();
            } else if (resultModal.classList.contains('show')) {
                nextContent();
            } else {
                // Kiểm tra và chuyển focus giữa các ô nhập liệu
                if (currentContent.def && !vocabInput.value.trim()) {
                    vocabInput.focus();
                } else if (currentContent.part_of_speech && !partOfSpeechInput.value.trim()) {
                    partOfSpeechInput.focus();
                } else if (currentContent.question && !answerInput.value.trim()) {
                    answerInput.focus();
                } else {
                    checkAnswer();
                }

                // Thêm sự kiện keydown cho mỗi trường nhập liệu
                vocabInput.addEventListener('keydown', handleBackspace);
                partOfSpeechInput.addEventListener('keydown', handleBackspace);
                answerInput.addEventListener('keydown', handleBackspace);

                function handleBackspace(event) {
                    if (event.key === 'Backspace' && event.target.value.trim() === '') {
                        // Ngăn chặn xóa ký tự
                        event.preventDefault();

                        // Di chuyển focus tới trường trước đó hoặc trường cuối cùng
                        switch (event.target.id) {
                            case 'answerInput':
                                if (currentContent.question) {
                                    partOfSpeechInput.focus();
                                } else {
                                    vocabInput.focus();
                                }
                                break;
                            case 'partOfSpeechInput':
                                vocabInput.focus();
                                break;
                            case 'vocabularyInput':
                                // Di chuyển focus đến trường cuối cùng
                                answerInput.focus();
                                break;
                        }
                    }
                }


                // Kiểm tra nếu Ctrl + Enter được nhấn
                if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                    event.preventDefault(); // Ngăn chặn hành động mặc định
                    checkAnswer(); // Gọi hàm kiểm tra
                }
            }
        }

        // Optimize event handling when resultModal is displayed
        if ($('#resultModal').hasClass('show')) {
            switch (event.key) {
                case 'ArrowRight':
                    skipForwardVideo();
                    break;
                case 'ArrowLeft':
                    skipBackwardVideo();
                    break;
                case 'm':
                    playAudio();
                    break;
                case 'n':
                    playSlowAudio();
                    break;
                case 'f':
                    toggleFullscreenVideo();
                    break;
                case 'b':
                    toggleEnlargeImage();
                    break;
            }
        }

        if ($('#resultModal').hasClass('show')) {
            switch (event.code) {
                case 'Space':
                    event.preventDefault(); // Ngăn chặn hành động mặc định của phím Space
                    togglePlayPauseVideo();
                    break;
            }
        }

    });


    function redirectToHome() {
        window.location.href = 'home.php';
    }

    function showMainContent() {
        mainContentDiv.style.display = 'block';
        resultModal.style.display = 'none';
        statisticsModal.style.display = 'none';
    }

    function showResultModal() {
        mainContentDiv.style.display = 'none';
        resultModal.style.display = 'block';
        statisticsModal.style.display = 'none';
    }

    function showStatisticsModal() {
        mainContentDiv.style.display = 'none';
        resultModal.style.display = 'none';
        statisticsModal.style.display = 'block';
        // Hiển thị tổng thời gian
        var totalMinutes = Math.floor(totalResponseTime / 60000); // Chuyển đổi milliseconds thành phút
        var totalSeconds = ((totalResponseTime % 60000) / 1000).toFixed(0); // Tính giây còn lại
        document.getElementById('totalTime').innerText = totalMinutes + " phút " + (totalSeconds < 10 ? '0' : '') +
            totalSeconds + " giây";
        playStatisticsSound();
    }

    function playStatisticsSound() {
        var audio = new Audio('assets/audio/anime_tuturu.mp3');
        audio.play();
    }

    function toggleFullscreenVideo() {
        var video = document.getElementById('resultVideo');
        if (!video) return;

        if (!document.fullscreenElement) {
            if (video.requestFullscreen) {
                video.requestFullscreen();
            } else if (video.mozRequestFullScreen) { // Firefox
                video.mozRequestFullScreen();
            } else if (video.webkitRequestFullscreen) { // Chrome, Safari & Opera
                video.webkitRequestFullscreen();
            } else if (video.msRequestFullscreen) { // IE/Edge
                video.msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) { // Firefox
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) { // Chrome, Safari & Opera
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) { // IE/Edge
                document.msExitfullscreen();
            }
        }
    }

    function toggleEnlargeImage() {
        var imageModal = $('#imageModal');

        if (imageModal.hasClass('show')) {
            imageModal.modal('hide'); // Ẩn modal nếu nó đang hiển thị
        } else {
            var image = document.getElementById('resultImage');
            var enlargedImage = document.getElementById('enlargedImage');
            if (image && enlargedImage) {
                enlargedImage.src = image.src; // Đặt src cho ảnh trong modal
                imageModal.modal('show');
            }
        }
    }


    function togglePlayPauseVideo() {
        var video = document.getElementById('resultVideo');
        if (video.src) {
            if (video.paused || video.ended) {
                video.play();
            } else {
                video.pause();
            }
        }
    }

    function skipForwardVideo() {
        var video = document.getElementById('resultVideo');
        if (video.src) {
            video.currentTime += 5;
        }
    }

    function skipBackwardVideo() {
        var video = document.getElementById('resultVideo');
        if (video.src) {
            video.currentTime -= 5;
        }
    }

    document.getElementById('resultImage').addEventListener('dblclick', function() {
        var src = this.src; // Lấy đường dẫn của hình ảnh
        document.getElementById('enlargedImage').src = src; // Đặt đường dẫn này cho hình ảnh trong modal
        $('#imageModal').modal('show'); // Hiển thị modal
    });


    var startTime, endTime;

    function startTimer() {
        startTime = new Date();
    }

    function endTimer() {
        endTime = new Date();
        var timeDiff = endTime - startTime; // thời gian tính bằng milliseconds
        totalResponseTime += timeDiff; // Cộng dồn vào tổng thời gian
        return timeDiff;
    }

    function enterFullScreenMode() {
        var elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.mozRequestFullScreen) { // Firefox
            elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) { // Chrome, Safari và Opera
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) { // IE/Edge
            elem.msRequestFullscreen();
        }
    }

    function toggleHeaderDisplay() {
        var header = document.querySelector('header');
        if (!header) return;
        if (document.fullscreenElement) {
            header.style.display = 'none';
        } else {
            header.style.display = 'block';
        }
    }

    // Đặt vị trí của marker-divider khi trang tải lần đầu
    window.onload = function() {
        const progressBar = document.getElementById('progressBar');
        const progressBarWidth = progressBar.offsetWidth;
        const progressContainerWidth = progressBar.parentElement.offsetWidth;

        // Tính toán tỉ lệ phần trăm
        const positionPercentage = (progressBarWidth / progressContainerWidth) * 100;

        // Tạo marker-divider
        const markerDivider = document.createElement('div');
        markerDivider.classList.add('marker-divider');

        // Đặt vị trí bằng phần trăm cộng thêm 2px
        markerDivider.style.left = `calc(${positionPercentage}% + 1.6px)`;

        // Thêm marker-divider vào progress-container
        progressBar.parentElement.appendChild(markerDivider);

        // Phát audio khi vừa mới truy cập trang
        var audio = new Audio('./assets/audio/anime_school_bells.mp3');
        audio.play();
    }

    // Hàm cập nhật giao diện khi đúng
    function updateProgressBarWhenCorrect() {
        const progressBar = document.getElementById('progressBar');

        // Thay đổi màu sắc của thanh tiến độ
        progressBar.style.backgroundColor = '#caf0f6';
        progressBar.style.width = (fractionalPart * 100) + '%';

        // Thêm lớp 'expanded' để phóng to thanh tiến độ
        progressBar.classList.add('expanded');

        // Sau 0.5 giây, thay đổi màu sắc trở lại màu cũ và loại bỏ lớp 'expanded'
        setTimeout(() => {
            progressBar.style.backgroundColor = '#65e300';
            progressBar.classList.remove('expanded');
        }, 650);
        console.log(totalLevels, userLevels, fractionalPart);

    }

    // Hàm cập nhật giao diện khi sai
    function updateProgressBarWhenIncorrect() {
        const progressBar = document.getElementById('progressBar');

        // Thay đổi màu sắc của thanh tiến độ
        progressBar.style.backgroundColor = '#dd6670';
        progressBar.style.width = (fractionalPart * 100) + '%';

        // Thêm lớp 'expanded' để phóng to thanh tiến độ
        progressBar.classList.add('expanded');

        setTimeout(() => {
            progressBar.style.backgroundColor = '#65e300';
            progressBar.classList.remove('expanded');
        }, 650);
        console.log(totalLevels, userLevels, fractionalPart);

    }

    document.addEventListener('fullscreenchange', toggleHeaderDisplay);

    toggleHeaderDisplay();
</script>
</body>

</html>