<?php
session_start();


$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8mb4");

$vendor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

//vendor info
$v_sql = "SELECT v.store_name, v.vendor_email, v.logo_url, 
          IFNULL(AVG(r.rating), 0) as avg_rating, 
          COUNT(r.review_id) as total_reviews 
          FROM vendors v 
          LEFT JOIN reviews r ON v.vendor_id = r.vendor_id_fk 
          WHERE v.vendor_id = ? 
          GROUP BY v.vendor_id";

$v_stmt = $conn->prepare($v_sql);
$v_stmt->bind_param("i", $vendor_id);
$v_stmt->execute();
$vendor = $v_stmt->get_result()->fetch_assoc();

if (!$vendor) { die("<h1 style='text-align:center; margin-top:50px; font-family:Plus Jakarta Sans;'>Store Not Found!</h1>"); }

// approved product only
$p_sql = "SELECT product_id, product_name, product_price, image_url 
          FROM products 
          WHERE vendor_id_FK = ? AND approved_by_admin = 'approved'";

$p_stmt = $conn->prepare($p_sql);
$p_stmt->bind_param("i", $vendor_id);
$p_stmt->execute();
$products_result = $p_stmt->get_result();

// reviews
$r_sql = "SELECT r.rating, r.comment, r.review_date, c.customer_firstname, c.customer_lastname 
          FROM reviews r 
          JOIN customers c ON r.customer_id_fk = c.customer_id 
          WHERE r.vendor_id_fk = ? 
          ORDER BY r.review_date DESC";
