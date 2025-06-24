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
    $_SESSION['error_message'] = "Vui lòng đăng nhập để xem lịch giảng dạy.";
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
    $avatar = !empty($row['AnhDaiDien']) ? $row['AnhDaiDien'] : '/Public/img/avatar-default.png';
    $quyen = $row['Quyen'];
} else {
    $full_name = "Không xác định";
    $avatar = BASE_URL . '/Public/img/avatar-default.png';
    $quyen = 'GiangVien';
    $_SESSION['error_message'] = "Không tìm thấy thông tin tài khoản.";
}
mysqli_stmt_close($stmt);

// Quy định số bản ghi hiển thị trên mỗi trang
$records_per_page = 5;

//Lấy trang hiện tại từ tham số GET hoặc mặc định là trang 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Đếm tổng số bản ghi để tính số trang
$count_query = "
    SELECT COUNT(DISTINCT hp.MaHocPhan, hp.TenHocPhan, lhp.LopHocPhan, lhp.DiaDiem) as total
    FROM hocphan hp
    JOIN lichhocphan lhp ON hp.MaHocPhan = lhp.MaHocPhan
    JOIN lichgiangday lgd ON lhp.MaLichHocPhan = lgd.MaLichHocPhan
    WHERE hp.TrangThai = 1 AND lhp.TrangThai = 1 AND lgd.MaGiangVien = ?
";
$stmt = mysqli_prepare($dbc, $count_query);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_fetch_assoc($count_result)['total']; // Tổng số bản ghi
$total_pages = ceil($total_records / $records_per_page); // Tính tổng số trang

//  Xử lý làm mới danh sách học phần với phân trang qua AJAX
if (isset($_POST['action']) && $_POST['action'] === 'refresh_courses') {
    header('Content-Type: application/json'); // Thiết lập header trả về JSON
    $courses = [];
    $page = isset($_POST['page']) && is_numeric($_POST['page']) ? (int)$_POST['page'] : 1;
    $offset = ($page - 1) * $records_per_page;

    $query = "
        SELECT 
            hp.MaHocPhan, 
            hp.TenHocPhan, 
            lhp.LopHocPhan, 
            lhp.DiaDiem, 
            GROUP_CONCAT(
                CONCAT('Thứ ', lgd.LichGiang, ': ', lgd.GioBatDau, ' - ', COALESCE(lgd.GioKetThuc, 'Chưa xác định'))
                ORDER BY lgd.LichGiang, lgd.GioBatDau 
                SEPARATOR ', '
            ) AS ThoiGian
        FROM hocphan hp
        JOIN lichhocphan lhp ON hp.MaHocPhan = lhp.MaHocPhan
        JOIN lichgiangday lgd ON lhp.MaLichHocPhan = lgd.MaLichHocPhan
        WHERE hp.TrangThai = 1 AND lhp.TrangThai = 1 AND lgd.MaGiangVien = ?
        GROUP BY hp.MaHocPhan, hp.TenHocPhan, lhp.LopHocPhan, lhp.DiaDiem
        LIMIT ? OFFSET ?
    ";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, "sii", $user_id, $records_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = [
                'MaHocPhan' => $row['MaHocPhan'],
                'TenHocPhan' => $row['TenHocPhan'],
                'LopHocPhan' => $row['LopHocPhan'],
                'ThoiGian' => $row['ThoiGian'],
                'DiaDiem' => $row['DiaDiem']
            ];
        }
    }
    //// Trả về JSON chứa trạng thái, danh sách học phần, tổng số trang, và trang hiện tại
    echo json_encode(['success' => true, 'courses' => $courses, 'total_pages' => $total_pages, 'current_page' => $page]);
    exit();
}

// MODIFIED: Truy vấn danh sách học phần với phân trang
$courses = [];
$query = "
    SELECT 
        hp.MaHocPhan, 
        hp.TenHocPhan, 
        lhp.LopHocPhan, 
        lhp.DiaDiem, 
        GROUP_CONCAT(
            CONCAT('Thứ ', lgd.LichGiang, ': ', lgd.GioBatDau, ' - ', COALESCE(lgd.GioKetThuc, 'Chưa xác định'))
            ORDER BY lgd.LichGiang, lgd.GioBatDau 
            SEPARATOR ', '
        ) AS ThoiGian
    FROM hocphan hp
    JOIN lichhocphan lhp ON hp.MaHocPhan = lhp.MaHocPhan
    JOIN lichgiangday lgd ON lhp.MaLichHocPhan = lgd.MaLichHocPhan
    WHERE hp.TrangThai = 1 AND lhp.TrangThai = 1 AND lgd.MaGiangVien = ?
    GROUP BY hp.MaHocPhan, hp.TenHocPhan, lhp.LopHocPhan, lhp.DiaDiem
    LIMIT ? OFFSET ?
";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, "sii", $user_id, $records_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $courses[] = [
            'MaHocPhan' => $row['MaHocPhan'],
            'TenHocPhan' => $row['TenHocPhan'],
            'LopHocPhan' => $row['LopHocPhan'],
            'ThoiGian' => $row['ThoiGian'],
            'DiaDiem' => $row['DiaDiem']
        ];
    }
}

