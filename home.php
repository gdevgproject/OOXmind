<?php
include 'view/header.php';
$colors = [
    '#e57373', // Đỏ nhạt
    '#ff8a65', // Cam đậm nhạt
    '#ffb74d', // Cam nhạt
    '#ffd54f', // Vàng đậm
    '#fff176', // Vàng nhạt
    '#aed581', // Xanh lục đậm
    '#81c784', // Xanh lục nhạt
    '#4dd0e1', // Xanh ngọc nhạt
    '#64b5f6', // Xanh dương nhạt
    '#ce93d8',  // Tím nhạt
    '#ba68c8' // Tím đậm nhạt
];
$colorIndex = 6;
?>

<style>
    .notebook-link {
        display: inline-block;
        /* Đảm bảo liên kết là khối nội tuyến */
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        /* Hiệu ứng chuyển tiếp */
    }

    .notebook-link:hover .notebook-img {
        transform: scale(1.2);
        /* Phóng to hình ảnh khi hover */
        filter: brightness(1.2);
        /* Tăng độ sáng hình ảnh khi hover */
    }

    .notebook-link:active .notebook-img {
        transform: scale(0.9);
        /* Giảm kích thước hình ảnh khi nhấn */
    }

    .notebook-link:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        /* Tạo bóng cho liên kết khi hover */
    }

    .add-link {
        display: inline-block;
        /* Đảm bảo liên kết là khối nội tuyến */
        transition: transform 0.3s ease;
        /* Hiệu ứng chuyển tiếp */
        animation: spin 8s linear infinite;
        /* Hiệu ứng xoay liên tục */
    }

    .add-img {
        transition: transform 0.3s ease;
        /* Hiệu ứng chuyển tiếp cho hình ảnh */
    }

    .add-link:hover {
        animation: none;
        /* Ngừng animation khi hover */
    }

    .add-link:hover .add-img {
        transform: scale(1.2);
        /* Phóng to hình ảnh khi hover */
    }

    .add-link:active .add-img {
        transform: scale(0.9);
        /* Giảm kích thước hình ảnh khi nhấn */
    }

    /* Hiệu ứng xoay */
    @keyframes spin {
        from {
            transform: rotate(0deg);
            /* Bắt đầu từ góc 0 độ */
        }

        to {
            transform: rotate(360deg);
            /* Kết thúc tại góc 360 độ */
        }
    }
