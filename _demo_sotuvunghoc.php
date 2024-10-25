<!DOCTYPE html>
<html>

<head>
    <title>Biểu Đồ Số Từ Vựng</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
</head>

<body>
    <h1>Biểu Đồ Số Từ Vựng Học Theo Ngày</h1>
    <canvas id="myChart" width="400" height="200"></canvas>

    <script>
    <?php
        require_once 'model/pdo.php';
        $data = array();
        $labels = array();

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $sql = "SELECT COUNT(*) FROM content WHERE DATE(create_time) = ?";
            $count = pdo_execute($sql, $date)->fetchColumn();
            array_push($data, $count);
            array_push($labels, $date);
        }
    ?>

    var ctx = document.getElementById('myChart').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'bar', // Thay đổi từ 'line' sang 'bar'
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Số từ vựng',
                data: <?php echo json_encode($data); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>

</html>