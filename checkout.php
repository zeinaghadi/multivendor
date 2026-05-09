<?php
session_start();
// 1. الاتصال بقاعدة البيانات
$conn = mysqli_connect("localhost", "root", "Zz0795426555$", "multivendor_marketplace");

if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8");

// تحديد الـ User ID
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// 2. جلب بيانات السلة
$sql_cart = "SELECT ci.*, p.product_name, p.product_price 
             FROM cart_items ci 
             JOIN products p ON ci.product_id_fk = p.product_id 
             JOIN carts c ON ci.cart_id_fk = c.cart_id 
             WHERE c.customer_id_fk = $user_id";

$result_cart = mysqli_query($conn, $sql_cart);

if (mysqli_num_rows($result_cart) == 0) {
    header("Location: cart.php");
    exit();
}

$subtotal = 0;
$shipping = 2.00; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout | Nashmi store</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary-red: #d72229;
            --deep-maroon: #770e13;
            --earth-tan: #a76f58;
            --chocolate: #5d382f;
            --rich-black: #170505;
            --soft-bg: #fdfbfb;
            --card-shadow: 0 25px 50px rgba(23, 5, 5, 0.08);
            --ease: cubic-bezier(0.23, 1, 0.32, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body { 
            background: linear-gradient(135deg, #fdfbfb 0%, #f5f0ee 100%); 
            color: var(--rich-black); 
            min-height: 100vh;
            padding: 60px 5%; 
        }

        .checkout-container { 
            max-width: 1200px; 
            margin: 0 auto; 
            display: grid; 
            grid-template-columns: 1.6fr 1fr; 
            gap: 40px; 
            align-items: flex-start;
            animation: fadeIn 0.8s var(--ease);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--earth-tan);
            font-weight: 700;
            margin-bottom: 30px;
            transition: 0.4s var(--ease);
        }
        .back-link:hover { color: var(--primary-red); transform: translateX(-8px); }

        .checkout-card { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(15px);
            padding: 40px; 
            border-radius: 32px; 
            box-shadow: var(--card-shadow); 
            border: 1px solid rgba(167, 111, 88, 0.15);
            margin-bottom: 30px;
        }

        h2 { 
            font-size: 1.6rem; 
            margin-bottom: 35px; 
            color: var(--chocolate); 
            font-weight: 800; 
            display: flex; 
            align-items: center; 
            gap: 15px;
            letter-spacing: -0.5px;
        }
        h2 i { color: var(--primary-red); }

        .form-group { margin-bottom: 25px; }
        label { 
            display: block; 
            margin-bottom: 12px; 
            font-weight: 700; 
            font-size: 0.95rem; 
            color: var(--chocolate); 
            padding-left: 4px; 
        }
        
        input, select, textarea { 
            width: 100%; 
            padding: 16px 20px; 
            border: 2px solid rgba(167, 111, 88, 0.2); 
            border-radius: 18px; 
            font-size: 1rem;
            transition: all 0.3s var(--ease);
            background: rgba(255, 255, 255, 0.7);
            color: var(--rich-black);
        }
        input:focus, select:focus, textarea:focus { 
            outline: none; 
            border-color: var(--primary-red); 
            box-shadow: 0 0 0 5px rgba(215, 34, 41, 0.08);
            background: #fff;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        
        .payment-methods { display: flex; flex-direction: column; gap: 18px; }
        .payment-option { 
            display: flex; 
            align-items: center; 
            padding: 24px; 
            border: 2px solid rgba(167, 111, 88, 0.15); 
            border-radius: 22px; 
            cursor: pointer; 
            transition: all 0.4s var(--ease); 
            position: relative;
            background: rgba(255, 255, 255, 0.5);
        }
        .payment-option:hover { border-color: var(--earth-tan); background: #fff; }
        .payment-option input { width: 20px; height: 20px; margin-right: 20px; accent-color: var(--primary-red); }
        .payment-option i { margin-right: 15px; font-size: 1.5rem; color: var(--chocolate); transition: 0.3s; }
        .payment-option span { font-weight: 700; font-size: 1.1rem; color: var(--chocolate); }
        
        .payment-option.active { 
            border-color: var(--primary-red); 
            background: #fff9f8; 
            box-shadow: 0 10px 25px rgba(215, 34, 41, 0.05); 
        }
        .payment-option.active i { color: var(--primary-red); }

        #card-info-box { 
            background: rgba(167, 111, 88, 0.05); 
            padding: 30px; 
            border-radius: 24px; 
            margin-top: 15px; 
            border: 1px dashed var(--earth-tan);
            animation: slideDown 0.5s var(--ease);
        }

        .summary-card { position: sticky; top: 40px; }
        .order-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-size: 1rem; }
        .order-item span:first-child { color: var(--chocolate); font-weight: 600; opacity: 0.8; }
        .order-item b { color: var(--rich-black); font-weight: 800; }

        .summary-totals { border-top: 2px dashed rgba(167, 111, 88, 0.2); padding-top: 30px; margin-top: 30px; }
        .total-line { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 1.05rem; }
        .grand-total { 
            font-weight: 900; 
            color: var(--primary-red); 
            font-size: 1.8rem; 
            margin-top: 25px;
            padding-top: 25px;
            border-top: 3px solid var(--chocolate);
            letter-spacing: -1px;
        }

        .place-order-btn { 
            width: 100%; 
            background: var(--primary-red); 
            color: white; 
            padding: 22px; 
            border: none; 
            border-radius: 20px; 
            font-size: 1.2rem; 
            font-weight: 800; 
            cursor: pointer; 
            transition: all 0.4s var(--ease); 
            margin-top: 35px;
            box-shadow: 0 15px 35px rgba(215, 34, 41, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .place-order-btn:hover { 
            background: var(--deep-maroon); 
            transform: translateY(-5px) scale(1.02); 
            box-shadow: 0 20px 40px rgba(119, 14, 19, 0.4); 
        }

        @keyframes slideDown { 
            from { opacity: 0; max-height: 0; transform: translateY(-10px); } 
            to { opacity: 1; max-height: 500px; transform: translateY(0); } 
        }

        @media (max-width: 968px) { 
            .checkout-container { grid-template-columns: 1fr; } 
            .summary-card { position: static; } 
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

    <div class="checkout-container">
        <div>
            <a href="cart.php" class="back-link">
                <i class="fa-solid fa-arrow-left-long"></i> Return to Cart
            </a>
            
            <form action="place_order.php" method="POST" id="checkout-form">
                
                <div class="checkout-card">
                    <h2><i class="fa-solid fa-truck-fast"></i> Shipping Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" placeholder="Enter first name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" placeholder="Enter last name" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Mobile Number (Active for WhatsApp)</label>
                            <input type="tel" name="phone" placeholder="07XXXXXXXX" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Delivery City</label>
                            <select name="city" required>
                                <option value="Aqaba">Aqaba (العقبة)</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Detailed Address</label>
                            <textarea name="address" rows="3" placeholder="Street name, Building number, Apartment..." required></textarea>
                        </div>
                    </div>
                </div>

                <div class="checkout-card">
                    <h2><i class="fa-solid fa-wallet"></i> Payment Choice</h2>
                    <div class="payment-methods">
                        <label class="payment-option active" id="opt-cash">
                            <input type="radio" name="payment_method" value="cash" checked onclick="togglePaymentUI('cash')">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                            <span>Cash on Delivery (JOD)</span>
                        </label>

                        <label class="payment-option" id="opt-card">
                            <input type="radio" name="payment_method" value="visa" onclick="togglePaymentUI('visa')">
                            <i class="fa-solid fa-credit-card"></i>
                            <span>Credit / Debit Card</span>
                        </label>

                        <div id="card-info-box" style="display:none;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; color: #10b981; font-size: 0.9rem; font-weight: 700;">
                                <i class="fa-solid fa-shield-check"></i> Encrypted Secure Gateway
                            </div>
                            <div class="form-group">
                                <label>Card Number</label>
                                <input type="text" id="card_no" maxlength="19" placeholder="0000 0000 0000 0000">
                            </div>
                            <div class="form-grid">
                                <div class="form-group"><label>Expiry</label><input type="text" id="exp" placeholder="MM/YY" maxlength="5"></div>
                                <div class="form-group"><label>CVV</label><input type="password" id="cvv" placeholder="***" maxlength="3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <aside class="summary-card">
            <div class="checkout-card">
                <h2>Order Summary</h2>
                <div class="order-summary-list">
                    <?php 
                    mysqli_data_seek($result_cart, 0); 
                    while($item = mysqli_fetch_assoc($result_cart)): 
                        $item_total = $item['product_price'] * $item['cart_quantity'];
                        $subtotal += $item_total;
                    ?>
                        <div class="order-item">
                            <span><?php echo htmlspecialchars($item['product_name']); ?> <small>(x<?php echo $item['cart_quantity']; ?>)</small></span>
                            <b><?php echo number_format($item_total, 2); ?></b>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="summary-totals">
                    <div class="total-line">
                        <span>Items Subtotal</span>
                        <span><?php echo number_format($subtotal, 2); ?> JOD</span>
                    </div>
                    <div class="total-line">
                        <span>Service Fee</span>
                        <span><?php echo number_format($shipping, 2); ?> JOD</span>
                    </div>
                    <div class="total-line grand-total">
                        <span>Grand Total</span>
                        <span><?php echo number_format($subtotal + $shipping, 2); ?> JOD</span>
                    </div>
                </div>

                <button type="submit" form="checkout-form" class="place-order-btn">
                    Confirm Order <i class="fa-solid fa-circle-check"></i>
                </button>
            </div>
        </aside>
    </div>

    <script>
        function togglePaymentUI(method) {
            const cardBox = document.getElementById('card-info-box');
            document.getElementById('opt-cash').classList.toggle('active', method === 'cash');
            document.getElementById('opt-card').classList.toggle('active', method === 'visa');
            cardBox.style.display = (method === 'visa') ? 'block' : 'none';
            ['card_no', 'exp', 'cvv'].forEach(id => document.getElementById(id).required = (method === 'visa'));
        }

        function validateLuhn(cardNumber) {
            let nCheck = 0, bEven = false;
            cardNumber = cardNumber.replace(/\s/g, '');
            for (var n = cardNumber.length - 1; n >= 0; n--) {
                var nDigit = parseInt(cardNumber.charAt(n), 10);
                if (bEven && (nDigit *= 2) > 9) nDigit -= 9;
                nCheck += nDigit; bEven = !bEven;
            }
            return (nCheck % 10) == 0;
        }

        document.getElementById('checkout-form').addEventListener('submit', function (e) {
            if (document.querySelector('input[name="payment_method"]:checked').value === 'visa') {
                const cardNo = document.getElementById('card_no').value;
                const expValue = document.getElementById('exp').value;
                
                if (!validateLuhn(cardNo) || cardNo.replace(/\s/g, '').length < 13) {
                    e.preventDefault(); alert("رقم البطاقة غير صحيح!"); return;
                }

                const expMatch = expValue.match(/^(\d{2})\/(\d{2})$/);
                if (!expMatch) {
                    e.preventDefault(); alert("تنسيق التاريخ غير صحيح (MM/YY)"); return;
                }

                const expMonth = parseInt(expMatch[1], 10);
                const expYear = parseInt("20" + expMatch[2], 10);
                const now = new Date();
                if (expMonth < 1 || expMonth > 12 || expYear < now.getFullYear() || (expYear === now.getFullYear() && expMonth < (now.getMonth() + 1))) {
                    e.preventDefault(); alert("البطاقة منتهية الصلاحية أو البيانات غير صحيحة!");
                }
            }
        });

        document.getElementById('card_no').addEventListener('input', e => {
            e.target.value = e.target.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim();
        });

        document.getElementById('exp').addEventListener('input', e => {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length >= 2) e.target.value = v.substring(0, 2) + '/' + v.substring(2, 4);
            else e.target.value = v;
        });

        document.getElementById('exp').addEventListener('keydown', e => {
            if (e.key === 'Backspace' && e.target.value.length === 3) e.target.value = e.target.value.substring(0, 2);
        });
    </script>
</body>
</html>