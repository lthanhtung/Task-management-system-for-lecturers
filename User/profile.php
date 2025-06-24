<?php
session_start();
require_once '../config.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Vui lòng đăng nhập để truy cập trang này.";
    header("Location: login.php");
    exit();
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$query = "SELECT g.MaGiangVien, g.HoGiangVien, g.TenGiangVien, g.NgaySinh, g.GioiTinh, g.HocVi, g.ChucDanh, 
                 g.Email, g.SoDienThoai, g.MaKhoa, g.AnhDaiDien, g.GioiThieu, g.TrangThai, 
                 k.TenKhoa, t.Quyen
          FROM giangvien g
          JOIN khoa k ON g.MaKhoa = k.MaKhoa
          JOIN taikhoan t ON g.MaGiangVien = t.MaTaiKhoan
          WHERE t.MaTaiKhoan = ?";
$stmt = mysqli_prepare($dbc, $query);
if ($stmt === false) {
    $_SESSION['error_message'] = "Lỗi khi chuẩn bị truy vấn: " . mysqli_error($dbc);
    header("Location: index.php");
    exit();
}
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $full_name = $row['HoGiangVien'] . ' ' . $row['TenGiangVien'];
    $avatar = !empty($row['AnhDaiDien']) ? $row['AnhDaiDien'] : BASE_URL . '/Public/img/default_avatar.jpg';
    $ngay_sinh = $row['NgaySinh'];
    $gioi_tinh = $row['GioiTinh'] == 1 ? 'Nam' : 'Nữ';
    $hoc_vi = $row['HocVi'];
    $chuc_danh = $row['ChucDanh'];
    $email = $row['Email'];
    $so_dien_thoai = $row['SoDienThoai'];
    $khoa = $row['TenKhoa'];
    $gioi_thieu = $row['GioiThieu'];
    $trang_thai = $row['TrangThai'] == 1 ? 'Đang làm việc' : 'Nghỉ việc';
    $quyen = $row['Quyen'];
} else {
    $_SESSION['error_message'] = "Không tìm thấy thông tin giảng viên.";
    header("Location: index.php");
    exit();
}

// Lấy danh sách thành tựu của giảng viên
$thanh_tuu_query = "SELECT tt.MaThanhTuu, tt.TenThanhTuu, tttt.NamDat
                    FROM thanhtuugiangvien tt
                    JOIN thongtinthanhtuugiangvien tttt ON tt.MaThanhTuu = tttt.MaThanhTuu
                    WHERE tttt.MaGiangVien = ?
                    ORDER BY tttt.NamDat DESC";
$thanh_tuu_stmt = mysqli_prepare($dbc, $thanh_tuu_query);
mysqli_stmt_bind_param($thanh_tuu_stmt, "s", $user_id);
mysqli_stmt_execute($thanh_tuu_stmt);
$thanh_tuu_result = mysqli_stmt_get_result($thanh_tuu_stmt);
$thanh_tuu_list = [];
while ($row = mysqli_fetch_assoc($thanh_tuu_result)) {
    $thanh_tuu_list[] = $row;
}
mysqli_stmt_close($thanh_tuu_stmt);

