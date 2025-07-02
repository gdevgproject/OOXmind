<?php
include 'view/header.php';

// Database queries for statistics
function getLastWeekActivity($conn)
{
  $sql = "SELECT activity_date, vocab_reviewed_count, total_time_spent
            FROM activity_log
            WHERE activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY activity_date ASC";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMonthlyActivity($conn)
{
  $sql = "SELECT
                YEAR(activity_date) as year,
                MONTH(activity_date) as month,
                SUM(vocab_reviewed_count) as total_reviewed,
                AVG(total_time_spent) as avg_time_spent
            FROM activity_log
            GROUP BY YEAR(activity_date), MONTH(activity_date)
            ORDER BY year ASC, month ASC";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getVocabByLevel($conn)
{
  $sql = "SELECT level, COUNT(*) as count FROM content WHERE is_active = 1 GROUP BY level ORDER BY level ASC";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getVocabVsQuestionDistribution($conn)
{
  $sql = "SELECT
            CASE WHEN vocab IS NOT NULL AND vocab != '' THEN 'Vocabulary' ELSE 'Question' END as content_type,
            COUNT(*) as count
          FROM content
          WHERE is_active = 1
          GROUP BY content_type";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopPerformingVocab($conn)
{
  $sql = "SELECT vocab, def, question, level, correct_count, incorrect_count, 
            CASE 
              WHEN (correct_count + incorrect_count) > 0 
              THEN ROUND((correct_count / (correct_count + incorrect_count)) * 100, 2)
              ELSE 0 
            END as accuracy_rate
            FROM content 
            WHERE (correct_count + incorrect_count) >= 3 AND is_active = 1
            ORDER BY accuracy_rate DESC, (correct_count + incorrect_count) DESC, level DESC
            LIMIT 20";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLowestPerformingVocab($conn)
{
  $sql = "SELECT vocab, def, question, level, correct_count, incorrect_count, 
            CASE 
              WHEN (correct_count + incorrect_count) > 0 
              THEN ROUND((correct_count / (correct_count + incorrect_count)) * 100, 2)
              ELSE 0 
            END as accuracy_rate
            FROM content 
            WHERE (correct_count + incorrect_count) >= 3 AND is_active = 1
            ORDER BY accuracy_rate ASC, (correct_count + incorrect_count) DESC, level DESC
            LIMIT 20";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getReviewScheduleDistribution($conn)
{
  $sql = "SELECT
                CASE
                    WHEN next_review IS NULL THEN 'Not Scheduled'
                    WHEN next_review <= NOW() THEN 'Overdue'
                    WHEN DATE(next_review) = CURDATE() THEN 'Today'
                    WHEN next_review <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'This Week'
                    ELSE 'Later'
                END as schedule_status,
                COUNT(*) as count
            FROM content
            WHERE is_active = 1
            GROUP BY schedule_status
            ORDER BY 
              CASE schedule_status
                WHEN 'Overdue' THEN 1
                WHEN 'Today' THEN 2
                WHEN 'This Week' THEN 3
                WHEN 'Later' THEN 4
                WHEN 'Not Scheduled' THEN 5
              END";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStudyTimeDistribution($conn)
{
  $sql = "SELECT
                HOUR(open_time) as hour_of_day,
                COUNT(*) as session_count,
                ROUND(AVG(total_time_spent), 2) as avg_time_spent
            FROM activity_log
            WHERE open_time IS NOT NULL
            GROUP BY HOUR(open_time)
            ORDER BY hour_of_day";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProgressOverTime($conn)
{
  $sql = "SELECT
                DATE_FORMAT(create_time, '%Y-%m') as month,
                COUNT(*) as new_vocab_count,
                ROUND(AVG(level), 2) as avg_level
            FROM content
            WHERE is_active = 1
            GROUP BY DATE_FORMAT(create_time, '%Y-%m')
            ORDER BY month";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLearningStreak($conn)
{
  try {
    // Get all unique activity dates, properly ordered
    $sql = "SELECT DISTINCT DATE(activity_date) as date 
            FROM activity_log 
            WHERE activity_date IS NOT NULL
            UNION
            SELECT DISTINCT DATE(create_time) as date 
            FROM content 
            WHERE create_time IS NOT NULL
            ORDER BY date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($dates)) {
      return 0;
    }

    $maxStreak = 1;
    $currentStreak = 1;

    // Convert to timestamps for easier calculation
    $dateTimestamps = array_map('strtotime', $dates);

    for ($i = 0; $i < count($dateTimestamps) - 1; $i++) {
      $dayDiff = ($dateTimestamps[$i] - $dateTimestamps[$i + 1]) / 86400;

      if ($dayDiff == 1) {
        // Consecutive days
        $currentStreak++;
      } else {
        // Gap found, update max and reset current
        $maxStreak = max($maxStreak, $currentStreak);
        $currentStreak = 1;
      }
    }

    // Final check
    $maxStreak = max($maxStreak, $currentStreak);
    return $maxStreak;
  } catch (Exception $e) {
    error_log("Error in getLearningStreak: " . $e->getMessage());
    return 0;
  }
}

function getCurrentStreak($conn)
{
  try {
    // Get recent activity dates in descending order
    $sql = "SELECT DISTINCT DATE(activity_date) as date 
            FROM activity_log 
            WHERE activity_date IS NOT NULL
            UNION
            SELECT DISTINCT DATE(create_time) as date 
            FROM content 
            WHERE create_time IS NOT NULL
            ORDER BY date DESC
            LIMIT 30"; // Only check last 30 days for performance

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($dates)) {
      return 0;
    }

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    // Check if there's activity today or yesterday to start streak
    if ($dates[0] != $today && $dates[0] != $yesterday) {
      return 0;
    }

    $streak = 1;
    $expectedDate = $dates[0];

    for ($i = 1; $i < count($dates); $i++) {
      $previousDay = date('Y-m-d', strtotime($expectedDate . ' -1 day'));

      if ($dates[$i] == $previousDay) {
        $streak++;
        $expectedDate = $dates[$i];
      } else {
        break; // Non-consecutive day found
      }
    }

    return $streak;
  } catch (Exception $e) {
    error_log("Error in getCurrentStreak: " . $e->getMessage());
    return 0;
  }
}

// Remove the complex getSimpleCurrentStreak function as it's redundant

function getContentTypePerformance($conn)
{
  $sql = "SELECT
            CASE
              WHEN vocab IS NOT NULL AND vocab != '' THEN 'Vocabulary'
              ELSE 'Question'
            END as content_type,
            SUM(correct_count) as total_correct,
            SUM(incorrect_count) as total_incorrect,
            SUM(correct_count + incorrect_count) as total_attempts,
            CASE
              WHEN SUM(correct_count + incorrect_count) > 0
              THEN ROUND((SUM(correct_count) / SUM(correct_count + incorrect_count)) * 100, 2)
              ELSE 0
            END as accuracy_rate
          FROM content
          WHERE is_active = 1
          GROUP BY content_type";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getReviewEfficiency($conn)
{
  $sql = "SELECT
                content_id, 
                COALESCE(vocab, SUBSTRING(question, 1, 50)) as display_content,
                correct_count,
                incorrect_count,
                (correct_count + incorrect_count) as total_attempts,
                CASE 
                  WHEN (correct_count + incorrect_count) > 0 
                  THEN ROUND((correct_count / (correct_count + incorrect_count)) * 100, 2)
                  ELSE 0 
                END as success_rate
            FROM content
            WHERE (correct_count + incorrect_count) > 0 AND is_active = 1
            ORDER BY success_rate DESC, total_attempts DESC
            LIMIT 100"; // Limit for performance
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function calculateOverallStats($conn)
{
  try {
    // Use single query for better performance
    $sql = "SELECT 
              COALESCE(AVG(vocab_reviewed_count), 0) as avg_daily_reviews,
              COALESCE(AVG(total_time_spent), 0) as avg_session_time,
              COUNT(DISTINCT activity_date) as total_days
            FROM activity_log
            WHERE activity_date IS NOT NULL";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $activityStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Content statistics
    $sql = "SELECT 
              COUNT(*) as total_items,
              SUM(correct_count) as total_correct,
              SUM(incorrect_count) as total_incorrect,
              COUNT(DISTINCT DATE(create_time)) as creation_days
            FROM content 
            WHERE is_active = 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $contentStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate derived statistics
    $totalAttempts = $contentStats['total_correct'] + $contentStats['total_incorrect'];
    $successRate = $totalAttempts > 0 ? ($contentStats['total_correct'] / $totalAttempts) * 100 : 0;
    $createdPerDay = $contentStats['creation_days'] > 0 ? $contentStats['total_items'] / $contentStats['creation_days'] : 0;

    return [
      'avg_daily_reviews' => round($activityStats['avg_daily_reviews'], 1),
      'avg_session_time' => round($activityStats['avg_session_time'] / 60, 1), // Convert to minutes
      'total_study_days' => (int)$activityStats['total_days'],
      'success_rate' => round($successRate, 1),
      'total_items' => (int)$contentStats['total_items'],
      'created_per_day' => round($createdPerDay, 1)
    ];
  } catch (Exception $e) {
    error_log("Error in calculateOverallStats: " . $e->getMessage());
    return [
      'avg_daily_reviews' => 0,
      'avg_session_time' => 0,
      'total_study_days' => 0,
      'success_rate' => 0,
      'total_items' => 0,
      'created_per_day' => 0
    ];
  }
}

// Fetch all required data with error handling
try {
  $conn = pdo_get_connection();
  $weeklyActivity = getLastWeekActivity($conn);
  $monthlyActivity = getMonthlyActivity($conn);
  $vocabByLevel = getVocabByLevel($conn);
  $contentTypeData = getVocabVsQuestionDistribution($conn);
  $contentTypePerf = getContentTypePerformance($conn);
  $topVocabs = getTopPerformingVocab($conn);
  $lowestVocabs = getLowestPerformingVocab($conn);
  $reviewSchedule = getReviewScheduleDistribution($conn);
  $studyTime = getStudyTimeDistribution($conn);
  $progressData = getProgressOverTime($conn);

  $maxStreak = getLearningStreak($conn);
  $currentStreak = getCurrentStreak($conn);
  $overall = calculateOverallStats($conn);
  $reviewEfficiency = getReviewEfficiency($conn);
} catch (Exception $e) {
  error_log("Database error in statistics: " . $e->getMessage());
  // Set default values for safety
  $weeklyActivity = $monthlyActivity = $vocabByLevel = $contentTypeData = [];
  $contentTypePerf = $topVocabs = $lowestVocabs = $reviewSchedule = [];
  $studyTime = $progressData = $reviewEfficiency = [];
  $maxStreak = $currentStreak = 0;
  $overall = [
    'avg_daily_reviews' => 0,
    'avg_session_time' => 0,
    'total_study_days' => 0,
    'success_rate' => 0,
    'total_items' => 0,
    'created_per_day' => 0
  ];
}

// Prepare data for charts
$weeklyDates = [];
$weeklyReviews = [];
$weeklyTime = [];

foreach ($weeklyActivity as $day) {
  $weeklyDates[] = date('D', strtotime($day['activity_date']));
  $weeklyReviews[] = $day['vocab_reviewed_count'];
  $weeklyTime[] = round($day['total_time_spent'] / 60, 1); // Convert to minutes
}

// JSON-encode chart data for JavaScript
$weeklyChartData = json_encode([
  'labels' => $weeklyDates,
  'reviews' => $weeklyReviews,
  'time' => $weeklyTime
]);

$levelChartData = json_encode(array_map(function ($item) {
  return [
    'level' => $item['level'],
    'count' => $item['count']
  ];
}, $vocabByLevel));

$contentTypeChartData = json_encode(array_map(function ($item) {
  return [
    'type' => $item['content_type'],
    'count' => $item['count']
  ];
}, $contentTypeData));

$contentTypePerfData = json_encode(array_map(function ($item) {
  return [
    'type' => $item['content_type'],
    'accuracy' => round($item['accuracy_rate'], 1),
    'attempts' => $item['total_attempts']
  ];
}, $contentTypePerf));

$reviewScheduleData = json_encode(array_map(function ($item) {
  return [
    'status' => $item['schedule_status'],
    'count' => $item['count']
  ];
}, $reviewSchedule));

$studyTimeData = json_encode(array_map(function ($item) {
  return [
    'hour' => $item['hour_of_day'],
    'count' => $item['session_count'],
    'avgTime' => round($item['avg_time_spent'] / 60, 1)
  ];
}, $studyTime));

// Calculate review efficiency stats
$totalReviewed = 0;
$totalCorrect = 0;

foreach ($reviewEfficiency as $item) {
  $totalReviewed += (int)$item['total_attempts'];
  $totalCorrect += (int)$item['correct_count'];
}

$overallEfficiency = $totalReviewed > 0 ? ($totalCorrect / $totalReviewed) * 100 : 0;
?>

<div class="container-fluid mt-5">
  <div class="row">
    <div class="col-12 text-center mb-4">
      <h1 class="text-white text-shadow">Learning Statistics</h1>
      <p class="text-white text-shadow">Track your progress and improve your learning efficiency</p>
    </div>
  </div>

  <!-- Key Stats Overview -->
  <div class="row">
    <div class="col-md-3 col-sm-6 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-body text-center">
          <h5 class="card-title">Current Streak</h5>
          <h2 class="display-4"><?php echo $currentStreak; ?></h2>
          <p class="card-text">consecutive days</p>
          <?php if ($maxStreak > $currentStreak): ?>
            <small class="text-muted">Best: <?php echo $maxStreak; ?> days</small>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-body text-center">
          <h5 class="card-title">Success Rate</h5>
          <h2 class="display-4"><?php echo round($overallEfficiency, 1); ?>%</h2>
          <p class="card-text">correct answers</p>
          <small class="text-muted"><?php echo $totalReviewed; ?> total attempts</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-body text-center">
          <h5 class="card-title">Daily Reviews</h5>
          <h2 class="display-4"><?php echo $overall['avg_daily_reviews']; ?></h2>
          <p class="card-text">items per day</p>
          <small class="text-muted">Total: <?php echo $overall['total_items']; ?> items</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-body text-center">
          <h5 class="card-title">Study Time</h5>
          <h2 class="display-4"><?php echo $overall['avg_session_time']; ?></h2>
          <p class="card-text">minutes per day</p>
          <small class="text-muted"><?php echo $overall['total_study_days']; ?> study days</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Weekly Progress Chart -->
  <div class="row mb-4">
    <div class="col-md-8 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-header">
          <h5 class="mb-0">Weekly Activity</h5>
        </div>
        <div class="card-body">
          <canvas id="weeklyActivityChart" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Content Distribution</h5>
          <div>
            <button class="btn btn-sm btn-outline-light active" id="showLevelsBtn">By Level</button>
            <button class="btn btn-sm btn-outline-light" id="showTypesBtn">By Type</button>
          </div>
        </div>
        <div class="card-body">
          <div id="vocabLevelChartContainer">
            <canvas id="vocabLevelChart" height="240"></canvas>
          </div>
          <div id="contentTypeChartContainer" style="display:none;">
            <canvas id="contentTypeChart" height="240"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Content Type Performance -->
  <div class="row mb-4">
    <div class="col-12 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-header">
          <h5 class="mb-0">Content Type Performance</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-8">
              <canvas id="contentTypePerfChart" height="200"></canvas>
            </div>
            <div class="col-md-4 d-flex justify-content-center align-items-center">
              <div class="text-center p-3">
                <?php foreach ($contentTypePerf as $type): ?>
                  <div class="mb-3">
                    <h4><?php echo htmlspecialchars($type['content_type']); ?></h4>
                    <h3 class="mb-1"><?php echo round($type['accuracy_rate'], 1); ?>%</h3>
                    <p class="text-muted mb-0">accuracy rate</p>
                    <small class="text-muted"><?php echo $type['total_attempts']; ?> total attempts</small>
                  </div>
                <?php endforeach; ?>
                <p class="mt-3 mb-0"><i class="fa fa-lightbulb-o text-warning"></i>
                  <?php if (count($contentTypePerf) > 1):
                    $vocabType = null;
                    $questionType = null;
                    foreach ($contentTypePerf as $type) {
                      if ($type['content_type'] == 'Vocabulary') $vocabType = $type;
                      if ($type['content_type'] == 'Question') $questionType = $type;
                    }

                    if ($vocabType && $questionType):
                      if ($vocabType['accuracy_rate'] > $questionType['accuracy_rate'] + 10): ?>
                        You're performing significantly better with vocabulary than questions.
                      <?php elseif ($questionType['accuracy_rate'] > $vocabType['accuracy_rate'] + 10): ?>
                        You're performing significantly better with questions than vocabulary.
                      <?php else: ?>
                        You have balanced performance across both content types.
                    <?php endif;
                    endif;
                  else: ?>
                    Focus on creating content of different types for comparative insights.
                  <?php endif; ?>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Review Schedule and Study Time -->
  <div class="row mb-4">
    <div class="col-md-6 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-header">
          <h5 class="mb-0">Review Schedule</h5>
        </div>
        <div class="card-body">
          <canvas id="reviewScheduleChart" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-header">
          <h5 class="mb-0">Study Time Distribution</h5>
        </div>
        <div class="card-body">
          <canvas id="studyTimeChart" height="200"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Performance Tables -->
  <div class="row mb-4">
    <div class="col-md-6 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-header">
          <h5 class="mb-0">Top Performing Items</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-dark table-striped">
              <thead>
                <tr>
                  <th>Content</th>
                  <th>Level</th>
                  <th>Correct</th>
                  <th>Incorrect</th>
                  <th>Accuracy</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($topVocabs as $vocab): ?>
                  <tr>
                    <td>
                      <?php
                      if (!empty($vocab['vocab'])) {
                        echo htmlspecialchars($vocab['vocab']);
                      } else {
                        // For questions, display a truncated version of the question
                        $question = $vocab['question'];
                        echo !empty($question) ?
                          htmlspecialchars(mb_strlen($question) > 50 ? mb_substr($question, 0, 47) . '...' : $question) :
                          '(Question)';
                      }
                      ?>
                      <span class="d-block small text-muted">
                        <?php echo !empty($vocab['def']) ? htmlspecialchars(mb_substr($vocab['def'], 0, 50)) . (mb_strlen($vocab['def']) > 50 ? '...' : '') : ''; ?>
                      </span>
                    </td>
                    <td><?php echo $vocab['level']; ?></td>
                    <td><?php echo $vocab['correct_count']; ?></td>
                    <td><?php echo $vocab['incorrect_count']; ?></td>
                    <td><?php echo round($vocab['accuracy_rate'], 1); ?>%</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-header">
          <h5 class="mb-0">Items Needing Attention</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-dark table-striped">
              <thead>
                <tr>
                  <th>Content</th>
                  <th>Level</th>
                  <th>Correct</th>
                  <th>Incorrect</th>
                  <th>Accuracy</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lowestVocabs as $vocab): ?>
                  <tr>
                    <td>
                      <?php
                      if (!empty($vocab['vocab'])) {
                        echo htmlspecialchars($vocab['vocab']);
                      } else {
                        // For questions, display a truncated version of the question
                        $question = $vocab['question'];
                        echo !empty($question) ?
                          htmlspecialchars(mb_strlen($question) > 50 ? mb_substr($question, 0, 47) . '...' : $question) :
                          '(Question)';
                      }
                      ?>
                      <span class="d-block small text-muted">
                        <?php echo !empty($vocab['def']) ? htmlspecialchars(mb_substr($vocab['def'], 0, 50)) . (mb_strlen($vocab['def']) > 50 ? '...' : '') : ''; ?>
                      </span>
                    </td>
                    <td><?php echo $vocab['level']; ?></td>
                    <td><?php echo $vocab['correct_count']; ?></td>
                    <td><?php echo $vocab['incorrect_count']; ?></td>
                    <td><?php echo round($vocab['accuracy_rate'], 1); ?>%</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Learning Tips Based on Stats -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card bg-dark text-white">
        <div class="card-header">
          <h5 class="mb-0">Smart Learning Insights</h5>
          <p class="text-muted mb-0">Personalized learning tips based on user's statistics</p>
        </div>
        <div class="card-body">
          <?php
          // Generate personalized learning tips based on user's statistics
          $tips = [];
          // Tip based on best study time
          $bestStudyHour = 0;
          $maxStudyEfficiency = 0;
          foreach ($studyTime as $hour) {
            $efficiency = $hour['session_count'] * $hour['avg_time_spent'];
            if ($efficiency > $maxStudyEfficiency) {
              $maxStudyEfficiency = $efficiency;
              $bestStudyHour = $hour['hour_of_day'];
            }
          }
          $amPm = $bestStudyHour >= 12 ? 'PM' : 'AM';
          $displayHour = $bestStudyHour > 12 ? ($bestStudyHour - 12) : $bestStudyHour;
          if ($bestStudyHour == 0) $displayHour = 12;
          $tips[] = "Your most productive study time appears to be around <strong>{$displayHour}:00 {$amPm}</strong>. Consider scheduling important review sessions during this time.";

          // Tip based on review schedule
          $overdue = false;
          foreach ($reviewSchedule as $item) {
            if ($item['schedule_status'] == 'Overdue' && $item['count'] > 5) {
              $overdue = true;
              $tips[] = "You have <strong>{$item['count']} overdue items</strong>. Consider catching up on these before they pile up further.";
              break;
            }
          }
          // Tip based on consistency
          if ($currentStreak < 3) {
            $tips[] = "Your current streak is <strong>{$currentStreak} days</strong>. Consistency is key to effective learning. Try to review at least a few items every day.";
          } else if ($currentStreak >= 7) {
            $tips[] = "Great job maintaining a <strong>{$currentStreak}-day streak</strong>! Your consistency is paying off. Keep it up!";
          }
          // Tip based on accuracy
          if ($overallEfficiency < 70) {
            $tips[] = "Your overall success rate is <strong>" . round($overallEfficiency, 1) . "%</strong>. Consider slowing down and focusing on quality over quantity in your reviews.";
          } else if ($overallEfficiency > 90) {
            $tips[] = "Your success rate is impressively high at <strong>" . round($overallEfficiency, 1) . "%</strong>. You might benefit from challenging yourself with more difficult material.";
          }
          // Display the tips
          echo '<div class="row">';
          foreach ($tips as $index => $tip) {
            echo '<div class="col-md-6 mb-3">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fa fa-lightbulb-o text-warning" style="font-size: 24px;"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-0">' . $tip . '</p>
                                </div>
                            </div>
                        </div>';
          }
          echo '</div>';
          ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Scripts for Charts -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Set default Chart.js colors that work well with dark theme
    Chart.defaults.color = 'rgba(255, 255, 255, 0.8)';
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';

    // Weekly Activity Chart
    const weeklyData = <?php echo $weeklyChartData; ?>;
    const weeklyCtx = document.getElementById('weeklyActivityChart').getContext('2d');
    new Chart(weeklyCtx, {
      type: 'bar',
      data: {
        labels: weeklyData.labels,
        datasets: [{
          label: 'Items Reviewed',
          data: weeklyData.reviews,
          backgroundColor: 'rgba(54, 162, 235, 0.7)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1,
          yAxisID: 'y'
        }, {
          label: 'Minutes Studied',
          data: weeklyData.time,
          backgroundColor: 'rgba(255, 159, 64, 0.7)',
          borderColor: 'rgba(255, 159, 64, 1)',
          borderWidth: 1,
          type: 'line',
          yAxisID: 'y1'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            position: 'left',
            grid: {
              color: 'rgba(255, 255, 255, 0.1)'
            },
            title: {
              display: true,
              text: 'Items Reviewed'
            }
          },
          y1: {
            beginAtZero: true,
            position: 'right',
            grid: {
              drawOnChartArea: false
            },
            title: {
              display: true,
              text: 'Minutes Studied'
            }
          },
          x: {
            grid: {
              color: 'rgba(255, 255, 255, 0.1)'
            }
          }
        },
        plugins: {
          tooltip: {
            mode: 'index',
            intersect: false,
          }
        }
      }
    });

    // Vocabulary by Level Chart
    const levelData = <?php echo $levelChartData; ?>;
    const levelCtx = document.getElementById('vocabLevelChart').getContext('2d');
    const levels = levelData.map(item => `Level ${item.level}`);
    const counts = levelData.map(item => item.count);

    new Chart(levelCtx, {
      type: 'pie',
      data: {
        labels: levels,
        datasets: [{
          data: counts,
          backgroundColor: [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199, 0.7)',
            'rgba(83, 102, 255, 0.7)',
            'rgba(40, 159, 64, 0.7)',
            'rgba(210, 199, 199, 0.7)'
          ],
          borderColor: 'rgba(255, 255, 255, 0.5)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              padding: 10
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                let label = context.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed) {
                  label += context.parsed + ' ';
                }
                const value = context.raw || 0;
                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label} (${percentage}%)`;
              }
            }
          }
        }
      }
    });

    // Content Type Chart
    const contentTypeData = <?php echo $contentTypeChartData; ?>;
    const contentTypeCtx = document.getElementById('contentTypeChart').getContext('2d');
    const contentTypes = contentTypeData.map(item => item.type);
    const contentCounts = contentTypeData.map(item => item.count);

    new Chart(contentTypeCtx, {
      type: 'pie',
      data: {
        labels: contentTypes,
        datasets: [{
          data: contentCounts,
          backgroundColor: [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)'
          ],
          borderColor: 'rgba(255, 255, 255, 0.5)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              padding: 10
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                let label = context.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed) {
                  label += context.parsed + ' ';
                }
                const value = context.raw || 0;
                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label} (${percentage}%)`;
              }
            }
          }
        }
      }
    });

    // Content Type Performance Chart
    const contentTypePerfData = <?php echo $contentTypePerfData; ?>;
    const contentTypePerfCtx = document.getElementById('contentTypePerfChart').getContext('2d');
    const contentPerfTypes = contentTypePerfData.map(item => item.type);
    const contentPerfAccuracy = contentTypePerfData.map(item => item.accuracy);
    const contentPerfAttempts = contentTypePerfData.map(item => item.attempts);

    new Chart(contentTypePerfCtx, {
      type: 'bar',
      data: {
        labels: contentPerfTypes,
        datasets: [{
          label: 'Accuracy Rate (%)',
          data: contentPerfAccuracy,
          backgroundColor: 'rgba(75, 192, 192, 0.7)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1,
          yAxisID: 'y'
        }, {
          label: 'Total Attempts',
          data: contentPerfAttempts,
          backgroundColor: 'rgba(255, 159, 64, 0.7)',
          borderColor: 'rgba(255, 159, 64, 1)',
          borderWidth: 1,
          type: 'line',
          yAxisID: 'y1'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            position: 'left',
            grid: {
              color: 'rgba(255, 255, 255, 0.1)'
            },
            title: {
              display: true,
              text: 'Accuracy Rate (%)'
            }
          },
          y1: {
            beginAtZero: true,
            position: 'right',
            grid: {
              drawOnChartArea: false
            },
            title: {
              display: true,
              text: 'Total Attempts'
            }
          },
          x: {
            grid: {
              color: 'rgba(255, 255, 255, 0.1)'
            }
          }
        },
        plugins: {
          tooltip: {
            mode: 'index',
            intersect: false,
          }
        }
      }
    });

    // Review Schedule Chart
    const scheduleData = <?php echo $reviewScheduleData; ?>;
    const scheduleCtx = document.getElementById('reviewScheduleChart').getContext('2d');
    const statuses = scheduleData.map(item => item.status);
    const statusCounts = scheduleData.map(item => item.count);

    new Chart(scheduleCtx, {
      type: 'doughnut',
      data: {
        labels: statuses,
        datasets: [{
          data: statusCounts,
          backgroundColor: [
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)'
          ],
          borderColor: 'rgba(255, 255, 255, 0.3)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            align: 'start'
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                let label = context.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed) {
                  label += context.parsed + ' ';
                }
                const value = context.raw || 0;
                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label} (${percentage}%)`;
              }
            }
          },
          cutout: '60%'
        },
        cutout: '60%'
      }
    });

    // Study Time Distribution Chart
    const timeData = <?php echo $studyTimeData; ?>;
    const timeCtx = document.getElementById('studyTimeChart').getContext('2d');
    const hours = timeData.map(item => {
      return item.hour < 12 ?
        (item.hour === 0 ? '12 AM' : `${item.hour} AM`) :
        (item.hour === 12 ? '12 PM' : `${item.hour - 12} PM`);
    });
    const sessionCounts = timeData.map(item => item.count);
    const avgTimes = timeData.map(item => item.avgTime);

    new Chart(timeCtx, {
      type: 'bar',
      data: {
        labels: hours,
        datasets: [{
          label: 'Number of Sessions',
          data: sessionCounts,
          backgroundColor: 'rgba(75, 192, 192, 0.7)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1,
          yAxisID: 'y'
        }, {
          label: 'Avg. Time (min)',
          data: avgTimes,
          backgroundColor: 'rgba(255, 99, 132, 0.7)',
          borderColor: 'rgba(255, 99, 132, 1)',
          borderWidth: 1,
          type: 'line',
          yAxisID: 'y1'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            position: 'left',
            grid: {
              color: 'rgba(255, 255, 255, 0.1)'
            },
            title: {
              display: true,
              text: 'Number of Sessions'
            }
          },
          y1: {
            beginAtZero: true,
            position: 'right',
            grid: {
              drawOnChartArea: false
            },
            title: {
              display: true,
              text: 'Average Time (min)'
            }
          },
          x: {
            grid: {
              color: 'rgba(255, 255, 255, 0.1)'
            }
          }
        },
        plugins: {
          tooltip: {
            mode: 'index',
            intersect: false,
          }
        }
      }
    });

    // Toggle between Level and Type charts
    document.getElementById('showLevelsBtn').addEventListener('click', function() {
      document.getElementById('vocabLevelChartContainer').style.display = 'block';
      document.getElementById('contentTypeChartContainer').style.display = 'none';
      this.classList.add('active');
      document.getElementById('showTypesBtn').classList.remove('active');
    });

    document.getElementById('showTypesBtn').addEventListener('click', function() {
      document.getElementById('vocabLevelChartContainer').style.display = 'none';
      document.getElementById('contentTypeChartContainer').style.display = 'block';
      this.classList.add('active');
      document.getElementById('showLevelsBtn').classList.remove('active');
    });
  });
</script>
<?php include 'view/footer.php'; ?>