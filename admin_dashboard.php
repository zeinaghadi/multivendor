<?php
session_start();

// 1. حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: test_login.php");
    exit();
}

$v_id = $_SESSION['user_id'];
$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8mb4");

// حساب الإحصائيات
$total_vendors = ($conn->query("SELECT COUNT(*) as total FROM vendors"))->fetch_assoc()['total'] ?? 0;
$global_revenue = ($conn->query("SELECT SUM(grand_total) as total_rev FROM orders"))->fetch_assoc()['total_rev'] ?? 0;

// جلب بيانات الـ Chart
$chart_query = $conn->query("
    SELECT c.category_name, COUNT(v.vendor_id) as count 
    FROM vendors v
    LEFT JOIN categories c ON v.category_ID_FK = c.category_ID
    GROUP BY v.category_ID_FK
");

$chart_labels = [];
$chart_data = [];
while($row = $chart_query->fetch_assoc()){
    $chart_labels[] = $row['category_name'] ?: 'Uncategorized';
    $chart_data[] = $row['count'];
}

$result = $conn->query("SELECT vendor_id, store_name, logo_url, vendor_email FROM vendors ORDER BY vendor_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nashmi store | Admin Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-red: #d72229;
            --night: #170505;
            --clay: #a76f58;
            --bg-soft: #f8f9fa;
            --white: #ffffff;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-soft);
            color: var(--night);
            display: flex;
            min-height: 100vh;
        }

        /* --- Sidebar  --- */
        .sidebar {
            width: 280px;
            background: var(--night);
            height: 100vh;
            position: fixed;
            padding: 40px 20px;
            color: white;
            z-index: 1000;
        }

        .sidebar-logo {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 50px;
            text-align: center;
            letter-spacing: -1px;
        }

        .sidebar-logo span { color: var(--primary-red); }

        .sidebar ul { list-style: none; }

        .sidebar ul li { margin-bottom: 8px; }

        .sidebar ul li a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            padding: 16px 22px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-radius: 20px;
            transition: var(--transition);
            font-weight: 600;
        }

        .sidebar ul li a:hover, .sidebar ul li a.active {
            background: rgba(215, 34, 41, 0.1);
            color: white;
        }

        .sidebar ul li a.active {
            background: var(--primary-red);
            box-shadow: 0 10px 20px rgba(215, 34, 41, 0.3);
        }

        /* --- Main Content Area --- */
        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 50px;
            animation: fadeIn 0.8s ease-out;
        }

        .dash-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .dash-header h1 { font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; }
        .highlight { color: var(--primary-red); }

        /* --- Stats Cards --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--white);
            padding: 30px;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(23, 5, 5, 0.03);
            border: 1px solid rgba(167, 111, 88, 0.05);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(215, 34, 41, 0.08);
        }

        .stat-card h3 { font-size: 0.85rem; color: var(--clay); margin-bottom: 12px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; }
        .stat-card p { font-size: 32px; font-weight: 800; color: var(--night); }
        .revenue-text { color: var(--primary-red) !important; }

        /* --- Grid Layout --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.8fr 1.2fr;
            gap: 30px;
        }

        /* --- Table Container --- */
        .table-container {
            background: var(--white);
            padding: 35px;
            border-radius: 35px;
            box-shadow: 0 20px 40px rgba(23, 5, 5, 0.03);
            border: 1px solid rgba(167, 111, 88, 0.05);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .admin-table th {
            text-align: left;
            padding: 20px;
            color: var(--clay);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            border-bottom: 1px solid #eee;
        }

        .admin-table td {
            padding: 20px;
            border-bottom: 1px solid #f8f9fa;
            font-weight: 500;
        }

        .store-flex { display: flex; align-items: center; gap: 15px; }
        .table-logo { width: 45px; height: 45px; border-radius: 12px; object-fit: contain; border: 1px solid #eee; background: white; }

        .chart-container {
            background: var(--white);
            padding: 35px;
            border-radius: 35px;
            box-shadow: 0 20px 40px rgba(23, 5, 5, 0.03);
            border: 1px solid rgba(167, 111, 88, 0.05);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1200px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .sidebar { width: 100px; }
            .sidebar-logo, .sidebar ul li a span { display: none; }
            .main-content { margin-left: 100px; width: calc(100% - 100px); }
        }
    </style>
</head>
<body>

   <nav class="sidebar">
        <div class="sidebar-logo">Store<span>نشمي</span></div>
        <ul>
            <li><a href="admin_dashboard.php" class="active"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></a></li>
            <li><a href="approve.php"><i class="fa-solid fa-shield-check"></i> <span>Approve Products</span></a></li>
            <li><a href="category.php"><i class="fa-solid fa-layer-group"></i> <span>Categories</span></a></li>
            <li style="margin-top: 50px;"><a href="log_out.php" style="color: #ffbaba;"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a></li>
        </ul>
    </nav>

    <main class="main-content">
        <header class="dash-header">
            <div class="header-info">
                <h1>Global <span class="highlight">Overview</span></h1>
                <p style="color: var(--clay); font-weight: 500;">Nashmi store Ecosystem Control Panel</p>
            </div>
            <div class="admin-profile" style="background: white; padding: 12px 25px; border-radius: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.03); border: 1px solid #eee;">
                <span style="font-weight: 700; font-size: 0.9rem;"><i class="fa-solid fa-user-shield" style="margin-right: 10px; color: var(--primary-red);"></i> Root Admin</span>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <h3>Managed Vendors</h3>
                <p><?php echo number_format($total_vendors); ?></p>
            </div>
            <div class="stat-card">
                <h3>Net Ecosystem Volume</h3>
                <p class="revenue-text">JOD <?php echo number_format($global_revenue, 2); ?></p>
            </div>
        </section>

        <div class="dashboard-grid">
            <div class="left-section">
                <div class="table-container">
                    <h3 style="font-weight: 800; font-size: 1.2rem; margin-bottom: 10px;">Recent Entities</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Partner Name</th>
                                <th>Contact Interface</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="store-flex">
                                                <img src="<?php echo htmlspecialchars($row['logo_url']); ?>" class="table-logo" alt="Logo">
                                                <div>
                                                    <div style="font-weight: 700; color: var(--night);"> <?php echo htmlspecialchars($row['store_name']); ?></div>
                                                    <div style="font-size: 11px; color: var(--clay);">#VE-<?php echo $row['vendor_id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span style="font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($row['vendor_email']); ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="right-section">
                <div class="chart-container">
                    <h3 style="font-weight: 800; font-size: 1.1rem; margin-bottom: 25px;">Sector Analysis</h3>
                    <canvas id="vendorChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('vendorChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($chart_data); ?>,
                    backgroundColor: ['#170505', '#d72229', '#a76f58', '#2d3436', '#e17055', '#636e72'],
                    borderWidth: 5,
                    borderColor: '#ffffff',
                    hoverOffset: 25
                }]
            },
            options: {
                cutout: '75%',
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { 
                            usePointStyle: true, 
                            padding: 25, 
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' },
                            color: '#a76f58'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>