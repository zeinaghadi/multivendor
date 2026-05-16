<?php
session_start();
$conn = mysqli_connect("localhost", "root", "Zz0795426555$", "multivendor_marketplace");
mysqli_set_charset($conn, "utf8mb4");

if (!isset($_SESSION['user_id'])) {
    header("Location: test_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// update & delete 
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM cart_items WHERE cart_item_id = $id");
    header("Location: cart.php"); exit();
}

if (isset($_GET['update_id']) && isset($_GET['action'])) {
    $id = intval($_GET['update_id']);
    $action = $_GET['action'];
    
    // check stock before update
    if ($action == 'plus') {
        $check = mysqli_query($conn, "SELECT ci.cart_quantity, p.product_quantity FROM cart_items ci JOIN products p ON ci.product_id_fk = p.product_id WHERE ci.cart_item_id = $id");
        $data = mysqli_fetch_assoc($check);
        if ($data['cart_quantity'] < $data['product_quantity']) {
            mysqli_query($conn, "UPDATE cart_items SET cart_quantity = cart_quantity + 1 WHERE cart_item_id = $id");
        }
    } elseif ($action == 'minus') {
        mysqli_query($conn, "UPDATE cart_items SET cart_quantity = cart_quantity - 1 WHERE cart_item_id = $id AND cart_quantity > 1");
    }
    header("Location: cart.php"); exit();
}


$sql = "SELECT ci.cart_item_id, p.product_name, p.product_price, p.image_url, p.vendor_id_fk, ci.cart_quantity,
        (p.product_price * ci.cart_quantity) AS total 
        FROM carts c
        JOIN cart_items ci ON c.cart_id = ci.cart_id_fk
        JOIN products p ON ci.product_id_fk = p.product_id
        WHERE c.customer_id_fk = $user_id";

$result = mysqli_query($conn, $sql);
$subtotal = 0;

// الحصول على ID المتجر لأول منتج بالسلة للرجوع إليه
$temp_res = mysqli_query($conn, $sql);
$row_for_link = mysqli_fetch_assoc($temp_res);
$continue_shopping_url = ($row_for_link) ? "store.php?id=" . $row_for_link['vendor_id_fk'] : "index.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart | Nashmi store</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-red: #d72229;
            --night: #170505;
            --soft-bg: #f8f9fa;
            --clay: #a76f58;
            --ease: cubic-bezier(0.23, 1, 0.32, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--soft-bg); color: var(--night); padding: 40px 5%; }

        .container { max-width: 1200px; margin: auto; }
        .cart-title { font-size: 2rem; font-weight: 800; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; }

        .cart-grid { display: grid; grid-template-columns: 1.8fr 1fr; gap: 40px; }

        /* Left Side: Items */
        .items-container { background: white; border-radius: 35px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); }
        .cart-item { display: grid; grid-template-columns: 100px 2fr 1fr 1fr auto; align-items: center; gap: 20px; padding: 25px 0; border-bottom: 1px solid #f1f1f1; transition: 0.3s; }
        .cart-item:last-child { border-bottom: none; }
        .cart-item:hover { transform: translateX(10px); }

        .item-img { width: 100px; height: 100px; border-radius: 20px; object-fit: cover; background: #f9f9f9; }
        .item-info h4 { font-size: 1.1rem; color: var(--night); margin-bottom: 5px; }
        .item-info p { color: var(--clay); font-weight: 600; font-size: 0.9rem; }

        .qty-control { display: flex; align-items: center; background: #f1f1f1; border-radius: 12px; padding: 5px 12px; gap: 15px; width: fit-content; }
        .qty-control a { color: var(--night); text-decoration: none; font-weight: 800; transition: 0.2s; }
        .qty-control a:hover { color: var(--primary-red); }

        .item-total { font-weight: 800; color: var(--primary-red); font-size: 1.1rem; }
        .btn-delete { color: #ddd; transition: 0.3s; font-size: 1.2rem; }
        .btn-delete:hover { color: var(--primary-red); transform: scale(1.1); }

        /* Right Side: Summary */
        .summary-card { background: var(--night); color: white; padding: 40px; border-radius: 35px; height: fit-content; position: sticky; top: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); }
        .summary-card h3 { margin-bottom: 25px; font-weight: 800; }
        .row { display: flex; justify-content: space-between; margin-bottom: 15px; opacity: 0.8; }
        .total-row { border-top: 1px solid rgba(255,255,255,0.1); margin-top: 20px; padding-top: 20px; font-size: 1.5rem; font-weight: 800; opacity: 1; }

        .btn-checkout { display: block; background: var(--primary-red); color: white; text-align: center; padding: 20px; border-radius: 18px; text-decoration: none; font-weight: 800; margin-top: 30px; transition: 0.4s var(--ease); }
        .btn-checkout:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(215, 34, 41, 0.3); }

        .btn-continue { display: inline-flex; align-items: center; gap: 10px; color: var(--clay); text-decoration: none; font-weight: 700; margin-top: 30px; transition: 0.3s; }
        .btn-continue:hover { color: var(--night); transform: translateX(-5px); }

        @media (max-width: 900px) { .cart-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <h2 class="cart-title"><i class="fa-solid fa-cart-shopping" style="color:var(--primary-red)"></i> Your Cart</h2>

    <div class="cart-grid">
        <div class="items-wrap">
            <div class="items-container">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): $subtotal += $row['total']; ?>
                        <div class="cart-item">
                            <img src="<?php echo htmlspecialchars($row['image_url']); ?>" class="item-img" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                            
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($row['product_name']); ?></h4>
                                <p>JOD <?php echo number_format($row['product_price'], 2); ?></p>
                            </div>

                            <div class="qty-control">
                                <a href="cart.php?update_id=<?php echo $row['cart_item_id']; ?>&action=minus"><i class="fa-solid fa-minus"></i></a>
                                <span><?php echo $row['cart_quantity']; ?></span>
                                <a href="cart.php?update_id=<?php echo $row['cart_item_id']; ?>&action=plus"><i class="fa-solid fa-plus"></i></a>
                            </div>

                            <div class="item-total">JOD <?php echo number_format($row['total'], 2); ?></div>

                            <a href="cart.php?delete_id=<?php echo $row['cart_item_id']; ?>" class="btn-delete" onclick="return confirm('Remove this item?')">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center; padding: 50px;">
                        <i class="fa-solid fa-basket-shopping" style="font-size:4rem; color:#eee; margin-bottom:20px;"></i>
                        <p style="font-weight:700; color:#ccc;">Your cart is empty.</p>
                    </div>
                <?php endif; ?>
            </div>

            <a href="<?php echo $continue_shopping_url; ?>" class="btn-continue">
                <i class="fa-solid fa-arrow-left-long"></i> Back to the store
            </a>
        </div>

        <aside class="summary-card">
            <h3>Summary</h3>
            <div class="row">
                <span>Subtotal</span>
                <span>JOD <?php echo number_format($subtotal, 2); ?></span>
            </div>
            
            <?php 
                $service_fee = $subtotal * 0.16; 
                $grand_total = $subtotal + $service_fee;
            ?>

            <div class="row">
                <span>Service Fee (16%)</span>
                <span>JOD <?php echo number_format($service_fee, 2); ?></span>
            </div>
            <div class="row total-row">
                <span>Total</span>
                <span>JOD <?php echo number_format($grand_total, 2); ?></span>
            </div>

            <?php if ($subtotal > 0): ?>
                <a href="checkout.php" class="btn-checkout">Checkout Securely</a>
            <?php endif; ?>

            <div style="margin-top:20px; font-size:0.8rem; text-align:center; opacity:0.5;">
                <i class="fa-solid fa-shield-halved"></i> 100% Secure Transaction
            </div>
        </aside>
    </div>
</div>

</body>
</html>