<?php
session_start();
$conn = new mysqli("localhost", "root", "Zz0795426555$", "multivendor_marketplace");
mysqli_set_charset($conn, "utf8mb4");

if (!isset($_SESSION['user_id'])) {
    header("Location: test_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. منطق الحذف من الويش ليست
if (isset($_GET['remove'])) {
    $item_id = intval($_GET['remove']);
    $conn->query("DELETE wi FROM wishlist_items wi 
                  JOIN wishlist w ON wi.wishlist_id_fk = w.wishlist_id 
                  WHERE wi.wishlist_item_id = $item_id AND w.customer_id_fk = $user_id");
    header("Location: wishlist.php?status=removed");
    exit();
}

// 2. استعلام جلب البيانات
$sql = "SELECT wi.wishlist_item_id, p.product_id, p.product_name, p.product_price, p.image_url, wi.wishlist_date 
        FROM wishlist w
        JOIN wishlist_items wi ON w.wishlist_id = wi.wishlist_id_fk
        JOIN products p ON wi.product_id_fk = p.product_id
        WHERE w.customer_id_fk = $user_id
        ORDER BY wi.wishlist_date DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist | Nashmi store</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-red: #d72229; --rich-black: #170505; --soft-bg: #fdfbfb; --earth-tan: #a76f58; --ease: cubic-bezier(0.23, 1, 0.32, 1); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: linear-gradient(135deg, #fdfbfb 0%, #f5f0ee 100%); color: var(--rich-black); padding: 50px 5%; min-height: 100vh; }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 50px; }
        .header-section h2 { font-size: 2.5rem; font-weight: 800; letter-spacing: -1px; }
        
        .wishlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
        .wishlist-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); border-radius: 30px; padding: 15px; border: 1px solid rgba(167, 111, 88, 0.15); transition: 0.5s var(--ease); }
        .wishlist-card:hover { transform: translateY(-10px); box-shadow: 0 30px 60px rgba(119, 14, 19, 0.1); border-color: var(--earth-tan); }
        
        .card-image { width: 100%; height: 240px; border-radius: 22px; overflow: hidden; background: #f9f9f9; }
        .card-image img { width: 100%; height: 100%; object-fit: contain; transition: 0.8s; }
        .wishlist-card:hover .card-image img { transform: scale(1.1); }
        
        .price { font-size: 1.4rem; font-weight: 800; color: var(--primary-red); margin: 10px 0; }
        .btn-add-cart { flex: 1; background: var(--primary-red); color: white; text-align: center; padding: 14px; border-radius: 18px; text-decoration: none; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-remove { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background: #fff1f2; color: var(--primary-red); border-radius: 18px; text-decoration: none; transition: 0.3s; }
        .btn-remove:hover { background: var(--primary-red); color: white; }
        
        .empty-state { text-align: center; padding: 100px; background: white; border-radius: 40px; }
    </style>
</head>
<body>

<div class="wishlist-wrapper">
    <div class="header-section">
        <h2><i class="fa-solid fa-heart" style="color: var(--primary-red);"></i> My Wishlist</h2>
        <a href="index.php" style="text-decoration:none; color: var(--rich-black); font-weight:700;">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="wishlist-grid">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="wishlist-card">
                    <div class="card-image">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                    </div>
                    
                    <div class="card-details" style="padding: 15px;">
                        <h3 style="font-size: 1.1rem;"><?php echo htmlspecialchars($row['product_name']); ?></h3>
                        <div class="price">JOD <?php echo number_format($row['product_price'], 2); ?></div>
                        <div style="font-size: 0.8rem; color: var(--earth-tan);">Added: <?php echo date("M d", strtotime($row['wishlist_date'])); ?></div>
                    </div>

                    <div class="card-actions" style="display:flex; gap:10px; margin-top:15px;">
                        <a href="add_to_cart.php?product_id=<?php echo $row['product_id']; ?>&quantity=1" class="btn-add-cart">
                            <i class="fa-solid fa-cart-plus"></i> Add to Cart
                        </a>
                        <a href="wishlist.php?remove=<?php echo $row['wishlist_item_id']; ?>" 
                           class="btn-remove" onclick="return confirm('Remove from favorites?')">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-regular fa-heart" style="font-size: 4rem; color: #eee; margin-bottom: 20px;"></i>
            <p>Your wishlist is currently empty.</p>
            <a href="index.php" style="color: var(--primary-red); font-weight: 800; display: block; margin-top: 20px;">Start Exploring</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>