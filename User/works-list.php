<?php
session_start();
require_once '../config.php';
require_once(BASE_PATH . '/Database/connect-database.php'); // Kết nối CSDL

// Kiểm tra đăng xuất
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Vui lòng đăng nhập để xem công việc hành chính.";
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

// Định nghĩa số bản ghi mỗi trang
$records_per_page = 5;

// Lấy trang hiện tại từ tham số GET hoặc mặc định là trang 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Đếm tổng số bản ghi để tính số trang
$count_query = "SELECT COUNT(DISTINCT cv.MaCongViecHanhChinh) as total 
                FROM congviechanhchinh cv
                JOIN thongtincongviechanhchinh tt ON cv.MaCongViecHanhChinh = tt.MaCongViecHanhChinh
                WHERE tt.MaGiangVien = ? AND tt.TrangThai IN (0, 1, 3)";
$stmt = mysqli_prepare($dbc, $count_query);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Hàm kiểm tra xung đột lịch giữa công việc hành chính và lịch giảng dạy
function checkWorkScheduleConflict($dbc, $user_id, $thongTinCongViecIds)
{
    // biến lưu chi tiết xung đột và trạng thái vô hiệu hóa nút
    $conflictDetails = null;
    $disableButton = false;

    // Ghi log để kiểm tra user_id
    error_log("checkWorkScheduleConflict: user_id = $user_id, thongTinCongViecIds = " . implode(',', $thongTinCongViecIds));

    // Lấy thông tin lịch công việc hành chính mới
    $scheduleQuery = "
        SELECT tt.NgayThucHien, tt.GioBatDau, tt.GioKetThuc, tt.DiaDiem
        FROM thongtincongviechanhchinh tt
        WHERE tt.MaThongTinCongViec IN ('" . implode("','", array_map('mysqli_real_escape_string', array_fill(0, count($thongTinCongViecIds), $dbc), $thongTinCongViecIds)) . "')";
    $scheduleResult = mysqli_query($dbc, $scheduleQuery);

    if ($scheduleResult) {
        $newSchedules = [];
        while ($row = mysqli_fetch_assoc($scheduleResult)) {
            $gioBatDau = $row['GioBatDau'];
            $gioKetThuc = $row['GioKetThuc'];

            // Xử lý khi GioKetThuc là NULL
            if (is_null($gioKetThuc)) {
                $hour = (int)substr($gioBatDau, 0, 2);
                // Gán giờ kết thúc mặc định dựa trên khung giờ
                if ($hour >= 0 && $hour <= 12) {
                    $gioKetThuc = '12:00:00'; // Sáng
                } elseif ($hour >= 13 && $hour <= 18) {
                    $gioKetThuc = '17:00:00'; // Chiều
                } else {
                    $gioKetThuc = '21:00:00'; // Tối
                }
            }

            // Chuyển đổi NgayThucHien thành thứ (1: Chủ Nhật, 2: Thứ Hai, ..., 7: Thứ Bảy)
            $ngayThucHien = $row['NgayThucHien'];
            $thu = (int)date('w', strtotime($ngayThucHien)) + 1;

            $newSchedules[] = [
                'NgayThucHien' => $ngayThucHien,
                'Thu' => $thu,
                'GioBatDau' => $gioBatDau,
                'GioKetThuc' => $gioKetThuc,
                'DiaDiem' => trim($row['DiaDiem'])
            ];
        }

        // Ghi log newSchedules
        error_log("newSchedules: " . json_encode($newSchedules));

        // 1. Kiểm tra xung đột với lịch công việc hành chính hiện tại
        $existingSchedules = [];
        $existingQuery = "
            SELECT tt.NgayThucHien, tt.GioBatDau, tt.GioKetThuc, tt.DiaDiem
            FROM thongtincongviechanhchinh tt
            WHERE tt.MaGiangVien = ? AND tt.TrangThai = 1";
        $stmt = mysqli_prepare($dbc, $existingQuery);
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $existingResult = mysqli_stmt_get_result($stmt);

        if ($existingResult) {
            while ($row = mysqli_fetch_assoc($existingResult)) {
                $gioBatDau = $row['GioBatDau'];
                $gioKetThuc = $row['GioKetThuc'];

                if (is_null($gioKetThuc)) {
                    $hour = (int)substr($gioBatDau, 0, 2);
                    if ($hour >= 0 && $hour <= 12) {
                        $gioKetThuc = '12:00:00';
                    } elseif ($hour >= 13 && $hour <= 18) {
                        $gioKetThuc = '17:00:00';
                    } else {
                        $gioKetThuc = '21:00:00';
                    }
                }

                $existingSchedules[] = [
                    'NgayThucHien' => $row['NgayThucHien'],
                    'GioBatDau' => $gioBatDau,
                    'GioKetThuc' => $gioKetThuc,
                    'DiaDiem' => trim($row['DiaDiem'])
                ];
            }
        }

        // Ghi log existingSchedules
        error_log("existingSchedules: " . json_encode($existingSchedules));
        // So sánh lịch mới với lịch hiện có
        foreach ($newSchedules as $newSchedule) {
            foreach ($existingSchedules as $existingSchedule) {
                // Kiểm tra cùng ngày
                if ($newSchedule['NgayThucHien'] == $existingSchedule['NgayThucHien']) {
                    // Chuyển đổi thời gian thành timestamp để so sánh
                    $newStart = strtotime($newSchedule['GioBatDau']);
                    $newEnd = strtotime($newSchedule['GioKetThuc']);
                    $existingStart = strtotime($existingSchedule['GioBatDau']);
                    $existingEnd = strtotime($existingSchedule['GioKetThuc']);
                    // Kiểm tra xung đột về thời gian
                    if ($newStart <= $existingEnd && $newEnd >= $existingStart) {
                        $conflictDetails = "Có công việc hành chính khác vào {$newSchedule['NgayThucHien']} từ {$existingSchedule['GioBatDau']} đến {$existingSchedule['GioKetThuc']} tại {$existingSchedule['DiaDiem']}.";
                        // Vô hiệu hóa nút đăng ký
                        $disableButton = true;
                        error_log("Conflict detected with existing work schedule: " . json_encode($newSchedule));
                        return ['conflict' => $conflictDetails, 'disable' => $disableButton];
                    }
                }
            }
        }

        // 2. Kiểm tra xung đột với lịch giảng dạy (không vô hiệu hóa nút)
        if (!$conflictDetails) {
            $teachingSchedules = [];
            $teachingQuery = "
                SELECT lg.LichGiang, lg.GioBatDau, lg.GioKetThuc, hp.DiaDiem, h.TenHocPhan
                FROM lichgiangday lg
                JOIN lichhocphan hp ON lg.MaLichHocPhan = hp.MaLichHocPhan
                JOIN hocphan h ON hp.MaHocPhan = h.MaHocPhan
                WHERE lg.MaGiangVien = ?";
            $stmt = mysqli_prepare($dbc, $teachingQuery);
            mysqli_stmt_bind_param($stmt, "s", $user_id);
            mysqli_stmt_execute($stmt);
            $teachingResult = mysqli_stmt_get_result($stmt);

            if ($teachingResult) {
                while ($row = mysqli_fetch_assoc($teachingResult)) {
                    $gioBatDau = $row['GioBatDau'];
                    $gioKetThuc = $row['GioKetThuc'];

                    if (is_null($gioKetThuc)) {
                        $hour = (int)substr($gioBatDau, 0, 2);
                        if ($hour >= 0 && $hour <= 12) {
                            $gioKetThuc = '12:00:00';
                        } elseif ($hour >= 13 && $hour <= 18) {
                            $gioKetThuc = '17:00:00';
                        } else {
                            $gioKetThuc = '21:00:00';
                        }
                    }

                    $teachingSchedules[] = [
                        'LichGiang' => (int)$row['LichGiang'],
                        'GioBatDau' => $gioBatDau,
                        'GioKetThuc' => $gioKetThuc,
                        'DiaDiem' => trim($row['DiaDiem']),
                        'TenHocPhan' => $row['TenHocPhan']
                    ];
                }
            } else {
                error_log("Error in teaching query: " . mysqli_error($dbc));
            }

            // Ghi log teachingSchedules
            error_log("teachingSchedules: " . json_encode($teachingSchedules));
            // So sánh lịch mới với lịch giảng dạy
            foreach ($newSchedules as $newSchedule) {
                foreach ($teachingSchedules as $teachingSchedule) {
                    // Kiểm tra cùng thứ
                    if ($newSchedule['Thu'] == $teachingSchedule['LichGiang']) {
                        $newStart = strtotime($newSchedule['GioBatDau']);
                        $newEnd = strtotime($newSchedule['GioKetThuc']);
                        $teachingStart = strtotime($teachingSchedule['GioBatDau']);
                        $teachingEnd = strtotime($teachingSchedule['GioKetThuc']);

                        error_log("Comparing: newSchedule = " . json_encode($newSchedule) . ", teachingSchedule = " . json_encode($teachingSchedule));
                        error_log("Time check: newStart=$newStart, newEnd=$newEnd, teachingStart=$teachingStart, teachingEnd=$teachingEnd");
                        // Kiểm tra xung đột thời gian
                        if ($newStart <= $teachingEnd && $newEnd >= $teachingStart) {
                            // Chuyển thứ thành văn bản
                            $thuText = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'][$newSchedule['Thu'] - 1];
                            $conflictDetails = "Có lịch dạy môn {$teachingSchedule['TenHocPhan']} vào {$thuText} từ {$teachingSchedule['GioBatDau']} đến {$teachingSchedule['GioKetThuc']} tại {$teachingSchedule['DiaDiem']}.";
                            error_log("Conflict detected with teaching schedule: " . json_encode($newSchedule));
                            return ['conflict' => $conflictDetails, 'disable' => $disableButton];
                        }
                    }
                }
            }
        }
    } else {
        error_log("Error in schedule query: " . mysqli_error($dbc));
    }

    error_log("checkWorkScheduleConflict result: no conflict");
    return ['conflict' => $conflictDetails, 'disable' => $disableButton];
}

