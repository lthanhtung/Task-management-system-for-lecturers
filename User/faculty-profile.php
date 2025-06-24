<?php
session_start();
require_once '../config.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Hàm chuyển đổi ChucDanh thành viết tắt
function formatChucDanh($chucDanh)
{
    switch (trim($chucDanh)) {
        case 'Giảng viên':
            return 'Gv';
        case 'Giảng viên chính':
            return 'Gvc';
        case 'Phó giáo sư':
            return 'Pgs';
        case 'Giáo sư':
            return 'Gs';
        default:
            return $chucDanh;
    }
}

// Hàm chuyển đổi HocVi thành viết tắt
function formatHocVi($hocVi)
{
    switch (trim($hocVi)) {
        case 'Thạc sĩ':
            return 'Ths';
        case 'Tiến sĩ':
            return 'Ts';
        default:
            return $hocVi;
    }
}

// Hàm chuẩn hóa chuỗi cho URL từ họ tên và tên khoa(nếu có)
function slugify($ho, $ten, $tenKhoa = '')
{
    // Ghép họ, tên và tên khoa (nếu có) thành một chuỗi
    $string = trim($ho . ' ' . $ten . ($tenKhoa ? ' ' . $tenKhoa : ''));
    // Thay thế ký tự tiếng Việt có dấu bằng ký tự ASCII
    $string = str_replace(
        array('à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ', 'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ', 'ì', 'í', 'ị', 'ỉ', 'ĩ', 'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ', 'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ', 'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ', 'đ', 'À', 'Á', 'Ạ', 'Ả', 'Ã', 'Â', 'Ầ', 'Ấ', 'Ậ', 'Ẩ', 'Ẫ', 'Ă', 'Ằ', 'Ắ', 'Ặ', 'Ẳ', 'Ẵ', 'È', 'É', 'Ẹ', 'Ẻ', 'Ẽ', 'Ê', 'Ề', 'Ế', 'Ệ', 'Ể', 'Ễ', 'Ì', 'Í', 'Ị', 'Ỉ', 'Ĩ', 'Ò', 'Ó', 'Ọ', 'Ỏ', 'Õ', 'Ô', 'Ồ', 'Ố', 'Ộ', 'Ổ', 'Ỗ', 'Ơ', 'Ờ', 'Ớ', 'Ợ', 'Ở', 'Ỡ', 'Ù', 'Ú', 'Ụ', 'Ủ', 'Ũ', 'Ư', 'Ừ', 'Ứ', 'Ự', 'Ử', 'Ữ', 'Ỳ', 'Ý', 'Ỵ', 'Ỷ', 'Ỹ', 'Đ'),
        array('a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y', 'd', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'Y', 'Y', 'Y', 'Y', 'Y', 'D'),
        $string
    );
    $string = str_replace(' ', '-', trim($string)); // Thay khoảng trắng bằng dấu gạch ngang
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Loại bỏ các ký tự không theo quy tắc
    $string = preg_replace('/-+/', '-', $string); // Gộp nhiều dấu gạch ngang thành một
    return strtolower($string); // trả về chữ thường
}

// Lấy tham số giảng viên từ URL
$giangvien_slug = isset($_GET['giangvien']) ? $_GET['giangvien'] : null;

// Lấy tham số lọc từ URL
$khoa_filter = isset($_GET['khoa']) ? $_GET['khoa'] : '';
$ten_filter = isset($_GET['ten']) ? trim($_GET['ten']) : '';

// Cài đặt Phân trang
$items_per_page = 10;

// Phân trang cho thành tựu
$thanh_tuu_page = isset($_GET['thanh_tuu_page']) && is_numeric($_GET['thanh_tuu_page']) ? intval($_GET['thanh_tuu_page']) : 1;
if ($thanh_tuu_page < 1) $thanh_tuu_page = 1;

//  Phân trang cho  Công bố khoa học
$nckh_page = isset($_GET['nckh_page']) && is_numeric($_GET['nckh_page']) ? intval($_GET['nckh_page']) : 1;
if ($nckh_page < 1) $nckh_page = 1;

//  Phân trang cho  Danh sách sinh viên
$sinhvien_page = isset($_GET['sinhvien_page']) && is_numeric($_GET['sinhvien_page']) ? intval($_GET['sinhvien_page']) : 1;
if ($sinhvien_page < 1) $sinhvien_page = 1;

//  Phân trang cho  Học phần
$hocphan_page = isset($_GET['hocphan_page']) && is_numeric($_GET['hocphan_page']) ? intval($_GET['hocphan_page']) : 1;
if ($hocphan_page < 1) $hocphan_page = 1;

// Lấy danh sách Khoa
$khoa_result = $dbc->query("SELECT MaKhoa, TenKhoa FROM khoa");
$khoas = $khoa_result->fetch_all(MYSQLI_ASSOC);

