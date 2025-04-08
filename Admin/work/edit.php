<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

// Function to get lecturers by faculty
function getGiangVienByKhoa($dbc, $maKhoa)
{
    $sql = "SELECT MaGiangVien, HoGiangVien, TenGiangVien FROM giangvien WHERE TrangThai=1 AND MaKhoa='$maKhoa'";
    $result = mysqli_query($dbc, $sql);
    $giangvien = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $giangvien[] = $row;
    }
    return $giangvien;
}

// Lấy danh sách khoa
$khoa_sql = "SELECT * FROM khoa WHERE TrangThai = 1";
$khoa_result = mysqli_query($dbc, $khoa_sql);
$khoa_options = [];
while ($row = mysqli_fetch_array($khoa_result)) {
    $khoa_options[$row['MaKhoa']] = $row['TenKhoa'];
}

// Lấy dữ liệu công việc cần chỉnh sửa
$maCV = isset($_GET['id']) ? mysqli_real_escape_string($dbc, $_GET['id']) : '';
$existingData = [];
$existingLecturers = [];
$selectedKhoa = '';

if (!empty($maCV)) {
    // Lấy thông tin công việc hành chính
    $query = "SELECT * FROM congviechanhchinh WHERE MaCongViecHanhChinh = '$maCV'";
    $result = mysqli_query($dbc, $query);
    $existingData = mysqli_fetch_assoc($result);

    // Lấy danh sách giảng viên đã gán và xác định MaKhoa
    $queryLecturers = "SELECT tt.*, gv.MaKhoa 
                       FROM thongtincongviechanhchinh tt 
                       JOIN giangvien gv ON tt.MaGiangVien = gv.MaGiangVien 
                       WHERE tt.MaCongViecHanhChinh = '$maCV'";
    $resultLecturers = mysqli_query($dbc, $queryLecturers);
    while ($row = mysqli_fetch_assoc($resultLecturers)) {
        $existingLecturers[] = $row;
        if (empty($selectedKhoa)) {
            $selectedKhoa = $row['MaKhoa'];
        }
    }
}

