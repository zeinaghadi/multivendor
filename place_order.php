<?php
session_start();

$conn = mysqli_connect("localhost", "root", "Zz0795426555$", "multivendor_marketplace");
if (!$conn) { 
    die("Error: " . mysqli_connect_error()); 
}
mysqli_set_charset($conn, "utf8mb4");


if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: test_login.php");
    exit();
}

$user_id =$_SESSION['user_id']; 

$first_name = mysqli_real_escape_string($conn, $_POST['first_name'] ?? '');
$last_name  = mysqli_real_escape_string($conn, $_POST['last_name'] ?? '');
$phone      = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
$address    = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
$city       = mysqli_real_escape_string($conn, $_POST['city'] ?? '');

$full_shipping_info = "Recipient: $first_name $last_name | Phone: $phone | Address: $city, $address";

//  payment method
$raw_payment = isset($_POST['payment_method']) ? strtolower(trim($_POST['payment_method'])) : 'cash';
$payment = (strpos($raw_payment, 'visa') !== false) ? 'visa' : 'cash';

//  cart items
$cart_query = "SELECT ci.*, p.product_price, p.vendor_id_fk 
               FROM cart_items ci 
               JOIN products p ON ci.product_id_fk = p.product_id 
               JOIN carts c ON ci.cart_id_fk = c.cart_id 
               WHERE c.customer_id_fk = $user_id";

$cart_res = mysqli_query($conn, $cart_query);

if (!$cart_res || mysqli_num_rows($cart_res) == 0) {
    die("<script>alert('Cart is empty!'); window.location.href='cart.php';</script>");
}

$order_sub_total = 0;
$vendor_id = 0;

// calculations
$temp_items = [];
while($item = mysqli_fetch_assoc($cart_res)) {
    $order_sub_total += ($item['product_price'] * $item['cart_quantity']);
    $vendor_id = $item['vendor_id_fk'];
    $temp_items[] = $item;
}

$service_fee = $order_sub_total * 0.16;
$order_grand_total = $order_sub_total + $service_fee;

// order
$sql_order = "INSERT INTO orders (customer_id_fk, vendor_id_fk, sub_total, grand_total, order_status, shipping_address, order_date) 
              VALUES ($user_id, $vendor_id, $order_sub_total, $order_grand_total, 'Pending', '$full_shipping_info', NOW())";

if (mysqli_query($conn, $sql_order)) {
    $order_id = mysqli_insert_id($conn);

    // Receipt
    mysqli_query($conn, "INSERT INTO reciept (order_id_fk, payement_method, reciept_date) VALUES ($order_id, '$payment', NOW())");

    // Update stock
    foreach ($temp_items as $item) {
        $p_id  = $item['product_id_fk'];
        $qty   = $item['cart_quantity'];
        $price = $item['product_price'];

        // order_items
        mysqli_query($conn, "INSERT INTO order_items (order_id_fk, product_id_fk, order_item_quantity, unit_price_at_purchase) 
                             VALUES ($order_id, $p_id, $qty, $price)");

        //  products
        $update_stock_sql = "UPDATE products SET product_quantity = product_quantity - $qty WHERE product_id = $p_id";
        
        if (!mysqli_query($conn, $update_stock_sql)) {
            // to detect errors
            die("Error in updating the database for product NO $p_id: " . mysqli_error($conn));
        }
    }

    //  make cart empty
    mysqli_query($conn, "DELETE ci FROM cart_items ci JOIN carts c ON ci.cart_id_fk = c.cart_id WHERE c.customer_id_fk = $user_id");

    // DONE
    echo "<script>
            alert('Your order has been registered successfully');
            window.location.href='confirm.php?id=$order_id';
          </script>";

} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>