// Xem chi tiết giảng viên nếu có slug
if ($giangvien_slug) {
    //// Kiểm tra slug chỉ chứa chữ cái, số và dấu gạch ngang
    if (preg_match('/^[A-Za-z0-9\-]+$/', $giangvien_slug)) {
        $query = "
            SELECT g.MaGiangVien, g.HoGiangVien, g.TenGiangVien, g.NgaySinh, g.GioiThieu, g.GioiTinh, g.HocVi, 
                   g.ChucDanh, g.Email, g.SoDienThoai, g.AnhDaiDien, g.TrangThai, k.TenKhoa, k.MaKhoa,
                   s.ShowNgaySinh, s.ShowGioiTinh, s.ShowEmail, s.ShowSoDienThoai, s.ShowThanhTuu, s.ShowNguyenCuuKhoaHoc, s.ShowLichTiep
            FROM giangvien g
            LEFT JOIN khoa k ON g.MaKhoa = k.MaKhoa
            LEFT JOIN giangvien_public_settings s ON g.MaGiangVien = s.MaGiangVien
            WHERE g.TrangThai IN (1, 2)
        ";
        if ($khoa_filter) {
            $query .= " AND g.MaKhoa = ?";
        }

        $stmt = $dbc->prepare($query);
        if ($khoa_filter) {
            $stmt->bind_param("s", $khoa_filter);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $giangvien = null;

        // Tìm giảng viên khớp với slug
        while ($row = $result->fetch_assoc()) {
            $current_slug = slugify($row['HoGiangVien'], $row['TenGiangVien'], $row['TenKhoa']);
            if ($current_slug === $giangvien_slug) {
                $giangvien = $row;
                break;
            }
        }
        //Nếu có giảng viên
        if ($giangvien) {
            $full_name = $giangvien['HoGiangVien'] . ' ' . $giangvien['TenGiangVien'];
            $avatar = $giangvien['AnhDaiDien'] ?: BASE_URL . '/Public/img/avatar-default.png';
            $khoa = $giangvien['TenKhoa'] ?: 'Không xác định';
            $chuc_danh = $giangvien['ChucDanh'];
            $hoc_vi = $giangvien['HocVi'];
            $email = $giangvien['Email'];
            $gioi_thieu = $giangvien['GioiThieu'];
            $ngay_sinh = $giangvien['NgaySinh'];
            $gioi_tinh = $giangvien['GioiTinh'];
            $gioi_tinh_text = ($gioi_tinh == 1) ? 'Nam' : (($gioi_tinh == 2) ? 'Nữ' : 'Không xác định');
            $so_dien_thoai = $giangvien['SoDienThoai'];
            $trang_thai = $giangvien['TrangThai'];
            $trang_thai_text = ($trang_thai == 1) ? 'Đang dạy' : 'Nghỉ việc';
            $public_settings = [
                'ShowNgaySinh' => $giangvien['ShowNgaySinh'] ?? 1,
                'ShowGioiTinh' => $giangvien['ShowGioiTinh'] ?? 1,
                'ShowEmail' => $giangvien['ShowEmail'] ?? 1,
                'ShowSoDienThoai' => $giangvien['ShowSoDienThoai'] ?? 1,
                'ShowThanhTuu' => $giangvien['ShowThanhTuu'] ?? 1,
                'ShowNguyenCuuKhoaHoc' => $giangvien['ShowNguyenCuuKhoaHoc'] ?? 1,
                'ShowLichTiep' => $giangvien['ShowLichTiep'] ?? 1
            ];

            // Lấy danh sách thành tựu
            $thanh_tuu_query = "SELECT tt.TenThanhTuu, tttt.NamDat
                                FROM thanhtuugiangvien tt
                                JOIN thongtinthanhtuugiangvien tttt ON tt.MaThanhTuu = tttt.MaThanhTuu
                                WHERE tttt.MaGiangVien = ?
                                ORDER BY tttt.NamDat DESC";
            $thanh_tuu_stmt = mysqli_prepare($dbc, $thanh_tuu_query);
            mysqli_stmt_bind_param($thanh_tuu_stmt, "s", $giangvien['MaGiangVien']);
            mysqli_stmt_execute($thanh_tuu_stmt);
            $thanh_tuu_result = mysqli_stmt_get_result($thanh_tuu_stmt);
            $thanh_tuu_list = $thanh_tuu_result->fetch_all(MYSQLI_ASSOC);
            mysqli_stmt_close($thanh_tuu_stmt);

            // phân trang cho Thành tựu
            $total_thanh_tuu = count($thanh_tuu_list);
            $total_thanh_tuu_pages = ceil($total_thanh_tuu / $items_per_page);
            $thanh_tuu_start = ($thanh_tuu_page - 1) * $items_per_page;
            $thanh_tuu_paginated = array_slice($thanh_tuu_list, $thanh_tuu_start, $items_per_page);

            // Lấy danh sách công bố khoa học
            $nckh_query = "SELECT nckh.MaNguyenCuuKhoaHoc, nckh.TenNguyenCuuKhoaHoc, nckh.HinhThuc
                           FROM nguyencuukhoahoc nckh
                           JOIN thongtinnguyencuukhoahoc ttnckh ON nckh.MaNguyenCuuKhoaHoc = ttnckh.MaNguyenCuuKhoaHoc
                           WHERE ttnckh.MaGiangVien = ?
                           ORDER BY nckh.MaNguyenCuuKhoaHoc DESC";
            $nckh_stmt = mysqli_prepare($dbc, $nckh_query);
            mysqli_stmt_bind_param($nckh_stmt, "s", $giangvien['MaGiangVien']);
            mysqli_stmt_execute($nckh_stmt);
            $nckh_result = mysqli_stmt_get_result($nckh_stmt);
            $nckh_list = $nckh_result->fetch_all(MYSQLI_ASSOC);
            mysqli_stmt_close($nckh_stmt);

            // phân trang cho Công bố khoa học
            $total_nckh = count($nckh_list);
            $total_nckh_pages = ceil($total_nckh / $items_per_page);
            $nckh_start = ($nckh_page - 1) * $items_per_page;
            $nckh_paginated = array_slice($nckh_list, $nckh_start, $items_per_page);

            // Lấy danh sách năm học từ kế hoạch hướng dẫn (chỉ lấy các năm công khai)
            $years_query = "SELECT DISTINCT YEAR(ThoiGianHuongDan) AS NamHoc
                            FROM huongdansinhvien
                            WHERE MaGiangVien = ? AND IsPublic = 1 AND ThoiGianHuongDan IS NOT NULL
                            ORDER BY NamHoc DESC";
            $years_stmt = mysqli_prepare($dbc, $years_query);
            mysqli_stmt_bind_param($years_stmt, "s", $giangvien['MaGiangVien']);
            mysqli_stmt_execute($years_stmt);
            $years_result = mysqli_stmt_get_result($years_stmt);
            $available_years = [];
            while ($row = mysqli_fetch_assoc($years_result)) {
                $available_years[] = $row['NamHoc'];
            }
            mysqli_stmt_close($years_stmt);

            // Xử lý năm học được chọn
            $current_year = date('Y'); // Lấy năm hiện tại
            $selected_huongdan_year = isset($_GET['huongdan_year']) && is_numeric($_GET['huongdan_year'])
                ? intval($_GET['huongdan_year'])
                : (!empty($available_years) ? $available_years[0] : $current_year); // Lấy năm đầu tiên hoặc năm hiện tại

            // Nếu không có năm nào khả dụng, đặt mặc định là năm hiện tại
            if (empty($available_years)) {
                $available_years = [$current_year];
            }

            // Lấy danh sách kế hoạch hướng dẫn sinh viên theo năm được chọn
            $huongdan_query = "SELECT MaHuongDan, KeHoachHuongDan, ThoiGianHuongDan
                               FROM huongdansinhvien
                               WHERE MaGiangVien = ? AND YEAR(ThoiGianHuongDan) = ? AND IsPublic = 1
                               ORDER BY ThoiGianHuongDan DESC";
            $huongdan_stmt = mysqli_prepare($dbc, $huongdan_query);
            mysqli_stmt_bind_param($huongdan_stmt, "si", $giangvien['MaGiangVien'], $selected_huongdan_year);
            mysqli_stmt_execute($huongdan_stmt);
            $huongdan_result = mysqli_stmt_get_result($huongdan_stmt);
            $huongdan_list = $huongdan_result->fetch_all(MYSQLI_ASSOC);
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
                mysqli_stmt_bind_param($sinhvien_stmt, "s", $selected_huongdan['MaHuongDan']);
                mysqli_stmt_execute($sinhvien_stmt);
                $sinhvien_result = mysqli_stmt_get_result($sinhvien_stmt);
                $sinhvien_list = $sinhvien_result->fetch_all(MYSQLI_ASSOC);
                mysqli_stmt_close($sinhvien_stmt);
            }

            //  Phân trang sinh viên
            $total_sinhvien = count($sinhvien_list);
            $total_sinhvien_pages = ceil($total_sinhvien / $items_per_page);
            $sinhvien_start = ($sinhvien_page - 1) * $items_per_page;
            $sinhvien_paginated = array_slice($sinhvien_list, $sinhvien_start, $items_per_page);

            // Lấy lịch tiếp sinh viên
            $lichtiep_query = "SELECT ThuTiepSinhVien, GioBatDau, GioKetThuc, DiaDiem
                               FROM lichtiepsinhvien
                               WHERE MaGiangVien = ?
                               ORDER BY FIELD(ThuTiepSinhVien, 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật')";
            $lichtiep_stmt = mysqli_prepare($dbc, $lichtiep_query);
            mysqli_stmt_bind_param($lichtiep_stmt, "s", $giangvien['MaGiangVien']);
            mysqli_stmt_execute($lichtiep_stmt);
            $lichtiep_result = mysqli_stmt_get_result($lichtiep_stmt);
            $lichtiep_list = $lichtiep_result->fetch_all(MYSQLI_ASSOC);
            mysqli_stmt_close($lichtiep_stmt);

            // Lấy danh sách học phần giảng dạy
            $hocphan_query = "
                SELECT hp.TenHocPhan
                FROM hocphan hp
                JOIN lichhocphan lhp ON hp.MaHocPhan = lhp.MaHocPhan
                JOIN lichgiangday lg ON lhp.MaLichHocPhan = lg.MaLichHocPhan
                WHERE lg.MaGiangVien = ?
                AND hp.TrangThai = 1
                AND lhp.TrangThai = 1
                GROUP BY hp.MaHocPhan
                ORDER BY hp.TenHocPhan ASC";
            $hocphan_stmt = mysqli_prepare($dbc, $hocphan_query);
            mysqli_stmt_bind_param($hocphan_stmt, "s", $giangvien['MaGiangVien']);
            mysqli_stmt_execute($hocphan_stmt);
            $hocphan_result = mysqli_stmt_get_result($hocphan_stmt);
            $hocphan_list = $hocphan_result->fetch_all(MYSQLI_ASSOC);
            mysqli_stmt_close($hocphan_stmt);

            // Phân trang học phần
            $total_hocphan = count($hocphan_list);
            $total_hocphan_pages = ceil($total_hocphan / $items_per_page);
            $hocphan_start = ($hocphan_page - 1) * $items_per_page;
            $hocphan_paginated = array_slice($hocphan_list, $hocphan_start, $items_per_page);
        } else {
            $_SESSION['error'] = "Không tìm thấy giảng viên.";
            header("Location: " . BASE_URL . "/User/faculty-profile.php");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "URL không hợp lệ.";
        header("Location: " . BASE_URL . "/User/faculty-profile.php");
        exit();
    }
} else {
    // Xem danh sách giảng viên
    $query = "
        SELECT g.MaGiangVien, g.HoGiangVien, g.TenGiangVien, g.AnhDaiDien, g.ChucDanh, g.HocVi, k.TenKhoa
        FROM giangvien g
        LEFT JOIN khoa k ON g.MaKhoa = k.MaKhoa
        WHERE g.TrangThai IN (1, 2)
    ";

    $params = [];
    $types = '';
    if ($khoa_filter) {
        $query .= " AND g.MaKhoa = ?";
        $params[] = $khoa_filter;
        $types .= 's';
    }
    if ($ten_filter) {
        $query .= " AND LOWER(CONCAT(g.HoGiangVien, ' ', g.TenGiangVien)) LIKE LOWER(?)";
        $params[] = '%' . $ten_filter . '%';
        $types .= 's';
    }

    $stmt = $dbc->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $giangviens = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$dbc->close();

include(BASE_PATH . '/Layout/header.php');
?>

<!doctype html>
<html lang="vi">

<head>
    <title><?php echo isset($full_name) ? htmlspecialchars($full_name) . ' - ' . htmlspecialchars($chuc_danh) . ', Khoa ' . htmlspecialchars($khoa) : 'Danh sách giảng viên'; ?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <?php if (isset($full_name)): ?>
        <meta name="description" content="Trang cá nhân của <?php echo htmlspecialchars($full_name); ?>, <?php echo htmlspecialchars($chuc_danh); ?>, Khoa <?php echo htmlspecialchars($khoa); ?>">
        <meta name="keywords" content="<?php echo htmlspecialchars($full_name); ?>, <?php echo htmlspecialchars($chuc_danh); ?>, <?php echo htmlspecialchars($khoa); ?>">
    <?php else: ?>
        <meta name="description" content="Danh sách giảng viên của trường, tra cứu thông tin giảng viên theo khoa và tên.">
        <meta name="keywords" content="danh sách giảng viên, tra cứu giảng viên, thông tin giảng viên">
    <?php endif; ?>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            border: 3px solid #007bff;
            transition: transform 0.3s ease;
            width: 200px;
            height: 200px;
        }

        .lecturer-detail img:hover {
            transform: scale(1.05);
        }

        .lecturer-detail h2 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 2rem;
        }

        .lecturer-detail h3 {
            color: #34495e;
            font-weight: 500;
            margin-top: 20px;
        }

        .lecturer-detail p {
            color: #555;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .lecturer-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            text-align: center;
            padding: 10px;
            background-color: #fff;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .lecturer-card:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .lecturer-card img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .lecturer-card h5 {
            margin: 0;
            font-size: 1rem;
            color: #007bff;
            text-transform: uppercase;
        }

        .search-form {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }

        .search-form select,
        .search-form input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .search-form button {
            padding: 8px 15px;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
        }

        .search-form button:hover {
            background-color: #0056b3;
        }

        .social-links {
            margin-top: 20px;
        }

        .social-links a {
            font-size: 1.5rem;
            margin: 0 10px;
            color: #007bff;
        }

        .social-links a:hover {
            color: #0056b3;
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

        .btn-back {
            background-color: #2C69A0;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 16px;
            text-decoration: none;
        }

        .btn-back:hover {
            background-color: #1a4e80;
            color: #fff;
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

        .year-filters {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .year-filters .btn {
            padding: 5px 10px;
            font-size: 14px;
        }

        .year-filters .btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .year-filters span {
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

        #gioi_thieu_content {
            white-space: pre-wrap;
            font-size: 16px;
            color: #555;
            margin-bottom: 10px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            background-color: #f8f9fa;
            width: 100%;
            resize: none;
            overflow: hidden;
            box-sizing: border-box;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .lecturer-detail {
                padding: 20px;
            }

            .lecturer-detail img {
                width: 150px;
                height: 150px;
            }

            .lecturer-detail h2 {
                font-size: 1.5rem;
            }

            .lecturer-detail p {
                font-size: 1rem;
            }

            .search-form {
                flex-direction: column;
                gap: 10px;
            }

            .search-form select,
            .search-form input,
            .search-form button {
                width: 100%;
                padding: 10px;
            }

            .lecturer-card img {
                height: 150px;
            }

            .lecturer-card h5 {
                font-size: 0.9rem;
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
            .year-filters .btn,
            .record-nav .btn {
                padding: 4px 8px;
                font-size: 12px;
            }

            .pagination-nav span,
            .year-filters span {
                font-size: 14px;
            }
        }

        .main-menu .menu {
            display: block !important;
        }
    </style>
</head>

<body>
    <div class="menu-container">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 col-6">
                    <nav class="main-menu">
                        <ul class="menu" style="padding-top: 10px;">
                            <li style="width: fit-content;" class="dropdown">
                                <a href="./index.php">Trang chủ</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="container mt-4" id="main-content">
        <?php if ($giangvien_slug && isset($giangvien)): ?>
            <!-- Hiển thị trang cá nhân giảng viên -->
            <div id="lecturer-detail" class="lecturer-detail">
                <a href="<?php echo BASE_URL; ?>/User/faculty-profile.php<?php echo $khoa_filter ? '?khoa=' . urlencode($khoa_filter) : ''; ?><?php echo $ten_filter ? ($khoa_filter ? '&' : '?') . 'ten=' . urlencode($ten_filter) : ''; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>&hocphan_page=<?php echo $hocphan_page; ?>" class="btn-back mb-4" onclick="showLecturerList(event)">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="<?php echo htmlspecialchars($avatar); ?>" class="img-fluid mb-1" alt="<?php echo htmlspecialchars($full_name); ?>">
                        <h2><?php echo htmlspecialchars($full_name); ?></h2>
                        <p><strong><?php echo htmlspecialchars($chuc_danh); ?>, <?php echo htmlspecialchars($hoc_vi); ?></strong></p>
                        <p>Khoa: <?php echo htmlspecialchars($khoa); ?></p>
                        <p>Trạng thái: <?php echo htmlspecialchars($trang_thai_text); ?></p>
                    </div>
                    <div class="col-md-8">
                        <h3><u>Thông tin cá nhân</u></h3>
                        <?php if ($public_settings['ShowNgaySinh'] && $ngay_sinh): ?>
                            <p>Ngày sinh: <?php echo (new DateTime($ngay_sinh))->format('d-m-Y'); ?></p>
                        <?php endif; ?>
                        <?php if ($public_settings['ShowGioiTinh']): ?>
                            <p>Giới tính: <?php echo htmlspecialchars($gioi_tinh_text); ?></p>
                        <?php endif; ?>
                        <?php if ($public_settings['ShowEmail'] && $email): ?>
                            <p>Email: <a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a></p>
                        <?php endif; ?>
                        <?php if ($public_settings['ShowSoDienThoai'] && $so_dien_thoai): ?>
                            <p>Số điện thoại: <?php echo htmlspecialchars($so_dien_thoai); ?></p>
                        <?php endif; ?>
                        <p>Website: <a href="#" class="text-primary">https://users.soict.hust.edu.vn/<?php echo strtolower(slugify($full_name, '', '')); ?>/</a></p>
                        <?php if ($gioi_thieu): ?>
                            <h3 class="mt-4"><u>Giới thiệu</u></h3>
                            <div class="mb-3">
                                <textarea id="gioi_thieu_content" readonly><?php echo htmlspecialchars($gioi_thieu); ?></textarea>
                            </div>
                        <?php endif; ?>
                        <?php if ($public_settings['ShowThanhTuu']): ?>
                            <h3 class="mt-4"><u>Thành tựu</u></h3>
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
                                        <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page - 1; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>&hocphan_page=<?php echo $hocphan_page; ?>"
                                            class="btn btn-outline-secondary <?php echo $thanh_tuu_page <= 1 ? 'disabled' : ''; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                        <span>Trang <?php echo $thanh_tuu_page; ?>/<?php echo $total_thanh_tuu_pages; ?></span>
                                        <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page + 1; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>&hocphan_page=<?php echo $hocphan_page; ?>"
                                            class="btn btn-outline-secondary <?php echo $thanh_tuu_page >= $total_thanh_tuu_pages ? 'disabled' : ''; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($public_settings['ShowNguyenCuuKhoaHoc']): ?>
                            <h3 class="mt-4"><u>Công bố khoa học</u></h3>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" style="width: 10%;">STT</th>
                                            <th scope="col" style="width: 70%;text-align: center;">Tên nghiên cứu khoa học</th>
                                            <th scope="col" style="width: 20%;text-align: center;">Hình thức</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($nckh_paginated)): ?>
                                            <tr>
                                                <td colspan="2" class="text-center">Chưa cập nhật...</td>
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
                                        <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page - 1; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>&hocphan_page=<?php echo $hocphan_page; ?>"
                                            class="btn btn-outline-secondary <?php echo $nckh_page <= 1 ? 'disabled' : ''; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                        <span>Trang <?php echo $nckh_page; ?>/<?php echo $total_nckh_pages; ?></span>
                                        <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page + 1; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>&hocphan_page=<?php echo $hocphan_page; ?>"
                                            class="btn btn-outline-secondary <?php echo $nckh_page >= $total_nckh_pages ? 'disabled' : ''; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($public_settings['ShowLichTiep']): ?>
                            <h3 class="mt-4"><u>Lịch tiếp sinh viên</u></h3>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" style="width: 10%;">STT</th>
                                            <th scope="col" style="width: 20%;">Thứ</th>
                                            <th scope="col" style="width: 20%;">Giờ bắt đầu</th>
                                            <th scope="col" style="width: 20%;">Giờ kết thúc</th>
                                            <th scope="col" style="width: 30%;">Địa điểm</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($lichtiep_list)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Chưa có lịch tiếp sinh viên.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($lichtiep_list as $index => $lichtiep): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($lichtiep['ThuTiepSinhVien']); ?></td>
                                                    <td><?php echo date('H:i', strtotime($lichtiep['GioBatDau'])); ?></td>
                                                    <td><?php echo date('H:i', strtotime($lichtiep['GioKetThuc'])); ?></td>
                                                    <td><?php echo htmlspecialchars($lichtiep['DiaDiem']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        <h3 class="mt-4"><u>Kế hoạch hướng dẫn (<?php echo $selected_huongdan_year; ?>)</u></h3>
                        <div class="year-filters">
                            <?php if (!empty($available_years)): ?>
                                <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&huongdan_year=<?php echo max(min($available_years), $selected_huongdan_year - 1); ?>&huongdan_index=0&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>&hocphan_page=<?php echo $hocphan_page; ?>"
                                    class="btn btn-outline-secondary <?php echo $selected_huongdan_year <= min($available_years) ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <span><?php echo $selected_huongdan_year; ?></span>
                                <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&huongdan_year=<?php echo min(max($available_years), $selected_huongdan_year + 1); ?>&huongdan_index=0&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>&hocphan_page=<?php echo $hocphan_page; ?>"
                                    class="btn btn-outline-secondary <?php echo $selected_huongdan_year >= max($available_years) ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <span>Không có kế hoạch hướng dẫn nào được công khai</span>
                            <?php endif; ?>
                        </div>
                        <div class="table-responsive">
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
                                            <td><?php echo $huongdan_list[$huongdan_index]['ThoiGianHuongDan'] ? (new DateTime($huongdan_list[$huongdan_index]['ThoiGianHuongDan']))->format('d-m-Y') : 'Chưa xác định'; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <div class="record-nav mt-3">
                                <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index - 1; ?>&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>&hocphan_page=<?php echo $hocphan_page; ?>"
                                    class="btn btn-outline-secondary <?php echo $huongdan_index <= 0 ? 'disabled' : ''; ?>">
                                    <<
                                        </a>
                                        <span>Kế hoạch <?php echo $total_huongdan > 0 ? ($huongdan_index + 1) . '/' . $total_huongdan : '0/0'; ?></span>
                                        <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index + 1; ?>&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>&hocphan_page=<?php echo $hocphan_page; ?>"
                                            class="btn btn-outline-secondary <?php echo $huongdan_index >= $total_huongdan - 1 ? 'disabled' : ''; ?>">
                                            >>
                                        </a>
                            </div>
                        </div>
                        <h3 class="mt-4"><u>Danh sách sinh viên</u></h3>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 15%;">Mã sinh viên</th>
                                        <th scope="col" style="width: 25%;">Họ và tên</th>
                                        <th scope="col" style="width: 30%;">Tên đề tài</th>
                                        <th scope="col" style="width: 15%;">Điểm</th>
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
                                    <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page - 1; ?>&hocphan_page=<?php echo $hocphan_page; ?>"
                                        class="btn btn-outline-secondary <?php echo $sinhvien_page <= 1 ? 'disabled' : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <span>Trang <?php echo $sinhvien_page; ?>/<?php echo $total_sinhvien_pages; ?></span>
                                    <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page + 1; ?>&hocphan_page=<?php echo $hocphan_page; ?>"
                                        class="btn btn-outline-secondary <?php echo $sinhvien_page >= $total_sinhvien_pages ? 'disabled' : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="mt-4"><u>Giảng dạy</u></h3>
                        <div class="table-responsive">
                            <table style="width: 50%;" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 10%;">STT</th>
                                        <th scope="col" style="width: 90%; text-align: center;">Học phần</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($hocphan_paginated)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center">Chưa có học phần nào.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($hocphan_paginated as $index => $hocphan): ?>
                                            <tr>
                                                <td><?php echo $hocphan_start + $index + 1; ?></td>
                                                <td style="text-align: center;"><?php echo htmlspecialchars($hocphan['TenHocPhan']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php if ($total_hocphan > $items_per_page): ?>
                                <div class="pagination-nav">
                                    <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>&hocphan_page=<?php echo $hocphan_page - 1; ?>"
                                        class="btn btn-outline-secondary <?php echo $hocphan_page <= 1 ? 'disabled' : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <span>Trang <?php echo $hocphan_page; ?>/<?php echo $total_hocphan_pages; ?></span>
                                    <a href="?giangvien=<?php echo urlencode($giangvien_slug); ?>&khoa=<?php echo urlencode($khoa_filter); ?>&ten=<?php echo urlencode($ten_filter); ?>&huongdan_year=<?php echo $selected_huongdan_year; ?>&huongdan_index=<?php echo $huongdan_index; ?>&thanh_tuu_page=<?php echo $thanh_tuu_page; ?>&nckh_page=<?php echo $nckh_page; ?>&sinhvien_page=<?php echo $sinhvien_page; ?>&hocphan_page=<?php echo $hocphan_page + 1; ?>"
                                        class="btn btn-outline-secondary <?php echo $hocphan_page >= $total_hocphan_pages ? 'disabled' : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Hiển thị danh sách giảng viên -->
            <div id="lecturer-list-view">
                <h1>Danh sách giảng viên</h1>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                <div class="search-form" id="search-form">
                    <select id="khoa-filter" name="khoa">
                        <option value="">Tất cả Khoa</option>
                        <?php foreach ($khoas as $khoa): ?>
                            <option value="<?php echo htmlspecialchars($khoa['MaKhoa']); ?>"
                                <?php echo ($khoa_filter == $khoa['MaKhoa'] || (!$khoa_filter && $khoa['MaKhoa'] == 'CNTT')) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($khoa['TenKhoa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="ten-filter" name="ten" placeholder="Tên Cán bộ" value="<?php echo htmlspecialchars($ten_filter); ?>">
                    <button type="button" onclick="searchLecturers()"><i class="fas fa-search"></i></button>
                </div>
                <div style="padding: 20px 0;" class="row row-cols-1 row-cols-sm-2 row-cols-md-4 row-cols-lg-5 g-4" id="lecturer-list">
                    <?php if (empty($giangviens)): ?>
                        <p>Không tìm thấy giảng viên nào phù hợp.</p>
                    <?php else: ?>
                        <?php foreach ($giangviens as $gv): ?>
                            <div class="col">
                                <div class="lecturer-card">
                                    <a href="<?php echo BASE_URL; ?>/User/faculty-profile.php?giangvien=<?php echo slugify($gv['HoGiangVien'], $gv['TenGiangVien'], $gv['TenKhoa']); ?><?php echo $khoa_filter ? '&khoa=' . urlencode($khoa_filter) : ''; ?><?php echo $ten_filter ? ($khoa_filter ? '&' : '&') . 'ten=' . urlencode($ten_filter) : ''; ?>&thanh_tuu_page=1&nckh_page=1&sinhvien_page=1&hocphan_page=1" onclick="loadLecturerDetail(event, this.href)">
                                        <img src="<?php echo htmlspecialchars($gv['AnhDaiDien'] ?: BASE_URL . '/Public/img/default_avatar.jpg'); ?>" alt="<?php echo htmlspecialchars($gv['HoGiangVien'] . ' ' . $gv['TenGiangVien']); ?>">
                                        <h5>
                                            <?php echo htmlspecialchars(formatChucDanh($gv['ChucDanh'])) . '.' . htmlspecialchars(formatHocVi($gv['HocVi'])) . '. ' . htmlspecialchars($gv['HoGiangVien'] . ' ' . $gv['TenGiangVien']); ?>
                                        </h5>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button onclick="scrollToTop()" class="scroll-to-top btn btn-primary d-none d-md-block"><i class="fas fa-arrow-up"></i></button>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
    <script>
        function adjustTextareaHeight() {
            const textarea = document.getElementById('gioi_thieu_content');
            if (textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }
        }
        // Hàm tìm kiếm giảng viên
        function searchLecturers() {
            // Lấy giá trị bộ lọc
            const khoa = document.getElementById('khoa-filter').value;
            const ten = document.getElementById('ten-filter').value;
            // Xây dựng URL cho lịch sử trình duyệt
            const url = '<?php echo BASE_URL; ?>/User/faculty-profile.php' +
                (khoa || ten ? '?' : '') +
                (khoa ? 'khoa=' + encodeURIComponent(khoa) : '') +
                (khoa && ten ? '&' : '') +
                (ten ? 'ten=' + encodeURIComponent(ten) : '');
            history.pushState(null, '', url);
            // Gửi yêu cầu để lấy danh sách giảng viên đã lọc
            fetch('<?php echo BASE_URL; ?>/User/search-faculty.php?khoa=' + encodeURIComponent(khoa) + '&ten=' + encodeURIComponent(ten))
                .then(response => response.text())
                .then(data => {
                    // Cập nhật danh sách giảng viên
                    document.getElementById('lecturer-list').innerHTML = data;
                    // Gắn sự kiện cho các liên kết giảng viên
                    document.querySelectorAll('a[href*="giangvien"]').forEach(link => {
                        link.removeEventListener('click', loadLecturerDetail);
                        link.addEventListener('click', (event) => loadLecturerDetail(event, link.href));
                    });
                })
                .catch(error => {
                    document.getElementById('lecturer-list').innerHTML = '<p>Đã xảy ra lỗi khi tìm kiếm. Vui lòng thử lại.</p>';
                });
        }
        // Hàm tải thông tin giảng viên
        function loadLecturerDetail(event, url) {
            event.preventDefault(); // Ngăn tải lại trang

            const mainContent = document.getElementById('main-content');
            mainContent.innerHTML = '<p>Đang tải...</p>';

            fetch(url) // Gửi yêu cầu
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const lecturerDetail = doc.querySelector('#lecturer-detail').innerHTML;
                    mainContent.innerHTML = `
                        <div id="lecturer-detail" class="lecturer-detail">
                            ${lecturerDetail}
                        </div>
                    `;
                    adjustTextareaHeight();
                })
                .catch(error => {
                    mainContent.innerHTML = '<p>Đã xảy ra lỗi khi tải thông tin giảng viên. Vui lòng thử lại.</p>';
                });
        }
        // Hàm tải lại danh sách giảng viên khi quay lại
        function showLecturerList(event) {
            event.preventDefault();

            const mainContent = document.getElementById('main-content');
            mainContent.innerHTML = '<p>Đang tải...</p>';

            const urlParams = new URLSearchParams(window.location.search);
            const khoa = urlParams.get('khoa') || '';
            const ten = urlParams.get('ten') || '';

            const url = '<?php echo BASE_URL; ?>/User/faculty-profile.php' +
                (khoa || ten ? '?' : '') +
                (khoa ? 'khoa=' + encodeURIComponent(khoa) : '') +
                (khoa && ten ? '&' : '') +
                (ten ? 'ten=' + encodeURIComponent(ten) : '');

            history.pushState(null, '', url);

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Phản hồi mạng không tốt');
                    }
                    return response.text();
                })
                .then(data => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const lecturerListView = doc.querySelector('#lecturer-list-view').innerHTML;
                    mainContent.innerHTML = `
                        <div id="lecturer-list-view">
                            ${lecturerListView}
                        </div>
                    `;
                    document.getElementById('khoa-filter').addEventListener('change', searchLecturers);
                    document.getElementById('ten-filter').addEventListener('input', searchLecturers);
                    searchLecturers();
                })
                .catch(error => {
                    mainContent.innerHTML = '<p>Đã xảy ra lỗi khi tải danh sách giảng viên. Vui lòng thử lại.</p>';
                });
        }

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('a[href*="giangvien"]').forEach(link => {
                link.removeEventListener('click', loadLecturerDetail);
                link.addEventListener('click', (event) => loadLecturerDetail(event, link.href));
            });
            adjustTextareaHeight();
        });

        document.getElementById('khoa-filter').addEventListener('change', searchLecturers);
        document.getElementById('ten-filter').addEventListener('input', searchLecturers);

        window.onload = function() {
            if (!window.location.search.includes('giangvien')) {
                searchLecturers();
            }
            adjustTextareaHeight();
        };
    </script>
</body>

</html>

<?php include(BASE_PATH . '/Layout/footer.php'); ?>