// Xử lý đăng ký lại công việc
if (isset($_POST['action']) && $_POST['action'] === 're_register') {
    header('Content-Type: application/json');
    if (!isset($_POST['maCongViec'])) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit();
    }

    $maCongViec = mysqli_real_escape_string($dbc, $_POST['maCongViec']);
    $query = "SELECT MaThongTinCongViec FROM thongtincongviechanhchinh WHERE MaCongViecHanhChinh = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, "s", $maCongViec);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn CSDL: ' . mysqli_error($dbc)]);
        exit();
    }

    if (mysqli_num_rows($result) > 0) {
        $thongTinCongViecIds = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $thongTinCongViecIds[] = $row['MaThongTinCongViec'];
        }

        $conflictResult = checkWorkScheduleConflict($dbc, $user_id, $thongTinCongViecIds);
        if ($conflictResult['conflict'] && !isset($_POST['confirm'])) {
            echo json_encode(['success' => false, 'conflict' => true, 'message' => $conflictResult['conflict']]);
            exit();
        }

        $success = true;
        mysqli_data_seek($result, 0);
        while ($row = mysqli_fetch_assoc($result)) {
            $maThongTinCongViec = $row['MaThongTinCongViec'];
            $checkQuery = "SELECT TrangThai FROM thongtincongviechanhchinh WHERE MaThongTinCongViec = ?";
            $checkStmt = mysqli_prepare($dbc, $checkQuery);
            mysqli_stmt_bind_param($checkStmt, "s", $maThongTinCongViec);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            $checkRow = mysqli_fetch_assoc($checkResult);

            if ($checkRow['TrangThai'] == 1) {
                echo json_encode(['success' => false, 'message' => 'Công việc đã được đăng ký']);
                exit();
            }

            $updateQuery = "UPDATE thongtincongviechanhchinh SET TrangThai = 1 
                            WHERE MaThongTinCongViec = ? AND MaGiangVien = ?";
            $updateStmt = mysqli_prepare($dbc, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "ss", $maThongTinCongViec, $user_id);
            if (!mysqli_stmt_execute($updateStmt)) {
                echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật CSDL: ' . mysqli_error($dbc)]);
                exit();
            }
            if (mysqli_affected_rows($dbc) == 0) {
                $success = false;
                break;
            }
        }

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Tham gia công việc thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể tham gia công việc']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Công việc không tồn tại']);
    }
    exit();
}

