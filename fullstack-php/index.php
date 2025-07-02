<?php
include 'view/header.php';
require_once 'process_add_content.php';

function getContentData($pdo, $page, $recordsPerPage = 10, $filter = 'all')
{
    $startFrom = ($page - 1) * $recordsPerPage;

    // Base SQL query
    $sql = "SELECT * FROM content";

    // Add filter condition
    if ($filter === 'active') {
        $sql .= " WHERE is_active = 1";
    } elseif ($filter === 'inactive') {
        $sql .= " WHERE is_active = 0";
    }

    $sql .= " ORDER BY create_time DESC LIMIT :startFrom, :recordsPerPage";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':startFrom', $startFrom, PDO::PARAM_INT);
    $stmt->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalPages($pdo, $recordsPerPage = 10, $filter = 'all')
{
    $sql = "SELECT COUNT(*) FROM content";

    // Add filter condition
    if ($filter === 'active') {
        $sql .= " WHERE is_active = 1";
    } elseif ($filter === 'inactive') {
        $sql .= " WHERE is_active = 0";
    }

    $totalRecords = $pdo->query($sql)->fetchColumn();
    return ceil($totalRecords / $recordsPerPage);
}

// Get database connection once
$pdo = pdo_get_connection();

// Get the current filter from GET parameters or set default to 'all'
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'active', 'inactive'])) {
    $filter = 'all';
}

// Get the current page from GET parameters or set default to 1
$page = max((int) ($_GET['page'] ?? 1), 1); // Ensure the page is not less than 1
$recordsPerPage = 10; // Number of items per page
$startCount = ($page - 1) * $recordsPerPage + 1; // Calculate the starting count for display

$contentData = getContentData($pdo, $page, $recordsPerPage, $filter);
$totalPages = getTotalPages($pdo, $recordsPerPage, $filter);

// Helper function for safe HTML output
function safeOutput($string)
{
    return htmlspecialchars($string ?? '', ENT_NOQUOTES);
}

// Generate pagination HTML
function getPaginationHtml($page, $totalPages, $filter = 'all', $paginationRange = 2)
{
    if ($totalPages <= 1) {
        return ''; // Don't show pagination if only one page
    }

    $html = '<div class="pagination-wrapper">';

    // Previous page link
    $html .= '<div class="pagination-nav">';
    if ($page > 1) {
        $html .= "<a href='?page=" . ($page - 1) . "&filter={$filter}' class='nav-btn prev-btn' title='Previous Page'>‹</a>";
    } else {
        $html .= "<span class='nav-btn prev-btn disabled' title='Previous Page'>‹</span>";
    }
    $html .= '</div>';

    // Page numbers with smart range
    $html .= '<div class="pagination-pages">';

    // Always show first page
    if ($page > $paginationRange + 2) {
        $html .= "<a href='?page=1&filter={$filter}' class='page-btn'>1</a>";
        if ($page > $paginationRange + 3) {
            $html .= "<span class='page-ellipsis'>…</span>";
        }
    }

    // Show range around current page
    for ($i = max(1, $page - $paginationRange); $i <= min($totalPages, $page + $paginationRange); $i++) {
        $activeClass = ($i == $page) ? 'active' : '';
        $html .= "<a href='?page={$i}&filter={$filter}' class='page-btn {$activeClass}'>{$i}</a>";
    }

    // Always show last page
    if ($page < $totalPages - $paginationRange - 1) {
        if ($page < $totalPages - $paginationRange - 2) {
            $html .= "<span class='page-ellipsis'>…</span>";
        }
        $html .= "<a href='?page={$totalPages}&filter={$filter}' class='page-btn'>{$totalPages}</a>";
    }

    $html .= '</div>';

    // Page jump input
    $html .= '<div class="pagination-jump">';
    $html .= '<input type="number" id="pageJumpInput" class="page-jump-input" min="1" max="' . $totalPages . '" value="' . $page . '" title="Jump to page">';
    $html .= '<button type="button" onclick="jumpToPage(\'' . $filter . '\', ' . $totalPages . ')" class="jump-btn" title="Go to page">Go</button>';
    $html .= '</div>';

    // Next page link
    $html .= '<div class="pagination-nav">';
    if ($page < $totalPages) {
        $html .= "<a href='?page=" . ($page + 1) . "&filter={$filter}' class='nav-btn next-btn' title='Next Page'>›</a>";
    } else {
        $html .= "<span class='nav-btn next-btn disabled' title='Next Page'>›</span>";
    }
    $html .= '</div>';

    // Page info
    $html .= '<div class="pagination-info">';
    $html .= '<span class="page-info">Page ' . $page . ' of ' . $totalPages . '</span>';
    $html .= '</div>';

    $html .= '</div>';

    return $html;
}

