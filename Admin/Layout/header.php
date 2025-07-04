<?php
session_start();
require_once '../../config.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Kiểm tra sự tồn tại của user_id trong session
if (!isset($_SESSION['user_id'])) {
    // Chuyển hướng về trang đăng nhập
    header("Location: " . BASE_URL . "/User/index.php");
    exit();
}

// Kiểm tra quyền User
if (isset($_SESSION['quyen']) && $_SESSION['quyen'] === 'User') {
    $_SESSION['error_message'] = "Bạn không đủ quyền hạn để truy cập vào trang quản trị.";
    header("Location: " . BASE_URL . "/User/index.php");
    exit();
}

// Lấy thông tin giảng viên và quyền từ cơ sở dữ liệu
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
    $quyen = $row['Quyen']; // Lấy giá trị Quyen từ bảng taikhoan
} else {
    // Xử lý trường hợp không tìm thấy dữ liệu
    $full_name = "Không xác định";
    $avatar = BASE_URL . '/Public/img/default_avatar.jpg';
    $quyen = 'Không xác định';
}

// Đóng statement nhưng không đóng kết nối $dbc
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/jqvmap/jqvmap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/daterangepicker/daterangepicker.css">
    <!-- summernote -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/summernote/summernote-bs4.min.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <?php
        $isIndexPage = basename($_SERVER['PHP_SELF']) === 'index.php';
        ?>

        <!-- Preloader -->
        <?php if ($isIndexPage): ?>
            <div class="preloader flex-column justify-content-center align-items-center">
                <img class="animation__shake" src="<?php echo BASE_URL ?>/Public/img/LogoNTU.jpg" alt="AdminLTELogo" height="60" width="60">
            </div>
        <?php endif; ?>

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="<?php echo BASE_URL ?>/Admin/work/index.php" class="nav-link">Home</a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="#" class="nav-link">Contact</a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- Navbar Search
                <li class="nav-item">
                    <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                        <i class="fas fa-search"></i>
                    </a>
                    <div class="navbar-search-block">
                        <form class="form-inline">
                            <div class="input-group input-group-sm">
                                <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                                <div class="input-group-append">
                                    <button class="btn btn-navbar" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </li> -->

                <!-- Messages Dropdown Menu
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-comments"></i>
                        <span class="badge badge-danger navbar-badge">3</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <a href="#" class="dropdown-item">
                            <div class="media">
                                <img src="<?php echo BASE_URL ?>/Public/dist/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                                <div class="media-body">
                                    <h3 class="dropdown-item-title">
                                        Brad Diesel
                                        <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                                    </h3>
                                    <p class="text-sm">Call me whenever you can...</p>
                                    <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
                    </div>
                </li> -->

                <!-- Notifications Dropdown Menu
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-bell"></i>
                        <span class="badge badge-warning navbar-badge">15</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item dropdown-header">15 Notifications</span>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-envelope mr-2"></i> 4 new messages
                            <span class="float-right text-muted text-sm">3 mins</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
                    </div>
                </li> -->

                <!-- Dark Mode Toggle Button -->
                <li class="nav-item">
                    <a class="nav-link" href="#" id="darkModeToggle" role="button">
                        <i class="fas fa-moon"></i>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>

                <!-- Logout Button -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL ?>/User/index.php" role="button" title="Quay lại">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </li>

                <!-- <li class="nav-item">
                    <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                        <i class="fas fa-th-large"></i>
                    </a>
                </li> -->
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="<?php echo BASE_URL ?>/Admin/work/index.php" class="brand-link">
                <img src="<?php echo BASE_URL ?>/Public/img/LogoNTU.jpg" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light"><?php echo htmlspecialchars($quyen); ?></span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user panel -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="<?php echo htmlspecialchars($avatar); ?>" class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="#" class="d-block"><?php echo htmlspecialchars($full_name); ?></a>
                    </div>
                </div>

                <!-- SidebarSearch Form -->
                <div class="form-inline">
                    <div class="input-group" data-widget="sidebar-search">
                        <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
                        <div class="input-group-append">
                            <button class="btn btn-sidebar">
                                <i class="fas fa-search fa-fw"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <!-- Menu Công việc -->
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fa-solid fa-briefcase" aria-hidden="true"></i>
                                <p>Công việc hành chính <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/work/index.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Danh sách công việc</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/work/create.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Thêm công việc</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Menu Khoa (Ẩn nếu Quyen là Admin) -->
                        <?php if ($quyen !== 'Admin'): ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fa-solid fa-building-columns"></i>
                                    <p>Khoa <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?php echo BASE_URL ?>/Admin/faculty/index.php" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Danh sách khoa</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?php echo BASE_URL ?>/Admin/faculty/create.php" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Thêm khoa</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Menu Giảng viên -->
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fa-solid fa-chalkboard-teacher"></i>
                                <p>Giảng Viên <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/lecturer/index.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Danh sách giảng viên</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/lecturer/create.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Thêm giảng viên</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/faculty-achievements/index.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Thành tựu giảng viên</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Menu Đánh Giá Viên Chức -->
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fa-solid fa-folder"></i>
                                <p>Hồ sơ đánh giá viên chức <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/employee-report/index.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Danh sách hồ sơ</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/employee-report/employee-evaluation.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Đánh giá viên chức</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Menu Hướng dẫn sinh viên -->
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <p>Hướng dẫn sinh viên <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/student-support/index.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Danh sách sinh viên</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Menu Học phần -->
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fa fa-book"></i>
                                <p>Học phần <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/course/index.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Danh sách học phần</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/course/create.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Thêm học phần</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Menu Lịch học phần -->
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fa-solid fa-calendar"></i>
                                <p>Lịch học phần <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/teaching-schedule/index.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Danh sách lịch học phần</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo BASE_URL ?>/Admin/teaching-schedule/create.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Thêm lịch học phần</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Menu Tài khoản (Ẩn nếu Quyen là Admin) -->
                        <?php if ($quyen !== 'Admin'): ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fa-solid fa-user"></i>
                                    <p>Tài khoản <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?php echo BASE_URL ?>/Admin/account/index.php" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Danh sách tài khoản</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?php echo BASE_URL ?>/Admin/account/User_Role.php" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Phân quyền</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Phần còn lại của menu -->
                        <li class="nav-header">LABELS</li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL ?>/Admin/teaching-resources/index.php" class="nav-link">
                                <i class="fa-solid fa-file"></i>
                                <p class="text">Tài liệu giảng dạy</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL ?>/Admin/scientific-research/index.php" class="nav-link">
                                <i class="fa-solid fa-microscope"></i>
                                <p class="text">Nghiên cứu khoa học</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>
    </div>

    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/jquery/jquery.min.js"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/jquery-ui/jquery-ui.min.js"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
        $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- ChartJS -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/chart.js/Chart.min.js"></script>
    <!-- Sparkline -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/sparklines/sparkline.js"></script>
    <!-- JQVMap -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/jqvmap/jquery.vmap.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
    <!-- jQuery Knob Chart -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/jquery-knob/jquery.knob.min.js"></script>
    <!-- daterangepicker -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/moment/moment.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/daterangepicker/daterangepicker.js"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- Summernote -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/summernote/summernote-bs4.min.js"></script>
    <!-- overlayScrollbars -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?php echo BASE_URL ?>/Public/dist/js/adminlte.js"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="<?php echo BASE_URL ?>/Public/dist/js/demo.js"></script>
    <!-- AdminLTE dashboard demo -->
    <script src="<?php echo BASE_URL ?>/Public/dist/js/pages/dashboard.js"></script>

    <!-- Dark Mode -->
    <script>
        $(document).ready(function() {
            const darkModeToggle = $('#darkModeToggle');
            const body = $('body');
            const icon = darkModeToggle.find('i');
            const navbar = $('.main-header.navbar');

            // Kiểm tra trạng thái dark mode từ localStorage
            if (localStorage.getItem('darkMode') === 'enabled') {
                body.addClass('dark-mode');
                icon.removeClass('fa-moon').addClass('fa-sun');
                navbar.removeClass('navbar-white navbar-light');
            }

            // Sự kiện click để chuyển đổi dark mode
            darkModeToggle.on('click', function(e) {
                e.preventDefault();
                body.toggleClass('dark-mode');

                if (body.hasClass('dark-mode')) {
                    localStorage.setItem('darkMode', 'enabled');
                    icon.removeClass('fa-moon').addClass('fa-sun');
                    navbar.removeClass('navbar-white navbar-light');
                } else {
                    localStorage.setItem('darkMode', 'disabled');
                    icon.removeClass('fa-sun').addClass('fa-moon');
                    navbar.addClass('navbar-white navbar-light');
                }
            });
        });
    </script>
</body>

</html>