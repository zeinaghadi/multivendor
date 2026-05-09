<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nashmi store | Logging Out</title>
    <meta http-equiv="refresh" content="2;url=index.php">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #d72229;
            --night: #170505;
            --white: #ffffff;
            --clay: #a76f58;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--night);
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .logout-wrapper {
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        /* لودر بتصميم Nashmi store */
        .loader {
            position: relative;
            width: 60px;
            height: 60px;
            margin: 0 auto 30px;
            border: 3px solid rgba(215, 34, 41, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary-red);
            animation: spin 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
        }

        /* إضافة توهج خلف اللودر */
        .loader::after {
            content: '';
            position: absolute;
            top: -5px; left: -5px; right: -5px; bottom: -5px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(215, 34, 41, 0.15) 0%, transparent 70%);
        }

        h2 {
            color: var(--white);
            font-weight: 800;
            font-size: 1.8rem;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        h2 span {
            color: var(--primary-red);
        }

        p {
            color: var(--clay);
            font-weight: 500;
            font-size: 1rem;
            opacity: 0.8;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* لمسة جمالية: شعار صغير في الأسفل */
        .mini-logo {
            position: absolute;
            bottom: 40px;
            font-weight: 800;
            color: rgba(255,255,255,0.2);
            font-size: 0.9rem;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

    <div class="logout-wrapper">
        <div class="loader"></div>
        <h2>Store<span>نشمي</span></h2>
        <p>Safely signing you out. See you soon!</p>
    </div>

    <div class="mini-logo">AUTHENTIC EXPERIENCE</div>

</body>
</html>