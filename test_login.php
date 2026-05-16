<?php
session_start();


$host = "localhost";
 $db_user = "root"; 
 $db_pass = "Zz0795426555$"; 
 $db_name = "multivendor_marketplace"; 

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) { die("Database Connection Failed"); }
mysqli_set_charset($conn, "utf8");

$message = "";
$message_type = "";

// Remember Me
$cookie_email = $_COOKIE['user_email'] ?? "";
$cookie_pass  = $_COOKIE['user_password'] ?? "";
$cookie_check = isset($_COOKIE['user_email']) ? "checked" : "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_email = trim($_POST['email']);
    $input_password = trim($_POST['password']); 
    $role = $_POST['role'] ?? 'customer';
    $remember = isset($_POST['remember']);

    $roles_map = [
        'admin' => ['table' => 'admins', 'email' => 'admin_email', 'pass' => 'admin_password', 'id' => 'admin_id', 'display' => 'admin_firstname', 'redir' => 'admin_dashboard.php'],
        'vendor' => ['table' => 'vendors', 'email' => 'vendor_email', 'pass' => 'vendor_password', 'id' => 'vendor_id', 'display' => 'store_name', 'redir' => 'vendor_dashboard.php'],
        'customer' => ['table' => 'customers', 'email' => 'customer_email', 'pass' => 'customer_password', 'id' => 'customer_id', 'display' => 'customer_firstname', 'redir' => 'index.php']
    ];

    if (isset($roles_map[$role])) {
        $c = $roles_map[$role];
        $sql = "SELECT * FROM {$c['table']} WHERE {$c['email']} = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $input_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user_data = $result->fetch_assoc()) {
                $db_pass = trim($user_data[$c['pass']]);
                $is_auth = ($role === 'admin') ? (hash('sha256', $input_password) === $db_pass) : password_verify($input_password, $db_pass);
                //cookies
                if ($is_auth) {
                    if ($remember) {
                        setcookie("user_email", $input_email, time() + (86400 * 30), "/");
                        setcookie("user_password", $input_password, time() + (86400 * 30), "/");
                    } else {
                        setcookie("user_email", "", time() - 3600, "/");
                        setcookie("user_password", "", time() - 3600, "/");
                    }
                //create session
                    $_SESSION['user_id'] = $user_data[$c['id']];
                    $_SESSION['role'] = $role;
                    $_SESSION['username'] = $user_data[$c['display']] ?? 'User';
                //welcoming
                    $message = "Welcome back, " . $_SESSION['username'] . "!";
                    $message_type = "success";
                    header("refresh:1.2; url=" . $c['redir']);
                } else {
                    $message = "Invalid password. Try again.";
                    $message_type = "error";
                }
            } else {
                $message = "Account not found.";
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Nashmi store | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-red: #d72229;
            --deep-maroon: #770e13;
            --clay: #a76f58;
            --wood: #5d382f;
            --night: #170505;
            --white: #ffffff;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; 
            height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center;
            background-color: #f8f9fa;
            /* خلفية بتدرج ناعم مستوحى من الباليت */
            background-image: 
                radial-gradient(at 0% 0%, rgba(119, 14, 19, 0.05) 0, transparent 50%), 
                radial-gradient(at 100% 100%, rgba(215, 34, 41, 0.05) 0, transparent 50%);
        }

        .login-card { 
            background: var(--white); 
            width: 420px; 
            border-radius: 35px; 
            box-shadow: 0 30px 60px rgba(23, 5, 5, 0.1); 
            overflow: hidden; 
            animation: cardEntrance 0.7s ease-out;
            border: 1px solid rgba(167, 111, 88, 0.1);
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(30px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Modern Tabs - Smooth Indicator */
        .tabs-container {
            display: flex;
            position: relative;
            background: #f1f1f1;
            margin: 25px 30px 10px;
            border-radius: 20px;
            padding: 5px;
        }

        .tab-btn { 
            flex: 1; 
            padding: 14px; 
            border: none; 
            background: none;
            cursor: pointer; 
            font-weight: 700; 
            color: var(--wood); 
            z-index: 2;
            transition: var(--transition);
            font-size: 14px;
        }

        .tab-btn.active { color: white; }

        .tab-indicator {
            position: absolute;
            height: calc(100% - 10px);
            width: calc(33.33% - 7px);
            background: var(--deep-maroon);
            border-radius: 16px;
            top: 5px;
            left: 5px;
            transition: transform 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            z-index: 1;
            box-shadow: 0 5px 15px rgba(119, 14, 19, 0.3);
        }

        .form-area { padding: 30px 40px 45px; }
        
        .logo { text-align: center; margin-bottom: 35px; }
        .logo h2 { margin: 0; color: var(--night); font-weight: 800; font-size: 30px; letter-spacing: -1px; }
        .logo span { color: var(--primary-red); }

        .input-group { margin-bottom: 22px; }
        .input-group label { 
            display: block; margin-bottom: 10px; font-weight: 700; 
            font-size: 12px; color: var(--clay); text-transform: uppercase; letter-spacing: 1px;
        }
        
        .input-group input { 
            width: 100%; padding: 16px 20px; 
            border: 2px solid #eee; border-radius: 18px; 
            box-sizing: border-box; background: #fafafa;
            transition: var(--transition); font-size: 15px;
            color: var(--night);
        }

        .input-group input:focus { 
            border-color: var(--clay); outline: none; 
            background: white;
            box-shadow: 0 0 0 5px rgba(167, 111, 88, 0.1);
        }

        .remember-box { 
            display: flex; align-items: center; gap: 12px; 
            margin-bottom: 30px; font-size: 14px; color: var(--wood); 
            font-weight: 600;
        }

        .remember-box input[type="checkbox"] {
            accent-color: var(--primary-red);
            width: 18px; height: 18px;
        }

        .btn-submit { 
            width: 100%; padding: 18px; 
            background: var(--night); color: white; 
            border: none; border-radius: 20px; 
            font-weight: 800; cursor: pointer; font-size: 16px; 
            transition: var(--transition);
        }

        .btn-submit:hover { 
            background: var(--primary-red); 
            transform: translateY(-3px); 
            box-shadow: 0 12px 25px rgba(215, 34, 41, 0.3);
        }

        .alert { 
            padding: 16px; border-radius: 15px; margin-top: 25px; 
            text-align: center; font-size: 14px; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .error { background: #fff1f1; color: var(--primary-red); border: 1px solid #ffebeb; }
        .success { background: #f0fff4; color: #2d8a4e; border: 1px solid #e6ffed; }

        .footer-link { text-align: center; margin-top: 30px; font-size: 14px; color: var(--clay); }
        .footer-link a { color: var(--deep-maroon); text-decoration: none; font-weight: 800; transition: 0.3s; }
        .footer-link a:hover { color: var(--primary-red); text-decoration: underline; }

    </style>
</head>
<body>

<div class="login-card">
    <div class="tabs-container">
        <div class="tab-indicator" id="indicator"></div>
        <button class="tab-btn active" onclick="setRole('customer', 0, this)">Customer</button>
        <button class="tab-btn" onclick="setRole('vendor', 1, this)">Vendor</button>
        <button class="tab-btn" onclick="setRole('admin', 2, this)">Admin</button>
    </div>

    <div class="form-area">
        <div class="logo">
            <h2>Store <span>نشمي </span></h2>
        </div>

        <form method="POST">
            <input type="hidden" name="role" id="role-input" value="customer">
            
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" required value="<?= $cookie_email ?? '' ?>" placeholder="name@example.com">
            </div>
            
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required value="<?= $cookie_pass ?? '' ?>" placeholder="••••••••">
            </div>

            <div class="remember-box">
                <input type="checkbox" name="remember" id="rem" <?= ($cookie_check ?? '') ? 'checked' : '' ?>>
                <label for="rem" style="cursor:pointer">Keep me signed in</label>
            </div>
            
            <button type="submit" class="btn-submit">Sign In Account</button>

            <?php if(isset($message) && $message): ?>
                <div class="alert <?= $message_type ?>">
                    <i class="fa-solid <?= ($message_type=='success') ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>
        </form>

        <div class="footer-link" id="register-link">
            Don't have an account? <a href="register.php">Create Account</a>
        </div>
    </div>
</div>

<script>
    function setRole(role, index, btn) {
        document.getElementById('role-input').value = role;
        
        const indicator = document.getElementById('indicator');
        indicator.style.transform = `translateX(${index * 100}%)`;
        

        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // إno register for admin
        const regLink = document.getElementById('register-link');
        if(role === 'admin') {
            regLink.style.opacity = '0';
            regLink.style.pointerEvents = 'none';
        } else {
            regLink.style.opacity = '1';
            regLink.style.pointerEvents = 'auto';
        }
    }
</script>

</body>
</html>