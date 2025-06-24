<?php
session_start();
require_once '../config.php';
require_once BASE_PATH . '/Database/connect-database.php';

// // Xử lý đăng xuất khi action=logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Kiểm tra người dùng đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];

// Truy vấn lấy thông tin giảng viên và quyền
$query = "SELECT g.HoGiangVien, g.TenGiangVien, g.AnhDaiDien, t.Quyen 
          FROM giangvien g
          JOIN taikhoan t ON g.MaGiangVien = t.MaTaiKhoan
          WHERE t.MaTaiKhoan = ?";
$stmt = mysqli_prepare($dbc, $query); // Chuẩn bị thực thi truy vấn
mysqli_stmt_bind_param($stmt, "s", $user_id); // truyền giá trị cần truy vấn vào.
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $row = mysqli_fetch_assoc($result)) {
    // Ghép họ và tên giảng viên
    $full_name = $row['HoGiangVien'] . ' ' . $row['TenGiangVien'];
    $avatar = !empty($row['AnhDaiDien']) ? $row['AnhDaiDien'] : BASE_URL . '/Public/img/avatar-default.png';
    $quyen = $row['Quyen'];
} else {
    $full_name = "Không xác định";
    $avatar = BASE_URL . '/Public/img/avatar-default.png';
    $quyen = 'GiangVien';
    $_SESSION['error_message'] = "Không tìm thấy thông tin tài khoản.";
}
mysqli_stmt_close($stmt);

$error = '';
$success = '';
// Xử lý form đổi mật khẩu khi gửi yêu cầu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Kiểm tra các trường nhập liệu
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Vui lòng điền đầy đủ tất cả các trường.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
    } elseif (strlen($new_password) < 6) {
        $error = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    } else {
        // Kiểm tra mật khẩu hiện tại
        $query = "SELECT MatKhau FROM taikhoan WHERE MaTaiKhoan = ?";
        $stmt = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Lấy mật khẩu đã mã hóa
            $stored_password = $row['MatKhau'];
            // Kiểm tra mật khẩu hiện tại bằng password_verify
            if (password_verify($current_password, $stored_password)) {
                // Mã hóa mật khẩu mới
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Cập nhật mật khẩu mới
                $update_query = "UPDATE taikhoan SET MatKhau = ? WHERE MaTaiKhoan = ?";
                $update_stmt = mysqli_prepare($dbc, $update_query);
                mysqli_stmt_bind_param($update_stmt, "ss", $hashed_new_password, $user_id);

                if (mysqli_stmt_execute($update_stmt)) {
                    $success = "Đổi mật khẩu thành công!";
                } else {
                    $error = "Có lỗi xảy ra khi đổi mật khẩu. Vui lòng thử lại.";
                }

                mysqli_stmt_close($update_stmt);
            } else {
                $error = "Mật khẩu hiện tại không đúng.";
            }
        } else {
            $error = "Không tìm thấy thông tin tài khoản.";
        }

        mysqli_stmt_close($stmt);
    }
}

mysqli_close($dbc);
include(BASE_PATH . '/Layout/header.php');
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .change-password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .error {
            color: red;
            font-size: 14px;
        }

        .success {
            color: green;
            font-size: 14px;
        }
    </style>
</head>

<body style="background-color: #f1f1f1;">
    <div class="menu-container">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 col-6">
                    <nav class="main-menu">
                        <button class="menu-toggle">☰</button>
                        <ul class="menu" style="padding-top: 10px;">
                            <li class="dropdown">
                                <a href="./index.php">Lịch làm việc</a>
                            </li>
                            <li class="dropdown">
                                <a href="#">Danh sách</a>
                                <ul class="submenu">
                                    <li><a href="./course-list.php">Đăng ký học phần</a></li>
                                    <li><a href="./works-list.php">Danh sách công việc hành chính</a></li>
                                </ul>
                            </li>
                            <li class="dropdown">
                                <a href="./guide-students.php">Hướng dẫn sinh viên</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <div class="col-md-6 col-6 d-flex justify-content-end">
                    <nav>
                        <ul class="menu-right">
                            <li class="dropdown">
                                <a href="#">
                                    <b><?php echo htmlspecialchars($full_name); ?></b>
                                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                </a>
                                <ul class="submenu">
                                    <?php if ($quyen === 'Admin' || $quyen === 'Super Admin'): ?>
                                        <li><a href="<?php echo BASE_URL; ?>/Admin/work/index.php">Trang Quản Trị</a></li>
                                    <?php endif; ?>
                                    <li><a href="./profile.php">Trang Cá Nhân</a></li>
                                    <li><a href="./teaching-materials.php">Tài liệu giảng dạy</a></li>
                                    <li><a href="./change-password.php">Đổi mật khẩu</a></li>
                                    <li><a href="?action=logout">Đăng xuất</a></li>
                                </ul>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="change-password-container">
        <h4 class="text-center">Đổi mật khẩu</h4>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="change-password.php">
            <div class="mb-3">
                <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">Mật khẩu mới</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Đổi mật khẩu</button>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            // Hiển thị thông báo thành công bằng SweetAlert2
            <?php if (!empty($success)): ?>
                Swal.fire({
                    toast: true,
                    position: 'top-end', // hiển thị góc trên bên phải
                    icon: 'success',
                    title: '<?php echo addslashes($success); ?>',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: '<?php echo addslashes($error); ?>',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            <?php endif; ?>
            
            $('.menu-toggle').click(function() {
                $('.main-menu .menu').slideToggle();
            });

            $(document).click(function(e) {
                if (!$(e.target).closest('.dropdown').length && $(window).width() <= 768) {
                    $('.submenu').hide();
                }
            });

            $('.main-menu .dropdown > a, .menu-right .dropdown > a').click(function(e) {
                if ($(window).width() <= 768) {
                    var $submenu = $(this).siblings('.submenu');
                    // Chỉ chặn sự kiện nếu mục có submenu
                    if ($submenu.length > 0) {
                        e.preventDefault();
                        if ($submenu.is(':visible')) {
                            $submenu.hide();
                        } else {
                            $('.submenu').hide();
                            $submenu.show();
                        }
                    }
                    // Nếu không có submenu, liên kết sẽ hoạt động bình thường
                }
            });
        });
    </script>
</body>
<?php include(BASE_PATH . '/Layout/footer.php'); ?>

</html>