// Xử lý hủy đăng ký công việc
if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
    header('Content-Type: application/json');
    if (!isset($_POST['maCongViec'])) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit();
    }

    $maCongViec = mysqli_real_escape_string($dbc, $_POST['maCongViec']);
    $query = "SELECT MaThongTinCongViec FROM thongtincongviechanhchinh WHERE MaCongViecHanhChinh = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, "s", $maCongViec);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn CSDL: ' . mysqli_error($dbc)]);
        exit();
    }

    if (mysqli_num_rows($result) > 0) {
        $success = true;
        $thongTinCongViecIds = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $thongTinCongViecIds[] = $row['MaThongTinCongViec'];
        }

        foreach ($thongTinCongViecIds as $maThongTinCongViec) {
            $updateQuery = "UPDATE thongtincongviechanhchinh SET TrangThai = 3 
                            WHERE MaThongTinCongViec = ? AND MaGiangVien = ? AND TrangThai = 1";
            $updateStmt = mysqli_prepare($dbc, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "ss", $maThongTinCongViec, $user_id);
            if (!mysqli_stmt_execute($updateStmt)) {
                echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật CSDL: ' . mysqli_error($dbc)]);
                exit();
            }
            if (mysqli_affected_rows($dbc) == 0) {
                $success = false;
                break;
            }
        }

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Hủy tham gia công việc thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể hủy tham gia công việc']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Công việc không tồn tại']);
    }
    exit();
}

