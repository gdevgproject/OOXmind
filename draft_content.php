<?php
include 'view/header.php';
function getdraft_contentData($pdo, $page, $recordsPerPage = 10)
{
    $startFrom = ($page - 1) * $recordsPerPage;
    $sql = "SELECT * FROM draft_content ORDER BY create_time DESC LIMIT :startFrom, :recordsPerPage";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':startFrom', $startFrom, PDO::PARAM_INT);
    $stmt->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalPages($pdo, $recordsPerPage = 10)
{
    $sql = "SELECT COUNT(*) FROM draft_content";
    $totalRecords = $pdo->query($sql)->fetchColumn();
    return ceil($totalRecords / $recordsPerPage);
}

// Get the current page from GET parameters or set default to 1
$page = max((int) ($_GET['page'] ?? 1), 1); // Ensure the page is not less than 1

$recordsPerPage = 10; // Number of items per page
$startCount = ($page - 1) * $recordsPerPage + 1; // Calculate the starting count for display

$contentData = getdraft_contentData(pdo_get_connection(), $page, $recordsPerPage);
?>

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
    .list-vocab-table {
        margin-top: 100px;
    }
</style>

<div class="mx-auto list-vocab-table" style="width:90%">
    <!-- Search Bar -->
    <div class="input-group my-3">
        <input type="text" class="input-box" id="searchInput" placeholder="Search..." aria-label="Search"
            aria-describedby="basic-addon2">
    </div>

    <!-- Add New Button -->
    <button type="button" class="custom-btn mb-3" data-toggle="modal" data-target="#addNewModal">
        <img src="assets/add.png" alt="">
    </button>

    <!-- Content Table -->
    <table class="custom-table" id="contentTable">
        <thead>
            <tr>
                <th>Action</th>
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
            require_once 'model/pdo.php'; // Include PDO model if not already included
            
            $count = $startCount;
            foreach ($contentData as $row) {
                $rowId = htmlspecialchars($row['draft_id']);
                $vocab = htmlspecialchars($row['vocab'] ?? '', ENT_NOQUOTES); // Chỉ mã hóa ký tự HTML đặc biệt, bỏ qua dấu ngoặc kép
                $partOfSpeech = htmlspecialchars($row['part_of_speech'] ?? '', ENT_NOQUOTES);
                $ipa = htmlspecialchars($row['ipa'] ?? '', ENT_NOQUOTES);
                $def = htmlspecialchars($row['def'] ?? '', ENT_NOQUOTES);
                $ex = htmlspecialchars($row['ex'] ?? '', ENT_NOQUOTES);
                $question = htmlspecialchars($row['question'] ?? '', ENT_NOQUOTES);
                $answer = htmlspecialchars($row['answer'] ?? '', ENT_NOQUOTES);
                $imagePath = htmlspecialchars($row['image_path'] ?? '', ENT_NOQUOTES);
                $videoPath = htmlspecialchars($row['video_path'] ?? '', ENT_NOQUOTES);
                $audioPath = htmlspecialchars($row['audio_path'] ?? '', ENT_NOQUOTES);

                echo "<tr>";
                echo "<td class='text-center'>
                    <button class='custom-btn' onclick='redirectToPracticeDraft(" . json_encode($def) . ", " . json_encode($vocab) . ")'>
                        <img src='assets/homework.png' alt='Practice Draft'>
                    </button>
                    <button class='custom-btn' onclick='fillEditModal($rowId, " . json_encode($vocab) . ", " . json_encode($partOfSpeech) . ", " . json_encode($ipa) . ", " . json_encode($def) . ", " . json_encode($ex) . ", " . json_encode($question) . ", " . json_encode($answer) . ", " . json_encode($imagePath) . ", " . json_encode($videoPath) . ", " . json_encode($audioPath) . ")'>
                        <img src='assets/edit.png' alt='Edit'>
                    </button>
                            <button class='custom-btn'
                        onmousedown='startDeleteHold(" . json_encode($rowId) . ")'
                        onmouseup='endDeleteHold()'
                        onmouseleave='endDeleteHold()'
                        onclick='confirmDelete(" . json_encode($rowId) . ")'> <!-- Thêm onclick cho xác nhận xóa -->
                        <img src='assets/bin.png' alt='Delete'>
                    </button>";
                if ($row['accepted'] == 1) {
                    // Nếu đã chấp nhận, hiển thị nút "Accept Again" và xử lý sự kiện khi nhấn nút
                    echo "<button class='custom-btn' onclick='acceptContent({$row['draft_id']})'><img src='assets/again.png' alt='accept again'></button>";
                } else {
                    // Nếu chưa chấp nhận, hiển thị nút "Accept" và xử lý sự kiện khi nhấn nút
                    echo "<button class='custom-btn' onclick='acceptContent({$row['draft_id']})'><img src='assets/accept.png' alt='accept'></button>";
                }
                echo "</td>";



                echo "<td class='text-center'>{$count}</td>";
                echo "<td>$vocab $partOfSpeech<br>$ipa</td>";
                echo "<td>$def</td>";
                echo "<td>$ex</td>";
                echo "<td>$question</td>";
                echo "<td>$answer</td>";

                // Hiển thị ảnh
                echo "<td>" . (!empty($imagePath) ? "<img src='$imagePath' alt='Image' style='max-width:100px; max-height:100px;' ondblclick='enlargeImage(\"$imagePath\")'>" : '') . "</td>";

                // Hiển thị video
                echo "<td>" . (!empty($videoPath) ? "<video width='150' height='100' controls><source src='$videoPath' type='video/mp4'>Your browser does not support the video tag.</video>" : '') . "</td>";

                // Hiển thị audio
                echo "<td>" . (!empty($audioPath) ? "<div class='custom-btn' style='cursor:pointer;' onclick='playAudio(\"$audioPath\")'><img src='assets/audio.png' alt='Play Audio'></div>" : '') . "</td>";

                echo "</tr>";
                $count++; // Tăng biến đếm
            }
            ?>
        </tbody>
    </table>

    <div class="text-center font-weight-bold h5">
        <?php
        $totalPages = getTotalPages(pdo_get_connection());
        for ($i = 1; $i <= $totalPages; $i++) {
            echo "<a class='custom-btn' href='?page=$i'>$i</a> ";
        }
        ?>
    </div>
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

