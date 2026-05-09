<?php
session_start();
// 1. حماية الصفحة
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
mysqli_set_charset($conn, "utf8");


//$vendor_id = $_SESSION['vendor_id'] ?? 1; 


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    
    $store_name = mysqli_real_escape_string($conn, $_POST['store_name']);
    $email      = mysqli_real_escape_string($conn, $_POST['vendor_email']);
    

    $password     = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];
    $pass_query = "";

    if (!empty($password)) {
        if ($password === $confirm_pass) {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $pass_query = ", vendor_password = '$hashed_pass'";
        } else {
            echo "<script>alert('The password does not match!'); window.history.back();</script>";
            exit();
        }
    }


    $update_sql = "UPDATE vendors SET 
                   store_name = '$store_name', 
                   vendor_email = '$email' 
                   $pass_query 
                   WHERE vendor_id = '$vendor_id'";
    
    if (mysqli_query($conn, $update_sql)) {
        

        if (!empty($_FILES['logo']['name'])) {
            $logo_name = time() . "_" . $_FILES['logo']['name'];
            if (move_uploaded_file($_FILES['logo']['tmp_name'], "uploads/" . $logo_name)) {
                mysqli_query($conn, "UPDATE vendors SET logo_url = '$logo_name' WHERE vendor_id = '$vendor_id'");
            }
        }
        
        echo "<script>alert('Store data has been successfully updated!'); window.location.href='vendor_profile.php';</script>";
    } else {
        echo "<script>alert('Update failed: " . mysqli_error($conn) . "');</script>";
    }
}


$res = mysqli_query($conn, "SELECT * FROM vendors WHERE vendor_id = '$v_id'");
$vendor = mysqli_fetch_assoc($res);
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
            --primary-red: #d72229;
            --clay: #a76f58;
            --night: #170505;
            --bg-soft: #f8f9fa;
            --white: #ffffff;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-soft);
            color: var(--night);
            display: flex;
            min-height: 100vh;
        }

        /* --- Sidebar (Nashmi store Identity) --- */
        .sidebar {
            width: 280px; background: var(--night); color: white;
            padding: 40px 20px; position: fixed; height: 100vh; z-index: 1000;
        }
        .sidebar-logo { font-size: 1.8rem; font-weight: 800; margin-bottom: 50px; text-align: center; letter-spacing: -1px; }
        .sidebar-logo span { color: var(--primary-red); }
        .sidebar ul { list-style: none; }
        .sidebar ul li a {
            color: rgba(255,255,255,0.6); text-decoration: none; padding: 16px 22px;
            display: flex; align-items: center; gap: 15px; border-radius: 20px;
            transition: var(--transition); font-weight: 600;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: rgba(215, 34, 41, 0.1); color: white; }
        .sidebar ul li a.active { background: var(--primary-red); box-shadow: 0 10px 20px rgba(215, 34, 41, 0.2); }

        /* --- Main Content --- */
        .dashboard-content { margin-left: 280px; width: calc(100% - 280px); padding: 50px; animation: fadeIn 0.8s ease-out; }

        .dash-header h2 { font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; margin-bottom: 30px; }
        .dash-header h2 i { color: var(--primary-red); margin-right: 10px; }

        /* --- Profile Container --- */
        .profile-card {
            background: var(--white); border-radius: 40px; padding: 50px;
            box-shadow: 0 20px 50px rgba(23, 5, 5, 0.03);
            max-width: 850px; margin: 0 auto;
            border: 1px solid rgba(167, 111, 88, 0.08);
        }

        /* --- Logo Section --- */
        .logo-upload-section {
            display: flex; flex-direction: column; align-items: center; margin-bottom: 40px;
        }
        .logo-preview-wrapper {
            position: relative; width: 140px; height: 140px; margin-bottom: 20px;
        }
        .logo-preview-wrapper img {
            width: 100%; height: 100%; border-radius: 40px; object-fit: cover;
            border: 4px solid var(--white); box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .file-input-label {
            background: var(--night); color: white; padding: 10px 20px; border-radius: 12px;
            font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: var(--transition);
        }
        .file-input-label:hover { background: var(--primary-red); transform: translateY(-2px); }

        /* --- Form Elements --- */
        .section-title {
            font-size: 1.1rem; font-weight: 800; color: var(--clay);
            margin: 30px 0 20px; display: flex; align-items: center; gap: 10px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .section-title::after { content: ''; flex: 1; height: 1px; background: #eee; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }

        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 0.85rem; font-weight: 700; color: var(--night); padding-left: 5px; }
        .form-group input {
            padding: 15px 20px; border-radius: 15px; border: 2px solid #f1f5f9;
            font-family: inherit; font-weight: 600; transition: var(--transition); outline: none;
        }
        .form-group input:focus { border-color: var(--primary-red); background: #fff; box-shadow: 0 5px 15px rgba(215, 34, 41, 0.05); }

        .btn-save {
            grid-column: span 2; margin-top: 30px; background: var(--night);
            color: white; border: none; padding: 18px; border-radius: 18px;
            font-weight: 800; font-size: 1rem; cursor: pointer; transition: var(--transition);
        }
        .btn-save:hover { background: var(--primary-red); box-shadow: 0 15px 30px rgba(215, 34, 41, 0.25); transform: translateY(-3px); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 900px) {
            .form-grid { grid-template-columns: 1fr; }
            .btn-save { grid-column: span 1; }
            .dashboard-content { margin-left: 100px; width: calc(100% - 100px); }
            .sidebar { width: 100px; }
            .sidebar-logo, .sidebar ul li a span { display: none; }
        }
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
        <h2><i class="fa-solid fa-sliders"></i> Store <span>Settings</span></h2>
    </header>

    <section class="profile-container">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="profile-card">
                
                <div class="logo-upload-section">
                    <div class="logo-preview-wrapper">
                        <img id="preview" src="uploads/<?php echo $vendor['logo_url'] ?? 'default_logo.png'; ?>" alt="Store Logo">
                    </div>
                    <label for="logo-input" class="file-input-label">
                        <i class="fa-solid fa-camera"></i> UPDATE LOGO
                    </label>
                    <input type="file" id="logo-input" name="logo" hidden onchange="previewImage(this)">
                </div>

                <h3 class="section-title">Store Identity</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Store Name</label>
                        <input type="text" name="store_name" placeholder="Enter store name" value="<?php echo htmlspecialchars($vendor['store_name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Professional Email</label>
                        <input type="email" name="vendor_email" placeholder="email@store.com" value="<?php echo htmlspecialchars($vendor['vendor_email'] ?? ''); ?>" required>
                    </div>
                </div>

                <h3 class="section-title">Security & Access</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" placeholder="Leave empty to keep current">
                    </div>

                    <div class="form-group">
                        <label>Confirm Identity</label>
                        <input type="password" name="confirm_password" placeholder="Re-type password">
                    </div>

                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="fa-solid fa-circle-check"></i> SAVE CHANGES
                    </button>
                </div>
            </div>
        </form>
    </section>
</main>

<script>
    // Preview image before upload
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

</body>
</html>