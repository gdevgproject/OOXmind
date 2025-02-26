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
  $sql = "SELECT level, COUNT(*) as count FROM content GROUP BY level ORDER BY level ASC";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopPerformingVocab($conn)
{
  $sql = "SELECT vocab, def, level, correct_count, incorrect_count, 
            (correct_count / (correct_count + incorrect_count)) * 100 as accuracy_rate
            FROM content 
            WHERE (correct_count + incorrect_count) > 3
            ORDER BY accuracy_rate DESC, level DESC
            LIMIT 10";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLowestPerformingVocab($conn)
{
  $sql = "SELECT vocab, def, level, correct_count, incorrect_count, 
            (correct_count / (correct_count + incorrect_count)) * 100 as accuracy_rate
            FROM content 
            WHERE (correct_count + incorrect_count) > 3
            ORDER BY accuracy_rate ASC, level DESC
            LIMIT 10";
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
                    WHEN next_review <= DATE_ADD(NOW(), INTERVAL 1 DAY) THEN 'Today'
                    WHEN next_review <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'This Week'
                    ELSE 'Later'
                END as schedule_status,
                COUNT(*) as count
            FROM content
            GROUP BY schedule_status";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStudyTimeDistribution($conn)
{
  $sql = "SELECT 
                HOUR(open_time) as hour_of_day,
                COUNT(*) as session_count,
                AVG(total_time_spent) as avg_time_spent
            FROM activity_log
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
                AVG(level) as avg_level
            FROM content
            GROUP BY DATE_FORMAT(create_time, '%Y-%m')
            ORDER BY month";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLearningStreak($conn)
{
  $sql = "SELECT 
                COUNT(*) as streak_length
            FROM (
                SELECT 
                    activity_date,
                    @row_num := @row_num + 1 as row_num,
                    DATEDIFF(activity_date, @base_date) as date_diff
                FROM 
                    activity_log,
                    (SELECT @row_num := 0, @base_date := '2000-01-01') as vars
                WHERE 
                    vocab_reviewed_count > 0
                ORDER BY 
                    activity_date DESC
            ) as numbered
            WHERE 
                row_num = date_diff
            LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  return $result ? $result['streak_length'] : 0;
}

function getReviewEfficiency($conn)
{
  $sql = "SELECT 
                content_id, vocab, 
                correct_count, 
                incorrect_count,
                (correct_count + incorrect_count) as total_attempts,
                (correct_count / (correct_count + incorrect_count)) * 100 as success_rate
            FROM 
                content
            WHERE 
                (correct_count + incorrect_count) > 0
            ORDER BY 
                success_rate DESC";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function calculateOverallStats($conn)
{
  // Daily average reviews
  $sqlDailyAvg = "SELECT AVG(vocab_reviewed_count) as avg_daily_reviews FROM activity_log";
  $stmtDailyAvg = $conn->prepare($sqlDailyAvg);
  $stmtDailyAvg->execute();
  $dailyAvg = $stmtDailyAvg->fetch(PDO::FETCH_ASSOC)['avg_daily_reviews'];

  // Average time per session
  $sqlTimeAvg = "SELECT AVG(total_time_spent) as avg_session_time FROM activity_log";
  $stmtTimeAvg = $conn->prepare($sqlTimeAvg);
  $stmtTimeAvg->execute();
  $timeAvg = $stmtTimeAvg->fetch(PDO::FETCH_ASSOC)['avg_session_time'];

  // Total unique study days
  $sqlTotalDays = "SELECT COUNT(DISTINCT activity_date) as total_days FROM activity_log";
  $stmtTotalDays = $conn->prepare($sqlTotalDays);
  $stmtTotalDays->execute();
  $totalDays = $stmtTotalDays->fetch(PDO::FETCH_ASSOC)['total_days'];

  // Success rate
  $sqlSuccessRate = "SELECT 
                        SUM(correct_count) as total_correct, 
                        SUM(incorrect_count) as total_incorrect
                      FROM content";
  $stmtSuccessRate = $conn->prepare($sqlSuccessRate);
  $stmtSuccessRate->execute();
  $successData = $stmtSuccessRate->fetch(PDO::FETCH_ASSOC);
  $totalAttempts = $successData['total_correct'] + $successData['total_incorrect'];
  $successRate = $totalAttempts > 0 ? ($successData['total_correct'] / $totalAttempts) * 100 : 0;

  return [
    'avg_daily_reviews' => round($dailyAvg, 1),
    'avg_session_time' => round($timeAvg / 60, 1), // Convert to minutes
    'total_study_days' => $totalDays,
    'success_rate' => round($successRate, 1)
  ];
}

// Fetch all required data
$conn = pdo_get_connection();
$weeklyActivity = getLastWeekActivity($conn);
$monthlyActivity = getMonthlyActivity($conn);
$vocabByLevel = getVocabByLevel($conn);
$topVocabs = getTopPerformingVocab($conn);
$lowestVocabs = getLowestPerformingVocab($conn);
$reviewSchedule = getReviewScheduleDistribution($conn);
$studyTime = getStudyTimeDistribution($conn);
$progressData = getProgressOverTime($conn);
$streak = getLearningStreak($conn);
$overall = calculateOverallStats($conn);
$reviewEfficiency = getReviewEfficiency($conn);

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
  $totalReviewed += $item['total_attempts'];
  $totalCorrect += $item['correct_count'];
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
          <h2 class="display-4"><?php echo $streak; ?></h2>
          <p class="card-text">consecutive days</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-body text-center">
          <h5 class="card-title">Success Rate</h5>
          <h2 class="display-4"><?php echo round($overallEfficiency, 1); ?>%</h2>
          <p class="card-text">correct answers</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-body text-center">
          <h5 class="card-title">Daily Reviews</h5>
          <h2 class="display-4"><?php echo $overall['avg_daily_reviews']; ?></h2>
          <p class="card-text">items per day</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
      <div class="card bg-dark text-white">
        <div class="card-body text-center">
          <h5 class="card-title">Study Time</h5>
          <h2 class="display-4"><?php echo $overall['avg_session_time']; ?></h2>
          <p class="card-text">minutes per day</p>
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
        <div class="card-header">
          <h5 class="mb-0">Vocabulary by Level</h5>
        </div>
        <div class="card-body">
          <canvas id="vocabLevelChart" height="240"></canvas>
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
          <div class="table-responsive">
            <table class="table table-dark table-striped">
              <thead>
                <tr>
                  <th>Vocabulary</th>
                  <th>Level</th>
                  <th>Correct</th>
                  <th>Incorrect</th>
                  <th>Accuracy</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($topVocabs as $vocab): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($vocab['vocab']); ?></td>
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
          <div class="table-responsive">
            <table class="table table-dark table-striped">
              <thead>
                <tr>
                  <th>Vocabulary</th>
                  <th>Level</th>
                  <th>Correct</th>
                  <th>Incorrect</th>
                  <th>Accuracy</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lowestVocabs as $vocab): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($vocab['vocab']); ?></td>
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
          if ($streak < 3) {
            $tips[] = "Your current streak is <strong>{$streak} days</strong>. Consistency is key to effective learning. Try to review at least a few items every day.";
          } else if ($streak >= 7) {
            $tips[] = "Great job maintaining a <strong>{$streak}-day streak</strong>! Your consistency is paying off. Keep it up!";
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

<!-- Scripts for Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                const label = context.label || '';
                const value = context.raw || 0;
                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} (${percentage}%)`;
              }
            }
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
            'rgba(153, 102, 255, 0.7)',
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
                const label = context.label || '';
                const value = context.raw || 0;
                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} (${percentage}%)`;
              }
            }
          }
        },
        cutout: '60%'
      }
    });

    // Study Time Distribution Chart
    const timeData = <?php echo $studyTimeData; ?>;
    const timeCtx = document.getElementById('studyTimeChart').getContext('2d');

    const hours = timeData.map(item =>
      item.hour < 12 ?
      (item.hour === 0 ? '12 AM' : `${item.hour} AM`) :
      (item.hour === 12 ? '12 PM' : `${item.hour - 12} PM`)
    );
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
  });
</script>

<?php include 'view/footer.php'; ?>