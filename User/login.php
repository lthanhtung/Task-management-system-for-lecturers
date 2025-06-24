<?php
session_start();
require_once '../config.php';
require BASE_PATH . '/Database/connect-database.php';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error_message = "Vui lòng điền đầy đủ thông tin!";
    } else {
        // Sử dụng prepared statement để truy vấn
        $query = "SELECT MaTaiKhoan, MatKhau, Quyen FROM taikhoan WHERE MaTaiKhoan = ?";
        $stmt = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && $user = mysqli_fetch_assoc($result)) {
            // Kiểm tra mật khẩu bằng password_verify
            if (password_verify($password, $user['MatKhau'])) {
                $_SESSION['user_id'] = $user['MaTaiKhoan'];
                $_SESSION['quyen'] = $user['Quyen']; // Gán Quyen vào session
                mysqli_stmt_close($stmt);
                mysqli_close($dbc);
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Tên đăng nhập hoặc mật khẩu không đúng!";
            }
        } else {
            $error_message = "Tên đăng nhập hoặc mật khẩu không đúng!";
        }
        mysqli_stmt_close($stmt);
    }
}

// Đóng kết nối nếu không chuyển hướng
mysqli_close($dbc);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng nhập - Hệ thống Quản lý</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Roboto', sans-serif;
        }

        .outer-container {
            max-width: 480px;
            width: 100%;
            margin: 20px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .title {
            color: #1e40af;
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .login-box {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1),
                0 1px 3px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease;
        }

        .login-box:hover {
            transform: translateY(-2px);
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .input-group {
            position: relative;
        }

        .input-field {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .input-field:focus {
            outline: none;
            border-color: #1e40af;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }

        .input-field::placeholder {
            color: #6b7280;
            font-weight: 400;
        }

        .action-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .login-button {
            background: linear-gradient(to right, #1e40af, #3b82f6);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            width: 60%;
            font-weight: 500;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-button:hover {
            background: linear-gradient(to right, #1e3a8a, #2563eb);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .lost-password {
            color: #1e40af;
            font-size: 0.875rem;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .lost-password:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .notification {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 1.5rem;
            }

            .logo {
                width: 100px;
                height: 100px;
            }

            .title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="outer-container">
        <div class="logo-container">
            <img src="<?php echo BASE_URL . '/Public/img/LogoNTU.jpg' ?>" alt="NTU logo" class="logo" />
            <p class="title">Hệ thống quản lý công việc giảng viên</p>
        </div>
        <div class="login-box">
            <?php
            if (isset($error_message)) {
                echo '<div class="notification">' . $error_message . '</div>';
            }
            ?>
            <form action="" method="post" class="login-form">
                <div class="input-group">
                    <input type="text" name="username" placeholder="Tên đăng nhập" class="input-field" required
                        oninvalid="this.setCustomValidity('Vui lòng điền tên đăng nhập')"
                        oninput="this.setCustomValidity('')" />
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Mật khẩu" class="input-field" required
                        oninvalid="this.setCustomValidity('Vui lòng điền mật khẩu')"
                        oninput="this.setCustomValidity('')" />
                </div>
                <div class="action-container">
                    <button type="submit" class="login-button">Đăng nhập</button>
                    <a href="./lost-password.php" class="lost-password">Quên mật khẩu?</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>