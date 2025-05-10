<?php
require_once 'model/pdo.php';

if (isset($_GET['q'])) {
    $searchValue = strtolower($_GET['q']);

    // Check the length of the search keyword
    if (strlen($searchValue) > 600) {
        echo '<div class="alert alert-danger" role="alert">The search keyword must not exceed 600 characters.</div>';
        exit;
    }

    $conn = pdo_get_connection();

    // Exact match query
    $stmtExact = executeQuery($conn, "
        SELECT * FROM draft_content WHERE 
        LOWER(vocab) = :searchValue OR 
        LOWER(def) = :searchValue OR 
        LOWER(question) = :searchValue OR 
        LOWER(answer) = :searchValue
    ", [':searchValue' => $searchValue]);

    // Partial match query
    $stmtLike = executeQuery($conn, "
        SELECT * FROM draft_content WHERE 
        LOWER(vocab) LIKE :likeValue OR 
        LOWER(def) LIKE :likeValue OR 
        LOWER(question) LIKE :likeValue OR 
        LOWER(answer) LIKE :likeValue
    ", [':likeValue' => "%$searchValue%"]);

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

    $count = 1;
    $resultsFound = false; // Variable to track if results are found

    // Display exact match results
    while ($row = $stmtExact->fetch(PDO::FETCH_ASSOC)) {
        echoRow($row, $count);
        $resultsFound = true; // Found results
        if ($count >= 30)
            break; // Limit to 30 results
    }

    // Display partial match results, excluding already displayed results
    while ($row = $stmtLike->fetch(PDO::FETCH_ASSOC)) {
        if (!isExactMatch($row, $searchValue)) {
            echoRow($row, $count);
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

function echoRow($row, &$count)
{
    echo "<tr>";
    echo "<td>
        <button class='custom-btn text-center' data-def='" . htmlspecialchars($row['def']) . "' data-vocab='" . htmlspecialchars($row['vocab']) . "'>
            <img src='assets/homework.png' alt='Practice Draft'>
        </button>
        <button onclick='fillEditModal(
            {$row['draft_id']},
            " . json_encode(htmlspecialchars($row['vocab']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['part_of_speech']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['ipa']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['def']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['ex']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['question']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['answer']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['image_path']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['video_path']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ",
            " . json_encode(htmlspecialchars($row['audio_path']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . "
            )' class='custom-btn text-center'><img src='assets/edit.png' alt='Edit'></button>
        <button class='custom-btn text-center' onclick='deleteContent({$row['draft_id']})'><img src='assets/bin.png' alt='Delete'></button>
    </td>";

    echo "<td>{$count}</td>";
    echo "<td>" . htmlspecialchars($row['vocab']) . " " . htmlspecialchars($row['part_of_speech']) . "<br>" . htmlspecialchars($row['ipa']) . "</td>";
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
