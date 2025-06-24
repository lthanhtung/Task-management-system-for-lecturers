<?php
session_start();
require_once '../config.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Kiểm tra đăng xuất
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Vui lòng đăng nhập để xem thông tin hướng dẫn sinh viên.";
    header("Location: login.php");
    exit();
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$query = "SELECT g.HoGiangVien, g.TenGiangVien, g.AnhDaiDien, t.Quyen 
          FROM giangvien g
          JOIN taikhoan t ON g.MaGiangVien = t.MaTaiKhoan
          WHERE t.MaTaiKhoan = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $full_name = $row['HoGiangVien'] . ' ' . $row['TenGiangVien'];
    $avatar = !empty($row['AnhDaiDien']) ? $row['AnhDaiDien'] : '/Public/img/default_avatar.jpg';
    $quyen = $row['Quyen'];
} else {
    $full_name = "Không xác định";
    $avatar = BASE_URL . '/Public/img/default_avatar.jpg';
    $quyen = 'GiangVien';
    $_SESSION['error_message'] = "Không tìm thấy thông tin tài khoản.";
}
mysqli_stmt_close($stmt);

// Lấy danh sách năm cho bộ lọc
$years = [];
$query_years = "SELECT DISTINCT YEAR(ThoiGianHuongDan) AS Year FROM huongdansinhvien WHERE MaGiangVien = ? ORDER BY Year DESC";
$stmt_years = mysqli_prepare($dbc, $query_years);
mysqli_stmt_bind_param($stmt_years, "s", $user_id);
mysqli_stmt_execute($stmt_years);
$result_years = mysqli_stmt_get_result($stmt_years);
while ($row = mysqli_fetch_assoc($result_years)) {
    $years[] = $row['Year'];
}
mysqli_stmt_close($stmt_years);

// Xử lý bộ lọc năm
$current_year = date('Y'); // Năm hiện tại 
$nearest_year = null;
if (!empty($years)) {
    // Nếu năm hiện tại có trong danh sách
    if (in_array($current_year, $years)) {
        $nearest_year = $current_year;
    } else {
        // Lọc các năm nhỏ hơn hoặc bằng năm hiện tại
        $filtered_years = array_filter($years, function ($year) use ($current_year) {
            return $year <= $current_year;
        });
        // Chọn năm gần nhất
        $nearest_year = !empty($filtered_years) ? max($filtered_years) : max($years);
    }
}
// Đặt năm được chọn từ tham số GET hoặc năm gần nhất
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : $nearest_year;

// Truy vấn danh sách sinh viên được hướng dẫn, sắp xếp theo ThoiGianHuongDan DESC
$grouped_students = [];
$query = "
    SELECT 
        h.MaHuongDan, 
        h.KeHoachHuongDan, 
        DATE_FORMAT(h.ThoiGianHuongDan, '%d/%m/%Y') AS ThoiGianHuongDan, 
        h.IsPublic, 
        t.MaSinhVien, 
        CONCAT(t.HoSinhVien, ' ', t.TenSinhVien) AS HoTen, 
        t.TenDeTai, 
        t.DiemTongKet
    FROM huongdansinhvien h
    LEFT JOIN thongtinhuongdansinhvien t ON h.MaHuongDan = t.MaHuongDan
    WHERE h.MaGiangVien = ? AND YEAR(h.ThoiGianHuongDan) = ?
    ORDER BY h.ThoiGianHuongDan DESC, h.MaHuongDan, t.MaSinhVien
";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, "si", $user_id, $selected_year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $ma_huong_dan = $row['MaHuongDan']; // Gộp theo MaHuongDan
        if (!isset($grouped_students[$ma_huong_dan])) {
            $grouped_students[$ma_huong_dan] = [];
        }
        $grouped_students[$ma_huong_dan][] = [
            'MaHuongDan' => $row['MaHuongDan'],
            'MaSinhVien' => $row['MaSinhVien'],
            'HoTen' => $row['HoTen'],
            'TenDeTai' => $row['TenDeTai'],
            'DiemTongKet' => $row['DiemTongKet'],
            'ThoiGianHuongDan' => $row['ThoiGianHuongDan'],
            'IsPublic' => $row['IsPublic'],
            'KeHoachHuongDan' => $row['KeHoachHuongDan']
        ];
    }
}
mysqli_stmt_close($stmt);

