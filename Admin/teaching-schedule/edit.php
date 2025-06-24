<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Lấy và làm sạch $id
if (!isset($_GET['MaLichHocPhan']) || empty($_GET['MaLichHocPhan'])) {
    die("Mã lịch học phần không hợp lệ.");
}
$id = mysqli_real_escape_string($dbc, $_GET['MaLichHocPhan']);

// Kiểm tra quyền và lấy MaKhoa của Admin
$user_id = $_SESSION['user_id'] ?? '';
$quyen = $_SESSION['quyen'] ?? 'Không xác định';
$ma_khoa = null;

if ($quyen === 'Admin') {
    // Lấy MaKhoa của Admin từ bảng giangvien
    $query_khoa = "SELECT MaKhoa FROM giangvien WHERE MaGiangVien = ?";
    $stmt_khoa = $dbc->prepare($query_khoa);
    $stmt_khoa->bind_param("s", $user_id);
    $stmt_khoa->execute();
    $result_khoa = $stmt_khoa->get_result();

    if ($row_khoa = $result_khoa->fetch_assoc()) {
        $ma_khoa = $row_khoa['MaKhoa'];
    }
    $stmt_khoa->close();

    // Kiểm tra xem lịch học phần có thuộc khoa của Admin không
    $query_check_faculty = "SELECT lichhocphan.MaLichHocPhan 
                            FROM lichhocphan 
                            JOIN hocphan ON lichhocphan.MaHocPhan = hocphan.MaHocPhan 
                            WHERE lichhocphan.MaLichHocPhan = ? AND hocphan.MaKhoa = ?";
    $stmt_check_faculty = $dbc->prepare($query_check_faculty);
    $stmt_check_faculty->bind_param("ss", $id, $ma_khoa);
    $stmt_check_faculty->execute();
    $result_check_faculty = $stmt_check_faculty->get_result();

    if ($result_check_faculty->num_rows === 0) {
        die("Bạn không có quyền chỉnh sửa lịch học phần này.");
    }
    $stmt_check_faculty->close();
}

// Lấy thông tin từ cơ sở dữ liệu để hiển thị
$sql = "SELECT lichhocphan.*, lichgiangday.MaGiangVien, lichgiangday.LichGiang, lichgiangday.GioBatDau, lichgiangday.GioKetThuc
        FROM lichhocphan
        LEFT JOIN lichgiangday ON lichgiangday.MaLichHocPhan = lichhocphan.MaLichHocPhan
        WHERE lichhocphan.MaLichHocPhan = ?";
$stmt = $dbc->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_assoc();
$stmt->close();

