<?php
session_start();

$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8mb4");

$cat_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


$cat_stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
$cat_stmt->bind_param("i", $cat_id);
$cat_stmt->execute();
$category = $cat_stmt->get_result()->fetch_assoc();

if (!$category) { die("<h1 style='text-align:center; font-family:Outfit; margin-top:100px;'>Category Not Found!</h1>"); }

// categories and thier reviews
$v_sql = "SELECT v.*, 
          IFNULL(AVG(r.rating), 0) as avg_rating, 
          COUNT(r.review_id) as total_reviews 
          FROM vendors v 
          LEFT JOIN reviews r ON v.vendor_id = r.vendor_id_fk 
          WHERE v.category_ID_FK = ? 
          GROUP BY v.vendor_id";

$v_stmt = $conn->prepare($v_sql);
$v_stmt->bind_param("i", $cat_id);
$v_stmt->execute();
$vendors_result = $v_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['category_name']); ?> | Nashmi store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #d72229;
            --deep-maroon: #770e13;
            --star-gold: #ffb800;
            --rich-black: #170505;
            --earth-tan: #a76f58;
            --soft-bg: #fdfbfb;
            --border-soft: rgba(167, 111, 88, 0.12);
            --ease: cubic-bezier(0.23, 1, 0.32, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--soft-bg); 
            color: var(--rich-black);
            line-height: 1.6;
        }

        /* Hero Header Section */
        .category-header {
            background: linear-gradient(135deg, var(--deep-maroon) 0%, var(--primary-red) 100%);
            color: white;
            padding: 100px 10% 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* إضافة زخرفة خفيفة في الخلفية */
        .category-header::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('https://www.transparenttextures.com/patterns/carbon-fibre.png');
            opacity: 0.1;
            pointer-events: none;
        }

        .back-btn {
            position: absolute;
            top: 40px;
            left: 5%;
            text-decoration: none;
            color: white;
            background: rgba(255,255,255,0.12);
            padding: 12px 24px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            transition: 0.4s var(--ease);
            border: 1px solid rgba(255,255,255,0.2);
            font-weight: 600;
            font-size: 0.9rem;
            z-index: 10;
        }
        .back-btn:hover { 
            background: white; 
            color: var(--deep-maroon); 
            transform: translateX(-5px);
        }

        .cat-circle-img {
            width: 160px; height: 160px;
            border-radius: 50px;
            object-fit: cover;
            border: 8px solid rgba(255,255,255,0.15);
            margin-bottom: 25px;
            background: white;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeInDown 0.8s var(--ease);
        }

        .category-header h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: -2px;
            animation: fadeInUp 0.8s var(--ease);
        }

        .category-header p {
            max-width: 600px;
            margin: 0 auto;
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        /* Stores Container */
        .stores-container { 
            padding: 80px 10%; 
            max-width: 1400px;
            margin: auto;
        }
        
        .section-title {
            margin-bottom: 50px;
            font-size: 2rem;
            font-weight: 800;
            color: var(--rich-black);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-title::after {
            content: '';
            height: 4px;
            width: 60px;
            background: var(--primary-red);
            border-radius: 10px;
        }

        .store-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 40px;
        }

        .store-card {
            background: white;
            border-radius: 35px;
            padding: 50px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(23, 5, 5, 0.03);
            transition: all 0.5s var(--ease);
            border: 1px solid var(--border-soft);
            position: relative;
        }

        .store-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 30px 60px rgba(119, 14, 19, 0.1);
            border-color: var(--earth-tan);
        }

        .vendor-logo-wrapper {
            position: relative;
            width: 120px; height: 120px;
            margin: 0 auto 25px;
        }

        .vendor-logo-circle {
            width: 100%; height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 15px 30px rgba(0,0,0,0.08);
            transition: 0.5s var(--ease);
        }

        .store-card:hover .vendor-logo-circle {
            transform: scale(1.1) rotate(5deg);
        }

        .store-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--rich-black);
            margin-bottom: 12px;
        }

        /* Rating Stars */
        .rating-box {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            background: rgba(167, 111, 88, 0.05);
            padding: 8px 15px;
            border-radius: 50px;
            width: fit-content;
            margin: 0 auto 25px;
        }
        .star-filled { color: var(--star-gold); font-size: 0.9rem; }
        .star-empty { color: #d1d5db; font-size: 0.9rem; }
        .review-count { 
            font-size: 0.8rem; 
            color: var(--earth-tan); 
            margin-left: 6px;
            font-weight: 700;
        }

        .btn-visit {
            text-decoration: none;
            background: var(--rich-black);
            color: white;
            padding: 15px 35px;
            border-radius: 20px;
            font-weight: 700;
            transition: 0.4s var(--ease);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }

        .btn-visit i { transition: 0.3s; }

        .btn-visit:hover { 
            background: var(--primary-red); 
            box-shadow: 0 10px 25px rgba(215, 34, 41, 0.3);
            padding: 15px 45px;
        }

        .btn-visit:hover i { transform: translateX(5px); }

        .no-data { 
            text-align: center; 
            grid-column: 1/-1; 
            padding: 100px 0; 
            background: white;
            border-radius: 40px;
            border: 2px dashed var(--border-soft);
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .category-header h1 { font-size: 2.5rem; }
            .category-header { padding: 80px 5% 60px; }
            .store-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <header class="category-header">
        <a href="index.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Home
        </a>

        <?php if(!empty($category['category_url'])): ?>
            <img src="<?php echo htmlspecialchars($category['category_url']); ?>" class="cat-circle-img" alt="Category">
        <?php endif; ?>

        <h1><?php echo htmlspecialchars($category['category_name']); ?></h1>
        <p><?php echo htmlspecialchars($category['category_description']); ?></p>
    </header>

    <main class="stores-container">
        <h2 class="section-title">Verified Vendors</h2>
        
        <div class="store-grid">
            <?php if ($vendors_result->num_rows > 0): ?>
                <?php while($vendor = $vendors_result->fetch_assoc()): ?>
                    <div class="store-card">
                        <div class="vendor-logo-wrapper">
                            <?php if(!empty($vendor['logo_url'])): ?>
                                <img src="<?php echo htmlspecialchars($vendor['logo_url']); ?>" class="vendor-logo-circle">
                            <?php else: ?>
                                <div class="vendor-logo-circle" style="background:#fdfbfb; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-shop fa-3x" style="color:var(--earth-tan); opacity: 0.3;"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h3><?php echo htmlspecialchars($vendor['store_name']); ?></h3>
                        
                        <div class="rating-box">
                            <?php 
                            $rating = round($vendor['avg_rating']); 
                            for($i = 1; $i <= 5; $i++) {
                                if($i <= $rating) {
                                    echo '<i class="fa-solid fa-star star-filled"></i>';
                                } else {
                                    echo '<i class="fa-regular fa-star star-empty"></i>';
                                }
                            }
                            ?>
                            <span class="review-count"><?php echo $vendor['total_reviews']; ?> Reviews</span>
                        </div>

                        <a href="store.php?id=<?php echo $vendor['vendor_id']; ?>" class="btn-visit">
                            Explore Collection <i class="fa-solid fa-arrow-right-long"></i>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fa-solid fa-store-slash fa-4x" style="color: var(--earth-tan); margin-bottom: 20px; opacity: 0.2;"></i>
                    <h3 style="color: var(--chocolate);">No stores available yet.</h3>
                    <p style="color: var(--earth-tan);">Check back soon for new arrivals.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>