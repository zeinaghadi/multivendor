<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. الاتصال
$conn = mysqli_connect("localhost", "root", "Zz0795426555$", "multivendor_marketplace");
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8mb4");

// 2. استقبال البيانات (دعم POST لصفحة التفاصيل و GET للروابط السريعة)
$product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$quantity_requested = isset($_REQUEST['quantity']) ? intval($_REQUEST['quantity']) : 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 

if ($product_id <= 0) {
    header("Location: index.php");
    exit();
}

// 3. جلب بيانات المنتج والمخزون
$prod_res = mysqli_query($conn, "SELECT vendor_id_fk, product_quantity FROM products WHERE product_id = $product_id");
$prod_data = mysqli_fetch_assoc($prod_res);

if (!$prod_data) { die("المنتج غير موجود."); }

$new_vendor_id = $prod_data['vendor_id_fk'];
$stock_available = $prod_data['product_quantity'];

// 4. التأكد من وجود سلة (carts) للمستخدم
$cart_res = mysqli_query($conn, "SELECT cart_id FROM carts WHERE customer_id_fk = $user_id");
if (mysqli_num_rows($cart_res) > 0) {
    $cart_row = mysqli_fetch_assoc($cart_res);
    $cart_id = $cart_row['cart_id'];
} else {
    $date_now = date("Y-m-d H:i:s");
    mysqli_query($conn, "INSERT INTO carts (customer_id_fk, cart_date) VALUES ($user_id, '$date_now')");
    $cart_id = mysqli_insert_id($conn);
}

// 5. فحص المحل الواحد (Vendor Validation)
$check_vendor = mysqli_query($conn, "SELECT p.vendor_id_fk FROM cart_items ci 
                                     JOIN products p ON ci.product_id_fk = p.product_id 
                                     WHERE ci.cart_id_fk = $cart_id LIMIT 1");

if (mysqli_num_rows($check_vendor) > 0) {
    $existing_vendor = mysqli_fetch_assoc($check_vendor);
    if ($existing_vendor['vendor_id_fk'] != $new_vendor_id) {
        echo "<script>alert('عذراً، يجب الشراء من تاجر واحد في كل طلب. يرجى إفراغ السلة أولاً.'); window.location.href='cart.php';</script>";
        exit();
    }
}

// 6. إضافة المنتج أو تحديث الكمية
$item_res = mysqli_query($conn, "SELECT cart_item_id, cart_quantity FROM cart_items WHERE cart_id_fk = $cart_id AND product_id_fk = $product_id");

if (mysqli_num_rows($item_res) > 0) {
    $current_item = mysqli_fetch_assoc($item_res);
    $total_in_cart = $current_item['cart_quantity'] + $quantity_requested;
    $final_qty = min($total_in_cart, $stock_available);
    mysqli_query($conn, "UPDATE cart_items SET cart_quantity = $final_qty WHERE cart_id_fk = $cart_id AND product_id_fk = $product_id");
} else {
    $final_qty = min($quantity_requested, $stock_available);
    mysqli_query($conn, "INSERT INTO cart_items (cart_id_fk, product_id_fk, cart_quantity) VALUES ($cart_id, $product_id, $final_qty)");
}

header("Location: cart.php");
exit();
?>