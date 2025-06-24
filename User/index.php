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

// Kiểm tra đăng nhập và lấy thông tin người dùng
$logged_in = isset($_SESSION['user_id']);
$calendar_events = [];
if ($logged_in) {
    $user_id = $_SESSION['user_id'];
    // Lấy thông tin người dùng
    $query = "SELECT g.HoGiangVien, g.TenGiangVien, g.AnhDaiDien, g.Email, t.Quyen 
              FROM giangvien g
              JOIN taikhoan t ON g.MaGiangVien = t.MaTaiKhoan
              WHERE t.MaTaiKhoan = ?";
    $stmt = mysqli_prepare($dbc, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            $full_name = $row['HoGiangVien'] . ' ' . $row['TenGiangVien'];
            $avatar = !empty($row['AnhDaiDien']) ? $row['AnhDaiDien'] : '/Public/img/default_avatar.jpg';
            $quyen = $row['Quyen'];
            $user_email = $row['Email'];
        } else {
            $full_name = "Không xác định";
            $avatar = BASE_URL . '/Public/img/default_avatar.jpg';
            $quyen = '';
            $user_email = '';
        }
        mysqli_stmt_close($stmt);
    }

    // Lấy dữ liệu lịch giảng dạy cho giao diện
    $query_teaching = "
        SELECT hp.TenHocPhan, lhp.LopHocPhan, lhp.ThoiGianBatDau, lhp.ThoiGianKetThuc, lhp.DiaDiem,
               lgd.LichGiang, lgd.GioBatDau, lgd.GioKetThuc
        FROM lichgiangday lgd
        JOIN lichhocphan lhp ON lgd.MaLichHocPhan = lhp.MaLichHocPhan
        JOIN hocphan hp ON lhp.MaHocPhan = hp.MaHocPhan
        WHERE lgd.MaGiangVien = ? AND lhp.TrangThai = 1 AND hp.TrangThai = 1";
    $stmt_teaching = mysqli_prepare($dbc, $query_teaching);
    if ($stmt_teaching) {
        mysqli_stmt_bind_param($stmt_teaching, "s", $user_id);
        mysqli_stmt_execute($stmt_teaching);
        $result_teaching = mysqli_stmt_get_result($stmt_teaching);

        while ($row = mysqli_fetch_assoc($result_teaching)) {
            $start_date = new DateTime($row['ThoiGianBatDau']);
            $end_date = new DateTime($row['ThoiGianKetThuc']);
            $day_of_week = $row['LichGiang'];
            $start_time = $row['GioBatDau'];
            $end_time = $row['GioKetThuc'];

            $dow_map = [1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => 4, 6 => 5, 7 => 6];
            $dow = $dow_map[$day_of_week];

            $current_date = clone $start_date;
            while ($current_date <= $end_date) {
                $day_of_week_current = $current_date->format('N');
                $adjusted_dow = $day_of_week_current == 7 ? 0 : $day_of_week_current;
                if ($adjusted_dow == $dow) {
                    $event_date = $current_date->format('Y-m-d');
                    $calendar_events[] = [
                        'title' => 'Giảng dạy: ' . $row['TenHocPhan'],
                        'start' => $event_date . 'T' . $start_time,
                        'end' => $event_date . 'T' . $end_time,
                        'description' => 'Lớp: ' . $row['LopHocPhan'] . ', Địa điểm: ' . $row['DiaDiem'],
                        'color' => '#2C69A0'
                    ];
                }
                $current_date->modify('+1 day');
            }
        }
        mysqli_stmt_close($stmt_teaching);
    }

    // Lấy công việc hành chính cho giao diện
    $query_admin = "
        SELECT cvc.TenCongViec, ttcvc.LoaiCongViec, ttcvc.NgayThucHien, ttcvc.GioBatDau, ttcvc.GioKetThuc, ttcvc.DiaDiem
        FROM thongtincongviechanhchinh ttcvc
        JOIN congviechanhchinh cvc ON ttcvc.MaCongViecHanhChinh = cvc.MaCongViecHanhChinh
        WHERE ttcvc.MaGiangVien = ? AND ttcvc.TrangThai = 1";
    $stmt_admin = mysqli_prepare($dbc, $query_admin);
    if ($stmt_admin) {
        mysqli_stmt_bind_param($stmt_admin, "s", $user_id);
        mysqli_stmt_execute($stmt_admin);
        $result_admin = mysqli_stmt_get_result($stmt_admin);

        while ($row = mysqli_fetch_assoc($result_admin)) {
            $calendar_events[] = [
                'title' => $row['TenCongViec'],
                'start' => $row['NgayThucHien'] . 'T' . $row['GioBatDau'],
                'end' => $row['NgayThucHien'] . 'T' . $row['GioKetThuc'],
                'description' => 'Loại: ' . $row['LoaiCongViec'] . ', Địa điểm: ' . $row['DiaDiem'],
                'color' => '#d9534f'
            ];
        }
        mysqli_stmt_close($stmt_admin);
    }
}

