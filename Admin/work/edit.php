<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Kiểm tra kết nối cơ sở dữ liệu
if (!$dbc) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed");
}

// Function to get lecturers by faculty
function getGiangVienByKhoa($dbc, $maKhoa)
{
    // Loại bỏ TrangThai=1 để đảm bảo lấy được giảng viên, có thể thêm lại nếu cần
    $sql = "SELECT MaGiangVien, HoGiangVien, TenGiangVien FROM giangvien WHERE MaKhoa='$maKhoa'";
    error_log("SQL Query: $sql");
    $result = mysqli_query($dbc, $sql);
    if (!$result) {
        error_log("MySQL Error: " . mysqli_error($dbc));
        return [];
    }
    $giangvien = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $giangvien[] = $row;
    }
    error_log("Lecturers returned: " . count($giangvien));
    return $giangvien;
}

// Function to check for scheduling conflicts
function checkScheduleConflict($dbc, $maGiangVien, $ngayThucHien, $gioBatDau, $gioKetThuc, $diaDiem, $maCongViecHanhChinh)
{
    $sql = "SELECT * FROM thongtincongviechanhchinh 
            WHERE MaGiangVien = '$maGiangVien' 
            AND NgayThucHien = '$ngayThucHien' 
            AND DiaDiem = '$diaDiem'
            AND MaCongViecHanhChinh != '$maCongViecHanhChinh'";
    $result = mysqli_query($dbc, $sql);
    $conflicts = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $existingStart = strtotime($row['GioBatDau']);
        $existingEnd = $row['GioKetThuc'] ? strtotime($row['GioKetThuc']) : null;
        $newStart = strtotime($gioBatDau);
        $newEnd = $gioKetThuc ? strtotime($gioKetThuc) : null;

        if (!$existingEnd && !$newEnd) {
            if ($existingStart == $newStart) {
                $conflicts[] = $row;
            }
        } elseif (!$existingEnd) {
            if ($newStart >= $existingStart) {
                $conflicts[] = $row;
            }
        } elseif (!$newEnd) {
            if ($existingStart >= $newStart) {
                $conflicts[] = $row;
            }
        } else {
            if (($newStart >= $existingStart && $newStart <= $existingEnd) ||
                ($newEnd >= $existingStart && $newEnd <= $existingEnd) ||
                ($newStart <= $existingStart && $newEnd >= $existingEnd)
            ) {
                $conflicts[] = $row;
            }
        }
    }
    return $conflicts;
}

// Lấy danh sách khoa
$khoa_sql = "SELECT * FROM khoa WHERE TrangThai = 1";
$khoa_result = mysqli_query($dbc, $khoa_sql);
if (!$khoa_result) {
    error_log("Error fetching khoa: " . mysqli_error($dbc));
}
$khoa_options = [];
while ($row = mysqli_fetch_array($khoa_result)) {
    $khoa_options[$row['MaKhoa']] = $row['TenKhoa'];
}

// Xử lý AJAX để lấy danh sách giảng viên theo khoa
if (isset($_POST['action']) && $_POST['action'] == 'get_lecturers') {
    ob_clean();
    $maKhoa = isset($_POST['maKhoa']) ? mysqli_real_escape_string($dbc, $_POST['maKhoa']) : '';
    error_log("maKhoa received: $maKhoa");
    $giangVienList = getGiangVienByKhoa($dbc, $maKhoa);
    error_log("Lecturers found: " . count($giangVienList));
    header('Content-Type: application/json');
    echo json_encode($giangVienList);
    exit;
}

// Lấy dữ liệu công việc cần chỉnh sửa
$maCV = isset($_GET['id']) ? mysqli_real_escape_string($dbc, $_GET['id']) : '';
$existingData = [];
$existingLecturers = [];

