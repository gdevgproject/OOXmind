<?php
require_once 'model/pdo.php';

if (isset($_GET['q'])) {
    $searchValue = strtolower($_GET['q']);
    $filter = $_GET['filter'] ?? 'all';

    // Check the length of the search keyword
    if (strlen($searchValue) > 600) {
        echo '<div class="alert alert-danger" role="alert">The search keyword must not exceed 600 characters.</div>';
        exit;
    }

    $conn = pdo_get_connection();

    // Base filter conditions
    $filterCondition = "";
    $baseParams = [':searchValue' => $searchValue, ':likeValue' => "%$searchValue%"];

    if ($filter === 'active') {
        $filterCondition = " AND is_active = 1";
    } elseif ($filter === 'inactive') {
        $filterCondition = " AND is_active = 0";
    }

    // Exact match query with filter
    $stmtExact = executeQuery($conn, "
        SELECT * FROM content WHERE 
        (LOWER(vocab) = :searchValue OR 
        LOWER(def) = :searchValue OR 
        LOWER(question) = :searchValue OR 
        LOWER(answer) = :searchValue)
        $filterCondition
    ", $baseParams);

    // Partial match query with filter
    $stmtLike = executeQuery($conn, "
        SELECT * FROM content WHERE 
        (LOWER(vocab) LIKE :likeValue OR 
        LOWER(def) LIKE :likeValue OR 
        LOWER(question) LIKE :likeValue OR 
        LOWER(answer) LIKE :likeValue)
        $filterCondition
    ", $baseParams);

    // Display results
    echo "<table class='table table-bordered'>
        <thead>
            <tr>
                <th>Action</th>
                <th>ID</th>
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
        <tbody>";

    // Add CSS for active/inactive styles
    echo "
    <style>
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
    </style>";

    $count = 1;
    $resultsFound = false; // Variable to track if results are found

    // Display exact match results
    while ($row = $stmtExact->fetch(PDO::FETCH_ASSOC)) {
        echoRow($row, $count, $filter);
        $resultsFound = true; // Found results
        if ($count >= 30)
            break; // Limit to 30 results
    }

    // Display partial match results, excluding already displayed results
    while ($row = $stmtLike->fetch(PDO::FETCH_ASSOC)) {
        if (!isExactMatch($row, $searchValue)) {
            echoRow($row, $count, $filter);
            $resultsFound = true; // Found results
            if ($count >= 30)
                break; // Limit to 30 results
        }
    }

    if (!$resultsFound) {
        echo '<tr><td colspan="10" class="text-center alert alert-warning" role="alert">No matching terms found.</td></tr>';
    }

    echo "</tbody></table>";
}

// Function to execute query
function executeQuery($conn, $query, $params)
{
    $stmt = $conn->prepare($query);
    foreach ($params as $key => &$value) {
        $stmt->bindParam($key, $value);
    }
    $stmt->execute();
    return $stmt;
}

// Function to check if the result matches exactly
function isExactMatch($row, $searchValue)
{
    return strtolower($row['vocab']) === $searchValue ||
        strtolower($row['def']) === $searchValue ||
        strtolower($row['question']) === $searchValue ||
        strtolower($row['answer']) === $searchValue ||
        (string) $row['level'] === $searchValue;
}

function echoRow($row, &$count, $filter)
{
    $isActive = (int)$row['is_active'];
    $rowClass = $isActive ? 'active-vocab' : 'inactive-vocab';

    echo "<tr class='$rowClass'>";
    echo "<td>
        <button class='custom-btn text-center' data-def='" . htmlspecialchars($row['def']) . "' data-vocab='" . htmlspecialchars($row['vocab']) . "' data-filter='" . htmlspecialchars($filter) . "'>
            <img src='assets/homework.png' alt='Practice Draft'>
        </button>
        <button onclick='fillEditModal(
            {$row['content_id']},
            " . json_encode(htmlspecialchars($row['vocab']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['part_of_speech']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['ipa']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['def']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['ex']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['question']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['answer']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['image_path']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['video_path']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['audio_path']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode((int)$row['is_active']) . "
            )' class='custom-btn text-center'><img src='assets/edit.png' alt='Edit'></button>
        <button class='custom-btn text-center' onclick='deleteContent({$row['content_id']})'><img src='assets/bin.png' alt='Delete'></button>
    </td>";

    echo "<td>{$count}</td>";
    echo "<td>
        <span class='status-indicator " . ($isActive ? 'status-active' : 'status-inactive') . "'></span>
        " . htmlspecialchars($row['vocab']) . " " . htmlspecialchars($row['part_of_speech']) . "<br>" . htmlspecialchars($row['ipa']) . "</td>";
    echo "<td>" . htmlspecialchars($row['def']) . "</td>";
    echo "<td>" . htmlspecialchars($row['ex']) . "</td>";
    echo "<td>" . htmlspecialchars($row['question']) . "</td>";
    echo "<td>" . htmlspecialchars($row['answer']) . "</td>";

    // Display image
    echo "<td>" . (!empty($row['image_path']) ? "<img src='{$row['image_path']}' alt='Image' style='max-width:100px; max-height:100px;' ondblclick='enlargeImage(\"{$row['image_path']}\")'>" : '') . "</td>";

    // Display video
    echo "<td>" . (!empty($row['video_path']) ? "<video width='150' height='100' controls><source src='{$row['video_path']}' type='video/mp4'>Your browser does not support the video tag.</video>" : '') . "</td>";

    // Display audio
    echo "<td>" . (!empty($row['audio_path']) ? "<div class='custom-btn text-center'><img src='assets/audio.png' alt='Play Audio' style='cursor:pointer;' onclick='playAudio(\"{$row['audio_path']}\")'></div>" : '') . "</td>";

    echo "</tr>";
    $count++;
}
