<?php
// 1. Database Connection
$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Fetch Stats
// Using IFNULL ensures that if the table is empty, we get 0 instead of NULL
$query = "SELECT COUNT(*) as total_orders, IFNULL(SUM(grand_total), 0) as total_revenue FROM orders";
$result = $conn->query($query);
$data = $result->fetch_assoc();

$total_orders  = $data['total_orders'] ?? 0;
$total_revenue = $data['total_revenue'] ?? 0;

// Close connection after fetching data
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="vendor_dashboard.css"> 
    <title>Vendor Dashboard | Nova Pixel</title>
</head>
<body class="dashboard-body">

    <nav class="sidebar">
    <div class="sidebar-logo">Nova<span>Pixel</span></div>
    <ul>
        <li><a href="vendor_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
        <li class="has-submenu">
            <a href="#" onclick="toggleSubmenu()">
                <i class="fa-solid fa-tags"></i> Products 
                <i class="fa-solid fa-chevron-down" style="margin-left: auto; font-size: 0.8rem;"></i>
            </a>
            <ul id="product-submenu" style="display: none; list-style: none; padding-left: 30px; background: #001a3d;">
                <li><a href="product_status.php">Status</a></li>
                <li><a href="add_product.php">Add Product</a></li>
                <li><a href="delete_update_product.php">Delete/Update</a></li>
            </ul>
        </li>
        <li><a href="orders.php" class="active"><i class="fa-solid fa-box"></i> Orders</a></li>
        <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
        <li><a href="index.html"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</nav>

    <main class="dashboard-content">
        <header class="dash-header">
            <h2>Welcome Back, <span style="color: #FF3838;">Vendor!</span></h2>
            <button id="lang-toggle" onclick="toggleLanguage()">EN</button>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <p>$<?php echo number_format($total_revenue, 2); ?></p>
                <i class="fa-solid fa-dollar-sign"></i>
            </div>
            
            <div class="stat-card">
                <h3>Total Orders</h3>
                <p><?php echo $total_orders; ?></p>
                <i class="fa-solid fa-cart-shopping"></i>
            </div>

            <div class="stat-card">
                <h3>Avg. Sale</h3>
                <p>$<?php 
                    $avg = ($total_orders > 0) ? ($total_revenue / $total_orders) : 0;
                    echo number_format($avg, 2); 
                ?></p>
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </section>

        <section class="analytics">
            <h3>Sales Performance Analysis</h3>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </section>


</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        // PHP to JS conversion
        const avgSale = <?php echo (float)($total_orders > 0 ? ($total_revenue / $total_orders) : 0); ?>;
        const totalRev = <?php echo (float)$total_revenue; ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Average Sale ($)', 'Total Revenue ($)'],
                datasets: [{
                    label: 'Performance Metrics',
                    data: [avgSale, totalRev],
                    backgroundColor: ['rgba(255, 56, 56, 0.6)', 'rgba(5, 14, 60, 0.8)'],
                    borderColor: ['#FF3838', '#002455'],
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100, 
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        ticks: { 
                            color: '  #050E3C',
                            callback: function(value) { return '$' + value; }
                        }
                    },
                    x: {
                        ticks: { color: '  #050E3C' },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    });
</script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="script.js"></script>
</body>
</html>