// Get recent images for the modal
$recentImages = getRecentImages(5);
?>

<style>
    :root {
        --primary-bg: rgba(243, 240, 255, 0.9);
        --primary-border: rgba(209, 207, 226, 0.7);
        --btn-primary: rgba(163, 196, 243, 0.9);
        --btn-primary-hover: rgba(111, 159, 231, 0.9);
        --btn-danger: rgba(255, 179, 179, 0.9);
        --btn-danger-hover: rgba(255, 128, 128, 0.9);
        --text-color: #333;
        --border-radius: 8px;
        --transition-speed: 0.3s ease;
    }

    /* Modal styles */
    .modal-dialog {
        max-width: 90%;
    }

    .modal-content {
        background-color: rgba(255, 255, 255, 0.7);
        padding: 20px;
        border-radius: var(--border-radius);
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

    /* Inputs */
    .soft-input {
        border: 1px solid var(--primary-border);
        background-color: var(--primary-bg);
        border-radius: var(--border-radius);
        padding: 10px;
        transition: background-color var(--transition-speed);
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
        border-radius: var(--border-radius);
        color: var(--text-color);
        transition: background-color var(--transition-speed);
    }

    .save-close {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .btn-save {
        background-color: var(--btn-primary);
        border: none;
    }

    .btn-save:hover {
        background-color: var(--btn-primary-hover);
    }

    .btn-close {
        background-color: var(--btn-danger);
        border: none;
    }

    .btn-close:hover {
        background-color: var(--btn-danger-hover);
    }

    /* Labels */
    label {
        font-weight: bold;
        color: var(--text-color);
    }

    /* File upload */
    .custom-file-upload {
        display: inline-block;
        padding: 10px 20px;
        cursor: pointer;
        background-color: var(--btn-primary);
        border-radius: var(--border-radius);
        color: var(--text-color);
        font-size: 14px;
        font-weight: bold;
        transition: background-color var(--transition-speed);
    }

    .custom-file-upload:hover {
        background-color: var(--btn-primary-hover);
    }

    input[type="file"] {
        display: none;
    }

    /* Preview styles */
    .upload-preview {
        position: relative;
        border: 2px solid var(--primary-border);
        background-color: var(--primary-bg);
        border-radius: var(--border-radius);
        padding: 10px;
        height: 200px;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        transition: all var(--transition-speed);
    }

    .preview-box {
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

    .list-vocab-table {
        margin-top: 100px;
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        padding: 0;
    }

    .pagination-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        padding: 8px 12px;
        border-radius: 25px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        flex-wrap: wrap;
        justify-content: center;
    }

    .pagination-nav {
        display: flex;
        align-items: center;
    }

    .pagination-pages {
        display: flex;
        align-items: center;
        gap: 2px;
        flex-wrap: wrap;
    }

    .pagination-jump {
        display: flex;
        align-items: center;
        gap: 4px;
        margin: 0 4px;
    }

    .pagination-info {
        display: flex;
        align-items: center;
        margin-left: 8px;
    }

    .nav-btn,
    .page-btn,
    .jump-btn {
        padding: 6px 10px;
        text-decoration: none;
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        background: rgba(255, 255, 255, 0.2);
        color: #333;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s ease;
        cursor: pointer;
        min-width: 32px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .page-btn {
        min-width: 28px;
        padding: 5px 8px;
        font-size: 13px;
    }

    .nav-btn:hover:not(.disabled),
    .page-btn:hover:not(.active),
    .jump-btn:hover {
        background: rgba(255, 255, 255, 0.4);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .page-btn.active {
        background: var(--btn-primary);
        color: #fff;
        border-color: var(--btn-primary);
        box-shadow: 0 0 10px rgba(163, 196, 243, 0.6);
        font-weight: bold;
    }

    .nav-btn.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        background: rgba(255, 255, 255, 0.1);
    }

    .page-ellipsis {
        padding: 5px 8px;
        color: #666;
        font-weight: bold;
        font-size: 16px;
    }

    .page-jump-input {
        width: 50px;
        padding: 4px 6px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.9);
        color: #333;
        font-size: 12px;
        text-align: center;
        font-weight: 600;
    }

    .page-jump-input:focus {
        outline: none;
        border-color: var(--btn-primary);
        box-shadow: 0 0 5px rgba(163, 196, 243, 0.5);
    }

    .jump-btn {
        padding: 4px 8px;
        font-size: 11px;
        min-width: auto;
        background: var(--btn-primary);
        color: #fff;
        border-color: var(--btn-primary);
    }

    .jump-btn:hover {
        background: var(--btn-primary-hover);
        border-color: var(--btn-primary-hover);
    }

    .page-info {
        font-size: 11px;
        color: #fff;
        font-weight: 600;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
        white-space: nowrap;
    }

    /* Responsive pagination */
    @media (max-width: 768px) {
        .pagination-wrapper {
            gap: 4px;
            padding: 6px 8px;
            flex-direction: column;
        }

        .pagination-pages {
            order: 1;
        }

        .pagination-nav {
            order: 2;
        }

        .pagination-jump {
            order: 3;
            margin: 2px 0;
        }

        .pagination-info {
            order: 4;
            margin: 2px 0;
        }

        .nav-btn,
        .page-btn {
            padding: 4px 6px;
            font-size: 12px;
            min-width: 24px;
        }

        .page-jump-input {
            width: 40px;
        }
    }

    /* Quick navigation tooltips */
    .pagination-wrapper [title]:hover::after {
        content: attr(title);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        white-space: nowrap;
        z-index: 1000;
        margin-bottom: 5px;
    }

    .pagination-wrapper [title] {
        position: relative;
    }

    /* Level badge styling */
    .badge-level {
        display: inline-block;
        padding: 3px 7px;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: bold;
        margin-top: 5px;
        color: white;
        background-color: #17a2b8;
        box-shadow: 0 0 8px rgba(23, 162, 184, 0.7);
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    /* Active and Inactive Vocabulary Styling */
    tr.active-vocab {
        background-color: rgba(164, 231, 176, 0.3);
        border-left: 4px solid #4CAF50;
    }

    tr.active-vocab:hover {
        background-color: rgba(164, 231, 176, 0.5);
    }

    tr.inactive-vocab {
        background-color: rgba(231, 164, 164, 0.2);
        border-left: 4px solid #F44336;
        opacity: 0.8;
    }

    tr.inactive-vocab:hover {
        background-color: rgba(231, 164, 164, 0.3);
    }

    /* Active status indicator */
    .status-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .status-active {
        background-color: #4CAF50;
        box-shadow: 0 0 5px rgba(76, 175, 80, 0.8);
    }

    .status-inactive {
        background-color: #F44336;
    }

    /* Filter buttons */
    .filter-container {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .filter-label {
        font-weight: bold;
        margin-right: 5px;
        color: #fff;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
    }

    .filter-btn {
        padding: 6px 12px;
        border-radius: var(--border-radius);
        border: 1px solid var(--primary-border);
        background-color: rgba(255, 255, 255, 0.2);
        color: #fff;
        cursor: pointer;
        transition: all var(--transition-speed);
        font-weight: bold;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
    }

    .filter-btn.active {
        background-color: var(--btn-primary);
        box-shadow: 0 0 8px rgba(163, 196, 243, 0.8);
    }

    .filter-btn:hover:not(.active) {
        background-color: rgba(255, 255, 255, 0.3);
        box-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
    }

    /* Recent Images Styles */
    .recent-images-container {
        margin-top: 10px;
        border: 1px solid var(--primary-border);
        border-radius: var(--border-radius);
        padding: 10px;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .recent-images-title {
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 8px;
        color: var(--text-color);
    }

    .recent-images-grid {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        max-height: 120px;
        overflow-y: auto;
    }

    .recent-image-item {
        position: relative;
        width: 60px;
        height: 60px;
        border-radius: 6px;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all var(--transition-speed);
    }

    .recent-image-item:hover {
        border-color: var(--btn-primary);
        transform: scale(1.05);
    }

    .recent-image-item.selected {
        border-color: #4CAF50;
        box-shadow: 0 0 8px rgba(76, 175, 80, 0.6);
    }

    .recent-image-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .recent-image-item .image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        opacity: 0;
        transition: opacity var(--transition-speed);
    }

    .recent-image-item:hover .image-overlay {
        opacity: 1;
    }

    .no-recent-images {
        text-align: center;
        color: #666;
        font-style: italic;
        font-size: 12px;
        padding: 20px;
    }

    .clear-selection-btn {
        background: var(--btn-danger);
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        cursor: pointer;
        margin-top: 5px;
        transition: background-color var(--transition-speed);
    }

    .clear-selection-btn:hover {
        background: var(--btn-danger-hover);
    }

    .selected-image-info {
        margin-top: 8px;
        padding: 5px;
        background: rgba(76, 175, 80, 0.2);
        border-radius: 4px;
        font-size: 11px;
        display: none;
    }
</style>

<div class="mx-auto list-vocab-table text-shadow-white" style="width:90%">
    <!-- Search Bar -->
    <div class="input-group my-3">
        <input type="text" class="input-box text-shadow-white" id="searchInput"
            placeholder="Search..." aria-label="Search">
    </div>

    <!-- Filter Buttons -->
    <div class="filter-container">
        <span class="filter-label">Filter:</span>
        <a href="?filter=all<?= $page > 1 ? '&page=' . $page : '' ?>" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All Items</a>
        <a href="?filter=active<?= $page > 1 ? '&page=1' : '' ?>" class="filter-btn <?= $filter === 'active' ? 'active' : '' ?>">Active Only</a>
        <a href="?filter=inactive<?= $page > 1 ? '&page=1' : '' ?>" class="filter-btn <?= $filter === 'inactive' ? 'active' : '' ?>">Inactive Only</a>

        <!-- Add New Button moved here -->
        <button type="button" class="custom-btn" data-toggle="modal" data-target="#addNewModal" style="margin-left: auto;">
            <img src="assets/add.png" alt="Add">
        </button>
    </div>

    <div class="pagination-container">
        <!-- Empty div to balance spacing -->
        <div></div>

        <!-- Pagination -->
        <div class="pagination" style="margin-bottom: 20px;">
            <?php echo getPaginationHtml($page, $totalPages, $filter); ?>
        </div>
        <div></div> <!-- Empty div to balance spacing -->
    </div>

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
            $count = $startCount;
            foreach ($contentData as $row) {
                $rowId = htmlspecialchars($row['content_id']);
                $vocab = safeOutput($row['vocab']);
                $partOfSpeech = safeOutput($row['part_of_speech']);
                $ipa = safeOutput($row['ipa']);
                $def = safeOutput($row['def']);
                $ex = safeOutput($row['ex']);
                $question = safeOutput($row['question']);
                $answer = safeOutput($row['answer']);
                $imagePath = safeOutput($row['image_path']);
                $videoPath = safeOutput($row['video_path']);
                $audioPath = safeOutput($row['audio_path']);
                $isActive = (int)$row['is_active']; // Get is_active status as integer
                $rowClass = $isActive ? 'active-vocab' : 'inactive-vocab'; // Set row class based on is_active
            ?>
                <tr class="<?= $rowClass ?>">
                    <td class="text-center">
                        <button class="custom-btn" onclick='redirectToPracticeDraft(<?= json_encode($def) ?>, <?= json_encode($vocab) ?>)'>
                            <img src="assets/homework.png" alt="Practice Draft">
                        </button>
                        <button class="custom-btn" onclick='fillEditModal(<?= $rowId ?>, <?= json_encode($vocab) ?>, <?= json_encode($partOfSpeech) ?>, <?= json_encode($ipa) ?>, <?= json_encode($def) ?>, <?= json_encode($ex) ?>, <?= json_encode($question) ?>, <?= json_encode($answer) ?>, <?= json_encode($imagePath) ?>, <?= json_encode($videoPath) ?>, <?= json_encode($audioPath) ?>, <?= $isActive ?>, <?= (int)$row['level'] ?>)'>
                            <img src="assets/edit.png" alt="Edit">
                        </button>
                        <button class="custom-btn"
                            onmousedown='startDeleteHold(<?= json_encode($rowId) ?>, <?= json_encode($vocab) ?>, <?= json_encode($question) ?>)'
                            onmouseup='endDeleteHold()'
                            onmouseleave='endDeleteHold()'
                            onclick='confirmDelete(<?= json_encode($rowId) ?>, <?= json_encode($vocab) ?>, <?= json_encode($question) ?>)'>
                            <img src="assets/bin.png" alt="Delete">
                        </button>
                    </td>
                    <td class="text-center"><?= $count ?></td>
                    <td>
                        <span class="status-indicator <?= $isActive ? 'status-active' : 'status-inactive' ?>"></span>
                        <?= $vocab ?> <?= $partOfSpeech ?><br><?= $ipa ?>
                        <?php if ((int)$row['level'] > 0): ?>
                            <br><span class="badge-level">Level: <?= (int)$row['level'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $def ?></td>
                    <td><?= $ex ?></td>
                    <td><?= $question ?></td>
                    <td><?= $answer ?></td>
                    <td>
                        <?php if (!empty($imagePath)): ?>
                            <img src="<?= $imagePath ?>" alt="Image" style="max-width:100px; max-height:100px;" ondblclick='enlargeImage("<?= $imagePath ?>")'>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($videoPath)): ?>
                            <video width="150" height="100" controls>
                                <source src="<?= $videoPath ?>" type="video/mp4">Your browser does not support the video tag.
                            </video>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($audioPath)): ?>
                            <div class="custom-btn" style="cursor:pointer;" onclick='playAudio("<?= $audioPath ?>")'>
                                <img src="assets/audio.png" alt="Play Audio">
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php
                $count++;
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
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
            <form action="process_add_content.php" method="post" enctype="multipart/form-data">
                <div class="container-form">
                    <div class="left-form">
                        <div class="form-group">
                            <label for="newVocab">Vocabulary</label>
                            <input type="text" class="form-control soft-input" id="newVocab" name="newVocab">
                        </div>
                        <div class="form-group">
                            <label for="newPartOfSpeech">Part of speech</label>
                            <input type="text" class="form-control soft-input" id="newPartOfSpeech" name="newPartOfSpeech" value="()">
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
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="newIsActive" name="newIsActive" checked>
                            <label class="form-check-label" for="newIsActive">Active (visible in app)</label>
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
                                        <input type="file" id="newImage" name="newImage" accept="image/*" onchange="previewFile(this, 'imagePreview')">
                                        Choose Image
                                    </label>
                                    <img id="imagePreview" class="preview-box" alt="Image Preview">
                                </div>

                                <!-- Recent Images Section -->
                                <?php if (!empty($recentImages)): ?>
                                    <div class="recent-images-container">
                                        <div class="recent-images-title">📷 Recent Images (Click to Select)</div>
                                        <div class="recent-images-grid">
                                            <?php foreach ($recentImages as $index => $image): ?>
                                                <div class="recent-image-item" onclick="selectRecentImage('<?= htmlspecialchars($image['path']) ?>', '<?= htmlspecialchars($image['name']) ?>', this)">
                                                    <img src="<?= htmlspecialchars($image['path']) ?>" alt="Recent image">
                                                    <div class="image-overlay">Select</div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="selected-image-info" id="selectedImageInfo">
                                            <strong>Selected:</strong> <span id="selectedImageName"></span>
                                            <button type="button" class="clear-selection-btn" onclick="clearRecentImageSelection()">Clear</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="recent-images-container">
                                        <div class="no-recent-images">No recent images found</div>
                                    </div>
                                <?php endif; ?>

                                <input type="hidden" id="selectedRecentImage" name="selectedRecentImage" value="">
                            </div>
                            <div class="form-group">
                                <label for="newVideo">Video</label>
                                <div class="upload-preview">
                                    <label class="custom-file-upload">
                                        <input type="file" id="newVideo" name="newVideo" accept="video/*" onchange="previewFile(this, 'videoPreview')">
                                        Choose Video
                                    </label>
                                    <video id="videoPreview" class="preview-box" controls></video>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="newAudio">Audio</label>
                                <div class="upload-preview">
                                    <label class="custom-file-upload">
                                        <input type="file" id="newAudio" name="newAudio" accept="audio/*" onchange="previewFile(this, 'audioPreview')">
                                        Choose Audio
                                    </label>
                                    <audio id="audioPreview" class="preview-box" controls></audio>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="returnFilter" value="<?= $filter ?>">
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
                            <input type="text" class="form-control soft-input" id="editPartOfSpeech" name="editPartOfSpeech" value="()">
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
                            <textarea class="form-control soft-input" id="editExample" name="editExample" rows="2"></textarea>
                        </div>
                        <div class="form-check form-group">
                            <input type="checkbox" class="form-check-input" id="editIsActive" name="editIsActive">
                            <label class="form-check-label" for="editIsActive">Active (visible in app)</label>
                        </div>
                        <!-- Add level information and reset checkbox -->
                        <div class="form-group" id="levelInfoContainer">
                            <div id="levelInfo">Current Level: <span id="currentLevel">0</span></div>
                            <div class="form-check" id="resetLevelContainer" style="display: none;">
                                <input type="checkbox" class="form-check-input" id="resetLevel" name="resetLevel">
                                <label class="form-check-label" for="resetLevel">Reset Level and Stats</label>
                            </div>
                        </div>
                    </div>
                    <div class="right-form">
                        <div class="form-group">
                            <label for="editQuestion">Question</label>
                            <textarea class="form-control soft-input" id="editQuestion" name="editQuestion" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="editAnswer">Answer</label>
                            <textarea class="form-control soft-input" id="editAnswer" name="editAnswer" rows="2"></textarea>
                        </div>
                        <div class="upload-container">
                            <div class="form-group">
                                <label for="editImage">Image</label>
                                <div class="upload-preview">
                                    <label class="custom-file-upload">
                                        <input type="file" id="editImage" name="editImage" accept="image/*" onchange="previewFile(this, 'editImagePreview')">
                                        Choose Image
                                    </label>
                                    <img id="editImagePreview" class="preview-box" alt="Image Preview">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="editVideo">Video</label>
                                <div class="upload-preview">
                                    <label class="custom-file-upload">
                                        <input type="file" id="editVideo" name="editVideo" accept="video/*" onchange="previewFile(this, 'editVideoPreview')">
                                        Choose Video
                                    </label>
                                    <video id="editVideoPreview" class="preview-box" controls></video>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="editAudio">Audio</label>
                                <div class="upload-preview">
                                    <label class="custom-file-upload">
                                        <input type="file" id="editAudio" name="editAudio" accept="audio/*" onchange="previewFile(this, 'editAudioPreview')">
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
                    <h6>Hãy tận hưởng niềm vui mỗi khi hiểu bài, vì tâm trạng thoải mái giúp bộ não nhẹ nhàng và tiếp thu tốt hơn.</h6>
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
    // Store current filter for use in JS functions
    const currentFilter = "<?= $filter ?>";

    // Lấy URL hiện tại và đặt vào input ẩn, preserving filter parameter
    document.getElementById('currentUrl').value = window.location.href;

    function previewFile(input, previewId) {
        const file = input.files[0];
        if (file) {
            // If user uploads a new file, clear recent image selection
            if (input.id === 'newImage') {
                clearRecentImageSelection();
            }

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
    let deleteContentInfo = {};

    function startDeleteHold(contentId, vocab, question) {
        deleteContentInfo = {
            contentId,
            vocab,
            question
        };
        deleteHoldTimeout = setTimeout(() => {
            deleteContent(contentId, vocab, question, true);
        }, 650);
    }

    function endDeleteHold() {
        clearTimeout(deleteHoldTimeout);
        deleteContentInfo = {};
    }

    function confirmDelete(contentId, vocab, question) {
        const displayText = getContentDisplayText(vocab, question);
        const confirmMessage = `Are you sure you want to delete this content?\n\nID: ${contentId}\nContent: ${displayText}`;

        if (confirm(confirmMessage)) {
            deleteContent(contentId, vocab, question);
        }
    }

    function getContentDisplayText(vocab, question) {
        // If vocabulary exists and is not empty, use it
        if (vocab && vocab.trim() !== '' && vocab.trim() !== '()') {
            return vocab.length > 50 ? vocab.substring(0, 50) + '...' : vocab;
        }
        // Otherwise, use first part of question
        else if (question && question.trim() !== '') {
            return question.length > 50 ? question.substring(0, 50) + '...' : question;
        }
        // Fallback
        return 'Unknown content';
    }

    function deleteContent(contentId, vocab = '', question = '', isForced = false) {
        if (!isForced) {
            // Add filter parameter to redirect URL
            window.location.href = `delete_content.php?id=${contentId}&returnFilter=${currentFilter}`;
        } else {
            window.location.href = `delete_content.php?id=${contentId}&returnFilter=${currentFilter}`;
        }
    }

    let searchTimer;
    document.getElementById("searchInput").addEventListener("input", function() {
        const searchValue = this.value.trim().toLowerCase();
        clearTimeout(searchTimer);

        if (searchValue) {
            // Include filter parameter in search
            searchTimer = setTimeout(() => searchContent(searchValue, currentFilter), 60);
        }
    });

    function searchContent(value, filter = 'all') {
        const xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                document.getElementById("contentTable").innerHTML = this.responseText;
                highlightSearchResults(value);
                attachPracticeDraftEvent();
            }
        };
        // Include filter in search request
        xhttp.open("GET", `search_content.php?q=${value}&filter=${filter}`, true);
        xhttp.send();
    }

    function attachPracticeDraftEvent() {
        const contentTable = document.getElementById("contentTable");
        contentTable.addEventListener("click", function(event) {
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

    function fillEditModal(id, vocab, partOfSpeech, ipa, def, ex, question, answer, imagePath, videoPath, audioPath, isActive = 0, level = 0) {
        document.getElementById('editId').value = id;
        document.getElementById('editVocab').value = decodeHTMLEntities(vocab);
        document.getElementById('editPartOfSpeech').value = decodeHTMLEntities(partOfSpeech);
        document.getElementById('editIPA').value = decodeHTMLEntities(ipa);
        document.getElementById('editDef').value = decodeHTMLEntities(def);
        document.getElementById('editExample').value = decodeHTMLEntities(ex);
        document.getElementById('editQuestion').value = decodeHTMLEntities(question);
        document.getElementById('editAnswer').value = decodeHTMLEntities(answer);

        // Set the active checkbox state
        document.getElementById('editIsActive').checked = (isActive === 1);

        // Update level information
        document.getElementById('currentLevel').textContent = level;

        // Show or hide reset level checkbox based on level value
        const resetLevelContainer = document.getElementById('resetLevelContainer');
        if (level > 0) {
            resetLevelContainer.style.display = 'block';
        } else {
            resetLevelContainer.style.display = 'none';
            document.getElementById('resetLevel').checked = false; // Ensure it's unchecked when hidden
        }

        // Display file previews if available
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
        window.location.href = `practice_draft.php?def=${encodeURIComponent(def)}&vocab=${encodeURIComponent(vocab)}&returnFilter=${currentFilter}`;
    }

    // Recent Image Selection Functions
    function selectRecentImage(imagePath, imageName, element) {
        // Clear previous selections
        document.querySelectorAll('.recent-image-item').forEach(item => {
            item.classList.remove('selected');
        });

        // Select current item
        element.classList.add('selected');

        // Set hidden input value
        document.getElementById('selectedRecentImage').value = imagePath;

        // Show selection info
        document.getElementById('selectedImageName').textContent = imageName;
        document.getElementById('selectedImageInfo').style.display = 'block';

        // Show preview
        const previewImg = document.getElementById('imagePreview');
        previewImg.src = imagePath;
        previewImg.style.display = 'block';

        // Clear file input to avoid conflicts
        document.getElementById('newImage').value = '';
    }

    function clearRecentImageSelection() {
        // Clear selection
        document.querySelectorAll('.recent-image-item').forEach(item => {
            item.classList.remove('selected');
        });

        // Clear hidden input
        document.getElementById('selectedRecentImage').value = '';

        // Hide selection info
        document.getElementById('selectedImageInfo').style.display = 'none';

        // Clear preview
        const previewImg = document.getElementById('imagePreview');
        previewImg.src = '';
        previewImg.style.display = 'none';
    }

    // Clear selections when modal is closed
    $('#addNewModal').on('hidden.bs.modal', function() {
        clearRecentImageSelection();
        // Clear all previews
        ['imagePreview', 'videoPreview', 'audioPreview'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.src = '';
                element.style.display = 'none';
            }
        });
        // Clear form
        document.querySelector('#addNewModal form').reset();
        // Reset part of speech default value
        document.getElementById('newPartOfSpeech').value = '()';
        // Ensure active checkbox is checked by default
        document.getElementById('newIsActive').checked = true;
    });

    // Add keyboard shortcut for quick save in Add New modal
    let addModalKeyboardHandler = null;

    // Enable Ctrl+Enter shortcut when Add New modal opens
    $('#addNewModal').on('shown.bs.modal', function() {
        addModalKeyboardHandler = function(e) {
            // Check if Ctrl+Enter is pressed
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                // Find and click the save button
                const saveButton = document.querySelector('#addNewModal button[name="save"]');
                if (saveButton) {
                    saveButton.click();
                }
            }
        };

        // Add event listener
        document.addEventListener('keydown', addModalKeyboardHandler);

        // Add paste functionality for images
        document.addEventListener('paste', handleImagePaste);
    });

    // Disable Ctrl+Enter shortcut when Add New modal closes
    $('#addNewModal').on('hidden.bs.modal', function() {
        if (addModalKeyboardHandler) {
            document.removeEventListener('keydown', addModalKeyboardHandler);
            addModalKeyboardHandler = null;
        }
        // Remove paste event listener
        document.removeEventListener('paste', handleImagePaste);
    });

    // Handle image paste functionality
    function handleImagePaste(e) {
        // Only handle paste if Add New modal is open
        if (!$('#addNewModal').hasClass('show')) {
            return;
        }

        const clipboardData = e.clipboardData || window.clipboardData;
        if (!clipboardData) return;

        const items = clipboardData.items;
        if (!items) return;

        // Look for image in clipboard
        for (let i = 0; i < items.length; i++) {
            const item = items[i];

            // Check if item is an image
            if (item.type.indexOf('image') !== -1) {
                e.preventDefault();

                const file = item.getAsFile();
                if (file) {
                    // Clear recent image selection since we're pasting a new image
                    clearRecentImageSelection();

                    // Create a custom file input event to trigger existing preview logic
                    const fileInput = document.getElementById('newImage');

                    // Create a new FileList-like object
                    const dataTransfer = new DataTransfer();

                    // Generate a filename with timestamp
                    const timestamp = new Date().getTime();
                    const extension = file.type.split('/')[1] || 'png';
                    const fileName = `pasted_image_${timestamp}.${extension}`;

                    // Create a new File object with custom name
                    const renamedFile = new File([file], fileName, {
                        type: file.type,
                        lastModified: file.lastModified
                    });

                    dataTransfer.items.add(renamedFile);
                    fileInput.files = dataTransfer.files;

                    // Trigger preview
                    previewFile(fileInput, 'imagePreview');

                    // Show visual feedback
                    showPasteSuccess();
                }
                break;
            }
        }
    }

    // Show visual feedback for successful paste
    function showPasteSuccess() {
        const previewContainer = document.querySelector('#addNewModal .upload-preview');
        if (previewContainer) {
            // Add temporary success indicator
            previewContainer.style.borderColor = '#4CAF50';
            previewContainer.style.boxShadow = '0 0 10px rgba(76, 175, 80, 0.6)';

            // Create success message
            const successMsg = document.createElement('div');
            successMsg.textContent = 'Image pasted successfully!';
            successMsg.style.cssText = `
                position: absolute;
                top: 5px;
                right: 5px;
                background: #4CAF50;
                color: white;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 11px;
                z-index: 10;
                animation: fadeInOut 2s ease-in-out;
            `;

            // Add CSS animation if not exists
            if (!document.getElementById('pasteSuccessStyle')) {
                const style = document.createElement('style');
                style.id = 'pasteSuccessStyle';
                style.textContent = `
                    @keyframes fadeInOut {
                        0% { opacity: 0; transform: translateY(-10px); }
                        20% { opacity: 1; transform: translateY(0); }
                        80% { opacity: 1; transform: translateY(0); }
                        100% { opacity: 0; transform: translateY(-10px); }
                    }
                `;
                document.head.appendChild(style);
            }

            previewContainer.appendChild(successMsg);

            // Reset styles after animation
            setTimeout(() => {
                previewContainer.style.borderColor = '';
                previewContainer.style.boxShadow = '';
                if (successMsg.parentNode) {
                    successMsg.remove();
                }
            }, 2000);
        }
    }

    // Add visual hint for paste functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Add paste hint to the image upload section
        const imageUploadSection = document.querySelector('#addNewModal .form-group label[for="newImage"]');
        if (imageUploadSection) {
            const pasteHint = document.createElement('small');
            pasteHint.style.cssText = `
                display: block;
                color: #666;
                font-style: italic;
                margin-top: 4px;
                font-size: 11px;
            `;
            pasteHint.textContent = '💡 Tip: You can also paste images directly (Ctrl+V)';
            imageUploadSection.parentNode.insertBefore(pasteHint, imageUploadSection.nextSibling);
        }
    });

    window.onload = () => document.getElementById('searchInput').focus();

    // Enhanced pagination functions
    function jumpToPage(filter, maxPages) {
        const input = document.getElementById('pageJumpInput');
        const pageNum = parseInt(input.value);

        if (pageNum >= 1 && pageNum <= maxPages) {
            window.location.href = `?page=${pageNum}&filter=${filter}`;
        } else {
            // Visual feedback for invalid input
            input.style.borderColor = '#ff4444';
            input.style.background = 'rgba(255, 68, 68, 0.1)';

            setTimeout(() => {
                input.style.borderColor = '';
                input.style.background = '';
            }, 1000);

            // Reset to current page
            input.value = <?= $page ?>;
        }
    }

    // Add keyboard support for page jumping
    document.addEventListener('DOMContentLoaded', function() {
        const pageJumpInput = document.getElementById('pageJumpInput');
        if (pageJumpInput) {
            pageJumpInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    jumpToPage(currentFilter, <?= $totalPages ?>);
                }
            });

            // Auto-select text when focused
            pageJumpInput.addEventListener('focus', function() {
                this.select();
            });

            // Prevent invalid characters
            pageJumpInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    });

    // Add keyboard shortcuts for pagination
    document.addEventListener('keydown', function(e) {
        // Only handle if not in an input field
        if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
            return;
        }

        const currentPage = <?= $page ?>;
        const totalPages = <?= $totalPages ?>;
        const filter = currentFilter;

        switch (e.key) {
            case 'ArrowLeft':
                if (currentPage > 1) {
                    window.location.href = `?page=${currentPage - 1}&filter=${filter}`;
                }
                break;
            case 'ArrowRight':
                if (currentPage < totalPages) {
                    window.location.href = `?page=${currentPage + 1}&filter=${filter}`;
                }
                break;
            case 'Home':
                if (currentPage !== 1) {
                    window.location.href = `?page=1&filter=${filter}`;
                }
                break;
            case 'End':
                if (currentPage !== totalPages) {
                    window.location.href = `?page=${totalPages}&filter=${filter}`;
                }
                break;
        }
    });
</script>

</body>

</html>