</style>

    <div>
        <div class="row justify-content-center">
            <div class="col-md-6 text-md-left">
                <div class="mt-3 custom-div-2 d-flex align-items-center justify-content-center">
                <h2 class='mb-0 ms-2'
                    style="border: 1px solid #607d8b; padding: 7px 10px; border-radius: 10px; font-weight: 600;">
                    <a href="./index.php" class="notebook-link">
                        <img src="assets/notebook.png" alt="notebook" width="40px" class="me-2 notebook-img">
                    </a>
                    <span style="color: #4FC3F7;" class="text-shadow"><?php echo $totalVocabCount; ?> .</span>
                    <span style="color: #81c784;" class="text-shadow"><?= $vocabCount; ?></span>
                    <span style="color: #FFB74D;" class="text-shadow">vocabs</span>
                    <span style="color: #64B5F6;" class="text-shadow"><?= $otherCount; ?></span>
                    <span style="color: #BA68C8;" class="text-shadow">questions</span>
                </h2>
                    <a type="button" data-toggle="modal" data-target="#addNewModal" class="ml-3 add-link"
                        style="border: 1px solid black; border-radius: 50%; padding: 9px;">
                        <img src="assets/add.png" alt="" width="35px" class="add-img">
                    </a>
                </div>

                <div id="chart" class="my-3 custom-div"></div>
                <div>

                </div>
                <?php if ($count > 0): ?>
                    <div class="text-center" style="margin: 35px 0;">
                        <div class="text-center">
                            <a href="practice.php" class="custom-big-btn text-shadow" style="background-color: <?= $reviewColor; ?>; color: #FFFFFF;">REVIEW NOW</a>
                        </div>
                    </div>
                <?php endif; ?>
                <div>
                    <?php foreach ($upcomingWords as $word): ?>
                        <div class="custom-div d-flex align-items-center mb-1 text-shadow" style="color: #fff176;">
                            <div class="text-center" style="padding: 7px 10px; border-radius: 15px; background-color: <?= $colors[$colorIndex]; ?>">
                                <div>
                                    <img src="assets/notification-bell.png" alt="đồng hồ cát" width="22px">
                                </div>
                                <div>
                                    <strong class="countdown" data-next-review="<?= $word['next_review'] ?>"
                                        data-last-review="<?= $word['last_review'] ?>"></strong>
                                </div>
                            </div>
                            <div class="text-justify text-shadow" style="padding-left: 20px; color: <?= $colors[$colorIndex]; ?>">
                                <strong>
                                    <?= mb_strlen($word['vocab']) > 100 ? mb_substr($word['vocab'], 0, 100) . ' ...' : $word['vocab'] ?>
                                </strong>
                                <strong>
                                    <?= mb_strlen($word['question']) > 100 ? mb_substr($word['question'], 0, 100) . ' ...' : $word['question'] ?>
                                </strong>
                            </div>
                        </div>
                        <?php $colorIndex = ($colorIndex + 1) % count($colors); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Styles for the modal */
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
            gap: 10px;
        }

        /* Input styles */
        .soft-input {
            border: 1px solid rgba(209, 207, 226, 0.7);
            background-color: rgba(243, 240, 255, 0.9);
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
        #imagePreview,
        #videoPreview,
        #audioPreview {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: none;
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

    <!-- Add New Modal -->
    <div class="modal" id="addNewModal" tabindex="-1" role="dialog" aria-labelledby="addNewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="process_add_content.php" method="post" enctype="multipart/form-data">
                    <div class="container-form">
                        <div class="left-form">
                            <!-- Các trường thông tin khác -->
                            <div class="form-group">
                                <label for="newVocab">Vocabulary</label>
                                <input type="text" class="form-control soft-input" id="newVocab" name="newVocab">
                            </div>
                            <div class="form-group">
                                <label for="newPartOfSpeech">Part of speech</label>
                                <input type="text" class="form-control soft-input" id="newPartOfSpeech"
                                    name="newPartOfSpeech" value="()">
                            </div>
                            <div class="form-group">
                                <label for="newIPA">IPA</label>
                                <input type="text" class="form-control soft-input" id="newIPA" name="newIPA">
                            </div>
                            <div class="form-group">
                                <label for="newDef">Definition</label>
                                <textarea class="form-control soft-input" id="newDef" name="newDef" rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="newExample">Example</label>
                                <textarea class="form-control soft-input" id="newExample" name="newExample"
                                    rows="2"></textarea>
                            </div>
                        </div>
                        <div class="right-form">
                            <div class="form-group">
                                <label for="newQuestion">Question</label>
                                <textarea class="form-control soft-input" id="newQuestion" name="newQuestion"
                                    rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="newAnswer">Answer</label>
                                <textarea class="form-control soft-input" id="newAnswer" name="newAnswer"
                                    rows="2"></textarea>
                            </div>
                            <div class="upload-container">
                                <div class="form-group">
                                    <label for="newImage">Image</label>
                                    <div class="upload-preview">
                                        <label class="custom-file-upload">
                                            <input type="file" id="newImage" name="newImage" accept="image/*"
                                                onchange="previewFile(this, 'imagePreview')">
                                            Choose Image
                                        </label>
                                        <img id="imagePreview" class="preview-box" alt="Image Preview">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="newVideo">Video</label>
                                    <div class="upload-preview">
                                        <label class="custom-file-upload">
                                            <input type="file" id="newVideo" name="newVideo" accept="video/*"
                                                onchange="previewFile(this, 'videoPreview')">
                                            Choose Video
                                        </label>
                                        <video id="videoPreview" class="preview-box" controls></video>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="newAudio">Audio</label>
                                    <div class="upload-preview">
                                        <label class="custom-file-upload">
                                            <input type="file" id="newAudio" name="newAudio" accept="audio/*"
                                                onchange="previewFile(this, 'audioPreview')">
                                            Choose Audio
                                        </label>
                                        <audio id="audioPreview" class="preview-box" controls></audio>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center save-close">
                        <button type="button" class="btn btn-close" data-dismiss="modal">Close</button>
                        <h6>Get an IELTS score of 7.5!</h6>
                        <button type="submit" class="btn btn-save" name="save">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        function previewFile(input, previewId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const previewElement = document.getElementById(previewId);
                    if (['IMG', 'VIDEO', 'AUDIO'].includes(previewElement.tagName)) {
                        previewElement.src = e.target.result;
                        previewElement.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        function handleEnterPress(event) {
            if (event.keyCode === 13) {
                const practiceButton = document.querySelector('.custom-big-btn');
                if (practiceButton && practiceButton.offsetParent !== null) {
                    window.location.href = practiceButton.getAttribute('href');
                }
            }
        }

        function refreshPageWhenCountdownEnds() {
            const countdownElements = document.querySelectorAll('.countdown');
            countdownElements.forEach((element) => {
                const nextReviewTime = new Date(element.dataset.nextReview).getTime();
                const lastReviewTime = new Date(element.dataset.lastReview).getTime();

                setInterval(() => updateCountdown(element, nextReviewTime), 1000);
            });
        }

        function updateCountdown(element, endTime) {
            const currentTime = Date.now();
            const timeDifference = endTime - currentTime;

            if (timeDifference <= 0) {
                element.style.display = 'none';
                setTimeout(() => location.reload(), 4000);
                return;
            }

            const hours = String(Math.floor(timeDifference / (1000 * 60 * 60))).padStart(2, '0');
            const minutes = String(Math.floor((timeDifference % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
            const seconds = String(Math.floor((timeDifference % (1000 * 60)) / 1000)).padStart(2, '0');

            element.innerHTML = `${hours}:${minutes}:${seconds}`;
        }

        refreshPageWhenCountdownEnds();

        const data = <?php echo $jsonData; ?>;

        // Sắp xếp lại dữ liệu từ cấp độ cao nhất đến thấp nhất
        data.sort((a, b) => a.level - b.level);

        // Xử lý dữ liệu, lấy ra số lượng từ vựng cho mỗi cấp độ
        const vocabCountByLevel = data.map(item => item.vocab_count);

        // Mảng màu sắc tương ứng với từng cấp độ
        const colors = ['#e57373', '#ffb74d', '#fff176', '#81c784', '#4dd0e1', '#64b5f6', '#ba68c8'];



        // Tính tổng số lượng từ vựng của 7 cấp độ cao nhất
        const totalVocabCount = vocabCountByLevel.reduce((a, b) => a + b, 0);

        // Tính chiều cao tương ứng với tỷ lệ phần trăm số lượng từ vựng của từng cấp độ
        const heights = vocabCountByLevel.map(count => Math.min((count / totalVocabCount) * 100, 100));

        // Kích thước của biểu đồ
        const width = 700;
        const height = 230;
        const columnWidth = Math.min(100, (width - 15 * (vocabCountByLevel.length - 1)) / vocabCountByLevel.length);
        const gap = 12;

        // Tạo đối tượng SVG
        const svg = d3.select("#chart").append("svg").attr("width", width).attr("height", height);

        // Tạo biểu đồ cột
        svg.selectAll("rect")
            .data(heights)
            .enter().append("rect")
            .attr("x", (_, i) => i * (columnWidth + gap))
            .attr("y", d => height - (d / 100) * height)
            .attr("width", columnWidth)
            .attr("height", d => (d / 100) * height)
            .attr("fill", (_, i) => colors[i])
            .attr("rx", 10)
            .attr("ry", 10);

        // Thêm nhãn văn bản lên trên đỉnh mỗi cột với màu sắc và hiệu ứng text-shadow
        svg.selectAll("text")
            .data(vocabCountByLevel)
            .enter().append("text")
            .attr("x", (_, i) => i * (columnWidth + gap) + columnWidth / 2)
            .attr("y", d => height - (d / totalVocabCount) * height - 20)
            .attr("text-anchor", "middle")
            .attr("dy", "0.75em")
            .attr("fill", (_, i) => colors[i]) // Màu chữ của từng cấp độ
            .attr("class", "text-shadow") // Gán class nếu đã có sẵn
            .html(d => `<tspan font-weight='bold'>${d} words</tspan>`)
            .style("text-shadow", "-1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000");

        // Thêm đường trục x
        svg.selectAll("line")
            .data(heights)
            .enter().append("line")
            .attr("x1", (_, i) => i * (columnWidth + gap))
            .attr("y1", height)
            .attr("x2", (_, i) => i * (columnWidth + gap) + columnWidth)
            .attr("y2", height)
            .attr("stroke", "#bdbdbd")
            .attr("stroke-width", 7);

    </script>
    </body>
    </html>