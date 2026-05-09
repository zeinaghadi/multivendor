<?php
session_start();
// 1. حماية الصفحة والتأكد من الرتبة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: test_login.php"); 
    exit();
}

$v_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "Zz0795426555$", "multivendor_marketplace");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// 2. جلب إجمالي المبيعات والطلبات (grand_total)
$stats_res = $conn->query("SELECT COUNT(order_id) as total_orders, SUM(grand_total) as total_revenue FROM orders WHERE vendor_id_fk = $v_id");
$stats = $stats_res->fetch_assoc();

// 3. حساب متوسط التقييم (بدون عرض نجوم)
$rating_res = $conn->query("SELECT AVG(rating) as avg_rate, COUNT(review_id) as review_count FROM reviews WHERE vendor_id_fk = $v_id");
$rating_data = $rating_res->fetch_assoc();
$avg_rating = round($rating_data['avg_rate'], 1);

// 4. جلب توزيع التقييمات للرسمة الدائرية
$dist_res = $conn->query("SELECT rating, COUNT(*) as count FROM reviews WHERE vendor_id_fk = $v_id GROUP BY rating");
$dist = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
while($row = $dist_res->fetch_assoc()){
    $dist[$row['rating']] = $row['count'];
}

// 5. جلب مبيعات آخر 7 أيام
$sales_data = []; $days_data = [];
for($i=6; $i>=0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $days_data[] = date('D', strtotime($date));
    $day_sales = $conn->query("SELECT SUM(grand_total) as daily FROM orders WHERE vendor_id_fk = $v_id AND DATE(order_date) = '$date'")->fetch_assoc();
    $sales_data[] = $day_sales['daily'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nashmi Store | Vendor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #d72229;
            --clay: #a76f58;
            --night: #170505;
            --bg-soft: #f8f9fa;
            --white: #ffffff;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-soft); color: var(--night); display: flex; min-height: 100vh; }

        /* --- Sidebar (Nashmi Identity) --- */
        .sidebar {
            width: 280px; background: var(--night); color: white;
            padding: 40px 20px; position: fixed; height: 100vh; z-index: 1000;
        }
        .sidebar-logo { font-size: 1.8rem; font-weight: 800; margin-bottom: 50px; text-align: center; letter-spacing: -1px; }
        .sidebar-logo span { color: var(--primary-red); }
        .sidebar ul { list-style: none; }
        .sidebar ul li a {
            color: rgba(255,255,255,0.6); text-decoration: none; padding: 16px 22px;
            display: flex; align-items: center; gap: 15px; border-radius: 20px;
            transition: var(--transition); font-weight: 600; margin-bottom: 5px;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: rgba(215, 34, 41, 0.1); color: white; }
        .sidebar ul li a.active { background: var(--primary-red); box-shadow: 0 10px 20px rgba(215, 34, 41, 0.2); }

        /* --- Main Content --- */
        .dashboard-content { margin-left: 280px; width: calc(100% - 280px); padding: 50px; animation: fadeIn 0.8s ease-out; }
        .dash-header h2 { font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; margin-bottom: 30px; }
        .dash-header h2 i { color: var(--primary-red); margin-right: 10px; }

        /* --- Stats Cards --- */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .stat-card {
            background: var(--white); border-radius: 30px; padding: 30px;
            box-shadow: 0 15px 35px rgba(23, 5, 5, 0.03);
            border: 1px solid rgba(167, 111, 88, 0.08); transition: var(--transition);
        }
        .stat-card:hover { transform: translateY(-5px); border-color: var(--clay); }
        .stat-card i { font-size: 1.5rem; color: var(--primary-red); margin-bottom: 15px; display: block; }
        .stat-card p { font-size: 0.8rem; font-weight: 700; color: var(--clay); text-transform: uppercase; letter-spacing: 1px; }
        .stat-card h3 { font-size: 1.8rem; font-weight: 800; margin-top: 5px; color: var(--night); }

        /* --- Charts Layout --- */
        .charts-flex { display: grid; grid-template-columns: 1.6fr 1.1fr; gap: 25px; }
        .chart-container {
            background: var(--white); border-radius: 35px; padding: 35px;
            box-shadow: 0 15px 35px rgba(23, 5, 5, 0.03); border: 1px solid rgba(167, 111, 88, 0.05);
        }
        .chart-header { font-weight: 800; font-size: 1.1rem; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; color: var(--night); }
        .chart-header i { color: var(--primary-red); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 1100px) {
            .charts-flex { grid-template-columns: 1fr; }
            .dashboard-content { margin-left: 100px; width: calc(100% - 100px); padding: 30px; }
            .sidebar { width: 100px; }
            .sidebar-logo, .sidebar ul li a span { display: none; }
        }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="sidebar-logo">Store<span>نشمي</span></div>
    <ul>
        <li><a href="vendor_dashboard.php" class="active"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
        <li><a href="delete_update_product.php"><i class="fa-solid fa-tags"></i> <span>Products</span></a></li>
        <li><a href="orders.php"><i class="fa-solid fa-box"></i> <span>Orders</span></a></li>
        <li><a href="vendor_profile.php"><i class="fa-solid fa-user-gear"></i> <span>Profile</span></a></li>
        <li style="margin-top: 40px;"><a href="log_out.php" style="color: #ffbaba;"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
    </ul>
</nav>

<main class="dashboard-content">
    <header class="dash-header">
        <h2><i class="fa-solid fa-house"></i> Vendor <span>Dashboard</span></h2>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <i class="fa-solid fa-sack-dollar"></i>
            <p>Total Revenue</p>
            <h3>JOD <?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></h3>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-cart-shopping"></i>
            <p>Total Orders</p>
            <h3><?php echo $stats['total_orders'] ?? 0; ?></h3>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-ranking-star"></i>
            <p>Satisfaction Score</p>
            <h3><?php echo $avg_rating; ?> <small style="font-size: 0.9rem; color: var(--clay); font-weight: 500;">/ 5.0</small></h3>
        </div>
    </div>

    <div class="charts-flex">
        <div class="chart-container">
            <div class="chart-header"><i class="fa-solid fa-chart-area"></i> Weekly Sales Trend</div>
            <div id="salesChart"></div>
        </div>

        <div class="chart-container">
            <div class="chart-header"><i class="fa-solid fa-circle-notch"></i> Rating Breakdown</div>
            <div id="ratingDistChart"></div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // Sales Trend Chart (Area)
    new ApexCharts(document.querySelector("#salesChart"), {
        series: [{ name: 'Revenue', data: <?php echo json_encode($sales_data); ?> }],
        chart: { type: 'area', height: 350, toolbar: {show: false}, fontFamily: 'Plus Jakarta Sans' },
        colors: ['#d72229'],
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } },
        xaxis: { categories: <?php echo json_encode($days_data); ?>, labels: { style: { colors: '#a76f58', fontWeight: 600 } } },
        grid: { borderColor: '#f1f1f1' },
        dataLabels: { enabled: false }
    }).render();

    // Ratings Breakdown (Donut)
    new ApexCharts(document.querySelector("#ratingDistChart"), {
        series: [<?php echo implode(',', array_reverse($dist)); ?>],
        chart: { type: 'donut', height: 350, fontFamily: 'Plus Jakarta Sans' },
        labels: ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
        colors: ['#170505', '#d72229', '#a76f58', '#e2e8f0', '#cbd5e1'],
        legend: { position: 'bottom' },
        plotOptions: { pie: { donut: { size: '75%', labels: { show: true, total: { show: true, label: 'Total', color: '#170505' } } } } },
        stroke: { show: false }
    }).render();
</script>

</body>
</html>