// Xử lý làm mới danh sách công việc với phân trang
if (isset($_POST['action']) && $_POST['action'] === 'refresh_works') {
    header('Content-Type: application/json');
    $works = [];
    // Lấy trang hiện tại từ tham số POST
    $page = isset($_POST['page']) && is_numeric($_POST['page']) ? (int)$_POST['page'] : 1;
    $offset = ($page - 1) * $records_per_page;

    // Thêm LIMIT và OFFSET vào truy vấn
    $query = "
        SELECT 
            cv.MaCongViecHanhChinh, 
            cv.TenCongViec, 
            tt.LoaiCongViec, 
            tt.NgayThucHien, 
            tt.GioBatDau, 
            tt.GioKetThuc, 
            tt.DiaDiem, 
            tt.TrangThai,
            GROUP_CONCAT(tt.MaThongTinCongViec) AS MaThongTinCongViecList
        FROM congviechanhchinh cv
        JOIN thongtincongviechanhchinh tt ON cv.MaCongViecHanhChinh = tt.MaCongViecHanhChinh
        WHERE tt.MaGiangVien = ? AND tt.TrangThai IN (0, 1, 3)
        GROUP BY cv.MaCongViecHanhChinh, cv.TenCongViec, tt.LoaiCongViec, tt.NgayThucHien, tt.GioBatDau, tt.GioKetThuc, tt.DiaDiem, tt.TrangThai
        LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, "sii", $user_id, $records_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $maCongViec = $row['MaCongViecHanhChinh'];
            $thongTinCongViecIds = explode(',', $row['MaThongTinCongViecList']);

            // Kiểm tra xung đột
            $conflictResult = checkWorkScheduleConflict($dbc, $user_id, $thongTinCongViecIds);

            // Xử lý GioKetThuc cho hiển thị
            $gioKetThuc = $row['GioKetThuc'] ?? 'Chưa xác định';
            $trangThai = $row['TrangThai'] == 1 ? 'Đã tham gia' : ($row['TrangThai'] == 3 ? 'Đã hủy' : 'Chưa đăng ký');

            $works[] = [
                'MaCongViec' => $maCongViec,
                'TenCongViec' => $row['TenCongViec'],
                'LoaiCongViec' => $row['LoaiCongViec'],
                'NgayThucHien' => date('d/m/Y', strtotime($row['NgayThucHien'])),
                'GioBatDau' => $row['GioBatDau'],
                'GioKetThuc' => $gioKetThuc,
                'DiaDiem' => $row['DiaDiem'],
                'TrangThai' => $trangThai,
                'DaDangKy' => $row['TrangThai'] == 1,
                'Disabled' => $conflictResult['disable'] && $row['TrangThai'] != 1
            ];
        }
    }
    // Trả về tổng số trang và trang hiện tại trong phản hồi JSON
    echo json_encode(['success' => true, 'works' => $works, 'total_pages' => $total_pages, 'current_page' => $page]);
    exit();
}

