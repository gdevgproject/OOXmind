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
    '#ce93d8', // Tím nhạt
    '#ba68c8'  // Tím đậm nhạt
];
$colorIndex = 6;
?>

<style>
    /* Element transitions and animations */
    .notebook-link,
    .add-link {
        display: inline-block;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .notebook-img,
    .add-img {
        transition: transform 0.3s ease;
    }

    /* Notebook link hover effects */
    .notebook-link:hover .notebook-img {
        transform: scale(1.2);
        filter: brightness(1.2);
    }

    .notebook-link:active .notebook-img {
        transform: scale(0.9);
    }

    .notebook-link:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    /* Add link effects */
    .add-link {
        animation: spin 8s linear infinite;
    }

    .add-link:hover {
        animation: none;
    }

    .add-link:hover .add-img {
        transform: scale(1.2);
    }

    .add-link:active .add-img {
        transform: scale(0.9);
    }

    /* Spinning animation */
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    /* Modal styles */
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

    /* Form layout */
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

    /* Form inputs */
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

    /* Buttons */
    .btn-save,
    .btn-close,
    .custom-file-upload {
        padding: 10px 20px;
        border-radius: 8px;
        color: #333;
        transition: background-color 0.3s ease;
        border: none;
    }

    .save-close {
        display: flex;
        justify-content: space-between;
        align-items: end;
    }

    .btn-save {
        background-color: rgba(163, 196, 243, 0.9);
    }

    .btn-save:hover {
        background-color: rgba(111, 159, 231, 0.9);
    }

    .btn-close {
        background-color: rgba(255, 179, 179, 0.9);
    }

    .btn-close:hover {
        background-color: rgba(255, 128, 128, 0.9);
    }

    /* File upload */
    label {
        font-weight: bold;
        color: #333;
    }

    .custom-file-upload {
        display: inline-block;
        cursor: pointer;
        background-color: rgba(163, 196, 243, 0.9);
        font-size: 14px;
        font-weight: bold;
    }

    .custom-file-upload:hover {
        background-color: rgba(111, 159, 231, 0.9);
    }

    input[type="file"] {
        display: none;
    }

    /* Preview containers */
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

    #imagePreview,
    #videoPreview,
    #audioPreview {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        display: none;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    /* No data message styles */
    .no-data-message {
        height: 230px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        color: #fff176;
        text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
        font-weight: bold;
        font-size: 18px;
    }

    .no-upcoming-words {
        animation: fadeInOut 3s infinite alternate;
    }

    @keyframes fadeInOut {
        from {
            opacity: 0.7;
        }

        to {
            opacity: 1;
        }
    }

    .pulse-animation {
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }
</style>

<div class="row justify-content-center">
    <div class="col-md-6 text-md-left">
        <div class="mt-3 custom-div-2 d-flex align-items-center justify-content-center">
            <h2 class="mb-0 ms-2" style="border: 1px solid #607d8b; padding: 7px 10px; border-radius: 10px; font-weight: 600;">
                <a href="./index.php" class="notebook-link">
                    <img src="assets/notebook.png" alt="notebook" width="40" class="me-2 notebook-img">
                </a>
                <span style="color: #4FC3F7;" class="text-shadow"><?php echo $totalVocabCount; ?> .</span>
                <span style="color: #81c784;" class="text-shadow"><?= $vocabCount; ?></span>
                <span style="color: #FFB74D;" class="text-shadow">vocabs</span>
                <span style="color: #64B5F6;" class="text-shadow"><?= $otherCount; ?></span>
                <span style="color: #BA68C8;" class="text-shadow">questions</span>
            </h2>
            <a type="button" data-toggle="modal" data-target="#addNewModal" class="ml-3 add-link"
                style="border: 1px solid black; border-radius: 50%; padding: 9px;">
                <img src="assets/add.png" alt="" width="35" class="add-img">
            </a>
        </div>

        <div id="chart" class="my-3 custom-div"></div>

        <?php if ($count > 0): ?>
            <div class="text-center" style="margin: 35px 0;">
                <a href="practice.php" class="custom-big-btn text-shadow" style="background-color: <?= $reviewColor; ?>; color: #FFFFFF;">
                    REVIEW NOW
                </a>
            </div>
        <?php endif; ?>

        <div>
            <?php if (empty($upcomingWords)): ?>
                <div class="custom-div d-flex align-items-center mb-1 text-shadow no-upcoming-words">
                    <div class="text-center" style="padding: 7px 10px; border-radius: 15px; background-color: #81c784;">
                        <div>
                            <img src="assets/perfection.png" alt="perfection" width="22">
                        </div>
                    </div>
                    <div class="text-justify text-shadow" style="padding-left: 20px; color: #81c784;">
                        <strong>No upcoming reviews. Great job! Add more vocabulary to continue learning.</strong>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($upcomingWords as $word): ?>
                    <div class="custom-div d-flex align-items-center mb-1 text-shadow" style="color: #fff176;">
                        <div class="text-center" style="padding: 7px 10px; border-radius: 15px; background-color: <?= $colors[$colorIndex]; ?>">
                            <div>
                                <img src="assets/notification-bell.png" alt="đồng hồ cát" width="22">
                            </div>
                            <div>
                                <strong class="countdown"
                                    data-next-review="<?= $word['next_review'] ?>"
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
            <?php endif; ?>
        </div>
    </div>
</div>

<script defer>
    function previewFile(input, previewId) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
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

        // If no countdown elements are found, don't proceed further
        if (countdownElements.length === 0) {
            return;
        }

        countdownElements.forEach((element) => {
            // Check if the element has the required data attributes
            if (element.dataset.nextReview) {
                const nextReviewTime = new Date(element.dataset.nextReview).getTime();
                setInterval(() => updateCountdown(element, nextReviewTime), 1000);
            }
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

        // Calculate hours, minutes, seconds
        const hours = String(Math.floor(timeDifference / (1000 * 60 * 60))).padStart(2, '0');
        const minutes = String(Math.floor((timeDifference % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
        const seconds = String(Math.floor((timeDifference % (1000 * 60)) / 1000)).padStart(2, '0');

        // Format based on time remaining
        if (hours === '00') {
            // If less than 1 hour, show in red
            element.innerHTML = `<span style="color: #ff5252;">${minutes}:${seconds}</span>`;
        } else {
            element.innerHTML = `${hours}:${minutes}:${seconds}`;
        }

        // Add pulse animation when less than 15 minutes remain
        if (hours === '00' && parseInt(minutes) < 15) {
            element.classList.add('pulse-animation');
        }
    }

    // Add CSS for pulse animation
    document.head.insertAdjacentHTML('beforeend', `
        <style>
            .pulse-animation {
                animation: pulse 1s infinite;
            }
            @keyframes pulse {
                0% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.05);
                }
                100% {
                    transform: scale(1);
                }
            }
        </style>
    `);

    // Initialize the countdown functionality
    document.addEventListener('DOMContentLoaded', function() {
        refreshPageWhenCountdownEnds();
    });

    const data = <?php echo $jsonData; ?>;

    // Check if data exists and has items
    if (!data || data.length === 0) {
        // Create a friendly message when no data is available
        d3.select("#chart")
            .append("div")
            .attr("class", "no-data-message")
            .style("height", "230px")
            .style("display", "flex")
            .style("align-items", "center")
            .style("justify-content", "center")
            .style("background-color", "rgba(255,255,255,0.1)")
            .style("border-radius", "10px")
            .style("color", "#fff176")
            .style("text-shadow", "-1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000")
            .style("font-weight", "bold")
            .style("font-size", "18px")
            .html("Start adding vocabulary to see your progress chart!");
    } else {
        // Sort data from lowest to highest level
        data.sort((a, b) => a.level - b.level);

        // Get vocab count for each level
        const vocabCountByLevel = data.map(item => item.vocab_count);

        // Define colors array
        const colors = ['#e57373', '#ffb74d', '#fff176', '#81c784', '#4dd0e1', '#64b5f6', '#ba68c8'];

        // Fill in missing colors if needed
        while (colors.length < vocabCountByLevel.length) {
            colors.push(colors[colors.length % 7]);
        }

        // Calculate total vocab count
        const totalVocabCount = vocabCountByLevel.reduce((a, b) => a + b, 0);

        // Ensure minimum height for visibility even with small counts
        const calculateHeight = count => {
            const percentage = (count / totalVocabCount) * 100;
            return Math.max(percentage, 10); // Minimum 10% height for visibility
        };

        const heights = vocabCountByLevel.map(calculateHeight);

        // Chart dimensions - adjust based on data points
        const width = 700;
        const height = 230;

        // Adjust column width based on number of data points
        const minColumnWidth = 40; // Minimum column width
        const maxColumnWidth = 100; // Maximum column width
        const calculatedWidth = (width - 15 * (vocabCountByLevel.length - 1)) / vocabCountByLevel.length;
        const columnWidth = Math.max(Math.min(calculatedWidth, maxColumnWidth), minColumnWidth);
        const gap = Math.max(8, Math.min(15, 20 - vocabCountByLevel.length)); // Dynamic gap

        // Create SVG
        const svg = d3.select("#chart").append("svg")
            .attr("width", "100%")
            .attr("height", height)
            .attr("viewBox", `0 0 ${width} ${height}`)
            .attr("preserveAspectRatio", "xMidYMid meet");

        // Create columns with animation
        svg.selectAll("rect")
            .data(heights)
            .enter().append("rect")
            .attr("x", (_, i) => i * (columnWidth + gap))
            .attr("y", height) // Start from bottom for animation
            .attr("width", columnWidth)
            .attr("height", 0) // Start with height 0 for animation
            .attr("fill", (_, i) => colors[i % colors.length])
            .attr("rx", 10)
            .attr("ry", 10)
            .transition() // Add transition
            .duration(1000) // 1 second animation
            .delay((_, i) => i * 100) // Stagger animation
            .attr("y", d => height - (d / 100) * height)
            .attr("height", d => (d / 100) * height);

        // Add labels
        svg.selectAll(".label")
            .data(vocabCountByLevel)
            .enter().append("text")
            .attr("class", "label text-shadow")
            .attr("x", (_, i) => i * (columnWidth + gap) + columnWidth / 2)
            .attr("y", d => height - (calculateHeight(d) / 100) * height - 20)
            .attr("text-anchor", "middle")
            .attr("dy", "0.75em")
            .attr("fill", (_, i) => colors[i % colors.length])
            .style("opacity", 0) // Start invisible for animation
            .style("text-shadow", "-1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000")
            .html(d => `<tspan font-weight='bold'>${d} word${d !== 1 ? 's' : ''}</tspan>`)
            .transition()
            .duration(1000)
            .delay((_, i) => i * 100 + 500) // Delay after columns appear
            .style("opacity", 1); // Fade in

        // Add level indicators
        svg.selectAll(".level-indicator")
            .data(data)
            .enter().append("text")
            .attr("class", "level-indicator text-shadow")
            .attr("x", (_, i) => i * (columnWidth + gap) + columnWidth / 2)
            .attr("y", height - 5)
            .attr("text-anchor", "middle")
            .attr("fill", "white")
            .style("font-size", "12px")
            .style("opacity", 0) // Start invisible
            .style("text-shadow", "-1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000")
            .text(d => `Level ${d.level}`)
            .transition()
            .duration(1000)
            .delay((_, i) => i * 100 + 800) // Appear last
            .style("opacity", 1);

        // Add axis line
        svg.append("line")
            .attr("x1", 0)
            .attr("y1", height)
            .attr("x2", Math.max(width, vocabCountByLevel.length * (columnWidth + gap) - gap))
            .attr("y2", height)
            .attr("stroke", "#bdbdbd")
            .attr("stroke-width", 4);
    }
</script>

<!-- Add New Modal -->
<div class="modal" id="addNewModal" tabindex="-1" role="dialog" aria-labelledby="addNewModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="process_add_content.php" method="post" enctype="multipart/form-data">
                <div class="container-form">
                    <div class="left-form">
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
                            <textarea class="form-control soft-input" id="newExample" name="newExample" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="right-form">
                        <div class="form-group">
                            <label for="newQuestion">Question</label>
                            <textarea class="form-control soft-input" id="newQuestion" name="newQuestion" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="newAnswer">Answer</label>
                            <textarea class="form-control soft-input" id="newAnswer" name="newAnswer" rows="2"></textarea>
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
</body>

</html>