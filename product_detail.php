<?php
session_start();
$conn = new mysqli("localhost", "root", "Zz0795426555$", "multivendor_marketplace");
mysqli_set_charset($conn, "utf8mb4");

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

//products
$query = "SELECT p.*, v.category_ID_FK, v.vendor_id, v.store_name 
          FROM products p 
          JOIN vendors v ON p.vendor_id_fk = v.vendor_id 
          WHERE p.product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) { die("Product Not Found"); }

if (isset($_SESSION['user_id'])) {
    $u_id = $_SESSION['user_id'];
    $cat_id = $product['category_ID_FK'];
    $conn->query("INSERT INTO user_views (customer_id_fk, category_id_fk, product_id_fk) VALUES ($u_id, $cat_id, $product_id)");
}

$qty = $product['product_quantity'];
$is_available = ($qty > 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['product_name']); ?> | Nashmi store</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-red: #d72229; --night: #170505; --soft-bg: #fdfbfb; --earth-tan: #a76f58; }
        body { background: var(--soft-bg); font-family: 'Plus Jakarta Sans', sans-serif; padding: 30px 50px; }
        
        .back-nav { max-width: 1100px; margin: 0 auto 20px auto; }
        .btn-back-store { 
            text-decoration: none; color: var(--night); font-weight: 700; 
            display: inline-flex; align-items: center; gap: 10px;
            transition: 0.3s; padding: 10px 0;
        }
        .btn-back-store:hover { color: var(--primary-red); transform: translateX(-5px); }

        .product-wrapper { 
            max-width: 1100px; margin: auto; display: flex; gap: 50px; 
            background: white; padding: 40px; border-radius: 40px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.05); 
        }
        
        .img-box { flex: 1; text-align: center; }
        .img-box img { max-width: 100%; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        
        .details-box { flex: 1; display: flex; flex-direction: column; }
        .price { font-size: 2.8rem; color: var(--primary-red); font-weight: 800; margin: 15px 0; }
        
        .qty-input { width: 70px; padding: 12px; border-radius: 12px; border: 1px solid #eee; margin-bottom: 20px; font-weight: 700; }

        .actions-wrapper { display: flex; gap: 15px; align-items: center; margin-top: auto; }
        
        .btn-add { 
            background: var(--night); color: white; padding: 18px 40px; 
            border-radius: 20px; border: none; font-weight: 800; 
            cursor: pointer; flex: 1; transition: 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-add:hover { background: var(--primary-red); box-shadow: 0 10px 20px rgba(215, 34, 41, 0.2); }

        .btn-wishlist { 
            background: #fff1f2; color: var(--primary-red); 
            padding: 18px; border-radius: 20px; border: 1px solid #ffe4e6;
            cursor: pointer; transition: 0.3s; font-size: 1.4rem;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; width: 60px;
        }
        .btn-wishlist:hover { background: var(--primary-red); color: white; }
        
        .out-of-stock-tag {
            background: #f1f1f1; color: #999; padding: 18px; 
            border-radius: 20px; flex: 1; text-align: center; 
            font-weight: 800; cursor: not-allowed;
        }
    </style>
</head>
<body>

<div class="back-nav">
    <a href="store.php?id=<?php echo $product['vendor_id']; ?>" class="btn-back-store">
        <i class="fa-solid fa-arrow-left"></i> Back to <strong><?php echo htmlspecialchars($product['store_name']); ?></strong>
    </a>
</div>

<div class="product-wrapper">
    <div class="img-box">
        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
    </div>
    
    <div class="details-box">
        <h1 style="font-size: 2.2rem;"><?php echo htmlspecialchars($product['product_name']); ?></h1>
        <div class="price">JOD <?php echo number_format($product['product_price'], 2); ?></div>
        
        <p style="color: #666; line-height: 1.6; margin-bottom: 30px;">
            <?php echo htmlspecialchars($product['product_description']); ?>
        </p>
        
        <div class="actions-section">
            <?php if($is_available): ?>
                <label style="font-weight: 700; display: block; margin-bottom: 8px;">Select Quantity</label>
                <input type="number" id="qtyView" value="1" min="1" max="<?php echo $qty; ?>" class="qty-input" onchange="document.getElementById('qtySubmit').value = this.value">
            <?php endif; ?>

            <div class="actions-wrapper">
                <?php if($is_available): ?>
                    <form action="add_to_cart.php" method="POST" style="flex: 1;">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        <input type="hidden" name="quantity" id="qtySubmit" value="1">
                        <button type="submit" class="btn-add">
                            <i class="fa-solid fa-bag-shopping"></i> Add to Cart
                        </button>
                    </form>
                <?php else: ?>
                    <div class="out-of-stock-tag">
                        <i class="fa-solid fa-circle-minus"></i> Out of Stock
                    </div>
                <?php endif; ?>

                <a href="add_to_wishlist.php?product_id=<?php echo $product_id; ?>" class="btn-wishlist" title="Add to Wishlist">
                    <i class="fa-regular fa-heart"></i>
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>