// Truy vấn danh sách công việc ban đầu với phân trang
$works = [];
$query = "
    SELECT 
        cv.MaCongViecHanhChinh, 
        cv.TenCongViec, 
        tt.LoaiCongViec, 
        tt.NgayThucHien, 
        tt.GioBatDau, 
        tt.GioKetThuc, 
        tt.DiaDiem, 
        tt.TrangThai,
        GROUP_CONCAT(tt.MaThongTinCongViec) AS MaThongTinCongViecList
    FROM congviechanhchinh cv
    JOIN thongtincongviechanhchinh tt ON cv.MaCongViecHanhChinh = tt.MaCongViecHanhChinh
    WHERE tt.MaGiangVien = ? AND tt.TrangThai IN (0, 1, 3)
    GROUP BY cv.MaCongViecHanhChinh, cv.TenCongViec, tt.LoaiCongViec, tt.NgayThucHien, tt.GioBatDau, tt.GioKetThuc, tt.DiaDiem, tt.TrangThai
    LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, "sii", $user_id, $records_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $maCongViec = $row['MaCongViecHanhChinh'];
        $thongTinCongViecIds = explode(',', $row['MaThongTinCongViecList']);

        // Kiểm tra xung đột
        $conflictResult = checkWorkScheduleConflict($dbc, $user_id, $thongTinCongViecIds);

        // Xử lý GioKetThuc cho hiển thị
        $gioKetThuc = $row['GioKetThuc'] ?? 'Chưa xác định';
        $trangThai = $row['TrangThai'] == 1 ? 'Đã đăng ký' : ($row['TrangThai'] == 3 ? 'Đã hủy' : 'Chưa đăng ký');

        $works[] = [
            'MaCongViec' => $maCongViec,
            'TenCongViec' => $row['TenCongViec'],
            'LoaiCongViec' => $row['LoaiCongViec'],
            'NgayThucHien' => date('d/m/Y', strtotime($row['NgayThucHien'])),
            'GioBatDau' => $row['GioBatDau'],
            'GioKetThuc' => $gioKetThuc,
            'DiaDiem' => $row['DiaDiem'],
            'TrangThai' => $trangThai,
            'DaDangKy' => $row['TrangThai'] == 1,
            'Disabled' => $conflictResult['disable'] && $row['TrangThai'] != 1
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
    <title>Công việc hành chính</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .content {
            margin-top: 20px;
        }

        .table th,
        .table td {
            vertical-align: middle;
            padding: 8px;
            font-size: 14px;
        }

        .btn-cancel,
        .btn-re-register {
            min-width: 100px;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }

        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
        }

        /* MODIFIED: CSS cho phân trang với nút trước/sau */
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
            .table th,
            .table td {
                font-size: 12px;
                padding: 6px;
            }

            .table th,
            .table td {
                white-space: nowrap;
            }

            .btn-cancel,
            .btn-re-register {
                min-width: 80px;
                font-size: 12px;
                padding: 4px 8px;
            }
        }
    </style>
</head>

