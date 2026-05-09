<?php
session_start();
$conn = new mysqli("localhost", "root", "Zz0795426555$", "multivendor_marketplace");
mysqli_set_charset($conn, "utf8mb4");

// التأكد من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('يرجى تسجيل الدخول أولاً'); window.location.href='test_login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
// لقط الـ ID سواء بعتيه GET أو POST من صفحة التفاصيل
$product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;

if ($product_id > 0) {
    // 1. فحص هل المستخدم عنده سجل في جدول wishlist
    $res = $conn->query("SELECT wishlist_id FROM wishlist WHERE customer_id_fk = $user_id LIMIT 1");
    
    if ($res->num_rows > 0) {
        $wish_row = $res->fetch_assoc();
        $w_id = $wish_row['wishlist_id'];
    } else {
        $conn->query("INSERT INTO wishlist (customer_id_fk) VALUES ($user_id)");
        $w_id = $conn->insert_id;
    }

    // 2. فحص التكرار
    $check = $conn->query("SELECT * FROM wishlist_items WHERE wishlist_id_fk = $w_id AND product_id_fk = $product_id");
    
    if ($check->num_rows == 0) {
        $date_now = date("Y-m-d H:i:s");
        $insert = $conn->query("INSERT INTO wishlist_items (wishlist_id_fk, product_id_fk, wishlist_date) 
                                VALUES ($w_id, $product_id, '$date_now')");
        
        if ($insert) {
            echo "<script>alert('❤️ تمت الإضافة للمفضلة بنجاح!'); window.location.href='wishlist.php';</script>";
        }
    } else {
        echo "<script>alert('المنتج موجود بالفعل في مفضلتك'); window.location.href='wishlist.php';</script>";
    }
} else {
    header("Location: index.php");
}
?>