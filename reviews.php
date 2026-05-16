<?php
session_start();

$db_host = "localhost";
$db_user = "root";
$db_pass = "Zz0795426555$";
$db_name = "multivendor_marketplace";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8");

if (!isset($_SESSION['user_id'])) {
    header("Location: test_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id == 0) {
    header("Location: tracking.php");
    exit();
}

// 2. Handle Review Submission
if (isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    
    // Insert into reviews table
    $sql = "INSERT INTO reviews (vendor_id_fk, customer_id_fk, order_id_fk, rating, comment, review_date) 
            VALUES ($vendor_id, $user_id, $order_id, $rating, '$comment', NOW())";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>
                alert('Thank you! Your feedback has been received.');
                window.location.href='tracking.php';
              </script>";
        exit();
    } else {
        $error_msg = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Experience | Nashmi store</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-red: #d72229;
            --night: #170505;
            --clay: #a76f58;
            --bg-soft: #fcfcfc;
            --white: #ffffff;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg-soft); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            padding: 20px;
        }

        .review-card { 
            background: var(--white); 
            padding: 50px 40px; 
            border-radius: 40px; 
            box-shadow: 0 30px 60px rgba(23, 5, 5, 0.05); 
            width: 100%; 
            max-width: 480px; 
            text-align: center;
            border: 1px solid rgba(167, 111, 88, 0.1);
            animation: fadeIn 0.8s ease-out;
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: rgba(215, 34, 41, 0.05);
            color: var(--primary-red);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 30px;
            transform: rotate(-5deg);
        }

        h2 { 
            color: var(--night); 
            font-weight: 800; 
            font-size: 1.8rem; 
            margin-bottom: 12px; 
            letter-spacing: -1px;
        }
        
        p { 
            color: var(--clay); 
            font-size: 1rem; 
            line-height: 1.6; 
            margin-bottom: 35px;
            font-weight: 500;
        }

        /* --- Custom Star Rating --- */
        .stars-container {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 12px;
            margin-bottom: 35px;
        }
        .stars-container input { display: none; }
        .stars-container label {
            font-size: 45px;
            color: #eee;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .stars-container input:checked ~ label,
        .stars-container label:hover,
        .stars-container label:hover ~ label {
            color: #ffb800; 
            transform: scale(1.2) rotate(10deg);
            text-shadow: 0 0 20px rgba(255, 184, 0, 0.4);
        }

        /* --- Textarea Style --- */
        textarea {
            width: 100%;
            border: 2px solid #f0f0f0;
            border-radius: 20px;
            padding: 20px;
            font-family: inherit;
            font-size: 1rem;
            resize: none;
            margin-bottom: 30px;
            outline: none;
            transition: var(--transition);
            background: #fafafa;
            color: var(--night);
        }
        
        textarea:focus {
            border-color: var(--clay);
            background: white;
            box-shadow: 0 10px 25px rgba(167, 111, 88, 0.08);
        }

        /* --- Actions Container --- */
        .actions-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* --- Submit Button --- */
        .btn-submit {
            background: var(--night);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 20px;
            width: 100%;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 10px 20px rgba(23, 5, 5, 0.1);
        }

        .btn-submit:hover {
            background: var(--primary-red);
            transform: translateY(-5px);
            box-shadow: 0 20px 35px rgba(215, 34, 41, 0.25);
        }

        /* --- Skip Button --- */
        .btn-skip {
            background: transparent;
            color: var(--clay);
            border: 2px solid rgba(167, 111, 88, 0.15);
            padding: 15px;
            border-radius: 20px;
            width: 100%;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: var(--transition);
        }

        .btn-skip:hover {
            background: rgba(167, 111, 88, 0.05);
            border-color: var(--clay);
            color: var(--night);
        }

        .btn-submit i {
            transition: var(--transition);
        }

        .btn-submit:hover i {
            transform: translateX(5px) translateY(-5px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .review-card { padding: 40px 25px; }
            h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="review-card">
    <div class="icon-box">
        <i class="fa-solid fa-comment-dots"></i>
    </div>

    <h2>Rate Order #<?php echo $order_id; ?></h2>
    <p>Your feedback is important in the bigger picture. Help us and the vendor improve.</p>

    <form method="POST">
        <div class="stars-container">
            <input type="radio" name="rating" value="5" id="s5" required><label for="s5">★</label>
            <input type="radio" name="rating" value="4" id="s4"><label for="s4">★</label>
            <input type="radio" name="rating" value="3" id="s3"><label for="s3">★</label>
            <input type="radio" name="rating" value="2" id="s2"><label for="s2">★</label>
            <input type="radio" name="rating" value="1" id="s1"><label for="s1">★</label>
        </div>

        <textarea name="comment" rows="4" placeholder="Share your experience (Optional)..."></textarea>
        
        <div class="actions-group">
            <button type="submit" name="submit_review" class="btn-submit">
                Publish Review <i class="fa-solid fa-arrow-right"></i>
            </button>
            <a href="index.php" class="btn-skip">Skip for now</a>
        </div>
    </form>
</div>

</body>
</html>