<?php
session_start();
require_once '../config.php';
require BASE_PATH . '/Database/connect-database.php';
require_once BASE_PATH . '/vendor/autoload.php'; // Dùng Composer để load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error_message = "Vui lòng nhập địa chỉ email!";
    } else {
        // Kiểm tra email trong giangvien (vì taikhoan không có cột Email)
        $query = "SELECT MaGiangVien FROM giangvien WHERE Email = ?";
        $stmt = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $maGiangVien = $row['MaGiangVien'];

            // Mật khẩu reset cố định
            $new_password = '1fFZ8o*J&zTp2L9v';
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT); // Mã hóa mật khẩu

            // Cập nhật mật khẩu trong taikhoan (giả định MaGiangVien = MaTaiKhoan)
            $query = "UPDATE taikhoan SET MatKhau = ? WHERE MaTaiKhoan = ?";
            $stmt_update = mysqli_prepare($dbc, $query);
            mysqli_stmt_bind_param($stmt_update, "ss", $hashed_password, $maGiangVien);
            $update_success = mysqli_stmt_execute($stmt_update);

            if ($update_success && mysqli_stmt_affected_rows($stmt_update) > 0) {
                // Gửi email thông báo
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'tung.lt.63cntt@ntu.edu.vn'; // Tài khoản gửi (Sender)
                    $mail->Password = 'hpss hfkw rwwl rnrz'; // App Password của tài khoản gửi
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Thiết lập mã hóa UTF-8 để hiển thị tiếng Việt chính xác
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64'; // Đảm bảo nội dung được mã hóa đúng

                    $mail->setFrom('tung.lt.63cntt@ntu.edu.vn', 'Hệ thống Quản lý');
                    $mail->addAddress($email); // Email người dùng nhập

                    $mail->isHTML(true);
                    $mail->Subject = 'Thông báo reset mật khẩu';
                    $mail->Body = "Chào bạn,<br><br>Mật khẩu của giảng viên đã được reset thành: <strong>$new_password</strong><br>Sau khi đăng nhập, hãy nhớ đổi mật khẩu nhé!<br><br>Trân trọng,<br>Hệ thống Quản lý";
                    $mail->AltBody = "Chào bạn,\n\nMật khẩu của giảng viên đã được reset thành: $new_password\nSau khi đăng nhập, hãy nhớ đổi mật khẩu nhé!\n\nTrân trọng,\nHệ thống Quản lý";

                    $mail->send();
                    $success_message = "Mật khẩu đã được reset và email thông báo đã được gửi!";
                } catch (Exception $e) {
                    $error_message = "Không thể gửi email. Lỗi: {$mail->ErrorInfo}";
                }
            } else {
                $error_message = "Không tìm thấy tài khoản liên kết hoặc lỗi khi reset mật khẩu.";
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $error_message = "Email không tồn tại trong hệ thống!";
        }
        mysqli_stmt_close($stmt);
    }

    mysqli_close($dbc);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Khôi phục mật khẩu - Hệ thống Quản lý</title>
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

        .reset-box {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1),
                0 1px 3px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease;
        }

        .reset-box:hover {
            transform: translateY(-2px);
        }

        .reset-form {
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

        .reset-button {
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

        .reset-button:hover {
            background: linear-gradient(to right, #1e3a8a, #2563eb);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .back-login {
            color: #1e40af;
            font-size: 0.875rem;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .back-login:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .notification {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }

        .notification.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .notification.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        @media (max-width: 480px) {
            .reset-box {
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
        <div class="reset-box">
            <?php
            if ($error_message) {
                echo '<div class="notification error">' . $error_message . '</div>';
            }
            if ($success_message) {
                echo '<div class="notification success">' . $success_message . '</div>';
            }
            ?>
            <form action="" method="post" class="reset-form">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Nhập địa chỉ email" class="input-field" required
                        oninvalid="this.setCustomValidity('Vui lòng nhập địa chỉ email')"
                        oninput="this.setCustomValidity('')" />
                </div>
                <div class="action-container">
                    <button type="submit" class="reset-button">Gửi yêu cầu</button>
                    <a href="./login.php" class="back-login">Quay lại đăng nhập</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>