<!-- Add New Modal -->
<div class="modal" id="addNewModal" tabindex="-1" role="dialog" aria-labelledby="addNewModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="process_add_draft_content.php" method="post" enctype="multipart/form-data">
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

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="process_edit_draft_content.php" method="post" enctype="multipart/form-data">
                <input type="hidden" id="editId" name="editId" value="">
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




<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.8/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    function previewFile(input, previewId) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewElement = document.getElementById(previewId);
                if (['IMG', 'VIDEO', 'AUDIO'].includes(previewElement.tagName)) {
                    previewElement.src = e.target.result;
                    previewElement.style.display = 'block';
                }
            };
            reader.readAsDataURL(file);
        }
    }

    let deleteHoldTimeout;

    function startDeleteHold(contentId) {
        deleteHoldTimeout = setTimeout(() => {
            deleteContent(contentId, true);
        }, 1000);
    }

    function endDeleteHold() {
        clearTimeout(deleteHoldTimeout);
    }

    function confirmDelete(contentId) {
        // Hiển thị hộp thoại xác nhận chỉ một lần
        if (confirm("Are you sure you want to delete this content?")) {
            deleteContent(contentId); // Gọi hàm deleteContent nếu xác nhận
        }
    }

    function deleteContent(contentId, isForced = false) {
        // Chỉ thực hiện xóa nếu không phải là trường hợp buộc
        if (!isForced) {
            // Không cần xác nhận ở đây nữa
            window.location.href = `delete_draft_content.php?id=${contentId}`;
        } else {
            // Nếu là trường hợp buộc, thực hiện xóa ngay lập tức
            window.location.href = `delete_draft_content.php?id=${contentId}`;
        }
    }

    let searchTimer;
    document.getElementById("searchInput").addEventListener("input", function () {
        const searchValue = this.value.trim().toLowerCase();
        clearTimeout(searchTimer);

        if (searchValue) {  // Chỉ tìm kiếm khi có giá trị
            searchTimer = setTimeout(() => searchContent(searchValue), 60);
        }
    });

    function searchContent(value) {
        const xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                document.getElementById("contentTable").innerHTML = this.responseText;
                highlightSearchResults(value);
                attachPracticeDraftEvent();
            }
        };
        xhttp.open("GET", `search_draft_content.php?q=${value}`, true);
        xhttp.send();
    }

    function attachPracticeDraftEvent() {
        const contentTable = document.getElementById("contentTable");
        contentTable.addEventListener("click", function (event) {
            const button = event.target.closest("button.custom-btn");
            const img = event.target.closest("img");

            if (button && img && img.src.includes('homework.png')) {
                const def = button.getAttribute("data-def");
                const vocab = button.getAttribute("data-vocab");

                if (def && vocab) {
                    redirectToPracticeDraft(def, vocab);
                } else {
                    console.log("No valid data found for Practice Draft.");
                }
            }
        });
    }

    function highlightSearchResults(value) {
        const table = document.getElementById("contentTable");
        const rows = table.getElementsByTagName("tr");

        for (const row of rows) {
            const cells = row.getElementsByTagName("td");

            for (let j = 1; j < cells.length; j++) {
                const cellText = cells[j].innerText.toLowerCase();
                const startIndex = cellText.indexOf(value);

                if (startIndex !== -1) {
                    const highlightedText = cellText.substring(startIndex, startIndex + value.length);
                    cells[j].innerHTML = cells[j].innerHTML.replace(new RegExp(highlightedText, 'gi'),
                        `<span style="background-color: yellow;">${highlightedText}</span>`);
                }
            }
        }
    }

    function decodeHTMLEntities(text) {
        const textArea = document.createElement('textarea');
        textArea.innerHTML = text;
        return textArea.value;
    }

    function fillEditModal(id, vocab, partOfSpeech, ipa, def, ex, question, answer, imagePath, videoPath, audioPath) {
        document.getElementById('editId').value = id;
        document.getElementById('editVocab').value = decodeHTMLEntities(vocab);
        document.getElementById('editPartOfSpeech').value = decodeHTMLEntities(partOfSpeech);
        document.getElementById('editIPA').value = decodeHTMLEntities(ipa);
        document.getElementById('editDef').value = decodeHTMLEntities(def);
        document.getElementById('editExample').value = decodeHTMLEntities(ex);
        document.getElementById('editQuestion').value = decodeHTMLEntities(question);
        document.getElementById('editAnswer').value = decodeHTMLEntities(answer);

        // Hiển thị bản xem trước nếu có đường dẫn
        displayFilePreview(imagePath, 'editImagePreview');
        displayFilePreview(videoPath, 'editVideoPreview');
        displayFilePreview(audioPath, 'editAudioPreview');

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

    function clearFilePreviews() {
        ['editImagePreview', 'editVideoPreview', 'editAudioPreview'].forEach(id => {
            const element = document.getElementById(id);
            element.src = '';
            element.style.display = 'none';
        });
    }

    let currentlyPlayingAudio = null;

    function playAudio(audioPath) {
        if (currentlyPlayingAudio) {
            currentlyPlayingAudio.pause();
            currentlyPlayingAudio.currentTime = 0;
        }

        currentlyPlayingAudio = new Audio(audioPath);
        currentlyPlayingAudio.play();
    }

    function enlargeImage(imagePath) {
        document.getElementById('enlargedImage').src = imagePath;
        $('#imageModal').modal('show');
    }

    function redirectToPracticeDraft(def, vocab) {
        console.log(def, vocab);
        window.location.href = `practice_draft.php?def=${encodeURIComponent(def)}&vocab=${encodeURIComponent(vocab)}`;
    }

    window.onload = () => document.getElementById('searchInput').focus();
    function acceptContent(draftId) {
        if (confirm("Are you sure you want to accept this content?")) {
            // Gọi Ajax hoặc chuyển hướng đến trang xử lý accept
            window.location.href = `accept_draft_content.php?id=${draftId}`;
        }
    }
</script>

</body>

</html>