// Xử lý cập nhật kế hoạch hướng dẫn, điểm tổng kết và trạng thái công khai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_score'])) {
    $ma_huong_dans = $_POST['ma_huong_dan'];
    $ma_sinh_viens = $_POST['ma_sinh_vien'];
    $ke_hoach_huong_dan = trim($_POST['ke_hoach_huong_dan']);
    $diem_tong_kets = $_POST['diem_tong_ket'];
    $is_public = isset($_POST['is_public']) ? 1 : 0;

    // Kiểm tra điểm hợp lệ (0-10, cho phép rỗng)
    $valid = true;
    foreach ($diem_tong_kets as $diem) {
        if ($diem !== '' && (!is_numeric($diem) || $diem < 0 || $diem > 10)) {
            $valid = false;
            break;
        }
    }

    if ($valid && !empty($ke_hoach_huong_dan)) {
        // Bắt đầu transaction
        mysqli_begin_transaction($dbc);
        try {
            // Cập nhật KeHoachHuongDan và IsPublic
            $query_plan = "UPDATE huongdansinhvien SET KeHoachHuongDan = ?, IsPublic = ? WHERE MaHuongDan = ? AND MaGiangVien = ?";
            $stmt_plan = mysqli_prepare($dbc, $query_plan);
            $ma_huong_dan_first = $ma_huong_dans[0];
            mysqli_stmt_bind_param($stmt_plan, "siss", $ke_hoach_huong_dan, $is_public, $ma_huong_dan_first, $user_id);
            mysqli_stmt_execute($stmt_plan);
            mysqli_stmt_close($stmt_plan);

            // Cập nhật DiemTongKet
            $query_score = "UPDATE thongtinhuongdansinhvien SET DiemTongKet = ? WHERE MaSinhVien = ? AND MaHuongDan = ?";
            $stmt_score = mysqli_prepare($dbc, $query_score);
            foreach ($ma_huong_dans as $index => $ma_huong_dan) {
                $diem = $diem_tong_kets[$index] === '' ? NULL : floatval($diem_tong_kets[$index]);
                mysqli_stmt_bind_param($stmt_score, "dss", $diem, $ma_sinh_viens[$index], $ma_huong_dan);
                mysqli_stmt_execute($stmt_score);
            }
            mysqli_stmt_close($stmt_score);

            // Commit transaction
            mysqli_commit($dbc);
            $_SESSION['success_message'] = "Cập nhật kế hoạch hướng dẫn, điểm tổng kết và trạng thái công khai thành công!";
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            mysqli_rollback($dbc);
            $_SESSION['error_message'] = "Lỗi khi cập nhật: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Dữ liệu không hợp lệ. Điểm phải từ 0-10 và kế hoạch không được để trống.";
    }

    header("Location: guide-students.php?year=$selected_year");
    exit();
}

