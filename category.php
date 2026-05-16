<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: test_login.php");
    exit();
}

$current_admin_id = $_SESSION['user_id'];


$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8");

$message = "";
$msg_type = "";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['v_cat_name'])) {
    $name = $_POST['v_cat_name'];
    $desc = $_POST['v_cat_desc'];
    
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $image_name = time() . "_" . basename($_FILES["cat_image"]["name"]);
    $target_file = $target_dir . $image_name;

    if(isset($_FILES["cat_image"]) && $_FILES["cat_image"]["tmp_name"] != "") {
        if (move_uploaded_file($_FILES["cat_image"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO categories (category_name, category_description, category_url, admin_id_fk) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $name, $desc, $target_file, $current_admin_id);
            
            if ($stmt->execute()) {
                $message = "Category has been added successfully! ✅";
                $msg_type = "success";
            }
            $stmt->close();
        } else {
            $message = "Error uploading image ❌";
            $msg_type = "error";
        }
    }
}

// delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $res = $conn->query("SELECT category_url FROM categories WHERE category_id = $delete_id");
    $img_data = $res->fetch_assoc();
    if($img_data['category_url'] && file_exists($img_data['category_url'])) { unlink($img_data['category_url']); }

    $conn->query("DELETE FROM categories WHERE category_id = $delete_id");
    header("Location: category.php");
    exit();
}