// Lấy danh sách lịch tiếp sinh viên
$lich_tiep_query = "SELECT ThuTiepSinhVien, GioBatDau, GioKetThuc, DiaDiem 
                    FROM lichtiepsinhvien 
                    WHERE MaGiangVien = ? 
                    ORDER BY FIELD(ThuTiepSinhVien, 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật')";
$lich_tiep_stmt = mysqli_prepare($dbc, $lich_tiep_query);
mysqli_stmt_bind_param($lich_tiep_stmt, "s", $user_id);
mysqli_stmt_execute($lich_tiep_stmt);
$lich_tiep_result = mysqli_stmt_get_result($lich_tiep_stmt);
$lich_tiep_list = [];
while ($row = mysqli_fetch_assoc($lich_tiep_result)) {
    $lich_tiep_list[] = $row;
}
mysqli_stmt_close($lich_tiep_stmt);

// Lấy danh sách công bố khoa học của giảng viên
$nckh_query = "SELECT nckh.MaNguyenCuuKhoaHoc, nckh.TenNguyenCuuKhoaHoc, nckh.HinhThuc
               FROM nguyencuukhoahoc nckh
               JOIN thongtinnguyencuukhoahoc ttnckh ON nckh.MaNguyenCuuKhoaHoc = ttnckh.MaNguyenCuuKhoaHoc
               WHERE ttnckh.MaGiangVien = ?
               ORDER BY nckh.MaNguyenCuuKhoaHoc DESC";
$nckh_stmt = mysqli_prepare($dbc, $nckh_query);
mysqli_stmt_bind_param($nckh_stmt, "s", $user_id);
mysqli_stmt_execute($nckh_stmt);
$nckh_result = mysqli_stmt_get_result($nckh_stmt);
$nckh_list = [];
while ($row = mysqli_fetch_assoc($nckh_result)) {
    $nckh_list[] = $row;
}
mysqli_stmt_close($nckh_stmt);

// Lấy danh sách năm học từ kế hoạch hướng dẫn (chỉ lấy các năm công khai)
$years_query = "SELECT DISTINCT YEAR(ThoiGianHuongDan) AS NamHoc
                FROM huongdansinhvien
                WHERE MaGiangVien = ? AND ThoiGianHuongDan IS NOT NULL AND IsPublic = 1
                ORDER BY NamHoc DESC";
$years_stmt = mysqli_prepare($dbc, $years_query);
mysqli_stmt_bind_param($years_stmt, "s", $user_id);
mysqli_stmt_execute($years_stmt);
$years_result = mysqli_stmt_get_result($years_stmt);
$available_years = [];
while ($row = mysqli_fetch_assoc($years_result)) {
    $available_years[] = $row['NamHoc'];
}
mysqli_stmt_close($years_stmt);

// Xử lý năm học được chọn
$current_year = date('Y');
$selected_huongdan_year = isset($_GET['huongdan_year']) && in_array($_GET['huongdan_year'], $available_years)
    ? intval($_GET['huongdan_year'])
    : (!empty($available_years) ? $available_years[0] : $current_year);

// Lấy tất cả kế hoạch hướng dẫn sinh viên theo năm học được chọn (chỉ lấy nếu công khai)
$huongdan_query = "SELECT MaHuongDan, KeHoachHuongDan, ThoiGianHuongDan
                   FROM huongdansinhvien
                   WHERE MaGiangVien = ? AND YEAR(ThoiGianHuongDan) = ? AND IsPublic = 1
                   ORDER BY ThoiGianHuongDan DESC";
$huongdan_stmt = mysqli_prepare($dbc, $huongdan_query);
mysqli_stmt_bind_param($huongdan_stmt, "si", $user_id, $selected_huongdan_year);
mysqli_stmt_execute($huongdan_stmt);
$huongdan_result = mysqli_stmt_get_result($huongdan_stmt);
$huongdan_list = [];
while ($row = mysqli_fetch_assoc($huongdan_result)) {
    $huongdan_list[] = $row;
}
mysqli_stmt_close($huongdan_stmt);

// Xử lý chỉ số bản ghi kế hoạch hướng dẫn
$huongdan_index = isset($_GET['huongdan_index']) && is_numeric($_GET['huongdan_index']) ? intval($_GET['huongdan_index']) : 0;
$total_huongdan = count($huongdan_list);
if ($huongdan_index < 0 || $huongdan_index >= $total_huongdan) {
    $huongdan_index = 0; // Đặt lại về bản ghi đầu tiên nếu chỉ số không hợp lệ
}

// Lấy danh sách sinh viên được hướng dẫn cho bản ghi kế hoạch hiện tại
$selected_huongdan = !empty($huongdan_list) ? $huongdan_list[$huongdan_index] : null;
$sinhvien_list = [];
if ($selected_huongdan) {
    $sinhvien_query = "SELECT tthd.Masinhvien, tthd.HoSinhVien, tthd.TenSinhVien, tthd.TenDeTai, tthd.DiemTongKet, hd.ThoiGianHuongDan
                       FROM thongtinhuongdansinhvien tthd
                       JOIN huongdansinhvien hd ON tthd.MaHuongDan = hd.MaHuongDan
                       WHERE tthd.MaHuongDan = ? AND hd.IsPublic = 1
                       ORDER BY tthd.Masinhvien";
    $sinhvien_stmt = mysqli_prepare($dbc, $sinhvien_query);
    if (!$sinhvien_stmt) {
        $_SESSION['error_message'] = "Lỗi khi chuẩn bị truy vấn danh sách sinh viên: " . mysqli_error($dbc);
        header("Location: index.php");
        exit();
    }
    mysqli_stmt_bind_param($sinhvien_stmt, "s", $selected_huongdan['MaHuongDan']);
    mysqli_stmt_execute($sinhvien_stmt);
    $sinhvien_result = mysqli_stmt_get_result($sinhvien_stmt);
    while ($row = mysqli_fetch_assoc($sinhvien_result)) {
        $sinhvien_list[] = $row;
    }
    mysqli_stmt_close($sinhvien_stmt);
}

// Xử lý bộ lọc năm
$selected_year = isset($_GET['year']) && is_numeric($_GET['year']) ? intval($_GET['year']) : $current_year;
if ($selected_year > $current_year) {
    $selected_year = $current_year;
}

// Lấy xếp loại theo năm được chọn
$xep_loai_query = "SELECT tthdg.XepLoai, tthdg.NgayXepLoai
                   FROM thongtinhosodanhgia tthdg
                   JOIN hosodanhgiavienchuc hdg ON tthdg.MaHoSo = hdg.MaHoSo
                   WHERE hdg.MaHoSo = ? AND YEAR(tthdg.NgayXepLoai) = ?
                   ORDER BY tthdg.NgayXepLoai DESC
                   LIMIT 1";
$xep_loai_stmt = mysqli_prepare($dbc, $xep_loai_query);
mysqli_stmt_bind_param($xep_loai_stmt, "si", $user_id, $selected_year);
mysqli_stmt_execute($xep_loai_stmt);
$xep_loai_result = mysqli_stmt_get_result($xep_loai_stmt);
$xep_loai = mysqli_fetch_assoc($xep_loai_result) ?: ['XepLoai' => 'Chưa có xếp loại trong năm này', 'NgayXepLoai' => null];
mysqli_stmt_close($xep_loai_stmt);

// Lấy cài đặt hiển thị công khai
$public_settings_query = "SELECT ShowNgaySinh, ShowGioiTinh, ShowEmail, ShowSoDienThoai, ShowThanhTuu, ShowLichTiep, ShowNguyenCuuKhoaHoc 
                         FROM giangvien_public_settings 
                         WHERE MaGiangVien = ?";
$public_settings_stmt = mysqli_prepare($dbc, $public_settings_query);
mysqli_stmt_bind_param($public_settings_stmt, "s", $user_id);
mysqli_stmt_execute($public_settings_stmt);
$public_settings_result = mysqli_stmt_get_result($public_settings_stmt);
$public_settings = $public_settings_result->fetch_assoc() ?: [
    'ShowNgaySinh' => 1,
    'ShowGioiTinh' => 1,
    'ShowEmail' => 1,
    'ShowSoDienThoai' => 1,
    'ShowThanhTuu' => 1,
    'ShowLichTiep' => 1,
    'ShowNguyenCuuKhoaHoc' => 1
];
mysqli_stmt_close($public_settings_stmt);

// Pagination settings
$items_per_page = 10;

// Thành tựu pagination
$thanh_tuu_page = isset($_GET['thanh_tuu_page']) && is_numeric($_GET['thanh_tuu_page']) ? intval($_GET['thanh_tuu_page']) : 1;
if ($thanh_tuu_page < 1) $thanh_tuu_page = 1;
$total_thanh_tuu = count($thanh_tuu_list);
$total_thanh_tuu_pages = ceil($total_thanh_tuu / $items_per_page);
$thanh_tuu_start = ($thanh_tuu_page - 1) * $items_per_page;
$thanh_tuu_paginated = array_slice($thanh_tuu_list, $thanh_tuu_start, $items_per_page);

// Công bố khoa học pagination
$nckh_page = isset($_GET['nckh_page']) && is_numeric($_GET['nckh_page']) ? intval($_GET['nckh_page']) : 1;
if ($nckh_page < 1) $nckh_page = 1;
$total_nckh = count($nckh_list);
$total_nckh_pages = ceil($total_nckh / $items_per_page);
$nckh_start = ($nckh_page - 1) * $items_per_page;
$nckh_paginated = array_slice($nckh_list, $nckh_start, $items_per_page);

// Danh sách sinh viên pagination
$sinhvien_page = isset($_GET['sinhvien_page']) && is_numeric($_GET['sinhvien_page']) ? intval($_GET['sinhvien_page']) : 1;
if ($sinhvien_page < 1) $sinhvien_page = 1;
$total_sinhvien = count($sinhvien_list);
$total_sinhvien_pages = ceil($total_sinhvien / $items_per_page);
$sinhvien_start = ($sinhvien_page - 1) * $items_per_page;
$sinhvien_paginated = array_slice($sinhvien_list, $sinhvien_start, $items_per_page);

mysqli_close($dbc);

include(BASE_PATH . '/Layout/header.php');
?>

<head>
    <title>Trang cá nhân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .lecturer-detail {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .lecturer-detail img {
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e0e0e0;
            transition: transform 0.3s ease;
        }

        .lecturer-detail img:hover {
            transform: scale(1.05);
        }

        .lecturer-detail h2 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .lecturer-detail p {
            color: #555;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-back {
            background-color: #2C69A0;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-back:hover {
            background-color: #1a4e80;
            color: #fff;
            transform: translateX(-3px);
            text-decoration: none;
        }

        .btn-back i {
            font-size: 18px;
        }

        .form-check.form-switch .form-check-label {
            font-size: 14px;
            color: #34495e;
            margin-left: 10px;
        }

        .static-field .d-flex {
            gap: 10px;
        }

        .static-field .d-flex p {
            margin: 0;
        }

        .thanh-tuu-header {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        #gioi_thieu_content {
            white-space: pre-wrap;
            font-size: 16px;
            color: #555;
            margin-bottom: 10px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            background-color: #f8f9fa;
            min-height: 100px;
            box-sizing: border-box;
        }

        .table-responsive {
            margin-top: 10px;
            border-radius: 8px;
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .table th,
        .table td {
            padding: 12px;
            vertical-align: middle;
            text-align: left;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #34495e;
        }

        .table tbody tr:hover {
            background-color: #f1f3f5;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #e0e0e0;
        }

        .text-center {
            text-align: center;
        }

        .pagination-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .pagination-nav .btn {
            padding: 5px 10px;
            font-size: 14px;
        }

        .pagination-nav .btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .pagination-nav span {
            font-size: 16px;
            font-weight: 500;
            color: #34495e;
        }

        .year-filter {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .year-filter .btn {
            padding: 5px 10px;
            font-size: 14px;
        }

        .year-filter .btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .year-filter span {
            font-size: 16px;
            font-weight: 500;
            color: #34495e;
        }

        .record-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .record-nav .btn {
            padding: 5px 10px;
            font-size: 14px;
        }

        .record-nav .btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        @media (max-width: 768px) {
            .lecturer-detail {
                padding: 20px;
            }

            .lecturer-detail img {
                width: 100% !important;
            }

            .btn-back {
                padding: 10px 20px;
                font-size: 14px;
            }

            .static-field .d-flex {
                flex-direction: column;
                gap: 5px;
            }

            .thanh-tuu-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .table th,
            .table td {
                padding: 8px;
                font-size: 14px;
            }

            .table-responsive {
                margin-bottom: 20px;
            }

            .pagination-nav .btn,
            .year-filter .btn,
            .record-nav .btn {
                padding: 4px 8px;
                font-size: 12px;
            }

            .pagination-nav span,
            .year-filter span {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5" id="main-content">
        <div class="lecturer-detail">
            <a href="./index.php" class="btn-back mb-4"><i class="fas fa-arrow-left"></i> Quay lại trang chủ</a>

            <div class="row">
                <div class="col-md-4 text-center">
                    <div class="avatar-upload">
                        <img style="width: 200px; height: 200px;"
                            src="<?php echo htmlspecialchars($avatar); ?>"
                            class="img-fluid mb-3"
                            alt="<?php echo htmlspecialchars($full_name); ?>">
                    </div>
                    <h2><?php echo htmlspecialchars($full_name); ?></h2>
                    <p><b><?php echo htmlspecialchars($chuc_danh); ?>, Khoa <?php echo htmlspecialchars($khoa); ?></b></p>
                    <p style="font-size: 14px;"><?php echo htmlspecialchars($hoc_vi); ?></p>
                    <p class="mt-2"><strong>Xếp loại: </strong><?php echo htmlspecialchars($xep_loai['XepLoai']); ?>
                        <?php if ($xep_loai['NgayXepLoai']): ?>
                            (<?php echo (new DateTime($xep_loai['NgayXepLoai']))->format('d-m-Y'); ?>)
                        <?php endif; ?>
                    </p>
                    <div class="year-filter">
                        <a href="?year=<?php echo $selected_year - 1; ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>" class="btn btn-outline-secondary"><i class="fas fa-chevron-left"></i></a>
                        <span><?php echo $selected_year; ?></span>
                        <a href="?year=<?php echo $selected_year + 1; ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>" class="btn btn-outline-secondary <?php echo $selected_year >= $current_year ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-8">
                    <h5>Thông tin cá nhân</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <p class="mb-3 me-3">Ngày sinh: <?php echo $ngay_sinh ? (new DateTime($ngay_sinh))->format('d-m-Y') : 'Chưa cập nhật'; ?></p>
                                <p class="mb-3">(<?php echo $public_settings['ShowNgaySinh'] ? 'Hiển thị' : 'Ẩn'; ?>)</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <p class="mb-3 me-3">Giới tính: <?php echo htmlspecialchars($gioi_tinh); ?></p>
                                <p class="mb-3">(<?php echo $public_settings['ShowGioiTinh'] ? 'Hiển thị' : 'Ẩn'; ?>)</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <p class="mb-3 me-3">Email: <a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a></p>
                                <p class="mb-3">(<?php echo $public_settings['ShowEmail'] ? 'Hiển thị' : 'Ẩn'; ?>)</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <p class="mb-3 me-3">Số điện thoại: <?php echo htmlspecialchars($so_dien_thoai); ?></p>
                                <p class="mb-3">(<?php echo $public_settings['ShowSoDienThoai'] ? 'Hiển thị' : 'Ẩn'; ?>)</p>
                            </div>
                        </div>
                    </div>
                    <p>Trạng thái: <?php echo htmlspecialchars($trang_thai); ?></p>
                    <p>Khoa: <?php echo htmlspecialchars($khoa); ?></p>

                    <h5 class="mt-4">Giới thiệu</h5>
                    <div class="mb-3">
                        <p id="gioi_thieu_content"><?php echo htmlspecialchars($gioi_thieu); ?></p>
                    </div>

                    <div class="thanh-tuu-header mt-4">
                        <h5>Thành tựu</h5>
                        <p class="mt-1">(<?php echo $public_settings['ShowThanhTuu'] ? 'Hiển thị' : 'Ẩn'; ?>)</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 10%;">STT</th>
                                    <th scope="col" style="width: 60%;">Tên thành tựu</th>
                                    <th scope="col" style="width: 30%;">Năm đạt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($thanh_tuu_paginated)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Chưa có thành tựu nào.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($thanh_tuu_paginated as $index => $thanh_tuu): ?>
                                        <tr>
                                            <td><?php echo $thanh_tuu_start + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($thanh_tuu['TenThanhTuu']); ?></td>
                                            <td><?php echo $thanh_tuu['NamDat'] ? htmlspecialchars($thanh_tuu['NamDat']) : 'Không xác định'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if ($total_thanh_tuu > $items_per_page): ?>
                            <div class="pagination-nav">
                                <a href="?year=<?php echo $selected_year; ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page - 1; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>"
                                    class="btn btn-outline-secondary <?php echo $thanh_tuu_page <= 1 ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <span>Trang <?php echo $thanh_tuu_page; ?>/<?php echo $total_4thanh_tuu_pages; ?></span>
                                <a href="?year=<?php echo $selected_year; ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page + 1; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>"
                                    class="btn btn-outline-secondary <?php echo $thanh_tuu_page >= $total_thanh_tuu_pages ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="thanh-tuu-header mt-4">
                        <h5>Lịch tiếp sinh viên</h5>
                        <p class="mt-1">(<?php echo $public_settings['ShowLichTiep'] ? 'Hiển thị' : 'Ẩn'; ?>)</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 10%;">STT</th>
                                    <th scope="col" style="width: 25%;">Thứ</th>
                                    <th scope="col" style="width: 20%;">Giờ bắt đầu</th>
                                    <th scope="col" style="width: 20%;">Giờ kết thúc</th>
                                    <th scope="col" style="width: 25%;">Địa điểm</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lich_tiep_list)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Chưa có lịch tiếp sinh viên nào.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($lich_tiep_list as $index => $lich): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($lich['ThuTiepSinhVien']); ?></td>
                                            <td><?php echo substr($lich['GioBatDau'], 0, 5); ?></td>
                                            <td><?php echo substr($lich['GioKetThuc'], 0, 5); ?></td>
                                            <td><?php echo htmlspecialchars($lich['DiaDiem'] ?: 'Chưa xác định'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="thanh-tuu-header mt-4">
                        <h5>Công bố khoa học</h5>
                        <p class="mt-1">(<?php echo $public_settings['ShowNguyenCuuKhoaHoc'] ? 'Hiển thị' : 'Ẩn'; ?>)</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 10%;">STT</th>
                                    <th scope="col" style="width: 60%;">Tên nghiên cứu khoa học</th>
                                    <th scope="col" style="width: 30%;">Hình thức</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($nckh_paginated)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Chưa có công bố khoa học nào.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($nckh_paginated as $index => $nckh): ?>
                                        <tr>
                                            <td><?php echo $nckh_start + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($nckh['TenNguyenCuuKhoaHoc']); ?></td>
                                            <td><?php echo htmlspecialchars($nckh['HinhThuc']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if ($total_nckh > $items_per_page): ?>
                            <div class="pagination-nav">
                                <a href="?year=<?php echo $selected_year; ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page - 1; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>"
                                    class="btn btn-outline-secondary <?php echo $nckh_page <= 1 ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <span>Trang <?php echo $nckh_page; ?>/<?php echo $total_nckh_pages; ?></span>
                                <a href="?year=<?php echo $selected_year; ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page + 1; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>"
                                    class="btn btn-outline-secondary <?php echo $nckh_page >= $total_nckh_pages ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="thanh-tuu-header mt-4">
                        <h5>Kế hoạch hướng dẫn sinh viên</h5>
                    </div>
                    <div class="mb-3">
                        <div class="year-filter">
                            <?php if (!empty($available_years)): ?>
                                <a href="?year=<?php echo $selected_year; ?>&huongdan_year=<?php echo max(min($available_years), $selected_huongdan_year - 1); ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>"
                                    class="btn btn-outline-secondary <?php echo $selected_huongdan_year <= min($available_years) ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <span><?php echo $selected_huongdan_year; ?></span>
                                <a href="?year=<?php echo $selected_year; ?>&huongdan_year=<?php echo min(max($available_years), $selected_huongdan_year + 1); ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>"
                                    class="btn btn-outline-secondary <?php echo $selected_huongdan_year >= max($available_years) ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <span>Không có kế hoạch hướng dẫn nào được công khai</span>
                            <?php endif; ?>
                        </div>
                        <div class="table-responsive mt-3">
                            <table id="huongdan-table" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 10%;">STT</th>
                                        <th scope="col" style="width: 60%;">Kế hoạch hướng dẫn</th>
                                        <th scope="col" style="width: 30%;">Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($huongdan_list)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Không có thông tin...</td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td><?php echo $huongdan_index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($huongdan_list[$huongdan_index]['KeHoachHuongDan']); ?></td>
                                            <td>
                                                <?php echo $huongdan_list[$huongdan_index]['ThoiGianHuongDan']
                                                    ? (new DateTime($huongdan_list[$huongdan_index]['ThoiGianHuongDan']))->format('d-m-Y')
                                                    : 'Chưa xác định'; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <div class="record-nav mt-3">
                                <a href="?year=<?php echo $selected_year; ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index - 1; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>"
                                    class="btn btn-outline-secondary <?php echo $huongdan_index <= 0 ? 'disabled' : ''; ?>">
                                    <<
                                        </a>
                                        <span>Kế hoạch <?php echo $total_huongdan > 0 ? ($huongdan_index + 1) . '/' . $total_huongdan : '0/0'; ?></span>
                                        <a href="?year=<?php echo $selected_year; ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index + 1; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>"
                                            class="btn btn-outline-secondary <?php echo $huongdan_index >= $total_huongdan - 1 ? 'disabled' : ''; ?>">
                                            >>
                                        </a>
                            </div>
                        </div>
                    </div>

                    <div class="thanh-tuu-header mt-4">
                        <h5>Danh sách sinh viên</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 15%;">Mã sinh viên</th>
                                    <th scope="col" style="width: 25%;">Họ và tên</th>
                                    <th scope="col" style="width: 30%;">Tên đề tài</th>
                                    <th scope="col" style="width: 15%;">Điểm tổng kết</th>
                                    <th scope="col" style="width: 15%;">Thời gian hướng dẫn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sinhvien_paginated)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Chưa có sinh viên nào được hướng dẫn.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sinhvien_paginated as $index => $sinhvien): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sinhvien['Masinhvien']); ?></td>
                                            <td><?php echo htmlspecialchars($sinhvien['HoSinhVien'] . ' ' . $sinhvien['TenSinhVien']); ?></td>
                                            <td><?php echo htmlspecialchars($sinhvien['TenDeTai']); ?></td>
                                            <td><?php echo htmlspecialchars($sinhvien['DiemTongKet'] ?? 'Chưa có'); ?></td>
                                            <td>
                                                <?php
                                                echo $sinhvien['ThoiGianHuongDan']
                                                    ? (new DateTime($sinhvien['ThoiGianHuongDan']))->format('d-m-Y')
                                                    : 'Chưa xác định';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if ($total_sinhvien > $items_per_page): ?>
                            <div class="pagination-nav">
                                <a href="?year=<?php echo $selected_year; ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page - 1; ?>"
                                    class="btn btn-outline-secondary <?php echo $sinhvien_page <= 1 ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <span>Trang <?php echo $sinhvien_page; ?>/<?php echo $total_sinhvien_pages; ?></span>
                                <a href="?year=<?php echo $selected_year; ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page + 1; ?>"
                                    class="btn btn-outline-secondary <?php echo $sinhvien_page >= $total_sinhvien_pages ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <a href="update-profile.php" class="btn btn-primary mt-3">Chỉnh sửa trang cá nhân</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '<?php echo addslashes($_SESSION['success_message']); ?>',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

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
    </script>
</body>

<?php include(BASE_PATH . '/Layout/footer.php'); ?>