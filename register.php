<?php
$host = "localhost"; 
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8mb4");

$message = "";

// Gemini
function extract_license_number($imagePath) {
    $api_Key = "AIzaSyBYypLxQVsAziO0jG8iQUYEyLHcZDNRMcA"; 
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=" . $api_Key;
    $imageData = base64_encode(file_get_contents($imagePath));

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => "Analyze this business license image. Extract ONLY the registration number or license ID. Return only the alphanumeric characters, nothing else. If not found, return 'NOT_FOUND'."],
                    ["inline_data" => ["mime_type" => "image/jpeg", "data" => $imageData]]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    return trim($result['candidates'][0]['content']['parts'][0]['text'] ?? 'NOT_FOUND');
}

$categories_result = $conn->query("SELECT category_id, category_name FROM categories");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_type = $_POST['user_type'];
    $password  = $_POST['password'];
    $confirm_p = $_POST['confirm_password'];
    $email     = ($user_type == 'customer') ? $_POST['email'] : $_POST['v_email'];
    $phone     = $_POST['phone'] ?? '';

    // pwd validation
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $special   = preg_match('@[^\w]@', $password);
    $is_strong = ($uppercase && $lowercase && $number && $special && strlen($password) >= 8);

    // pwd validation
    $is_phone_valid = preg_match('/^[0-9]+$/', $phone);

    if (!$is_strong) {
        $message = "<div class='alert error'>Security Error: Password must be STRONG (Uppercase, Lowercase, Number, and Special character).</div>";
    } elseif ($password !== $confirm_p) {
        $message = "<div class='alert error'>Passwords do not match!</div>";
    } elseif ($user_type == 'customer' && (!$is_phone_valid || (int)$phone < 0)) {
        $message = "<div class='alert error'>Invalid Phone Number! Positive digits only.</div>";
    } else {
        $table = ($user_type == 'customer') ? "customers" : "vendors";
        $email_col = ($user_type == 'customer') ? "customer_email" : "vendor_email";
        
        $check_email = $conn->prepare("SELECT $email_col FROM $table WHERE $email_col = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        
        if ($check_email->get_result()->num_rows > 0) {
            $message = "<div class='alert error'>Email already registered!</div>";
        } else {
            $extracted_number = "";
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

            if ($user_type == 'vendor') {
                $license_path = $target_dir . "lic_temp_" . time() . "_" . basename($_FILES["license_file"]["name"]);
                move_uploaded_file($_FILES["license_file"]["tmp_name"], $license_path);

                $extracted_number = extract_license_number($license_path);

                if ($extracted_number === 'NOT_FOUND' || strlen($extracted_number) < 3) {
                    $message = "<div class='alert error'>AI failed to read License ID. Please upload a clear image.</div>";
                    unlink($license_path);
                } else {
                    $check_lic = $conn->prepare("SELECT license_number FROM vendors WHERE license_number = ?");
                    $check_lic->bind_param("s", $extracted_number);
                    $check_lic->execute();
                    if ($check_lic->get_result()->num_rows > 0) {
                        $message = "<div class='alert error'>This license is already registered!</div>";
                        unlink($license_path);
                        $extracted_number = "";
                    }
                }
            }

            if (empty($message) && ($user_type == 'customer' || !empty($extracted_number))) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                if ($user_type == 'customer') {
                    $stmt = $conn->prepare("INSERT INTO customers (customer_firstname, customer_lastname, customer_email, customer_password, customer_phone, customer_address) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $_POST['fname'], $_POST['lname'], $email, $hashed_password, $phone, $_POST['address']);
                } else {
                    $logo_name = "logo_" . time() . "_" . basename($_FILES["logo_file"]["name"]);
                    move_uploaded_file($_FILES["logo_file"]["tmp_name"], $target_dir . $logo_name);
                    $stmt = $conn->prepare("INSERT INTO vendors (store_name, license_number, vendor_email, vendor_password, logo_url, category_ID_FK) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssi", $_POST['store_name'], $extracted_number, $email, $hashed_password, $logo_name, $_POST['category_id']);
                }

                if ($stmt->execute()) {
                    $message = "<div class='alert success'>Account created successfully!</div>";
                } else {
                    $message = "<div class='alert error'>Database error: " . $conn->error . "</div>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nashmi Store | Create Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary-red: #d72229; --night: #170505; --clay: #a76f58; --bg-soft: #fcfcfc; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-soft); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .register-card { background: white; width: 100%; max-width: 500px; border-radius: 35px; padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); }
        .brand-logo { text-align: center; font-size: 2rem; font-weight: 800; color: var(--night); margin-bottom: 25px; }
        .brand-logo span { color: var(--primary-red); }
        .alert { padding: 15px; border-radius: 15px; margin-bottom: 20px; text-align: center; font-weight: 600; font-size: 14px; }
        .error { background: #fff1f1; color: #d72229; border: 1px solid #ffcfcf; }
        .success { background: #f0fff4; color: #2f855a; border: 1px solid #c6f6d5; }
        
        .user-type-switch { display: flex; background: #f1f1f1; padding: 5px; border-radius: 20px; margin-bottom: 30px; position: relative; }
        .user-type-switch input { display: none; }
        .user-type-switch label { flex: 1; text-align: center; padding: 12px; cursor: pointer; font-weight: 700; z-index: 2; transition: 0.3s; }
        .switch-bg { position: absolute; width: calc(50% - 5px); height: calc(100% - 10px); background: var(--night); border-radius: 17px; transition: 0.4s; z-index: 1; }
        #vendor:checked ~ .switch-bg { left: calc(50%); }
        #customer:checked ~ label[for="customer"], #vendor:checked ~ label[for="vendor"] { color: white; }

        .form-input { width: 100%; padding: 14px 20px; border-radius: 15px; border: 2px solid #f1f1f1; margin-bottom: 15px; outline: none; transition: 0.3s; font-size: 14px; }
        .form-input:focus { border-color: var(--clay); }
        
        /* زر التسجيل */
        .btn-submit { width: 100%; padding: 16px; background: var(--night); color: white; border: none; border-radius: 15px; font-weight: 800; cursor: pointer; transition: 0.3s; margin-bottom: 15px; }
        .btn-submit:hover:not(:disabled) { background: var(--primary-red); transform: translateY(-2px); }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; filter: grayscale(1); }

        .strength-container { margin: -10px 0 15px 5px; }
        .strength-meter { height: 6px; background: #eee; border-radius: 10px; overflow: hidden; margin-bottom: 5px; }
        .strength-bar { height: 100%; width: 0; transition: width 0.4s ease; }
        .strength-text { font-size: 11px; font-weight: 700; color: var(--clay); }

        .upload-box { background: #fffafa; border: 2px dashed #eee; padding: 15px; border-radius: 15px; margin-bottom: 15px; }
        .upload-box label { font-size: 12px; font-weight: 700; color: var(--clay); display: block; margin-bottom: 5px; }
        .login-link { text-align: center; font-size: 14px; color: var(--clay); }
        .login-link a { color: var(--primary-red); text-decoration: none; font-weight: 800; }
    </style>
</head>
<body>

<div class="register-card">
    <div class="brand-logo">Store<span>نشمي</span></div>
    
    <?php if(!empty($message)) echo $message; ?>

    <form action="" method="POST" enctype="multipart/form-data" id="regForm">
        <div class="user-type-switch">
            <input type="radio" id="customer" name="user_type" value="customer" checked>
            <label for="customer">Customer</label>
            <input type="radio" id="vendor" name="user_type" value="vendor">
            <label for="vendor">Vendor</label>
            <div class="switch-bg"></div>
        </div>

        <!-- حقول العميل -->
        <div id="customer-fields">
            <div style="display: flex; gap: 10px;">
                <input type="text" name="fname" placeholder="First Name" required class="form-input">
                <input type="text" name="lname" placeholder="Last Name" required class="form-input">
            </div>
            <input type="email" name="email" placeholder="Email" required class="form-input">
            <input type="number" name="phone" id="phone" placeholder="Phone (Digits only)" class="form-input" min="0">
            <input type="text" name="address" placeholder="Address" class="form-input">
        </div>

        <!-- حقول التاجر -->
        <div id="vendor-fields" style="display: none;">
            <input type="text" name="store_name" placeholder="Store Name" class="form-input">
            <div class="upload-box" style="border-color: var(--primary-red);">
                <label>Business License (AI Scan) *</label>
                <input type="file" name="license_file" accept="image/*" style="font-size: 11px;">
            </div>
            <div class="upload-box">
                <label>Store Logo *</label>
                <input type="file" name="logo_file" accept="image/*" style="font-size: 11px;">
            </div>
            <input type="email" name="v_email" placeholder="Business Email" class="form-input">
            <select name="category_id" class="form-input">
                <option value="">Select Category</option>
                <?php if($categories_result) { while($cat = $categories_result->fetch_assoc()) { 
                    echo "<option value='".$cat['category_id']."'>".$cat['category_name']."</option>";
                }} ?>
            </select>
        </div>

        <!-- حقول كلمة المرور (مشتركة) -->
        <input type="password" name="password" id="password" placeholder="Password" required class="form-input">
        <div class="strength-container">
            <div class="strength-meter"><div id="strength-bar" class="strength-bar"></div></div>
            <span id="strength-text" class="strength-text">Security: None</span>
        </div>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required class="form-input">

        <button type="submit" id="submitBtn" class="btn-submit" disabled>Create Account</button>
        
        <div class="login-link">
            Already have an account? <a href="test_login.php">Login Here</a>
        </div>
    </form>
</div>

<script>
    // تبديل الحقول بين العميل والتاجر
    const radios = document.querySelectorAll('input[name="user_type"]');
    radios.forEach(r => r.addEventListener('change', () => {
        const isVendor = document.getElementById('vendor').checked;
        document.getElementById('customer-fields').style.display = isVendor ? 'none' : 'block';
        document.getElementById('vendor-fields').style.display = isVendor ? 'block' : 'none';
        document.querySelectorAll('#customer-fields input').forEach(i => i.required = !isVendor);
        document.querySelectorAll('#vendor-fields input, #vendor-fields select').forEach(i => i.required = isVendor);
    }));

    // phone no constraints 
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('keydown', (e) => {
        if (["e", "E", "-", "+", "."].includes(e.key)) e.preventDefault();
    });
    phoneInput.addEventListener('input', function() {
        if (this.value < 0) this.value = Math.abs(this.value);
    });

    // pwd constraints
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    const submitBtn = document.getElementById('submitBtn');

    passwordInput.addEventListener('input', () => {
        const val = passwordInput.value;
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^\w]/.test(val)) score++;

        const states = [
            { color: '#eee', text: 'None', width: '0%' },
            { color: '#d72229', text: 'Weak - Need (A-z, 0-9, !@#)', width: '25%' },
            { color: '#ffa500', text: 'Fair - Keep going', width: '50%' },
            { color: '#a76f58', text: 'Good - Almost there', width: '75%' },
            { color: '#170505', text: 'Strong - Ready to register!', width: '100%' }
        ];

        const result = states[score];
        strengthBar.style.width = result.width;
        strengthBar.style.backgroundColor = result.color;
        strengthText.innerText = 'Security: ' + result.text;
        strengthText.style.color = result.color === '#eee' ? '#a76f58' : result.color;

        // Only active when the pwd is strong
        if (score === 4) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    });
</script>
</body>
</html>