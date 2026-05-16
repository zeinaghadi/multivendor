<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: test_login.php"); 
    exit();
}

$v_id = $_SESSION['user_id'];

$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8mb4");

$message = "";
$message_type = "";

$res = mysqli_query($conn, "SELECT * FROM vendors WHERE vendor_id = '$v_id'");
$vendor = mysqli_fetch_assoc($res);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    
    $store_name = mysqli_real_escape_string($conn, $_POST['store_name']);
    $email      = mysqli_real_escape_string($conn, $_POST['vendor_email']);
    
    $old_password = $_POST['old_password'];
    $new_password = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];
    $pass_query = "";
    $can_update = true;

    if (!empty($new_password)) {
        // فحص قوة كلمة المرور في PHP (للأمان الإضافي)
        $is_strong = strlen($new_password) >= 6 && 
                     preg_match('/[A-Z]/', $new_password) && 
                     preg_match('/[a-z]/', $new_password) && 
                     preg_match('/[0-9]/', $new_password);

        if (!$is_strong) {
            $message = "Security Error: Password must be STRONG (include Uppercase, Lowercase, and Numbers).";
            $message_type = "error";
            $can_update = false;
        } 
        elseif (password_verify($old_password, $vendor['vendor_password'])) {
            if ($new_password === $confirm_pass) {
                $hashed_pass = password_hash($new_password, PASSWORD_DEFAULT);
                $pass_query = ", vendor_password = '$hashed_pass'";
            } else {
                $message = "New passwords do not match!";
                $message_type = "error";
                $can_update = false;
            }
        } else {
            $message = "Current password is incorrect!";
            $message_type = "error";
            $can_update = false;
        }
    }

    if ($can_update) {
        $update_sql = "UPDATE vendors SET store_name = '$store_name', vendor_email = '$email' $pass_query WHERE vendor_id = '$v_id'";
        if (mysqli_query($conn, $update_sql)) {
            if (!empty($_FILES['logo']['name'])) {
                $logo_name = time() . "_" . $_FILES['logo']['name'];
                if (move_uploaded_file($_FILES['logo']['tmp_name'], "uploads/" . $logo_name)) {
                    mysqli_query($conn, "UPDATE vendors SET logo_url = '$logo_name' WHERE vendor_id = '$v_id'");
                }
            }
            $message = "Profile updated successfully!";
            $message_type = "success";
            $res = mysqli_query($conn, "SELECT * FROM vendors WHERE vendor_id = '$v_id'");
            $vendor = mysqli_fetch_assoc($res);
        } else {
            $message = "Update failed!";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nashmi store | Store Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #d72229; --clay: #a76f58; --night: #170505;
            --bg-soft: #f8f9fa; --white: #ffffff; --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-soft); color: var(--night); display: flex; min-height: 100vh; }

        .sidebar { width: 280px; background: var(--night); color: white; padding: 40px 20px; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar-logo { font-size: 1.8rem; font-weight: 800; margin-bottom: 50px; text-align: center; }
        .sidebar-logo span { color: var(--primary-red); }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { color: rgba(255,255,255,0.6); text-decoration: none; padding: 16px 22px; display: flex; align-items: center; gap: 15px; border-radius: 20px; transition: var(--transition); font-weight: 600; }
        .sidebar ul li a.active { background: var(--primary-red); color: white; }

        .dashboard-content { margin-left: 280px; width: calc(100% - 280px); padding: 50px; }
        .profile-card { background: var(--white); border-radius: 40px; padding: 50px; box-shadow: 0 20px 50px rgba(23, 5, 5, 0.03); max-width: 850px; margin: 0 auto; border: 1px solid rgba(167, 111, 88, 0.08); }

        .alert { padding: 15px 25px; border-radius: 15px; margin-bottom: 25px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #e6fffa; color: #2c7a7b; border: 1px solid #b2f5ea; }
        .alert-error { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }

        .logo-upload-section { display: flex; flex-direction: column; align-items: center; margin-bottom: 40px; }
        .logo-preview-wrapper { position: relative; width: 140px; height: 140px; margin-bottom: 20px; }
        .logo-preview-wrapper img { width: 100%; height: 100%; border-radius: 40px; object-fit: cover; border: 4px solid var(--white); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .file-input-label { background: var(--night); color: white; padding: 10px 20px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: var(--transition); }

        .section-title { font-size: 1.1rem; font-weight: 800; color: var(--clay); margin: 30px 0 20px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; }
        .section-title::after { content: ''; flex: 1; height: 1px; background: #eee; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .full-width { grid-column: span 2; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 0.85rem; font-weight: 700; }
        .form-group input { padding: 15px 20px; border-radius: 15px; border: 2px solid #f1f5f9; outline: none; transition: var(--transition); }
        .form-group input:focus { border-color: var(--primary-red); }

        .strength-meter { height: 6px; background: #eee; border-radius: 3px; margin-top: 8px; overflow: hidden; }
        .strength-bar { height: 100%; width: 0%; transition: width 0.5s ease, background 0.5s ease; }
        .strength-text { font-size: 0.75rem; font-weight: 700; margin-top: 5px; display: block; }

        .btn-save { grid-column: span 2; margin-top: 30px; background: var(--night); color: white; border: none; padding: 18px; border-radius: 18px; font-weight: 800; cursor: pointer; transition: var(--transition); }
        .btn-save:hover:not(:disabled) { background: var(--primary-red); transform: translateY(-3px); }
        .btn-save:disabled { opacity: 0.5; cursor: not-allowed; filter: grayscale(1); }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="sidebar-logo">Store<span>نشمي</span></div>
    <ul>
        <li><a href="vendor_dashboard.php"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
        <li><a href="delete_update_product.php"><i class="fa-solid fa-tags"></i> <span>Products</span></a></li>
        <li><a href="orders.php"><i class="fa-solid fa-box"></i> <span>Orders</span></a></li>
        <li><a href="vendor_profile.php" class="active"><i class="fa-solid fa-user-gear"></i> <span>Profile</span></a></li>
        <li style="margin-top: 40px;"><a href="log_out.php" style="color: #ffbaba;"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
    </ul>
</nav>

<main class="dashboard-content">
    <header class="dash-header">
        <h2 style="font-size: 2.2rem; font-weight: 800; margin-bottom:30px;"><i class="fa-solid fa-sliders" style="color:var(--primary-red)"></i> Store <span>Settings</span></h2>
    </header>

    <div class="profile-card">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fa-solid <?php echo $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" id="profileForm">
            <div class="logo-upload-section">
                <div class="logo-preview-wrapper">
                    <img id="preview" src="uploads/<?php echo !empty($vendor['logo_url']) ? $vendor['logo_url'] : 'default_logo.png'; ?>" alt="Store Logo">
                </div>
                <label for="logo-input" class="file-input-label"><i class="fa-solid fa-camera"></i> UPDATE LOGO</label>
                <input type="file" id="logo-input" name="logo" hidden onchange="previewImage(this)">
            </div>

            <h3 class="section-title">Store Identity</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Store Name</label>
                    <input type="text" name="store_name" value="<?php echo htmlspecialchars($vendor['store_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Professional Email</label>
                    <input type="email" name="vendor_email" value="<?php echo htmlspecialchars($vendor['vendor_email']); ?>" required>
                </div>
            </div>

            <h3 class="section-title">Security Check</h3>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Current Password (To authorize changes)</label>
                    <input type="password" name="old_password" placeholder="Verify your current password">
                </div>
                <div class="form-group">
                    <label>New Password (Must be Strong)</label>
                    <input type="password" id="new-password" name="password" placeholder="A-z, 0-9, symbols" oninput="checkStrength(this.value)">
                    <div class="strength-meter"><div id="strength-bar" class="strength-bar"></div></div>
                    <span id="strength-text" class="strength-text"></span>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Repeat new password">
                </div>
                <button type="submit" name="update_profile" id="save-btn" class="btn-save">
                    <i class="fa-solid fa-circle-check"></i> SAVE ALL CHANGES
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { document.getElementById('preview').src = e.target.result; }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function checkStrength(password) {
        let strength = 0;
        const bar = document.getElementById('strength-bar');
        const text = document.getElementById('strength-text');
        const btn = document.getElementById('save-btn');

        if (password.length === 0) {
            bar.style.width = '0%';
            text.innerText = '';
            btn.disabled = false; // إذا كان الحقل فارغاً نسمح بالتحديث (تحديث الاسم فقط مثلاً)
            return;
        }

        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;

        switch(strength) {
            case 1: 
                bar.style.width = '25%'; bar.style.background = '#e53e3e'; 
                text.innerText = 'Weak - You cannot save'; text.style.color = '#e53e3e';
                btn.disabled = true;
                break;
            case 2: 
                bar.style.width = '50%'; bar.style.background = '#dd6b20'; 
                text.innerText = 'Fair - You cannot save'; text.style.color = '#dd6b20';
                btn.disabled = true;
                break;
            case 3: 
                bar.style.width = '75%'; bar.style.background = '#38a169'; 
                text.innerText = 'Strong - Ready to save'; text.style.color = '#38a169';
                btn.disabled = false;
                break;
            case 4: 
                bar.style.width = '100%'; bar.style.background = '#2f855a'; 
                text.innerText = 'Very Strong'; text.style.color = '#2f855a';
                btn.disabled = false;
                break;
        }
    }
</script>

</body>
</html>