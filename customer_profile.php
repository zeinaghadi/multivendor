<?php
session_start();

$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8");

if (!isset($_SESSION['user_id'])) {
    header("Location: test_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM customers WHERE customer_id = $user_id";
$result = $conn->query($sql);
$customer = $result->fetch_assoc();

// update
if (isset($_POST['update_profile'])) {
    $fname = mysqli_real_escape_string($conn, $_POST['customer_firstname']);
    $lname = mysqli_real_escape_string($conn, $_POST['customer_lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['customer_email']);
    $phone = mysqli_real_escape_string($conn, $_POST['customer_phone']);
    $address = mysqli_real_escape_string($conn, $_POST['customer_address']);
    
    $old_password = $_POST['old_password'];
    $new_password = $_POST['customer_password'];
    $confirm_password = $_POST['confirm_password'];

    $password_update = "";
    $error = "";

    // change pass
    if (!empty($new_password)) {
        // confirm old
        if ($old_password !== $customer['customer_password']) {
            $error = "كلمة السر القديمة غير صحيحة!";
        } 
        // فحص التطابق
        elseif ($new_password !== $confirm_password) {
            $error = "كلمات المرور الجديدة غير متطابقة!";
        }
        //update
        else {
            $password_update = ", customer_password = '$new_password'";
        }
    }

    if (empty($error)) {
        $update_query = "UPDATE customers SET 
                        customer_firstname = '$fname', 
                        customer_lastname = '$lname',
                        customer_email = '$email', 
                        customer_phone = '$phone', 
                        customer_address = '$address' 
                        $password_update 
                        WHERE customer_id = $user_id";

        if ($conn->query($update_query)) {
            $_SESSION['fname'] = $fname;
            echo "<script>alert('تم تحديث بياناتك بنجاح!'); window.location.href='index.php';</script>";
        }
    } else {
        echo "<script>alert('$error');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Nashmi store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #d72229;
            --deep-maroon: #770e13;
            --earth-tan: #a76f58;
            --chocolate: #5d382f;
            --rich-black: #170505;
            --soft-bg: #fdfbfb;
            --border-soft: rgba(167, 111, 88, 0.15);
            --ease: cubic-bezier(0.23, 1, 0.32, 1);
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: linear-gradient(135deg, #fdfbfb 0%, #f5f0ee 100%); 
            margin: 0; padding: 20px;
            display: flex; align-items: center; justify-content: center; min-height: 100vh;
            color: var(--rich-black);
        }

        .profile-container { 
            max-width: 600px; width: 100%; 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(15px);
            padding: 45px; 
            border-radius: 35px; 
            border: 1px solid var(--border-soft);
            box-shadow: 0 30px 60px rgba(23, 5, 5, 0.08);
            animation: slideUp 0.8s var(--ease);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .profile-header { text-align: center; margin-bottom: 35px; }
        .profile-header i.main-icon { 
            font-size: 4.5rem; 
            color: var(--deep-maroon); 
            margin-bottom: 15px; 
            filter: drop-shadow(0 10px 15px rgba(119, 14, 19, 0.2));
        }
        .profile-header h2 { font-weight: 800; letter-spacing: -1px; color: var(--rich-black); }

        .form-row { display: flex; gap: 20px; margin-bottom: 10px; }
        .form-group { flex: 1; margin-bottom: 22px; position: relative; }
        
        label { 
            display: block; font-size: 0.75rem; color: var(--earth-tan); 
            font-weight: 800; margin-bottom: 8px; 
            text-transform: uppercase; letter-spacing: 1px; 
        }
        
        input { 
            width: 100%; padding: 14px 18px; 
            border: 2px solid rgba(167, 111, 88, 0.1); 
            border-radius: 16px; outline: none; font-family: inherit; 
            box-sizing: border-box; transition: 0.4s var(--ease);
            background: white; font-size: 0.95rem; color: var(--rich-black);
        }
        
        input:focus { 
            border-color: var(--primary-red); 
            box-shadow: 0 10px 25px rgba(215, 34, 41, 0.08); 
            transform: translateY(-2px);
        }

        .password-wrapper { position: relative; }
        .password-wrapper i { 
            position: absolute; right: 18px; top: 50%; transform: translateY(-50%); 
            cursor: pointer; color: var(--earth-tan); transition: 0.3s;
        }
        .password-wrapper i:hover { color: var(--primary-red); }

        /* Strength Meter */
        .strength-meter { height: 5px; background: rgba(167, 111, 88, 0.1); margin-top: 12px; border-radius: 10px; overflow: hidden; display: none; }
        .strength-bar { height: 100%; width: 0; transition: 0.6s var(--ease); }
        #strength-text { font-size: 0.75rem; font-weight: 700; margin-top: 8px; display: block; }

        .btn-update { 
            background: var(--primary-red); color: white; border: none; padding: 18px; 
            border-radius: 18px; width: 100%; font-weight: 800; cursor: pointer; 
            transition: 0.4s var(--ease); font-size: 1.05rem; margin-top: 25px;
            box-shadow: 0 12px 25px rgba(215, 34, 41, 0.25);
        }

        .btn-update:hover:not(:disabled) { 
            background: var(--deep-maroon); 
            transform: translateY(-4px); 
            box-shadow: 0 15px 35px rgba(119, 14, 19, 0.35); 
        }
        
        .btn-update:disabled { background: #d1d5db; cursor: not-allowed; box-shadow: none; }

        .back-home { 
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 30px; color: var(--earth-tan); text-decoration: none; 
            font-size: 0.9rem; font-weight: 700; transition: 0.3s; 
        }
        .back-home:hover { color: var(--primary-red); transform: translateX(-5px); }
        
        h3 { 
            font-size: 1.1rem; color: var(--chocolate); margin: 35px 0 20px; 
            font-weight: 800; border-left: 4px solid var(--primary-red); padding-left: 12px; 
        }

        @media (max-width: 500px) {
            .form-row { flex-direction: column; gap: 0; }
            .profile-container { padding: 30px 20px; }
        }
    </style>
</head>
<body>

    <div class="profile-container">
        <div class="profile-header">
            <i class="fa-solid fa-circle-user main-icon"></i>
            <h2>Personal Settings</h2>
        </div>

        <form method="POST" id="profileForm">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="customer_firstname" value="<?php echo htmlspecialchars($customer['customer_firstname']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="customer_lastname" value="<?php echo htmlspecialchars($customer['customer_lastname']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="customer_email" value="<?php echo htmlspecialchars($customer['customer_email']); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="customer_phone" value="<?php echo htmlspecialchars($customer['customer_phone'] ?? ''); ?>" placeholder="07XXXXXXXX">
                </div>
                <div class="form-group">
                    <label>Default Address</label>
                    <input type="text" name="customer_address" value="<?php echo htmlspecialchars($customer['customer_address'] ?? ''); ?>" placeholder="City, Street">
                </div>
            </div>

            <h3>Security & Authentication</h3>
            
            <div class="form-group">
                <label>Current Password</label>
                <div class="password-wrapper">
                    <input type="password" name="old_password" id="old_pass" placeholder="Verify identity to save changes">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="customer_password" id="new_pass" placeholder="Leave blank to keep current">
                        <i class="fa-solid fa-eye toggle-eye"></i>
                    </div>
                    <div class="strength-meter" id="meter"><div class="strength-bar" id="bar"></div></div>
                    <small id="strength-text"></small>
                </div>
                <div class="form-group">
                    <label>Confirm New</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_pass" placeholder="Re-type new password">
                        <i class="fa-solid fa-eye toggle-eye"></i>
                    </div>
                </div>
            </div>

            <button type="submit" name="update_profile" class="btn-update" id="submitBtn">Save Preferences</button>
        </form>

        <a href="index.php" class="back-home"><i class="fa-solid fa-arrow-left"></i> Back to Storefront</a>
    </div>

    <script>
        // 1. Toggle Password Visibility with Smooth Icon Switch
        document.querySelectorAll('.toggle-eye').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });

        // 2. Enhanced Password Strength Logic
        const newPass = document.getElementById('new_pass');
        const meter = document.getElementById('meter');
        const bar = document.getElementById('bar');
        const text = document.getElementById('strength-text');
        const submitBtn = document.getElementById('submitBtn');

        newPass.addEventListener('input', function() {
            const val = this.value;
            if(val === "") {
                meter.style.display = "none";
                text.textContent = "";
                submitBtn.disabled = false;
                return;
            }

            meter.style.display = "block";
            let strength = 0;
            
            if (val.length >= 8) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            const colors = ["#ff4d4d", "#ffa64d", "#2eb8b8", "#2ecc71"];
            const labels = ["Very Weak", "Weak", "Good", "Strong Password!"];
            
            let index = Math.max(0, strength - 1);
            bar.style.width = ((strength / 4) * 100) + "%";
            bar.style.backgroundColor = colors[index];
            text.textContent = labels[index];
            text.style.color = colors[index];
            
            // Disable if it's too weak but they started typing
            submitBtn.disabled = (strength < 2);
        });
    </script>
</body>
</html>