// Hàm kiểm tra trùng lịch giảng dạy tại địa điểm và thời gian cụ thể
function checkScheduleConflict($dbc, $diaDiem, $lichGiang, $gioBatDau, $gioKetThuc, $maLichHocPhan = null)
{
    $sql = "SELECT * FROM lichgiangday WHERE LichGiang = ?";
    if ($maLichHocPhan) {
        $sql .= " AND MaLichHocPhan != ?";
    }
    $stmt = $dbc->prepare($sql);
    if ($maLichHocPhan) {
        $stmt->bind_param("ss", $lichGiang, $maLichHocPhan);
    } else {
        $stmt->bind_param("s", $lichGiang);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $conflicts = [];

    while ($row = $result->fetch_assoc()) {
        // Lấy thông tin lịch học phần để kiểm tra địa điểm
        $maLichHocPhanRow = $row['MaLichHocPhan'];
        $sqlHocPhan = "SELECT DiaDiem FROM lichhocphan WHERE MaLichHocPhan = ?";
        $stmtHocPhan = $dbc->prepare($sqlHocPhan);
        $stmtHocPhan->bind_param("s", $maLichHocPhanRow);
        $stmtHocPhan->execute();
        $resultHocPhan = $stmtHocPhan->get_result();
        $hocPhan = $resultHocPhan->fetch_assoc();

        // Chỉ kiểm tra nếu địa điểm trùng
        if ($hocPhan['DiaDiem'] === $diaDiem) {
            $existingStart = strtotime($row['GioBatDau']);
            $existingEnd = strtotime($row['GioKetThuc']);
            $newStart = strtotime($gioBatDau);
            $newEnd = strtotime($gioKetThuc);

            // Kiểm tra xung đột thời gian
            if (($newStart >= $existingStart && $newStart <= $existingEnd) ||
                ($newEnd >= $existingStart && $newEnd <= $existingEnd) ||
                ($newStart <= $existingStart && $newEnd >= $existingEnd)
            ) {
                $conflicts[] = $row;
            }
        }
        $stmtHocPhan->close();
    }
    $stmt->close();
    return $conflicts;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array();

    // Mã lịch học phần
    if (empty($_POST['MaLichHocPhan'])) {
        $errors['MaLichHocPhan'] = 'Mã lịch học phần không để trống!';
    } else {
        $MaLichHocPhan = mysqli_real_escape_string($dbc, trim($_POST['MaLichHocPhan']));
    }

    // Lớp học phần
    if (empty($_POST['lophocphan'])) {
        $errors['lophocphan'] = 'Chưa nhập lớp học phần';
    } else {
        $lophocphan = mysqli_real_escape_string($dbc, trim($_POST['lophocphan']));
    }

    // Tên học phần
    if (empty($_POST['TenHocPhan'])) {
        $errors['TenHocPhan'] = 'Vui lòng chọn học phần';
    } else {
        $Mahocphan = mysqli_real_escape_string($dbc, trim($_POST['TenHocPhan']));
    }

    // Giảng viên
    if (empty($_POST['MaGiangVien'])) {
        $errors['MaGiangVien'] = 'Vui lòng chọn giảng viên';
    } else {
        $MaGiangVien = mysqli_real_escape_string($dbc, trim($_POST['MaGiangVien']));
    }

    // Thời gian bắt đầu
    if (empty($_POST['DateStart'])) {
        $errors['DateStart'] = 'Thời gian bắt đầu không để trống';
    } else {
        $DateStartInput = trim($_POST['DateStart']);
        // Kiểm tra định dạng dd/mm/yyyy
        if (!preg_match("/^\d{2}\/\d{2}\/\d{4}$/", $DateStartInput)) {
            $errors['DateStart'] = 'Thời gian bắt đầu phải có định dạng dd/mm/yyyy';
        } else {
            // Chuyển từ dd/mm/yyyy sang yyyy-mm-dd
            $dateParts = explode('/', $DateStartInput);
            if (!checkdate($dateParts[1], $dateParts[0], $dateParts[2])) {
                $errors['DateStart'] = 'Thời gian bắt đầu không hợp lệ';
            } else {
                $DateStart = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0]; // Định dạng yyyy-mm-dd
                $DateStart = mysqli_real_escape_string($dbc, $DateStart);
            }
        }
    }

    // Thời gian kết thúc
    if (empty($_POST['DateEnd'])) {
        $errors['DateEnd'] = 'Thời gian kết thúc không để trống';
    } else {
        $DateEndInput = trim($_POST['DateEnd']);
        // Kiểm tra định dạng dd/mm/yyyy
        if (!preg_match("/^\d{2}\/\d{2}\/\d{4}$/", $DateEndInput)) {
            $errors['DateEnd'] = 'Thời gian kết thúc phải có định dạng dd/mm/yyyy';
        } else {
            // Chuyển từ dd/mm/yyyy sang yyyy-mm-dd
            $dateParts = explode('/', $DateEndInput);
            if (!checkdate($dateParts[1], $dateParts[0], $dateParts[2])) {
                $errors['DateEnd'] = 'Thời gian kết thúc không hợp lệ';
            } else {
                $DateEnd = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0]; // Định dạng yyyy-mm-dd
                $DateEnd = mysqli_real_escape_string($dbc, $DateEnd);
            }
        }
    }

    // Lịch giảng dạy
    $lichgiang = isset($_POST['Lichgiang']) ? $_POST['Lichgiang'] : [];
    $thoigian_batdau = isset($_POST['thoigian_batdau']) ? $_POST['thoigian_batdau'] : [];
    $thoigian_ketthuc = isset($_POST['thoigian_ketthuc']) ? $_POST['thoigian_ketthuc'] : [];

    if (empty($lichgiang) || count($lichgiang) === 0) {
        $errors['lichgiang'] = 'Vui lòng thêm ít nhất một lịch giảng dạy';
    } else {
        foreach ($lichgiang as $index => $ngayday) {
            if (empty($thoigian_batdau[$index]) || empty($thoigian_ketthuc[$index])) {
                $errors['thoigian'] = 'Vui lòng nhập thời gian bắt đầu hoặc kết thúc cho ngày dạy';
                break;
            }
        }
    }

    // Địa điểm học
    if (empty($_POST['DiaDiem'])) {
        $errors['DiaDiem'] = 'Địa điểm học không để trống';
    } else {
        $DiaDiem = mysqli_real_escape_string($dbc, trim($_POST['DiaDiem']));
    }

    // Trạng thái
    if (isset($_POST['TrangThai'])) {
        $trangthai = ($_POST['TrangThai'] === 'xuat') ? 1 : 2;
    } else {
        $errors['TrangThai'] = 'Vui lòng chọn trạng thái';
    }

    // Kiểm tra trùng lịch giảng dạy tại địa điểm và thời gian
    if (empty($errors) && !empty($lichgiang)) {
        $conflictMessages = [];
        foreach ($lichgiang as $index => $ngayday) {
            $gioBatDau = mysqli_real_escape_string($dbc, $thoigian_batdau[$index]);
            $gioKetThuc = mysqli_real_escape_string($dbc, $thoigian_ketthuc[$index]);

            $conflicts = checkScheduleConflict($dbc, $DiaDiem, $ngayday, $gioBatDau, $gioKetThuc, $MaLichHocPhan);

            if (!empty($conflicts)) {
                // Chuyển số ngày thành tên ngày
                $ngayTrongTuan = [
                    '1' => 'Chủ Nhật',
                    '2' => 'Thứ Hai',
                    '3' => 'Thứ Ba',
                    '4' => 'Thứ Tư',
                    '5' => 'Thứ Năm',
                    '6' => 'Thứ Sáu',
                    '7' => 'Thứ Bảy'
                ];
                $tenNgay = $ngayTrongTuan[$ngayday] ?? 'Ngày không xác định';

                $conflictMessages[] = "Phòng {$DiaDiem} đã được sử dụng vào {$tenNgay}, giờ {$gioBatDau} - {$gioKetThuc}";
            }
        }

        if (!empty($conflictMessages)) {
            $errors['scheduleConflict'] = implode('<br>', $conflictMessages);
        }
    }

    if (empty($errors)) {
        // Cập nhật lịch học phần
        $qLichHocPhan = "UPDATE lichhocphan 
                         SET MaHocPhan = ?, 
                             LopHocPhan = ?, 
                             ThoiGianBatDau = ?, 
                             ThoiGianKetThuc = ?, 
                             DiaDiem = ?, 
                             TrangThai = ? 
                         WHERE MaLichHocPhan = ?";
        $stmtLichHocPhan = $dbc->prepare($qLichHocPhan);
        $stmtLichHocPhan->bind_param("sssssis", $Mahocphan, $lophocphan, $DateStart, $DateEnd, $DiaDiem, $trangthai, $id);
        $r = $stmtLichHocPhan->execute();

        if ($r) {
            // Xử lý lịch giảng dạy
            $existingSchedules = [];
            $sql_lichgiang = "SELECT * FROM lichgiangday WHERE MaLichHocPhan = ?";
            $stmt_lichgiang = $dbc->prepare($sql_lichgiang);
            $stmt_lichgiang->bind_param("s", $id);
            $stmt_lichgiang->execute();
            $result_lichgiang = $stmt_lichgiang->get_result();
            while ($row = $result_lichgiang->fetch_assoc()) {
                $existingSchedules[$row['LichGiang']] = [
                    'GioBatDau' => $row['GioBatDau'],
                    'GioKetThuc' => $row['GioKetThuc'],
                    'MaLichGiang' => $row['MaLichGiang'],
                    'MaGiangVien' => $row['MaGiangVien']
                ];
            }
            $stmt_lichgiang->close();

            $newSchedules = [];
            foreach ($lichgiang as $index => $ngayday) {
                $startTime = mysqli_real_escape_string($dbc, $thoigian_batdau[$index]);
                $endTime = mysqli_real_escape_string($dbc, $thoigian_ketthuc[$index]);
                $newSchedules[$ngayday] = [
                    'GioBatDau' => $startTime,
                    'GioKetThuc' => $endTime,
                    'MaGiangVien' => $MaGiangVien
                ];
            }

            // So sánh và cập nhật lịch giảng dạy
            foreach ($newSchedules as $day => $times) {
                if (isset($existingSchedules[$day])) {
                    if (
                        $existingSchedules[$day]['GioBatDau'] !== $times['GioBatDau'] ||
                        $existingSchedules[$day]['GioKetThuc'] !== $times['GioKetThuc'] ||
                        $existingSchedules[$day]['MaGiangVien'] !== $times['MaGiangVien']
                    ) {
                        $maLichGiang = $existingSchedules[$day]['MaLichGiang'];
                        $qUpdateSchedule = "UPDATE lichgiangday 
                                           SET GioBatDau = ?, 
                                               GioKetThuc = ?, 
                                               MaGiangVien = ? 
                                           WHERE MaLichGiang = ?";
                        $stmtUpdateSchedule = $dbc->prepare($qUpdateSchedule);
                        $stmtUpdateSchedule->bind_param("ssss", $times['GioBatDau'], $times['GioKetThuc'], $MaGiangVien, $maLichGiang);
                        $stmtUpdateSchedule->execute();
                        $stmtUpdateSchedule->close();
                    }
                    unset($existingSchedules[$day]);
                } else {
                    $qInsertSchedule = "INSERT INTO lichgiangday (MaLichHocPhan, MaGiangVien, LichGiang, GioBatDau, GioKetThuc) 
                                       VALUES (?, ?, ?, ?, ?)";
                    $stmtInsertSchedule = $dbc->prepare($qInsertSchedule);
                    $stmtInsertSchedule->bind_param("ssiss", $id, $MaGiangVien, $day, $times['GioBatDau'], $times['GioKetThuc']);
                    $stmtInsertSchedule->execute();
                    $stmtInsertSchedule->close();
                }
            }

            // Xóa các lịch không còn trong lịch mới
            foreach ($existingSchedules as $day => $schedule) {
                $maLichGiang = $schedule['MaLichGiang'];
                $qDeleteSchedule = "DELETE FROM lichgiangday WHERE MaLichGiang = ?";
                $stmtDeleteSchedule = $dbc->prepare($qDeleteSchedule);
                $stmtDeleteSchedule->bind_param("s", $maLichGiang);
                $stmtDeleteSchedule->execute();
                $stmtDeleteSchedule->close();
            }

            $_SESSION['success_message'] = 'Đã cập nhật lịch học phần thành công!';
            if (ob_get_length() > 0) {
                ob_end_clean();
            }
            header("Location: index.php");
            exit();
        } else {
            echo '<h1>Lỗi hệ thống</h1><p class="error">Cập nhật thất bại: ' . mysqli_error($dbc) . '</p>';
            echo '<p>Query: ' . $qLichHocPhan . '</p>';
        }
        $stmtLichHocPhan->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật lịch học phần</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
    <!-- jQuery UI CSS cho Datepicker -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- Thêm CSS của Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Tùy chỉnh Select2 để không tràn container */
        .select2-container {
            max-width: 100%; /* Ngăn dropdown vượt quá container */
            box-sizing: border-box; /* Đảm bảo padding và border không làm tràn */
        }

        .select2-container .select2-selection--single {
            height: 38px; /* Chiều cao đồng bộ với các input khác */
            line-height: 38px; /* Căn giữa nội dung */
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }

        /* Cắt ngắn nội dung dài trong dropdown để tránh tràn */
        .select2-selection__rendered {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Bù padding của col-md-6 để dropdown hiển thị đúng */
        .select2-container-parent {
            padding-left: 0;
            padding-right: 0;
        }
    </style>
</head>

<body>
    <div class="content-wrapper">
        <section class="content my-2">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <strong class="text-blue">CẬP NHẬT LỊCH GIẢNG DẠY</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="./index.php" class="btn-sm btn-info"> <i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                        </div>
                    </div>
                </div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Mã lịch học phần<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input readonly class="form-control" type="text" name="MaLichHocPhan"
                                            value="<?php echo isset($_POST['MaLichHocPhan']) ? htmlspecialchars($_POST['MaLichHocPhan']) : htmlspecialchars($rows['MaLichHocPhan']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Lớp học phần<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="lophocphan"
                                            value="<?php echo isset($_POST['lophocphan']) ? htmlspecialchars($_POST['lophocphan']) : htmlspecialchars($rows['LopHocPhan']); ?>">
                                        <?php if (isset($errors['lophocphan'])): ?>
                                            <small class="text-danger"><?php echo htmlspecialchars($errors['lophocphan']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Tên học phần <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6 select2-container-parent">
                                        <select class="form-control select2-hocphan" name="TenHocPhan">
                                            <option value="">Chọn học phần</option>
                                            <?php
                                            $sql = "SELECT MaHocPhan, TenHocPhan FROM hocphan WHERE TrangThai = 1" . ($quyen === 'Admin' && $ma_khoa ? " AND MaKhoa = '$ma_khoa'" : "");
                                            $result = mysqli_query($dbc, $sql);
                                            $selectedValue = isset($_POST['TenHocPhan']) ? $_POST['TenHocPhan'] : $rows['MaHocPhan'];
                                            while ($row = mysqli_fetch_array($result)) {
                                                $selected = ($row['MaHocPhan'] == $selectedValue) ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($row['MaHocPhan']) . "' $selected>" . htmlspecialchars($row['TenHocPhan']) . "</option>";
                                            }
                                            ?>
                                        </select>
                                        <?php if (isset($errors['TenHocPhan'])): ?>
                                            <small class="text-danger"><?php echo htmlspecialchars($errors['TenHocPhan']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Giảng viên <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6 select2-container-parent">
                                        <select class="form-control select2-giangvien" name="MaGiangVien">
                                            <option value="">Chọn giảng viên</option>
                                            <?php
                                            $sql = "SELECT MaGiangVien, CONCAT(HoGiangVien, ' ', TenGiangVien) AS TenGiangVien 
                                                    FROM giangvien 
                                                    WHERE TrangThai = 1" . ($quyen === 'Admin' && $ma_khoa ? " AND MaKhoa = '$ma_khoa'" : "");
                                            $result = mysqli_query($dbc, $sql);
                                            $selectedGiangVien = isset($_POST['MaGiangVien']) ? $_POST['MaGiangVien'] : $rows['MaGiangVien'];
                                            while ($row = mysqli_fetch_array($result)) {
                                                $selected = ($row['MaGiangVien'] == $selectedGiangVien) ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($row['MaGiangVien']) . "' $selected>" . htmlspecialchars($row['TenGiangVien']) . "</option>";
                                            }
                                            ?>
                                        </select>
                                        <?php if (isset($errors['MaGiangVien'])): ?>
                                            <small class="text-danger"><?php echo htmlspecialchars($errors['MaGiangVien']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Lịch dạy <span class="text-danger"> (*)</span></label>
                                    <button type="button" id="addScheduleButton">Thêm lịch dạy</button>
                                    <div id="scheduleContainer"></div>
                                    <?php if (isset($errors['lichgiang']) && isset($errors['thoigian'])): ?>
                                        <small class="text-danger">Vui lòng thêm ít nhất 1 lịch giảng dạy</small>
                                    <?php else: ?>
                                        <?php if (isset($errors['lichgiang'])): ?>
                                            <small class="text-danger"><?php echo htmlspecialchars($errors['lichgiang']); ?></small>
                                        <?php endif; ?>
                                        <?php if (isset($errors['thoigian'])): ?>
                                            <small class="text-danger"><?php echo htmlspecialchars($errors['thoigian']); ?></small>
                                        <?php endif; ?>
                                        <?php if (isset($errors['scheduleConflict'])): ?>
                                            <small class="text-danger"><?php echo $errors['scheduleConflict']; ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label>Địa điểm học <span class="text-danger">(*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="DiaDiem"
                                            value="<?php echo isset($_POST['DiaDiem']) ? htmlspecialchars($_POST['DiaDiem']) : htmlspecialchars($rows['DiaDiem']); ?>">
                                        <?php if (isset($errors['DiaDiem'])): ?>
                                            <small class="text-danger"><?php echo htmlspecialchars($errors['DiaDiem']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Thời gian bắt đầu <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="DateStart" id="dateStart" required
                                            value="<?php
                                                    if (isset($_POST['DateStart'])) {
                                                        echo htmlspecialchars($_POST['DateStart']);
                                                    } else {
                                                        $date = DateTime::createFromFormat('Y-m-d', $rows['ThoiGianBatDau']);
                                                        echo $date ? $date->format('d/m/Y') : '';
                                                    }
                                                    ?>">
                                        <?php if (isset($errors['DateStart'])): ?>
                                            <small class="text-danger"><?php echo htmlspecialchars($errors['DateStart']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Thời gian kết thúc <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="DateEnd" id="dateEnd" required
                                            value="<?php
                                                    if (isset($_POST['DateEnd'])) {
                                                        echo htmlspecialchars($_POST['DateEnd']);
                                                    } else {
                                                        $date = DateTime::createFromFormat('Y-m-d', $rows['ThoiGianKetThuc']);
                                                        echo $date ? $date->format('d/m/Y') : '';
                                                    }
                                                    ?>">
                                        <?php if (isset($errors['DateEnd'])): ?>
                                            <small class="text-danger"><?php echo htmlspecialchars($errors['DateEnd']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Trạng Thái <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="TrangThai">
                                            <option value="xuat" <?php echo (isset($_POST['TrangThai']) && $_POST['TrangThai'] == 'xuat') || (!isset($_POST['TrangThai']) && $rows['TrangThai'] == 1) ? 'selected' : ''; ?>>Xuất bản</option>
                                            <option value="an" <?php echo (isset($_POST['TrangThai']) && $_POST['TrangThai'] == 'an') || (!isset($_POST['TrangThai']) && $rows['TrangThai'] == 2) ? 'selected' : ''; ?>>Ẩn</option>
                                        </select>
                                        <?php if (isset($errors['TrangThai'])): ?>
                                            <small class="text-danger"><?php echo htmlspecialchars($errors['TrangThai']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-offset-2 col-md-12">
                                    <button class="btn-sm btn-success" type="submit" name="create"> Lưu [Cập nhật] <i class="fa fa-save"></i> </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <script src="<?php echo BASE_URL ?>/Public/plugins/jquery/jquery.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/jszip/jszip.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/pdfmake/pdfmake.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/pdfmake/vfs_fonts.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/js/buttons.print.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/dist/js/demo.js"></script>
    <!-- Thêm JS của Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- jQuery UI JS cho Datepicker -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <script>
        $(document).ready(function() {
            // Khởi tạo Select2 cho dropdown Tên học phần
            $('.select2-hocphan').select2({
                placeholder: "Chọn 1 học phần",
                allowClear: false,
                width: 'resolve', // Tự động điều chỉnh chiều rộng
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                }
            });

            // Khởi tạo Select2 cho dropdown Giảng viên
            $('.select2-giangvien').select2({
                placeholder: "Chọn 1 giảng viên",
                allowClear: false,
                width: 'resolve', // Tự động điều chỉnh chiều rộng
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                }
            });

            // Khởi tạo Datepicker cho Thời gian bắt đầu
            $("#dateStart").datepicker({
                dateFormat: "dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                yearRange: "2000:2030"
            });

            // Khởi tạo Datepicker cho Thời gian kết thúc
            $("#dateEnd").datepicker({
                dateFormat: "dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                yearRange: "2000:2030"
            });
        });

        const scheduleContainer = document.getElementById('scheduleContainer');

        document.getElementById('addScheduleButton').addEventListener('click', function() {
            addScheduleRow();
        });

        function addScheduleRow(day = '', startTime = '', endTime = '') {
            const newSchedule = document.createElement('div');
            newSchedule.classList.add('row');

            newSchedule.innerHTML = `
                <div class="col-md-2">
                    <select class="form-control" name="Lichgiang[]">
                        <option value="">Chọn ngày</option>
                        <option value="2" ${day === '2' ? 'selected' : ''}>Thứ Hai</option>
                        <option value="3" ${day === '3' ? 'selected' : ''}>Thứ Ba</option>
                        <option value="4" ${day === '4' ? 'selected' : ''}>Thứ Tư</option>
                        <option value="5" ${day === '5' ? 'selected' : ''}>Thứ Năm</option>
                        <option value="6" ${day === '6' ? 'selected' : ''}>Thứ Sáu</option>
                        <option value="7" ${day === '7' ? 'selected' : ''}>Thứ Bảy</option>
                        <option value="1" ${day === '1' ? 'selected' : ''}>Chủ Nhật</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input class="form-control" type="time" name="thoigian_batdau[]" value="${startTime}">
                </div>
                <p style="margin-top: 10px;">
                    <i class="fa fa-arrow-right" aria-hidden="true"></i>
                </p>
                <div class="col-md-2">
                    <input class="form-control" type="time" name="thoigian_ketthuc[]" value="${endTime}">
                </div>
                <div class="col-md-offset-2 col-md-2">
                    <button type="button" class="btn btn-danger remove-button"><i class="fa-solid fa-trash"></i></button>
                </div>
            `;

            scheduleContainer.appendChild(newSchedule);

            newSchedule.querySelector('.remove-button').addEventListener('click', function() {
                scheduleContainer.removeChild(newSchedule);
            });
        }

        // Xử lý hiển thị dữ liệu
        <?php
        if (isset($_POST['Lichgiang']) && !empty($_POST['Lichgiang'])) {
            foreach ($_POST['Lichgiang'] as $index => $day) {
                $startTime = isset($_POST['thoigian_batdau'][$index]) ? htmlspecialchars($_POST['thoigian_batdau'][$index]) : '';
                $endTime = isset($_POST['thoigian_ketthuc'][$index]) ? htmlspecialchars($_POST['thoigian_ketthuc'][$index]) : '';
                echo "addScheduleRow('" . htmlspecialchars($day) . "', '" . $startTime . "', '" . $endTime . "');";
            }
        } else {
            $sql_lichgiang = "SELECT * FROM lichgiangday WHERE MaLichHocPhan = ?";
            $stmt_lichgiang = $dbc->prepare($sql_lichgiang);
            $stmt_lichgiang->bind_param("s", $id);
            $stmt_lichgiang->execute();
            $result_lichgiang = $stmt_lichgiang->get_result();

            if ($result_lichgiang->num_rows > 0) {
                while ($row = $result_lichgiang->fetch_assoc()) {
                    echo "addScheduleRow('" . htmlspecialchars($row['LichGiang']) . "', '" . htmlspecialchars($row['GioBatDau']) . "', '" . htmlspecialchars($row['GioKetThuc']) . "');";
                }
            }
            $stmt_lichgiang->close();
        }
        ?>
    </script>
</body>
</html>

<?php
require_once '../Layout/footer.php';
mysqli_close($dbc);
?>