// Đóng kết nối database
if (isset($dbc) && $dbc) {
    mysqli_close($dbc);
}

include(BASE_PATH . '/Layout/header.php');
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống quản lý giảng viên</title>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css' rel='stylesheet' />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/locale/vi.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .notification {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }

        .notification.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .notification.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .calendar-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .calendar-controls select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 14px;
            min-width: 150px;
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
                            <?php if ($logged_in): ?>
                                <li class="dropdown">
                                    <a href="#">Lịch làm việc</a>
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
                            <?php else: ?>
                                <li><a href="./faculty-profile.php">Đội ngũ giảng viên</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php if ($logged_in): ?>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container content">
        <?php if ($logged_in): ?>
            <h4>Lịch làm việc</h4>
            <div class="calendar-controls">
                <select id="day-select">
                    <option value="">Chọn ngày</option>
                    <!-- Populated dynamically via JavaScript -->
                </select>
                <select id="month-select">
                    <?php
                    $months = [
                        1 => 'Tháng 1',
                        2 => 'Tháng 2',
                        3 => 'Tháng 3',
                        4 => 'Tháng 4',
                        5 => 'Tháng 5',
                        6 => 'Tháng 6',
                        7 => 'Tháng 7',
                        8 => 'Tháng 8',
                        9 => 'Tháng 9',
                        10 => 'Tháng 10',
                        11 => 'Tháng 11',
                        12 => 'Tháng 12'
                    ];
                    $currentMonth = date('n');
                    foreach ($months as $m => $name) {
                        echo "<option value='$m'" . ($m == $currentMonth ? " selected" : "") . ">$name</option>";
                    }
                    ?>
                </select>
                <select id="year-select">
                    <?php
                    $currentYear = date('Y');
                    for ($y = $currentYear - 5; $y <= $currentYear + 5; $y++) {
                        echo "<option value='$y'" . ($y == $currentYear ? " selected" : "") . ">$y</option>";
                    }
                    ?>
                </select>
                <select id="week-select">
                    <option value="">Chọn tuần</option>
                    <!-- Populated dynamically via JavaScript -->
                </select>
            </div>
            <div id="calendar"></div>
        <?php else: ?>
            <h4>Chào mừng đến với hệ thống quản lý giảng viên</h4>
            <p>Vui lòng <a href="<?php echo BASE_URL; ?>/User/login.php">đăng nhập</a> để xem lịch làm việc và các thông tin khác.</p>
        <?php endif; ?>
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

            <?php if ($logged_in): ?>
                // Hàm tạo danh sách tuần trong một tháng
                function getWeeksInMonth(year, month) {
                    let weeks = []; // Mảng lưu trữ các tuần
                    let firstDay = moment({
                        year: year,
                        month: month - 1,
                        day: 1
                    });
                    let lastDay = moment({
                        year: year,
                        month: month - 1
                    }).endOf('month');
                    let current = firstDay.clone().startOf('isoWeek'); // Start on Monday
                    let weekCount = 1;
                    // Duyệt cho đến khi current vượt quá lastDay
                    while (current.isSameOrBefore(lastDay, 'day')) {
                        let weekStart = current.clone();
                        let weekEnd = current.clone().endOf('isoWeek');
                        // Adjust weekStart and weekEnd to be within the month
                        if (weekStart.isBefore(firstDay)) {
                            weekStart = firstDay.clone();
                        }
                        if (weekEnd.isAfter(lastDay)) {
                            weekEnd = lastDay.clone();
                        }
                        weeks.push({
                            value: weekStart.format('YYYY-MM-DD'), // Keep YYYY-MM-DD for FullCalendar
                            text: `Tuần ${weekCount} (${weekStart.format('DD/MM/YYYY')} - ${weekEnd.format('DD/MM/YYYY')})`
                        });
                        current.add(1, 'week');
                        weekCount++;
                    }

                    console.log('Weeks generated:', weeks); // Debug log
                    return weeks;
                }

                //Hàm tạo danh sách ngày trong một tháng
                function getDaysInMonth(year, month) {
                    let days = [];
                    let daysInMonth = moment({
                        year: year,
                        month: month - 1
                    }).daysInMonth();
                    for (let d = 1; d <= daysInMonth; d++) {
                        let date = moment({
                            year: year,
                            month: month - 1,
                            day: d
                        });
                        days.push({
                            value: date.format('YYYY-MM-DD'), // Giá trị cho FullCalendar
                            text: `Ngày ${d} (${date.format('DD/MM/YYYY')})`
                        });
                    }
                    return days;
                }

                // Hàm điền danh sách tuần vào dropdown
                function populateWeekDropdown(year, month) {
                    let weeks = getWeeksInMonth(year, month); //Lấy danh sách tuần
                    let $weekSelect = $('#week-select'); // Chọn dropdown
                    $weekSelect.empty().append('<option value="">Chọn tuần</option>');
                    if (weeks.length === 0) {
                        console.warn('No weeks generated for year:', year, 'month:', month);
                    }
                    // Thêm từng tuần vào dropdown
                    weeks.forEach(week => {
                        $weekSelect.append(`<option value="${week.value}">${week.text}</option>`);
                    });
                }

                // Hàm điền danh sách ngày vào dropdown
                function populateDayDropdown(year, month) {
                    let days = getDaysInMonth(year, month);
                    let $daySelect = $('#day-select');
                    $daySelect.empty().append('<option value="">Chọn ngày</option>');
                    days.forEach(day => {
                        $daySelect.append(`<option value="${day.value}">${day.text}</option>`);
                    });
                }

                // Khởi tạo dropdown với năm và tháng hiện tại
                let currentYear = <?php echo date('Y'); ?>; //Lấy năm hiện tại
                let currentMonth = <?php echo date('n'); ?>; //Lấy tháng hiện tại
                console.log('Initializing with year:', currentYear, 'month:', currentMonth);
                populateDayDropdown(currentYear, currentMonth);
                populateWeekDropdown(currentYear, currentMonth);

                // Initialize FullCalendar
                $('#calendar').fullCalendar({
                    header: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'agendaDay,agendaWeek,month'
                    },
                    defaultView: 'agendaWeek',
                    locale: 'vi',
                    timeFormat: 'H:mm',
                    slotLabelFormat: 'H:mm',
                    allDaySlot: false,
                    minTime: '06:00:00',
                    maxTime: '22:00:00',
                    height: 'auto',
                    events: <?php echo json_encode($calendar_events); ?>,
                    eventRender: function(event, element) {
                        element.find('.fc-title').html('<strong>' + event.title + '</strong>');
                        element.attr('title', event.description || event.title);
                        if (event.color) {
                            element.css('background-color', event.color);
                        }
                    },
                    eventClick: function(calEvent, jsEvent, view) {
                        Swal.fire({
                            title: calEvent.title,
                            html: `<p><strong>Ngày:</strong> ${moment(calEvent.start).format('DD/MM/YYYY')}</p>
                                   <p><strong>Thời gian:</strong> ${moment(calEvent.start).format('H:mm')} - ${moment(calEvent.end).format('H:mm')}</p>
                                   <p><strong>Chi tiết:</strong> ${calEvent.description || 'Không có mô tả'}</p>`,
                            icon: 'info',
                            confirmButtonText: 'Đóng'
                        });
                    }
                });

                // Handle year, month, or day change
                $('#year-select, #month-select, #day-select').change(function() {
                    let year = parseInt($('#year-select').val());
                    let month = parseInt($('#month-select').val());
                    let day = $('#day-select').val();
                    console.log('Year/Month/Day changed:', year, month, day);

                    // Update week and day dropdowns
                    populateWeekDropdown(year, month);
                    populateDayDropdown(year, month);

                    // Update calendar view
                    if (day) {
                        $('#calendar').fullCalendar('gotoDate', day);
                        $('#calendar').fullCalendar('changeView', 'agendaDay');
                        $('#week-select').val(''); // Clear week selection
                    } else {
                        let newDate = moment({
                            year: year,
                            month: month - 1,
                            day: 1
                        });
                        $('#calendar').fullCalendar('gotoDate', newDate);
                        $('#calendar').fullCalendar('changeView', 'month');
                        $('#week-select').val(''); // Clear week selection
                    }
                });

                // Handle week change
                $('#week-select').change(function() {
                    let weekStart = $(this).val();
                    console.log('Week selected:', weekStart);
                    if (weekStart) {
                        $('#calendar').fullCalendar('gotoDate', weekStart);
                        $('#calendar').fullCalendar('changeView', 'agendaWeek');
                        $('#day-select').val(''); // Clear day selection
                    }
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
</body>
<?php include(BASE_PATH . '/Layout/footer.php'); ?>

</html>