// select
$query = "SELECT categories.*, COUNT(vendors.vendor_id) as total_stores 
          FROM categories 
          LEFT JOIN vendors ON categories.category_id = vendors.category_ID_FK 
          GROUP BY categories.category_id
          ORDER BY categories.category_id DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nashmi store | Manage Categories</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-red: #d72229;
            --night: #170505;
            --clay: #a76f58;
            --bg-soft: #f8f9fa;
            --white: #ffffff;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-soft); display: flex; color: var(--night); }

        /* --- Sidebar --- */
        .sidebar { width: 280px; background: var(--night); height: 100vh; position: fixed; padding: 40px 20px; color: white; z-index: 1000; }
        .sidebar-logo { font-size: 1.8rem; font-weight: 800; margin-bottom: 50px; text-align: center; letter-spacing: -1px; }
        .sidebar-logo span { color: var(--primary-red); }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { color: rgba(255,255,255,0.6); text-decoration: none; padding: 16px 22px; display: flex; align-items: center; gap: 15px; border-radius: 20px; transition: var(--transition); font-weight: 600; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: rgba(215, 34, 41, 0.1); color: white; }
        .sidebar ul li a.active { background: var(--primary-red); box-shadow: 0 10px 20px rgba(215, 34, 41, 0.2); }

        /* --- Main Content --- */
        .main-content { margin-left: 280px; width: calc(100% - 280px); padding: 50px; animation: fadeIn 0.8s ease-out; }
        
        .dash-header { margin-bottom: 40px; }
        .dash-header h1 { font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; }
        .highlight { color: var(--primary-red); }

        .category-wrapper { display: grid; grid-template-columns: 1fr 1.8fr; gap: 35px; align-items: start; }

        /* --- Card Styling --- */
        .card { 
            background: var(--white); padding: 35px; border-radius: 35px; 
            box-shadow: 0 20px 40px rgba(23, 5, 5, 0.03); border: 1px solid rgba(167, 111, 88, 0.05); 
        }
        .card h3 { margin-bottom: 30px; font-size: 1.2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }

        /* --- Form --- */
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 10px; font-size: 14px; font-weight: 700; color: var(--clay); }
        .form-group input, .form-group textarea { 
            width: 100%; padding: 15px 20px; border: 1.5px solid #f1f1f1; border-radius: 18px; 
            font-family: inherit; transition: var(--transition); outline: none; background: #fafafa;
        }
        .form-group input:focus { border-color: var(--primary-red); background: #fff; }
        
        .file-input-wrapper { 
            border: 2px dashed #eee; padding: 30px; border-radius: 20px; text-align: center; 
            transition: var(--transition); cursor: pointer; position: relative;
        }
        .file-input-wrapper:hover { border-color: var(--primary-red); background: #fff; }
        .file-input-wrapper input { position: absolute; top: 0; left: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }

        .btn-add { 
            width: 100%; padding: 16px; background: var(--night); color: white; border: none; 
            border-radius: 18px; font-weight: 800; cursor: pointer; transition: var(--transition); 
        }
        .btn-add:hover { background: var(--primary-red); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(215, 34, 41, 0.2); }

        /* --- Table --- */
        .cat-table { width: 100%; border-collapse: collapse; }
        .cat-table th { text-align: left; padding: 20px; color: var(--clay); font-size: 12px; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid #eee; }
        .cat-table td { padding: 20px; border-bottom: 1px solid #f8f9fa; vertical-align: middle; }
        
        .img-preview-table { width: 55px; height: 55px; object-fit: cover; border-radius: 15px; border: 1px solid #eee; }
        .badge-count { background: rgba(167, 111, 88, 0.1); color: var(--clay); padding: 6px 12px; border-radius: 10px; font-weight: 800; font-size: 11px; }

        .btn-delete { color: #ccc; font-size: 18px; transition: var(--transition); text-decoration: none; }
        .btn-delete:hover { color: var(--primary-red); }

        /* --- Alerts --- */
        .alert { padding: 20px; border-radius: 20px; margin-bottom: 30px; font-weight: 700; font-size: 14px; }
        .alert-success { background: #e6fffb; color: #00b894; border: 1px solid #00b894; }
        .alert-error { background: #fff1f0; color: var(--primary-red); border: 1px solid var(--primary-red); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 1200px) {
            .category-wrapper { grid-template-columns: 1fr; }
            .sidebar { width: 100px; }
            .sidebar-logo, .sidebar ul li a span { display: none; }
            .main-content { margin-left: 100px; width: calc(100% - 100px); }
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-logo">Store<span>نشمي</span></div>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></a></li>
            <li><a href="approve.php"><i class="fa-solid fa-shield-check"></i> <span>Approve Products</span></a></li>
            <li><a href="category.php" class="active"><i class="fa-solid fa-layer-group"></i> <span>Categories</span></a></li>
            <li style="margin-top: 50px;"><a href="log_out.php" style="color: #ffbaba;"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a></li>
        </ul>
    </nav>

    <main class="main-content">
        <header class="dash-header">
            <h1>Manage <span class="highlight">Categories</span></h1>
            <p style="color: var(--clay); font-weight: 500;">Organize and define your store categories.</p>
        </header>

        <?php if ($message): ?>
            <div class="alert <?php echo ($msg_type == 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="category-wrapper">
            <section class="card">
                <h3><i class="fa-solid fa-plus-circle"></i> Add Category</h3>
                <form action="category.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" name="v_cat_name" placeholder="e.g. Handcrafts" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Category Image</label>
                        <div class="file-input-wrapper">
                            <i class="fa-solid fa-image" style="font-size: 24px; color: var(--clay); margin-bottom: 10px; display: block;"></i>
                            <span style="font-size: 12px; font-weight: 700;">Click to upload</span>
                            <input type="file" name="cat_image" accept="image/*" required onchange="updateFileName(this)">
                            <p id="file-name" style="font-size: 11px; margin-top: 10px; color: var(--primary-red);"></p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="v_cat_desc" rows="3" placeholder="Enter category details..."required></textarea>
                    </div>
                    
                    <button type="submit" class="btn-add">Save Category</button>
                </form>
            </section>

            <section class="card">
                <h3><i class="fa-solid fa-list"></i> Existing Categories</h3>
                <table class="cat-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Info</th>
                            <th>Stores</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $row['category_url']; ?>" class="img-preview-table" alt="cat">
                                </td>
                                <td>
                                    <div style="font-weight: 800; color: var(--night);"><?php echo htmlspecialchars($row['category_name']); ?></div>
                                    <div style="font-size: 11px; color: var(--clay);"><?php echo htmlspecialchars($row['category_description']); ?></div>
                                </td>
                                <td>
                                    <span class="badge-count"><?php echo $row['total_stores']; ?> Stores</span>
                                </td>
                                <td>
                                    <a href="?delete_id=<?php echo $row['category_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure?')">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--clay);">No categories found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>

    <script>
        function updateFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('file-name').textContent = "Selected: " + input.files[0].name;
            }
        }
    </script>

</body>
</html>