include(BASE_PATH . '/Layout/header.php');
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch giảng dạy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .content {
            margin-top: 20px;
        }

        .table th,
        .table td {
            vertical-align: middle;
            white-space: normal;
            word-wrap: break-word;
        }

        .table th:nth-child(1),
        .table td:nth-child(1) {
            width: 10%;
        }

        .table th:nth-child(2),
        .table td:nth-child(2) {
            width: 25%;
        }

        .table th:nth-child(3),
        .table td:nth-child(3) {
            width: 15%;
        }

        .table th:nth-child(4),
        .table td:nth-child(4) {
            width: 35%;
            min-width: 200px;
        }

        .table th:nth-child(5),
        .table td:nth-child(5) {
            width: 15%;
        }

        /* NEW: CSS cho phân trang */
        .pagination {
            margin-top: 20px;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .pagination a {
            margin: 0 5px;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            color: #333;
            border-radius: 4px;
        }

        .pagination a.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination a:hover {
            background-color: #f8f9fa;
        }

        .pagination a.disabled {
            color: #ccc;
            cursor: not-allowed;
            border-color: #ddd;
        }

        .pagination a.nav-arrow {
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .table {
                font-size: 14px;
            }

            .table th,
            .table td {
                padding: 6px;
            }

            .table th:nth-child(4),
            .table td:nth-child(4) {
                min-width: 150px;
            }
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
                                    <li><a href="./course-list.php">Lịch giảng dạy</a></li>
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
        <h4>Danh sách lịch giảng dạy</h4>
        <div id="courseTable">
            <?php if (empty($courses)): ?>
                <p>Chưa có học phần nào được phân công.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Mã học phần</th>
                                <th>Tên học phần</th>
                                <th>Lớp học phần</th>
                                <th>Thời gian</th>
                                <th>Địa điểm</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['MaHocPhan']); ?></td>
                                    <td><?php echo htmlspecialchars($course['TenHocPhan']); ?></td>
                                    <td><?php echo htmlspecialchars($course['LopHocPhan']); ?></td>
                                    <td><?php echo str_replace(', ', '<br>', htmlspecialchars($course['ThoiGian'])); ?></td>
                                    <td><?php echo htmlspecialchars($course['DiaDiem']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- NEW: Hiển thị phân trang với nút trước/sau -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="nav-arrow">
                            << /a>
                            <?php else: ?>
                                <a href="javascript:void(0)" class="nav-arrow disabled">
                                    << /a>
                                    <?php endif; ?>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                    <?php endfor; ?>
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>" class="nav-arrow">></a>
                                    <?php else: ?>
                                        <a href="javascript:void(0)" class="nav-arrow disabled">></a>
                                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            <?php if (isset($_SESSION['error_message'])): ?>
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: '<?php echo addslashes($_SESSION['error_message']); ?>',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            $('.menu-toggle').click(function() {
                $('.main-menu .menu').slideToggle();
            });

            $(document).click(function(e) {
                if (!$(e.target).closest('.dropdown').length && $(window).width() <= 768) {
                    $('.submenu').hide();
                }
            });

            $('.main-menu .dropdown > a, .menu-right .dropdown-menu > a').click(function(e) {
                if ($(window).width() <= 768) {
                    var $submenu = $(this).siblings('.submenu');
                    if ($submenu.length > 0) {
                        e.preventDefault();
                        if ($submenu.is(':visible')) {
                            $submenu ').hide();
                        } else {
                            $('.submenu').hide();
                            $submenu.show();
                        }
                    }
                }
            });

            // NEW: Gọi refreshCourses ngay khi trang tải để kiểm tra dữ liệu
            refreshCourses(<?php echo $page; ?>);
        });

        // NEW: Hàm làm mới danh sách học phần với phân trang
        function refreshCourses(page = <?php echo $page; ?>) {
            $.ajax({
                url: 'course-list.php',
                type: 'POST',
                data: {
                    action: 'refresh_courses',
                    page: page
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        let html = '';
                        if (response.courses.length === 0) {
                            html = '<p>Chưa có học phần nào được phân công.</p>';
                        } else {
                            html = `
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Mã học phần</th>
                                                <th>Tên học phần</th>
                                                <th>Lớp học phần</th>
                                                <th>Thời gian</th>
                                                <th>Địa điểm</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;
                            // Lặp qua danh sách học phần để tạo các hàng
                            response.courses.forEach(course => {
                                html += `
                                    <tr>
                                        <td>${course.MaHocPhan}</td>
                                        <td>${course.TenHocPhan}</td>
                                        <td>${course.LopHocPhan}</td>
                                        <td>${course.ThoiGian.replace(/, /g, '<br>')}</td>
                                        <td>${course.DiaDiem}</td>
                                    </tr>
                                `;
                            });
                            // Đóng bảng
                            html += '</tbody></table></div>';
                            // NEW: Thêm phân trang với nút trước/sau
                            html += '<div class="pagination">';
                            if (response.current_page > 1) {
                                html += `<a href="javascript:refreshCourses(${response.current_page - 1})" class="nav-arrow"><</a>`;
                            } else {
                                html += `<a href="javascript:void(0)" class="nav-arrow disabled"><</a>`;
                            }
                            // Liên kết cho từng trang
                            for (let i = 1; i <= response.total_pages; i++) {
                                html += `<a href="javascript:refreshCourses(${i})" class="${i == response.current_page ? 'active' : ''}">${i}</a>`;
                            }
                            if (response.current_page < response.total_pages) {
                                html += `<a href="javascript:refreshCourses(${response.current_page + 1})" class="nav-arrow">></a>`;
                            } else {
                                html += `<a href="javascript:void(0)" class="nav-arrow disabled">></a>`;
                            }
                            html += '</div>';
                        }
                        $('#courseTable').html(html);
                    } else {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: 'Không thể làm mới danh sách học phần',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Lỗi hệ thống khi làm mới danh sách',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                }
            });
        }
    </script>
</body>

<?php
mysqli_close($dbc);
include(BASE_PATH . '/Layout/footer.php');
?>

</html>