include(BASE_PATH . '/Layout/header.php');
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hướng dẫn sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f1f1f1;
            font-family: Arial, sans-serif;
        }

        .content {
            margin-top: 20px;
        }

        .table-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .score-input {
            width: 80px;
        }

        .plan-textarea,
        .topic-textarea {
            width: 100%;
            resize: vertical;
        }

        .topic-textarea[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .filter-container {
            margin-bottom: 20px;
        }

        /* Điều chỉnh chiều rộng cột */
        th.stt,
        td.stt {
            width: 5%;
        }

        th.plan,
        td.plan {
            width: 25%;
        }

        th.time,
        td.time {
            width: 10%;
        }

        th.public,
        td.public {
            width: 8%;
        }

        th.mssv,
        td.mssv {
            width: 10%;
        }

        th.name,
        td.name {
            width: 15%;
        }

        th.topic,
        td.topic {
            width: 22%;
        }

        th.score,
        td.score {
            width: 5%;
        }

        th.action,
        td.action {
            width: 5%;
        }
    </style>
</head>

<body>
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
                                    <li><a href="./course-list.php">Danh sách học phần</a></li>
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

    <div class="container content">
        <h4>Hướng dẫn sinh viên</h4>
        <div class="filter-container">
            <?php if (!empty($years)): ?>
                <form method="GET" action="guide-students.php">
                    <label for="year">Lọc theo năm: </label>
                    <select name="year" id="year" onchange="this.form.submit()">
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $selected_year === $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>
        <div class="table-container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <script>
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: '<?php echo addslashes($_SESSION['success_message']); ?>',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                </script>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <script>
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: '<?php echo addslashes($_SESSION['error_message']); ?>',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                </script>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            <table id="studentsTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th class="stt">STT</th>
                        <th class="plan">Kế hoạch hướng dẫn</th>
                        <th class="time">Thời gian hướng dẫn</th>
                        <th class="public">Công khai</th>
                        <th class="mssv">MSSV</th>
                        <th class="name">Họ tên</th>
                        <th class="topic">Đề tài</th>
                        <th class="score">Điểm</th>
                        <th class="action">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grouped_students)): ?>
                        <tr>
                            <td colspan="9" class="text-center">Chưa có sinh viên nào được hướng dẫn.</td>
                        </tr>
                    <?php else: ?>
                        <?php $stt = 0; ?>
                        <?php foreach ($grouped_students as $ma_huong_dan => $group): ?>
                            <?php $stt++; ?>
                            <form action="guide-students.php?year=<?php echo $selected_year; ?>" method="POST" id="form-<?php echo $stt; ?>">
                                <?php $first_student = true; ?>
                                <?php foreach ($group as $index => $student): ?>
                                    <tr>
                                        <?php if ($first_student): ?>
                                            <td class="stt" rowspan="<?php echo count($group); ?>"><?php echo $stt; ?></td>
                                            <td class="plan" rowspan="<?php echo count($group); ?>">
                                                <textarea name="ke_hoach_huong_dan" class="form-control plan-textarea" rows="4" required><?php echo htmlspecialchars($student['KeHoachHuongDan']); ?></textarea>
                                            </td>
                                            <td class="time" rowspan="<?php echo count($group); ?>">
                                                <?php echo htmlspecialchars($student['ThoiGianHuongDan']); ?>
                                            </td>
                                            <td class="public" rowspan="<?php echo count($group); ?>">
                                                <input type="checkbox" name="is_public" value="1" <?php echo $student['IsPublic'] == 1 ? 'checked' : ''; ?>>
                                            </td>
                                        <?php endif; ?>
                                        <td class="mssv"><?php echo htmlspecialchars($student['MaSinhVien']); ?></td>
                                        <td class="name"><?php echo htmlspecialchars($student['HoTen']); ?></td>
                                        <td class="topic">
                                            <textarea class="form-control topic-textarea" rows="4" readonly><?php echo htmlspecialchars($student['TenDeTai']); ?></textarea>
                                        </td>
                                        <td class="score">
                                            <input type="number" name="diem_tong_ket[]" class="form-control score-input" step="0.1" min="0" max="10" value="<?php echo $student['DiemTongKet'] !== null ? htmlspecialchars($student['DiemTongKet']) : ''; ?>" placeholder="Nhập điểm">
                                            <input type="hidden" name="ma_huong_dan[]" value="<?php echo $student['MaHuongDan']; ?>">
                                            <input type="hidden" name="ma_sinh_vien[]" value="<?php echo $student['MaSinhVien']; ?>">
                                        </td>
                                        <?php if ($first_student): ?>
                                            <td class="action" rowspan="<?php echo count($group); ?>">
                                                <button type="submit" name="update_score" class="btn btn-primary btn-sm">Cập nhật</button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php $first_student = false; ?>
                                <?php endforeach; ?>
                            </form>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {
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
                    if ($submenu.length > 0) {
                        e.preventDefault();
                        if ($submenu.is(':visible')) {
                            $submenu.hide();
                        } else {
                            $('.submenu').hide();
                            $submenu.show();
                        }
                    }
                }
            });
        });
    </script>

    <?php
    mysqli_close($dbc);
    include(BASE_PATH . '/Layout/footer.php');
    ?>
</body>

</html>