if (!empty($maCV)) {
    $query = "SELECT * FROM congviechanhchinh WHERE MaCongViecHanhChinh = '$maCV'";
    $result = mysqli_query($dbc, $query);
    $existingData = mysqli_fetch_assoc($result);

    $queryLecturers = "SELECT tt.*, gv.MaKhoa 
                       FROM thongtincongviechanhchinh tt 
                       JOIN giangvien gv ON tt.MaGiangVien = gv.MaGiangVien 
                       WHERE tt.MaCongViecHanhChinh = '$maCV'";
    $resultLecturers = mysqli_query($dbc, $queryLecturers);
    while ($row = mysqli_fetch_assoc($resultLecturers)) {
        $existingLecturers[] = $row;
    }
}

// Handle form submission for update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $errors = array();

    // Validate required fields
    if (empty($_POST['MaCongViecHanhChinh'])) {
        $errors['MaCongViecHanhChinh'] = 'Vui lòng nhập mã công việc';
    }
    if (empty($_POST['tencongviec'])) {
        $errors['tencongviec'] = 'Vui lòng nhập tên công việc';
    }
    if (empty($_POST['DiaDiem'])) {
        $errors['DiaDiem'] = 'Vui lòng nhập địa điểm';
    }
    if (empty($_POST['tengiangvien']) || !array_filter($_POST['tengiangvien'])) {
        $errors['tengiangvien'] = 'Vui lòng chọn ít nhất một giảng viên';
    }
    if (empty($_POST['DateStart'])) {
        $errors['DateStart'] = 'Vui lòng nhập ngày thực hiện';
    } else {
        $DateStartInput = trim($_POST['DateStart']);
        if (!preg_match("/^\d{2}\/\d{2}\/\d{4}$/", $DateStartInput)) {
            $errors['DateStart'] = 'Ngày thực hiện phải có định dạng dd/mm/yyyy';
        } else {
            $dateParts = explode('/', $DateStartInput);
            if (!checkdate($dateParts[1], $dateParts[0], $dateParts[2])) {
                $errors['DateStart'] = 'Ngày thực hiện không hợp lệ';
            } else {
                $dateStart = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                $dateStart = mysqli_real_escape_string($dbc, $dateStart);
            }
        }
    }
    if (empty($_POST['TimeStart'])) {
        $errors['TimeStart'] = 'Vui lòng chọn giờ bắt đầu';
    }

    // Kiểm tra trùng lặp giảng viên
    if (!empty($_POST['tengiangvien'])) {
        $lecturerIds = array_filter($_POST['tengiangvien']);
        $lecturerCount = array_count_values($lecturerIds);
        $duplicateLecturers = [];

        foreach ($lecturerCount as $maGiangVien => $count) {
            if ($count > 1 && !empty($maGiangVien)) {
                $sql = "SELECT HoGiangVien, TenGiangVien FROM giangvien WHERE MaGiangVien = '$maGiangVien'";
                $result = mysqli_query($dbc, $sql);
                if ($row = mysqli_fetch_assoc($result)) {
                    $duplicateLecturers[] = $row['HoGiangVien'] . ' ' . $row['TenGiangVien'];
                }
            }
        }

        if (!empty($duplicateLecturers)) {
            $errors['tengiangvien'] = 'Các giảng viên bị trùng lặp: ' . implode(', ', $duplicateLecturers);
        }
    }

    // Kiểm tra trùng lịch
    if (!empty($_POST['tengiangvien']) && empty($errors)) {
        $timeStart = mysqli_real_escape_string($dbc, $_POST['TimeStart']);
        $timeEnd = !empty($_POST['TimeEnd']) ? mysqli_real_escape_string($dbc, $_POST['TimeEnd']) : null;
        $diaDiem = mysqli_real_escape_string($dbc, $_POST['DiaDiem']);
        $maCV = mysqli_real_escape_string($dbc, $_POST['MaCongViecHanhChinh']);
        $conflictMessages = [];

        foreach ($_POST['tengiangvien'] as $maGiangVien) {
            if (empty($maGiangVien)) continue;
            $maGiangVien = mysqli_real_escape_string($dbc, $maGiangVien);
            $conflicts = checkScheduleConflict($dbc, $maGiangVien, $dateStart, $timeStart, $timeEnd, $diaDiem, $maCV);

            if (!empty($conflicts)) {
                $sql = "SELECT HoGiangVien, TenGiangVien FROM giangvien WHERE MaGiangVien = '$maGiangVien'";
                $result = mysqli_query($dbc, $sql);
                if ($row = mysqli_fetch_assoc($result)) {
                    $conflictMessages[] = "Giảng viên {$row['HoGiangVien']} {$row['TenGiangVien']} có lịch trùng tại {$diaDiem} vào ngày {$dateStart}, giờ {$timeStart}" . ($timeEnd ? " - {$timeEnd}" : "");
                }
            }
        }

        if (!empty($conflictMessages)) {
            $errors['scheduleConflict'] = implode('<br>', $conflictMessages);
        }
    }

    if (empty($errors)) {
        $maCV = mysqli_real_escape_string($dbc, $_POST['MaCongViecHanhChinh']);
        $tenCV = mysqli_real_escape_string($dbc, $_POST['tencongviec']);
        $diaDiem = mysqli_real_escape_string($dbc, $_POST['DiaDiem']);
        $loaiCV = mysqli_real_escape_string($dbc, $_POST['loaicongviec']);
        $timeStart = mysqli_real_escape_string($dbc, $_POST['TimeStart']);
        $gioKetThuc = !empty($_POST['TimeEnd']) ? mysqli_real_escape_string($dbc, $_POST['TimeEnd']) : null;

        mysqli_begin_transaction($dbc);

        try {
            $q1 = "UPDATE congviechanhchinh SET TenCongViec = '$tenCV' WHERE MaCongViecHanhChinh = '$maCV'";
            $r1 = mysqli_query($dbc, $q1);

            if (!$r1) {
                throw new Exception("Lỗi khi cập nhật công việc hành chính: " . mysqli_error($dbc));
            }

            $qDelete = "DELETE FROM thongtincongviechanhchinh WHERE MaCongViecHanhChinh = '$maCV'";
            $rDelete = mysqli_query($dbc, $qDelete);

            if (!$rDelete) {
                throw new Exception("Lỗi khi xóa thông tin công việc cũ: " . mysqli_error($dbc));
            }

            foreach ($_POST['tengiangvien'] as $maGiangVien) {
                if (empty($maGiangVien)) continue;
                $maGiangVien = mysqli_real_escape_string($dbc, $maGiangVien);
                $gioKetThucValue = $gioKetThuc ? "'$gioKetThuc'" : 'NULL';
                $q2 = "INSERT INTO thongtincongviechanhchinh 
                       (MaCongViecHanhChinh, MaGiangVien, LoaiCongViec, NgayThucHien, GioBatDau, GioKetThuc, DiaDiem, TrangThai) 
                       VALUES ('$maCV', '$maGiangVien', '$loaiCV', '$dateStart', '$timeStart', $gioKetThucValue, '$diaDiem', 0)";
                $r2 = mysqli_query($dbc, $q2);

                if (!$r2) {
                    throw new Exception("Lỗi khi cập nhật thông tin công việc: " . mysqli_error($dbc));
                }
            }

            mysqli_commit($dbc);

            session_start();
            $_SESSION['success_message'] = 'Đã cập nhật thành công!';
            if (ob_get_length() > 0) {
                ob_end_clean();
            }
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($dbc);
            echo '<h1>System Error</h1><p class="error">' . $e->getMessage() . '</p>';
            mysqli_close($dbc);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa công việc hành chính</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- Thêm CSS của Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Tùy chỉnh Select2 để không tràn container */
        .select2-container {
            max-width: 100%;
            /* Ngăn dropdown vượt quá container */
            box-sizing: border-box;
            /* Đảm bảo padding và border không làm tràn */
        }

        .select2-container .select2-selection--single {
            height: 38px;
            /* Chiều cao đồng bộ với các input khác */
            line-height: 38px;
            /* Căn giữa nội dung */
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

        /* Bù padding của container để dropdown hiển thị đúng */
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
                            <strong class="text-blue">SỬA CÔNG VIỆC GIẢNG VIÊN</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="./index.php" class="btn-sm btn-info"> <i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                        </div>
                    </div>
                </div>
                <form action="" method="post" id="editForm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Tên công việc hành chính<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="tencongviec"
                                            value="<?php echo isset($existingData['TenCongViec']) ? htmlspecialchars($existingData['TenCongViec']) : ''; ?>">
                                        <?php if (isset($errors['tencongviec'])): ?>
                                            <small class="text-danger"><?php echo $errors['tencongviec']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Tên giảng viên <span class="text-danger"> (*)</span></label>
                                    <button type="button" id="addScheduleButton">Thêm giảng viên</button>
                                    <div id="scheduleContainer">
                                        <?php
                                        if (!empty($existingLecturers)) {
                                            foreach ($existingLecturers as $lecturer) {
                                                $selectedKhoa = $lecturer['MaKhoa'];
                                                $giangVienList = $selectedKhoa ? getGiangVienByKhoa($dbc, mysqli_real_escape_string($dbc, $selectedKhoa)) : [];
                                                echo '<div class="row mb-2">';
                                                echo '<div class="col-md-4 select2-container-parent">';
                                                echo '<select class="form-control khoa-select select2-khoa" name="khoa[]">';
                                                echo '<option value="">Chọn khoa</option>';
                                                foreach ($khoa_options as $maKhoa => $tenKhoa) {
                                                    $selected = ($selectedKhoa == $maKhoa) ? 'selected' : '';
                                                    echo "<option value='" . htmlspecialchars($maKhoa) . "' $selected>" . htmlspecialchars($tenKhoa) . "</option>";
                                                }
                                                echo '</select>';
                                                echo '</div>';
                                                echo '<div class="col-md-4 select2-container-parent">';
                                                echo '<select class="form-control giangvien-select select2-giangvien" name="tengiangvien[]">';
                                                echo '<option value="">Chọn giảng viên</option>';
                                                foreach ($giangVienList as $gv) {
                                                    $selected = ($lecturer['MaGiangVien'] == $gv['MaGiangVien']) ? 'selected' : '';
                                                    echo "<option value='" . htmlspecialchars($gv['MaGiangVien']) . "' $selected>" . htmlspecialchars($gv['HoGiangVien'] . ' ' . $gv['TenGiangVien']) . "</option>";
                                                }
                                                echo '</select>';
                                                echo '</div>';
                                                echo '<div class="col-md-2">';
                                                echo '<button type="button" class="btn btn-danger remove-button"><i class="fa fa-trash"></i></button>';
                                                echo '</div>';
                                                echo '</div>';
                                            }
                                        } else {
                                            echo '<div class="row mb-2">';
                                            echo '<div class="col-md-3 select2-container-parent">';
                                            echo '<select class="form-control khoa-select select2-khoa" name="khoa[]">';
                                            echo '<option value="">Chọn khoa</option>';
                                            foreach ($khoa_options as $maKhoa => $tenKhoa) {
                                                echo "<option value='" . htmlspecialchars($maKhoa) . "'>" . htmlspecialchars($tenKhoa) . "</option>";
                                            }
                                            echo '</select>';
                                            echo '</div>';
                                            echo '<div class="col-md-3 select2-container-parent">';
                                            echo '<select class="form-control giangvien-select select2-giangvien" name="tengiangvien[]">';
                                            echo '<option value="">Chọn giảng viên</option>';
                                            echo '</select>';
                                            echo '</div>';
                                            echo '<div class="col-md-2">';
                                            echo '<button type="button" class="btn btn-danger remove-button"><i class="fa fa-trash"></i></button>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                    <?php if (isset($errors['tengiangvien'])): ?>
                                        <small class="text-danger"><?php echo $errors['tengiangvien']; ?></small>
                                    <?php endif; ?>
                                    <?php if (isset($errors['scheduleConflict'])): ?>
                                        <small class="text-danger"><?php echo $errors['scheduleConflict']; ?></small>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label>Địa điểm<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="DiaDiem"
                                            value="<?php echo isset($existingLecturers[0]['DiaDiem']) ? htmlspecialchars($existingLecturers[0]['DiaDiem']) : ''; ?>">
                                        <?php if (isset($errors['DiaDiem'])): ?>
                                            <small class="text-danger"><?php echo $errors['DiaDiem']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Mã công việc hành chính<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-6">
                                        <input class="form-control" type="text" name="MaCongViecHanhChinh" readonly
                                            value="<?php echo isset($existingData['MaCongViecHanhChinh']) ? htmlspecialchars($existingData['MaCongViecHanhChinh']) : ''; ?>">
                                        <?php if (isset($errors['MaCongViecHanhChinh'])): ?>
                                            <small class="text-danger"><?php echo $errors['MaCongViecHanhChinh']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Loại công việc <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="loaicongviec" style="width: auto;">
                                            <option value="xuat" <?php echo (isset($existingLecturers[0]['LoaiCongViec']) && $existingLecturers[0]['LoaiCongViec'] == 'xuat') ? 'selected' : ''; ?>>Chọn loại công việc</option>
                                            <option value="xếp loại" <?php echo (isset($existingLecturers[0]['LoaiCongViec']) && $existingLecturers[0]['LoaiCongViec'] == 'xếp loại') ? 'selected' : ''; ?>>Xếp loại chất lượng viên chức</option>
                                            <option value="coi thi" <?php echo (isset($existingLecturers[0]['LoaiCongViec']) && $existingLecturers[0]['LoaiCongViec'] == 'coi thi') ? 'selected' : ''; ?>>Coi thi</option>
                                            <option value="phục vụ" <?php echo (isset($existingLecturers[0]['LoaiCongViec']) && $existingLecturers[0]['LoaiCongViec'] == 'phục vụ') ? 'selected' : ''; ?>>Phục vụ cộng đồng</option>
                                            <option value="công việc khác" <?php echo (isset($existingLecturers[0]['LoaiCongViec']) && $existingLecturers[0]['LoaiCongViec'] == 'công việc khác') ? 'selected' : ''; ?>>Công việc khác</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Ngày thực hiện<span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="DateStart" id="dateStart" required
                                            value="<?php echo isset($existingLecturers[0]['NgayThucHien']) ? date('d/m/Y', strtotime($existingLecturers[0]['NgayThucHien'])) : ''; ?>">
                                        <?php if (isset($errors['DateStart'])): ?>
                                            <small class="text-danger"><?php echo $errors['DateStart']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Giờ bắt đầu<span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="time" class="form-control" name="TimeStart" required
                                            value="<?php echo isset($existingLecturers[0]['GioBatDau']) ? htmlspecialchars($existingLecturers[0]['GioBatDau']) : ''; ?>">
                                        <?php if (isset($errors['TimeStart'])): ?>
                                            <small class="text-danger"><?php echo $errors['TimeStart']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Giờ kết thúc</label>
                                    <div class="col-md-6">
                                        <input type="time" class="form-control" name="TimeEnd"
                                            value="<?php echo isset($existingLecturers[0]['GioKetThuc']) ? htmlspecialchars($existingLecturers[0]['GioKetThuc']) : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-12">
                                    <button class="btn-sm btn-success" type="submit" name="update"> Lưu [Sửa] <i class="fa fa-save"></i> </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <script src="<?php echo BASE_URL ?>/Public/plugins/jquery/jquery.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
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

    <script>
        $(document).ready(function() {
            const scheduleContainer = $('#scheduleContainer');

            // Khởi tạo Datepicker
            $("#dateStart").datepicker({
                dateFormat: "dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                yearRange: "2000:2030"
            });

            // Hàm khởi tạo Select2 cho khoa và giảng viên
            function initializeSelect2(row) {
                const khoaSelect = row.find('.khoa-select');
                const giangVienSelect = row.find('.giangvien-select');

                khoaSelect.select2({
                    placeholder: "Chọn khoa",
                    allowClear: false,
                    width: 'resolve',
                    language: {
                        noResults: function() {
                            return "Không có dữ liệu";
                        }
                    }
                });

                giangVienSelect.select2({
                    placeholder: "Chọn giảng viên",
                    allowClear: false,
                    width: 'resolve',
                    language: {
                        noResults: function() {
                            return "Không có dữ liệu";
                        }
                    }
                });
            }

            // Hàm gắn sự kiện xóa
            function attachRemoveEvents() {
                $('.remove-button').off('click').on('click', function() {
                    $(this).closest('.row').remove();
                });
            }

            // Hàm tải danh sách giảng viên
            function loadLecturers(khoaSelect, giangVienSelect, selectedGiangVien = '') {
                const maKhoa = khoaSelect.val();
                console.log('Loading lecturers for maKhoa:', maKhoa);
                giangVienSelect.empty().append('<option value="">Chọn giảng viên</option>');

                if (maKhoa) {
                    $.ajax({
                        url: '<?php echo basename($_SERVER['PHP_SELF']); ?>',
                        type: 'POST',
                        data: {
                            action: 'get_lecturers',
                            maKhoa: maKhoa
                        },
                        dataType: 'json',
                        success: function(data) {
                            console.log('AJAX Success:', data);
                            if (data.length === 0) {
                                alert('Không có giảng viên nào cho khoa này.');
                            }
                            $.each(data, function(index, lecturer) {
                                const selected = lecturer.MaGiangVien === selectedGiangVien ? 'selected' : '';
                                giangVienSelect.append(
                                    `<option value="${lecturer.MaGiangVien}" ${selected}>${lecturer.HoGiangVien} ${lecturer.TenGiangVien}</option>`
                                );
                            });
                            // Cập nhật Select2 sau khi thêm options
                            giangVienSelect.trigger('change');
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX Error:', status, error, xhr.responseText);
                            alert('Lỗi khi tải danh sách giảng viên. Vui lòng kiểm tra kết nối hoặc liên hệ quản trị viên.');
                        }
                    });
                } else {
                    giangVienSelect.trigger('change');
                }
            }

            // Gắn sự kiện thay đổi khoa
            scheduleContainer.on('change', '.khoa-select', function() {
                const row = $(this).closest('.row');
                const giangVienSelect = row.find('.giangvien-select');
                console.log('Khoa changed, calling loadLecturers');
                loadLecturers($(this), giangVienSelect);
            });

            // Thêm giảng viên mới
            $('#addScheduleButton').on('click', function() {
                const newRow = $(`
                    <div class="row mb-2">
                        <div class="col-md-3 select2-container-parent">
                            <select  class="form-control khoa-select select2-khoa" name="khoa[]">
                                <option value="">Chọn khoa</option>
                                <?php foreach ($khoa_options as $maKhoa => $tenKhoa): ?>
                                    <option value="<?php echo htmlspecialchars($maKhoa); ?>"><?php echo htmlspecialchars($tenKhoa); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 select2-container-parent">
                            <select class="form-control giangvien-select select2-giangvien" name="tengiangvien[]">
                                <option value="">Chọn giảng viên</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-button">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `);
                scheduleContainer.append(newRow);
                initializeSelect2(newRow);
                attachRemoveEvents();
            });

            // Gắn sự kiện xóa
            attachRemoveEvents();

            // Khởi tạo Select2 cho các hàng hiện có
            scheduleContainer.find('.row').each(function() {
                initializeSelect2($(this));
                const khoaSelect = $(this).find('.khoa-select');
                const giangVienSelect = $(this).find('.giangvien-select');
                const selectedGiangVien = giangVienSelect.val();
                if (khoaSelect.val()) {
                    console.log('Reloading lecturers for maKhoa:', khoaSelect.val());
                    loadLecturers(khoaSelect, giangVienSelect, selectedGiangVien);
                }
            });

            // Ngăn reload không mong muốn
            $('#editForm').on('submit', function(e) {
                if (!$(e.target).find('button[name="update"]').is(':focus')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>

<?php
require_once BASE_PATH . '/Layout/footer.php';
mysqli_close($dbc);
?>