<body style="background-color: #f1f1f1;">
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

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

    <div class="container content">
        <h4>Danh sách công việc hành chính</h4>
        <div id="workTable">
            <?php if (empty($works)): ?>
                <p>Bạn chưa tham gia công việc hành chính nào.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Mã công việc</th>
                                <th>Tên công việc</th>
                                <th class="d-none-mobile">Loại công việc</th>
                                <th>Ngày thực hiện</th>
                                <th>Giờ bắt đầu</th>
                                <th class="d-none-mobile">Giờ kết thúc</th>
                                <th>Địa điểm</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="workTableBody">
                            <?php foreach ($works as $work): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($work['MaCongViec']); ?></td>
                                    <td><?php echo htmlspecialchars($work['TenCongViec']); ?></td>
                                    <td class="d-none-mobile"><?php echo htmlspecialchars($work['LoaiCongViec']); ?></td>
                                    <td><?php echo htmlspecialchars($work['NgayThucHien']); ?></td>
                                    <td><?php echo htmlspecialchars($work['GioBatDau']); ?></td>
                                    <td class="d-none-mobile"><?php echo htmlspecialchars($work['GioKetThuc']); ?></td>
                                    <td><?php echo htmlspecialchars($work['DiaDiem']); ?></td>
                                    <td><?php echo htmlspecialchars($work['TrangThai']); ?></td>
                                    <td>
                                        <?php if ($work['DaDangKy']): ?>
                                            <button class="btn btn-danger btn-cancel"
                                                onclick="cancelWork('<?php echo htmlspecialchars($work['MaCongViec']); ?>')">
                                                Hủy tham gia
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-success btn-re-register"
                                                onclick="reRegisterWork('<?php echo htmlspecialchars($work['MaCongViec']); ?>')"
                                                <?php echo $work['Disabled'] ? 'disabled' : ''; ?>>
                                                Tham gia
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- MODIFIED: Hiển thị phân trang với nút trước/sau -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="nav-arrow">&lt;</a>
                    <?php else: ?>
                        <a href="javascript:void(0)" class="nav-arrow disabled">&lt;</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="nav-arrow">&gt;</a>
                    <?php else: ?>
                        <a href="javascript:void(0)" class="nav-arrow disabled">&gt;</a>
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

        function showLoading() {
            $('#loadingOverlay').show();
        }

        function hideLoading() {
            $('#loadingOverlay').hide();
        }

        // MODIFIED: Cập nhật hàm refreshWorkTable để hỗ trợ nút trước/sau
        function refreshWorkTable(page = <?php echo $page; ?>) {
            showLoading();
            $.ajax({
                url: 'works-list.php',
                type: 'POST',
                data: {
                    action: 'refresh_works',
                    page: page
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        let html = '';
                        if (response.works.length === 0) {
                            html = '<p>Bạn chưa tham gia công việc hành chính nào.</p>';
                        } else {
                            html = `
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Mã công việc</th>
                                                <th>Tên công việc</th>
                                                <th class="d-none-mobile">Loại công việc</th>
                                                <th>Ngày thực hiện</th>
                                                <th>Giờ bắt đầu</th>
                                                <th class="d-none-mobile">Giờ kết thúc</th>
                                                <th>Địa điểm</th>
                                                <th>Trạng thái</th>
                                                <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;
                            response.works.forEach(work => {
                                html += `
                                    <tr>
                                        <td>${work.MaCongViec}</td>
                                        <td>${work.TenCongViec}</td>
                                        <td class="d-none-mobile">${work.LoaiCongViec}</td>
                                        <td>${work.NgayThucHien}</td>
                                        <td>${work.GioBatDau}</td>
                                        <td class="d-none-mobile">${work.GioKetThuc}</td>
                                        <td>${work.DiaDiem}</td>
                                        <td>${work.TrangThai}</td>
                                        <td>
                                            ${work.DaDangKy ? 
                                                `<button class="btn btn-danger btn-cancel" onclick="cancelWork('${work.MaCongViec}')">Hủy tham gia</button>` : 
                                                `<button class="btn btn-success btn-re-register" onclick="reRegisterWork('${work.MaCongViec}')" ${work.Disabled ? 'disabled' : ''}>Tham gia</button>`}
                                        </td>
                                    </tr>
                                `;
                            });
                            html += '</tbody></table></div>';
                            // MODIFIED: Thêm phân trang với nút trước/sau
                            html += '<div class="pagination">';
                            if (response.current_page > 1) {
                                html += `<a href="javascript:refreshWorkTable(${response.current_page - 1})" class="nav-arrow">&lt;</a>`;
                            } else {
                                html += `<a href="javascript:void(0)" class="nav-arrow disabled">&lt;</a>`;
                            }
                            for (let i = 1; i <= response.total_pages; i++) {
                                html += `<a href="javascript:refreshWorkTable(${i})" class="${i == response.current_page ? 'active' : ''}">${i}</a>`;
                            }
                            if (response.current_page < response.total_pages) {
                                html += `<a href="javascript:refreshWorkTable(${response.current_page + 1})" class="nav-arrow">&gt;</a>`;
                            } else {
                                html += `<a href="javascript:void(0)" class="nav-arrow disabled">&gt;</a>`;
                            }
                            html += '</div>';
                        }
                        $('#workTable').html(html);
                    } else {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: 'Không thể làm mới danh sách công việc',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                    hideLoading();
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
                    hideLoading();
                }
            });
        }

        function cancelWork(maCongViec) {
            if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại!',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 3000);
                return;
            }

            Swal.fire({
                title: 'Xác nhận hủy tham gia',
                text: `Bạn có muốn hủy tham gia công việc mã ${maCongViec}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Không đồng ý'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    $.ajax({
                        url: 'works-list.php',
                        type: 'POST',
                        data: {
                            action: 'cancel',
                            maCongViec: maCongViec
                        },
                        dataType: 'json',
                        success: function(response) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: response.success ? 'success' : 'error',
                                title: response.message,
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                            if (response.success) {
                                refreshWorkTable(<?php echo $page; ?>);
                            } else {
                                hideLoading();
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: 'Lỗi hệ thống: ' + error,
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                            hideLoading();
                        }
                    });
                }
            });
        }

        function reRegisterWork(maCongViec) {
            if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại!',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 3000);
                return;
            }

            // Kiểm tra nếu nút bị vô hiệu hóa
            const button = $(`button[onclick="reRegisterWork('${maCongViec}')"]`);
            if (button.prop('disabled')) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Công việc này có xung đột với lịch công việc hành chính, không thể tham gia!',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                return;
            }

            Swal.fire({
                title: 'Xác nhận tham gia',
                text: `Bạn có muốn tham gia công việc mã ${maCongViec}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Tham gia',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    $.ajax({
                        url: 'works-list.php',
                        type: 'POST',
                        data: {
                            action: 're_register',
                            maCongViec: maCongViec
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.conflict) {
                                hideLoading();
                                Swal.fire({
                                    title: 'Cảnh báo xung đột lịch',
                                    text: `${response.message} Bạn có muốn tiếp tục tham gia công việc?`,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Tiếp tục',
                                    cancelButtonText: 'Hủy'
                                }).then((confirmResult) => {
                                    if (confirmResult.isConfirmed) {
                                        showLoading();
                                        $.ajax({
                                            url: 'works-list.php',
                                            type: 'POST',
                                            data: {
                                                action: 're_register',
                                                maCongViec: maCongViec,
                                                confirm: true
                                            },
                                            dataType: 'json',
                                            success: function(confirmResponse) {
                                                Swal.fire({
                                                    toast: true,
                                                    position: 'top-end',
                                                    icon: confirmResponse.success ? 'success' : 'error',
                                                    title: confirmResponse.message,
                                                    showConfirmButton: false,
                                                    timer: 3000,
                                                    timerProgressBar: true
                                                });
                                                if (confirmResponse.success) {
                                                    refreshWorkTable(<?php echo $page; ?>);
                                                } else {
                                                    hideLoading();
                                                }
                                            },
                                            error: function(xhr, status, error) {
                                                Swal.fire({
                                                    toast: true,
                                                    position: 'top-end',
                                                    icon: 'error',
                                                    title: 'Lỗi hệ thống: ' + error,
                                                    showConfirmButton: false,
                                                    timer: 3000,
                                                    timerProgressBar: true
                                                });
                                                hideLoading();
                                            }
                                        });
                                    } else {
                                        hideLoading();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: response.success ? 'success' : 'error',
                                    title: response.message,
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true
                                });
                                if (response.success) {
                                    refreshWorkTable(<?php echo $page; ?>);
                                } else {
                                    hideLoading();
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: 'Lỗi hệ thống: ' + error,
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                            hideLoading();
                        }
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