// Xử lý khi chọn khoa từ form hoặc từ dữ liệu hiện có
$selectedKhoa = isset($_POST['Khoa']) ? $_POST['Khoa'] : $selectedKhoa;
$giangVienList = [];
if (!empty($selectedKhoa)) {
    $giangVienList = getGiangVienByKhoa($dbc, mysqli_real_escape_string($dbc, $selectedKhoa));
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
    if (empty($_POST['tengiangvien'])) {
        $errors['tengiangvien'] = 'Vui lòng chọn ít nhất một giảng viên';
    }
    if (empty($_POST['DateStart'])) {
        $errors['DateStart'] = 'Vui lòng chọn ngày thực hiện';
    }
    if (empty($_POST['TimeStart'])) {
        $errors['TimeStart'] = 'Vui lòng chọn giờ bắt đầu';
    }

    // Kiểm tra trùng lặp giảng viên trong danh sách được gửi
    if (!empty($_POST['tengiangvien'])) {
        $lecturerIds = $_POST['tengiangvien'];
        $lecturerCount = array_count_values($lecturerIds); // Đếm số lần xuất hiện của mỗi MaGiangVien
        $duplicateLecturers = [];

        // Tìm các giảng viên bị trùng
        foreach ($lecturerCount as $maGiangVien => $count) {
            if ($count > 1 && !empty($maGiangVien)) { // Chỉ kiểm tra nếu trùng và không phải rỗng
                foreach ($giangVienList as $gv) {
                    if ($gv['MaGiangVien'] == $maGiangVien) {
                        $duplicateLecturers[] = $gv['HoGiangVien'] . ' ' . $gv['TenGiangVien'];
                        break;
                    }
                }
            }
        }

        // Nếu có giảng viên trùng, thêm thông báo lỗi với tên giảng viên
        if (!empty($duplicateLecturers)) {
            $errors['tengiangvien'] = 'Giảng viên đã được chọn: ' . implode(', ', $duplicateLecturers);
        }
    }

    if (empty($errors)) {
        $maCV = mysqli_real_escape_string($dbc, $_POST['MaCongViecHanhChinh']);
        $tenCV = mysqli_real_escape_string($dbc, $_POST['tencongviec']);
        $diaDiem = mysqli_real_escape_string($dbc, $_POST['DiaDiem']);
        $loaiCV = mysqli_real_escape_string($dbc, $_POST['loaicongviec']);
        $dateStart = mysqli_real_escape_string($dbc, $_POST['DateStart']);
        $timeStart = mysqli_real_escape_string($dbc, $_POST['TimeStart']);

        mysqli_begin_transaction($dbc);

        try {
            // Cập nhật bảng congviechanhchinh
            $q1 = "UPDATE congviechanhchinh SET TenCongViec = '$tenCV' WHERE MaCongViecHanhChinh = '$maCV'";
            $r1 = mysqli_query($dbc, $q1);

            if (!$r1) {
                throw new Exception("Lỗi khi cập nhật công việc hành chính: " . mysqli_error($dbc));
            }

            // Xóa danh sách giảng viên cũ
            $qDelete = "DELETE FROM thongtincongviechanhchinh WHERE MaCongViecHanhChinh = '$maCV'";
            $rDelete = mysqli_query($dbc, $qDelete);

            if (!$rDelete) {
                throw new Exception("Lỗi khi xóa thông tin công việc cũ: " . mysqli_error($dbc));
            }

            // Thêm lại danh sách giảng viên mới
            foreach ($_POST['tengiangvien'] as $maGiangVien) {
                $maGiangVien = mysqli_real_escape_string($dbc, $maGiangVien);
                $q2 = "INSERT INTO thongtincongviechanhchinh 
                       (MaCongViecHanhChinh, MaGiangVien, LoaiCongViec, NgayThucHien, GioBatDau, DiaDiem) 
                       VALUES ('$maCV', '$maGiangVien', '$loaiCV', '$dateStart', '$timeStart', '$diaDiem')";
                $r2 = mysqli_query($dbc, $q2);

                if (!$r2) {
                    throw new Exception("Lỗi khi cập nhật thông tin công việc: " . mysqli_error($dbc));
                }
            }

            mysqli_commit($dbc);

            session_start();
            $_SESSION['success_message'] = 'Đã cập nhật thành công!';
            header("Location: index.php");
            ob_end_flush();
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
    <title>Sửa lịch giảng dạy</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
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
                                    <label>Khoa <span class="text-danger">(*)</span></label>
                                    <div class="col-md-7">
                                        <select class="form-control" name="Khoa" id="khoaSelect" style="width: auto;">
                                            <option value="">-- Chọn khoa --</option>
                                            <?php foreach ($khoa_options as $maKhoa => $tenKhoa): ?>
                                                <option value="<?php echo $maKhoa; ?>" <?php echo $selectedKhoa == $maKhoa ? 'selected' : ''; ?>>
                                                    <?php echo $tenKhoa; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Mã công việc hành chính<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="MaCongViecHanhChinh" readonly
                                            value="<?php echo isset($existingData['MaCongViecHanhChinh']) ? htmlspecialchars($existingData['MaCongViecHanhChinh']) : ''; ?>">
                                        <?php if (isset($errors['MaCongViecHanhChinh'])): ?>
                                            <small class="text-danger"><?php echo $errors['MaCongViecHanhChinh']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

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
                                                echo '<div class="row mb-2">';
                                                echo '<div class="col-md-2">';
                                                echo '<select class="form-control" name="tengiangvien[]">';
                                                echo '<option value="">Chọn giảng viên</option>';
                                                foreach ($giangVienList as $gv) {
                                                    $selected = ($lecturer['MaGiangVien'] == $gv['MaGiangVien']) ? 'selected' : '';
                                                    echo "<option value='{$gv['MaGiangVien']}' $selected>{$gv['HoGiangVien']} {$gv['TenGiangVien']}</option>";
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
                                            echo '<div class="col-md-2">';
                                            echo '<select class="form-control" name="tengiangvien[]">';
                                            echo '<option value="">Chọn giảng viên</option>';
                                            foreach ($giangVienList as $lecturer) {
                                                echo "<option value='{$lecturer['MaGiangVien']}'>{$lecturer['HoGiangVien']} {$lecturer['TenGiangVien']}</option>";
                                            }
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
                                    <label>Loại công việc <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="loaicongviec" style="width: auto;">
                                            <option value="xuat" <?php echo (isset($existingLecturers[0]['LoaiCongViec']) && $existingLecturers[0]['LoaiCongViec'] == 'xuat') ? 'selected' : ''; ?>>Chọn loại công việc</option>
                                            <option value="xl" <?php echo (isset($existingLecturers[0]['LoaiCongViec']) && $existingLecturers[0]['LoaiCongViec'] == 'xl') ? 'selected' : ''; ?>>Xếp loại chất lượng viên chức</option>
                                            <option value="ct" <?php echo (isset($existingLecturers[0]['LoaiCongViec']) && $existingLecturers[0]['LoaiCongViec'] == 'ct') ? 'selected' : ''; ?>>Coi thi</option>
                                            <option value="pv" <?php echo (isset($existingLecturers[0]['LoaiCongViec']) && $existingLecturers[0]['LoaiCongViec'] == 'pv') ? 'selected' : ''; ?>>Phục vụ cộng đồng</option>
                                            <option value="ck" <?php echo (isset($existingLecturers[0]['LoaiCongViec']) && $existingLecturers[0]['LoaiCongViec'] == 'ck') ? 'selected' : ''; ?>>Công việc khác</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Ngày thực hiện<span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="date" class="form-control" name="DateStart" required style="width: auto;"
                                            value="<?php echo isset($existingLecturers[0]['NgayThucHien']) ? htmlspecialchars($existingLecturers[0]['NgayThucHien']) : ''; ?>">
                                        <?php if (isset($errors['DateStart'])): ?>
                                            <small class="text-danger"><?php echo $errors['DateStart']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Giờ bắt đầu<span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="time" class="form-control" name="TimeStart" required style="width: auto;"
                                            value="<?php echo isset($existingLecturers[0]['GioBatDau']) ? htmlspecialchars($existingLecturers[0]['GioBatDau']) : ''; ?>">
                                        <?php if (isset($errors['TimeStart'])): ?>
                                            <small class="text-danger"><?php echo $errors['TimeStart']; ?></small>
                                        <?php endif; ?>
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
    <script src="<?php echo BASE_URL ?>/Public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/dist/js/adminlte.min.js"></script>
    <script>
        $(document).ready(function() {
            const scheduleContainer = $('#scheduleContainer');

            // Khi chọn khoa
            $('#khoaSelect').on('change', function(e) {
                e.preventDefault();
                var formData = $('#editForm').serialize();

                scheduleContainer.empty().append(`
                    <div class="row mb-2">
                        <div class="col-md-2">
                            <select class="form-control" name="tengiangvien[]">
                                <option value="">Chọn giảng viên</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-button"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                `);

                $.post('', formData, function(data) {
                    var $newContent = $(data);
                    scheduleContainer.html($newContent.find('#scheduleContainer').html());
                    attachRemoveEvents();
                });
            });

            // Chỉ cho phép reload khi nhấn "Lưu [Sửa]"
            $('#editForm').on('submit', function(e) {
                if (!$(e.target).find('button[name="update"]').is(':focus')) {
                    e.preventDefault();
                }
            });

            // Hàm gắn sự kiện xóa
            function attachRemoveEvents() {
                $('.remove-button').off('click').on('click', function() {
                    $(this).closest('.row').remove();
                });
            }

            attachRemoveEvents();

            // Thêm giảng viên mới
            $('#addScheduleButton').on('click', function() {
                const khoaSelected = $('#khoaSelect').val();
                if (!khoaSelected) {
                    alert('Vui lòng chọn khoa trước!');
                    return;
                }

                var formData = $('#editForm').serialize();
                $.post('', formData, function(data) {
                    var $newContent = $(data);
                    var $lecturerSelect = $newContent.find('#scheduleContainer select').first().clone();
                    $lecturerSelect.val(''); // Đặt giá trị mặc định là "Chọn giảng viên"
                    var newRow = `
                        <div class="row mb-2">
                            <div class="col-md-2">
                                ${$lecturerSelect.prop('outerHTML')}
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger remove-button">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    scheduleContainer.append(newRow);
                    attachRemoveEvents();
                });
            });
        });
    </script>
</body>

</html>

<?php
require_once '../Layout/footer.php';
mysqli_close($dbc);
?>