$r_stmt = $conn->prepare($r_sql);
$r_stmt->bind_param("i", $vendor_id);
$r_stmt->execute();
$reviews_result = $r_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($vendor['store_name']); ?> | Nashmi store</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-red: #d72229;
            --deep-maroon: #770e13;
            --star-gold: #ffb800;
            --night: #170505;
            --clay: #a76f58;
            --soft-bg: #fcfcfc;
            --border-soft: rgba(167, 111, 88, 0.15);
            --ease: cubic-bezier(0.23, 1, 0.32, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--soft-bg); color: var(--night); line-height: 1.6; overflow-x: hidden; }

        .nav-overlay { position: fixed; top: 30px; left: 30px; z-index: 1000; }
        .btn-home { 
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); color: var(--night); 
            text-decoration: none; padding: 12px 25px; border-radius: 18px; font-weight: 700; 
            display: flex; align-items: center; gap: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            border: 1px solid var(--border-soft); transition: var(--ease) 0.4s;
        }
        .btn-home:hover { transform: translateX(-5px); color: var(--primary-red); border-color: var(--primary-red); }

        .store-header { 
            background: linear-gradient(135deg, var(--night) 0%, var(--deep-maroon) 100%);
            padding: 140px 10% 80px; display: flex; align-items: center; gap: 50px; position: relative;
            color: white; overflow: hidden;
        }

        .store-logo { 
            width: 180px; height: 180px; border-radius: 45px; object-fit: cover; 
            border: 8px solid rgba(255,255,255,0.1); box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            background: white; transform: rotate(-2deg); transition: 0.5s var(--ease);
        }
        .store-logo:hover { transform: rotate(0deg) scale(1.05); }

        .store-info h1 { font-size: 3.8rem; font-weight: 800; letter-spacing: -2px; line-height: 1.1; }
        .rating-badge { 
            display: flex; align-items: center; gap: 12px; margin-top: 20px; 
            background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
            padding: 10px 25px; border-radius: 50px; width: fit-content; border: 1px solid rgba(255,255,255,0.2);
        }

        .container { padding: 80px 10%; max-width: 1440px; margin: auto; }
        .section-title { font-size: 2.2rem; font-weight: 800; margin-bottom: 50px; display: flex; align-items: center; gap: 20px; color: var(--night); }
        .section-title i { color: var(--primary-red); font-size: 1.8rem; }
        .section-title::after { content: ""; flex: 1; height: 1px; background: var(--border-soft); }

        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 35px; margin-bottom: 100px; }
        .product-card { background: white; padding: 15px; border-radius: 35px; border: 1px solid var(--border-soft); transition: 0.5s var(--ease); position: relative; }
        .product-card:hover { transform: translateY(-15px); box-shadow: 0 30px 60px rgba(119, 14, 19, 0.1); border-color: var(--primary-red); }
        
        .product-img-wrapper { background: #f9f9f9; border-radius: 28px; height: 280px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .product-img { width: 90%; height: 90%; object-fit: contain; transition: 0.6s var(--ease); }

        .product-info { padding: 15px 10px; }
        .product-name { font-weight: 700; font-size: 1.2rem; color: var(--night); text-decoration: none; display: block; margin-bottom: 8px; }
        .price-tag { color: var(--primary-red); font-weight: 800; font-size: 1.4rem; display: block; }

        .reviews-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; }
        .review-item { padding: 35px; background: white; border-radius: 30px; border: 1px solid var(--border-soft); transition: 0.4s var(--ease); box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        .rev-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .cust-name { font-weight: 800; font-size: 1.1rem; color: var(--night); }
        .rev-text { color: #555; font-style: italic; line-height: 1.8; font-size: 0.95rem; }

        .empty-state { grid-column: 1/-1; text-align: center; padding: 80px; color: var(--clay); font-weight: 600; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; display: block; opacity: 0.5; }
    </style>
</head>
<body>

    <div class="nav-overlay">
        <a href="index.php" class="btn-home"><i class="fa-solid fa-house-chimney"></i> Home</a>
    </div>

    <header class="store-header">
        <img src="<?php echo htmlspecialchars($vendor['logo_url']); ?>" class="store-logo" alt="Store Logo">
        <div class="store-info">
            <h1><?php echo htmlspecialchars($vendor['store_name']); ?></h1>
            <div class="rating-badge">
                <span style="font-weight: 800; font-size: 1.2rem;"><?php echo number_format($vendor['avg_rating'], 1); ?></span>
                <div style="color: var(--star-gold);">
                    <?php 
                    $avg = round($vendor['avg_rating']);
                    for($i=1; $i<=5; $i++) echo ($i <= $avg) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                    ?>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <h2 class="section-title"><i class="fa-solid fa-bag-shopping"></i> Exclusive Collection</h2>
        <div class="products-grid">
            <?php if ($products_result->num_rows > 0): ?>
                <?php while($p = $products_result->fetch_assoc()): ?>
                    <a href="product_detail.php?id=<?php echo $p['product_id']; ?>" style="text-decoration: none;">
                        <div class="product-card">
                            <div class="product-img-wrapper">
                                <img src="<?php echo htmlspecialchars($p['image_url']); ?>" class="product-img" alt="Product">
                            </div>
                            <div class="product-info">
                                <span class="product-name"><?php echo htmlspecialchars($p['product_name']); ?></span>
                                <span class="price-tag">JOD <?php echo number_format($p['product_price'], 2); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-boxes-packing"></i>
                    <p>New treasures are being curated. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>

        <h2 class="section-title"><i class="fa-solid fa-quote-left"></i> Client reviews</h2>
        <div class="reviews-container">
            <?php if ($reviews_result->num_rows > 0): ?>
                <?php while($rev = $reviews_result->fetch_assoc()): ?>
                    <div class="review-item">
                        <div class="rev-header">
                            <span class="cust-name"><?php echo htmlspecialchars($rev['customer_firstname'] . " " . $rev['customer_lastname']); ?></span>
                            <div style="color:var(--star-gold); font-size: 0.8rem;">
                                <?php for($i=1; $i<=5; $i++) echo ($i <= $rev['rating']) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>'; ?>
                            </div>
                        </div>
                        <p class="rev-text">"<?php echo htmlspecialchars($rev['comment']); ?>"</p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-comment-slash"></i>
                    <p>No reviews yet. Be the first to share your experience!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>