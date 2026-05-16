<?php
session_start();

$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8mb4");

$is_customer = isset($_SESSION['user_id']);
$cart_count = 0;

if ($is_customer) {
    $user_id = $_SESSION['user_id'];
    $cart_res = $conn->query("SELECT SUM(cart_quantity) as total FROM cart_items ci JOIN carts c ON ci.cart_id_fk = c.cart_id WHERE c.customer_id_fk = $user_id");
    if ($cart_res) {
        $cart_data = $cart_res->fetch_assoc();
        $cart_count = $cart_data['total'] ?? 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nashmi Store | Home</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-red: #d72229;
            --deep-maroon: #770e13;
            --clay: #a76f58;
            --wood: #5d382f;
            --night: #170505;
            --bg-soft: #fcfcfc;
            --transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }

        html { scroll-behavior: smooth; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-soft); color: var(--night); line-height: 1.6; overflow-x: hidden; }

        /* --- Navbar --- */
        header {
            background: var(--night);
            padding: 12px 5%;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--deep-maroon);
            transition: var(--transition);
        }

        .navbar { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .logo { display: flex; align-items: center; cursor: pointer; color: white; font-size: 1.6rem; font-weight: 800; text-decoration: none; }
        .logo img { height: 48px; margin-right: 10px; }
        .logo span { color: var(--primary-red); }

        .search-container { flex: 1; max-width: 550px; }
        .search-bar { 
            display: flex; align-items: center; background: rgba(255,255,255,0.08); 
            border-radius: 12px; padding: 4px 15px; border: 1px solid var(--wood);
            transition: 0.3s;
        }
        .search-bar:focus-within { border-color: var(--primary-red); background: rgba(255,255,255,0.12); }
        .search-bar input { flex: 1; background: transparent; border: none; padding: 10px; color: white; outline: none; }
        .search-bar i, .search-bar label { color: var(--clay); cursor: pointer; margin: 0 8px; font-size: 1.1rem; transition: 0.3s; }
        .search-bar i:hover, .search-bar label:hover { color: var(--primary-red); }

        /* Icons & Dropdown */
        .nav-icons { display: flex; align-items: center; gap: 22px; }
        .nav-icons a { color: white; text-decoration: none; font-size: 1.35rem; position: relative; transition: 0.3s; }
        .nav-icons a:hover { color: var(--primary-red); }
        .badge { background: var(--primary-red); position: absolute; top: -10px; right: -12px; border-radius: 50%; padding: 2px 7px; font-size: 10px; color: white; }
        
        .user-profile-menu { position: relative; cursor: pointer; }
        .dropdown-content { display: none; position: absolute; right: 0; top: 130%; background: white; min-width: 200px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); z-index: 1001; overflow: hidden; }
        .user-profile-menu:hover .dropdown-content { display: block; }
        .dropdown-content a { color: var(--night); padding: 14px 20px; display: block; text-decoration: none; border-bottom: 1px solid #f0f0f0; transition: 0.3s; font-size: 14px; }
        .dropdown-content a:hover { background: #fff5f5; color: var(--primary-red); }

        /* Auth Buttons */
        .btn-auth { text-decoration: none; padding: 9px 20px; border-radius: 10px; font-weight: 700; font-size: 14px; transition: var(--transition); }
        .btn-signin { color: white; border: 1px solid var(--primary-red); }
        .btn-signin:hover { background: var(--primary-red); }
        .btn-join { background: var(--primary-red); color: white; border: 1px solid var(--primary-red); margin-left: 10px; }
        .btn-join:hover { background: transparent; color: var(--primary-red); }

        /* --- Hero --- */
        .hero {
            height: 70vh;
            background: linear-gradient(rgba(23, 5, 5, 0.4), rgba(23, 5, 5, 0.6)), url('Untitled-17.png');
            background-size: cover; background-position: center; background-attachment: fixed;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: white; text-align: center; padding: 0 5%;
        }
        .hero h1 { font-size: 4.2rem; font-weight: 800; letter-spacing: -2px; margin-bottom: 10px; text-shadow: 0 5px 20px rgba(0,0,0,0.5); }

        /* --- Zebra Slider --- */
        .zebra-section { background: linear-gradient(135deg, var(--night) 0%, var(--deep-maroon) 100%); padding: 80px 0; overflow: hidden; }
        .zebra-content { padding: 0 5% 35px; color: white; }
        .zebra-content h3 { font-size: 2.6rem; margin-bottom: 5px; }

        .slider-container { width: 100%; overflow: hidden; }
        .slider-wrapper { display: flex; width: max-content; animation: infiniteScroll 30s linear infinite; }
        .slider-wrapper:hover { animation-play-state: paused; }
        @keyframes infiniteScroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

        .product-mini-card { 
            width: 260px; background: rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; 
            margin: 0 15px; border: 1px solid rgba(255,255,255,0.1); text-decoration: none; 
            color: white; transition: var(--transition); flex-shrink: 0; backdrop-filter: blur(10px);
        }
        .product-mini-card:hover { transform: translateY(-10px); border-color: var(--primary-red); background: rgba(255,255,255,0.1); }
        .product-mini-card img { width: 100%; height: 180px; object-fit: contain; margin-bottom: 15px; }
        .product-mini-card .price { color: var(--primary-red); font-weight: 800; font-size: 1.1rem; }

        /* Heritage Divider */
        .heritage-divider {
            width: 100%; height: 150px;
            background-image: url('WhatsApp Image 2026-04-14 at 2.48.34 PM.jpeg');
            background-size: contain; background-repeat: repeat-x; background-position: center;
            margin: 50px 0; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.15));
        }

        /* --- Categories --- */
        .section-title { padding: 60px 5% 30px; font-size: 2.3rem; font-weight: 800; color: var(--night); display: flex; align-items: center; gap: 15px; }
        .section-title i { color: var(--primary-red); }
        
        .category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 25px; padding: 0 5% 50px; }
        .cat-card { text-decoration: none; text-align: center; padding: 25px; background: white; border-radius: 25px; border: 1px solid #eee; transition: var(--transition); }
        .cat-card:hover { transform: translateY(-10px); border-color: var(--clay); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        
        .cat-card img { 
            width: 80px; 
            height: 80px; 
            object-fit: contain; 
            margin-bottom: 15px; 
            transition: var(--transition);
        }
        .cat-card:hover img { transform: scale(1.1); }
        .cat-card p { font-weight: 700; color: var(--night); font-size: 1rem; }

        .vendor-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 35px; padding: 0 5% 80px; }
        .vendor-card { background: white; border-radius: 35px; padding: 40px; text-align: center; border: 1px solid #eee; transition: var(--transition); position: relative; }
        .rank-tag { background: var(--deep-maroon); color: white; padding: 6px 16px; border-radius: 50px; font-size: 0.75rem; position: absolute; top: 20px; right: 20px; font-weight: 800; }
        .vendor-img { width: 110px; height: 110px; border-radius: 30px; object-fit: cover; margin-bottom: 20px; border: 3px solid #f9f9f9; background: white; }

        /* Camera Modal */
        #cameraModal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.96); backdrop-filter: blur(10px); }
        .modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 480px; background: var(--night); padding: 30px; border-radius: 30px; border: 2px solid var(--primary-red); text-align: center; }
        #cameraVideo { width: 100%; border-radius: 20px; margin-bottom: 25px; border: 2px solid var(--wood); }
    </style>
</head>
<body>

    <div id="cameraModal">
        <div class="modal-content">
            <h3 style="color:white; margin-bottom:20px;">Visual Search</h3>
            <video id="cameraVideo" autoplay playsinline></video>
            <div style="display:flex; justify-content:center; gap:15px;">
                <button onclick="takeSnapshot()" style="background:var(--primary-red); color:white; border:none; padding:15px 35px; border-radius:12px; cursor:pointer; font-weight:800;"><i class="fa-solid fa-camera"></i> Snap Photo</button>
                <button onclick="closeCameraModal()" style="background:var(--wood); color:white; border:none; padding:15px 25px; border-radius:12px; cursor:pointer;">Cancel</button>
            </div>
        </div>
    </div>

    <header id="mainHeader">
        <nav class="navbar">
            <a href="index.php" class="logo">
                <img src="WhatsApp_Image_2026-04-14_at_8.05.20_PM-removebg-preview.png" alt="Logo">
                Store <span>نشمي</span>
            </a>
            
            <div class="search-container">
                <form action="search_results.php" method="POST" id="searchForm" enctype="multipart/form-data" class="search-bar">
                    <input type="text" name="query" placeholder="Search products or take a photo...">
                    <i class="fa-solid fa-camera" onclick="openCameraModal()" title="Visual Search"></i>
                    <label for="imgInput" title="Upload Image"><i class="fa-solid fa-paperclip"></i></label>
                    <input type="file" name="image_query" id="imgInput" style="display:none;" onchange="this.form.submit()">
                    <button type="submit" style="background:none; border:none; color:white; cursor:pointer;"><i class="fa-solid fa-magnifying-glass"></i></button>
                    <input type="hidden" name="camera_image" id="cameraImageInput">
                </form>
            </div>

            <div class="nav-links">
                <?php if ($is_customer): ?>
                    <div class="nav-icons">
                        <a href="tracking.php" title="Track Orders"><i class="fa-solid fa-truck-fast"></i></a>
                        <a href="wishlist.php" title="My Wishlist"><i class="fa-regular fa-heart"></i></a>
                        <a href="cart.php" title="My Cart">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <?php if ($cart_count > 0): ?><span class="badge"><?php echo $cart_count; ?></span><?php endif; ?>
                        </a>
                        <div class="user-profile-menu">
                            <i class="fa-solid fa-circle-user" style="font-size: 2.3rem; color:white;"></i>
                            <div class="dropdown-content">
                                <a href="customer_profile.php">My Account</a>
                                <a href="log_out.php" style="color:var(--primary-red);">Sign Out</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display:flex; align-items:center;">
                        <a href="test_login.php" class="btn-auth btn-signin">Sign In</a>
                        <a href="register.php" class="btn-auth btn-join">Join Us</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h1>Innovation in Every Click.</h1>
            <p>Where luxury tradition meets next-gen technology.</p>
        </section>

        <?php if ($is_customer): 
            $sql_track = "SELECT p.* FROM products p
                          JOIN user_views uv ON p.category_id_fk = uv.category_id_fk
                          WHERE uv.customer_id_fk = $user_id 
                          AND p.approved_by_admin = 'approved'
                          GROUP BY p.product_id
                          ORDER BY COUNT(uv.view_id) DESC 
                          LIMIT 6";

            $prod_res = $conn->query($sql_track);

            if (!$prod_res || $prod_res->num_rows == 0) {
                $prod_res = $conn->query("SELECT * FROM products WHERE approved_by_admin='approved' ORDER BY RAND() LIMIT 6");
            }

            if ($prod_res && $prod_res->num_rows > 0): 
                $items = []; while($row = $prod_res->fetch_assoc()) { $items[] = $row; }
                $double_items = array_merge($items, $items); 
            ?>
            <section class="zebra-section">
                <div class="zebra-content">
                    <h3>Picked Just For You</h3>
                    <p>Curated items based on your personal style.</p>
                </div>
                <div class="slider-container">
                    <div class="slider-wrapper">
                        <?php foreach($double_items as $p): ?>
                            <a href="product_detail.php?id=<?php echo $p['product_id']; ?>" class="product-mini-card">
                                <img src="<?php echo htmlspecialchars($p['image_url']); ?>" alt="<?php echo htmlspecialchars($p['product_name']); ?>">
                                <h4><?php echo htmlspecialchars($p['product_name']); ?></h4>
                                <div class="price">JOD <?php echo number_format($p['product_price'], 2); ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        <?php endif; ?>

        <section class="categories">
            <h2 class="section-title"><i class="fa-solid fa-layer-group"></i> Categories</h2>
            <div class="category-grid">
                <?php 
                $cat_res = $conn->query("SELECT * FROM categories");
                while($row = $cat_res->fetch_assoc()): ?>
                    <a href="index_category.php?id=<?php echo $row['category_id']; ?>" class="cat-card">
                        <img src="<?php echo htmlspecialchars($row['category_url']); ?>" alt="<?php echo htmlspecialchars($row['category_name']); ?>">
                        <p><?php echo htmlspecialchars($row['category_name']); ?></p>
                    </a>
                <?php endwhile; ?>
            </div>
        </section>

        <div class="heritage-divider"></div>

        <section class="best-stores">
            <h2 class="section-title"><i class="fa-solid fa-crown"></i> Best Stores</h2>
            <div class="vendor-grid">
                <?php 
                $sql_best = "SELECT v.vendor_id, v.store_name, v.logo_url, AVG(r.rating) as avg_rating, COUNT(r.review_id) as total_reviews
                             FROM vendors v JOIN reviews r ON v.vendor_id = r.vendor_id_fk
                             GROUP BY v.vendor_id HAVING avg_rating >= 4 ORDER BY avg_rating DESC LIMIT 4";
                $best_res = $conn->query($sql_best);
                if ($best_res):
                    $rank = 1;
                    while ($vendor = $best_res->fetch_assoc()): ?>
                        <div class="vendor-card">
                            <div class="rank-tag">TOP RATED #<?php echo $rank++; ?></div>
                            <img src="<?php echo htmlspecialchars($vendor['logo_url']); ?>" class="vendor-img" alt="Store Logo">
                            <h3><?php echo htmlspecialchars($vendor['store_name']); ?></h3>
                            <div style="color: #ffc107; margin: 10px 0;"><i class="fa-solid fa-star"></i> <?php echo number_format($vendor['avg_rating'], 1); ?></div>
                            <a href="store.php?id=<?php echo $vendor['vendor_id']; ?>" style="color:var(--primary-red); font-weight:800; text-decoration:none;">Visit Store</a>
                        </div>
                    <?php endwhile; 
                endif; ?>
            </div>
        </section>
    </main>

    <script>
        // Camera Logic
        let camStream = null;
        async function openCameraModal() {
            document.getElementById('cameraModal').style.display = "block";
            try {
                camStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
                document.getElementById('cameraVideo').srcObject = camStream;
            } catch (e) { alert("Camera access denied."); closeCameraModal(); }
        }
        function closeCameraModal() {
            document.getElementById('cameraModal').style.display = "none";
            if(camStream) camStream.getTracks().forEach(t => t.stop());
        }
        function takeSnapshot() {
            const video = document.getElementById('cameraVideo');
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth; canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            document.getElementById('cameraImageInput').value = canvas.toDataURL('image/jpeg');
            document.getElementById('searchForm').submit();
        }

        window.addEventListener('scroll', () => {
            const header = document.getElementById('mainHeader');
            header.style.background = window.scrollY > 60 ? 'rgba(23, 5, 5, 0.98)' : 'var(--night)';
        });
    </script>
</body>
</html>