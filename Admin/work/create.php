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

// Xử lý khi chọn khoa
$selectedKhoa = isset($_POST['Khoa']) ? $_POST['Khoa'] : '';
$giangVienList = [];
if (!empty($selectedKhoa)) {
    $giangVienList = getGiangVienByKhoa($dbc, mysqli_real_escape_string($dbc, $selectedKhoa));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
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

    if (empty($errors)) {
        $maCV = mysqli_real_escape_string($dbc, $_POST['MaCongViecHanhChinh']);
        $tenCV = mysqli_real_escape_string($dbc, $_POST['tencongviec']);
        $diaDiem = mysqli_real_escape_string($dbc, $_POST['DiaDiem']);
        $loaiCV = mysqli_real_escape_string($dbc, $_POST['loaicongviec']);
        $dateStart = mysqli_real_escape_string($dbc, $_POST['DateStart']);
        $timeStart = mysqli_real_escape_string($dbc, $_POST['TimeStart']);

        mysqli_begin_transaction($dbc);

        try {
            $q1 = "INSERT INTO congviechanhchinh (MaCongViecHanhChinh, TenCongViec) 
                   VALUES ('$maCV', '$tenCV')";
            $r1 = mysqli_query($dbc, $q1);

            if (!$r1) {
                throw new Exception("Lỗi khi thêm công việc hành chính: " . mysqli_error($dbc));
            }

            foreach ($_POST['tengiangvien'] as $maGiangVien) {
                $maGiangVien = mysqli_real_escape_string($dbc, $maGiangVien);
                $q2 = "INSERT INTO thongtincongviechanhchinh 
                       (MaCongViecHanhChinh, MaGiangVien, LoaiCongViec, NgayThucHien, GioBatDau, DiaDiem) 
                       VALUES ('$maCV', '$maGiangVien', '$loaiCV', '$dateStart', '$timeStart', '$diaDiem')";
                $r2 = mysqli_query($dbc, $q2);

                if (!$r2) {
                    throw new Exception("Lỗi khi thêm thông tin công việc: " . mysqli_error($dbc));
                }
            }

            mysqli_commit($dbc);

            session_start();
            $_SESSION['success_message'] = 'Đã thêm thành công!';
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
    <title>Thêm lịch giảng dạy</title>
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
                            <strong class="text-blue">THÊM CÔNG VIỆC GIẢNG VIÊN</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="./index.php" class="btn-sm btn-info"> <i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                        </div>
                    </div>
                </div>
                <form action="" method="post" id="addForm">
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
                                        <input class="form-control" type="text" name="MaCongViecHanhChinh"
                                            value="<?php echo isset($_POST['MaCongViecHanhChinh']) ? htmlspecialchars($_POST['MaCongViecHanhChinh']) : ''; ?>">
                                        <?php if (isset($errors['MaCongViecHanhChinh'])): ?>
                                            <small class="text-danger"><?php echo $errors['MaCongViecHanhChinh']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Tên công việc hành chính<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="tencongviec"
                                            value="<?php echo isset($_POST['tencongviec']) ? htmlspecialchars($_POST['tencongviec']) : ''; ?>">
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
                                        if (isset($_POST['tengiangvien']) && is_array($_POST['tengiangvien'])) {
                                            foreach ($_POST['tengiangvien'] as $gv) {
                                                echo '<div class="row mb-2">';
                                                echo '<div class="col-md-2">';
                                                echo '<select class="form-control" name="tengiangvien[]">';
                                                echo '<option value="">Chọn giảng viên</option>';
                                                foreach ($giangVienList as $lecturer) {
                                                    $selected = ($gv == $lecturer['MaGiangVien']) ? 'selected' : '';
                                                    echo "<option value='{$lecturer['MaGiangVien']}' $selected>{$lecturer['HoGiangVien']} {$lecturer['TenGiangVien']}</option>";
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
                                            value="<?php echo isset($_POST['DiaDiem']) ? htmlspecialchars($_POST['DiaDiem']) : ''; ?>">
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
                                            <option value="xuat" <?php echo (isset($_POST['loaicongviec']) && $_POST['loaicongviec'] == 'xuat') ? 'selected' : ''; ?>>Chọn loại công việc</option>
                                            <option value="xl" <?php echo (isset($_POST['loaicongviec']) && $_POST['loaicongviec'] == 'xl') ? 'selected' : ''; ?>>Xếp loại chất lượng viên chức</option>
                                            <option value="ct" <?php echo (isset($_POST['loaicongviec']) && $_POST['loaicongviec'] == 'ct') ? 'selected' : ''; ?>>Coi thi</option>
                                            <option value="pv" <?php echo (isset($_POST['loaicongviec']) && $_POST['loaicongviec'] == 'pv') ? 'selected' : ''; ?>>Phục vụ cộng đồng</option>
                                            <option value="ck" <?php echo (isset($_POST['loaicongviec']) && $_POST['loaicongviec'] == 'ck') ? 'selected' : ''; ?>>Công việc khác</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Ngày thực hiện<span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="date" class="form-control" name="DateStart" required style="width: auto;"
                                            value="<?php echo isset($_POST['DateStart']) ? htmlspecialchars($_POST['DateStart']) : ''; ?>">
                                        <?php if (isset($errors['DateStart'])): ?>
                                            <small class="text-danger"><?php echo $errors['DateStart']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Giờ bắt đầu<span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="time" class="form-control" name="TimeStart" required style="width: auto;"
                                            value="<?php echo isset($_POST['TimeStart']) ? htmlspecialchars($_POST['TimeStart']) : ''; ?>">
                                        <?php if (isset($errors['TimeStart'])): ?>
                                            <small class="text-danger"><?php echo $errors['TimeStart']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-12">
                                    <button class="btn-sm btn-success" type="submit" name="create"> Lưu [Thêm] <i class="fa fa-save"></i> </button>
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
                e.preventDefault(); // Ngăn reload trang
                var formData = $('#addForm').serialize();

                // Reset danh sách giảng viên ngay lập tức
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

                // Gửi POST để lấy danh sách giảng viên mới
                $.post('', formData, function(data) {
                    var $newContent = $(data);
                    scheduleContainer.html($newContent.find('#scheduleContainer').html());
                    attachRemoveEvents(); // Gắn lại sự kiện xóa
                });
            });

            // Chỉ cho phép reload khi nhấn "Lưu [Thêm]"
            $('#addForm').on('submit', function(e) {
                if (!$(e.target).find('button[name="create"]').is(':focus')) {
                    e.preventDefault(); // Ngăn reload trừ khi nhấn nút "Lưu"
                }
            });

            // Hàm gắn sự kiện xóa cho các nút "Remove"
            function attachRemoveEvents() {
                $('.remove-button').off('click').on('click', function() {
                    $(this).closest('.row').remove();
                });
            }

            // Gắn sự kiện xóa cho các nút hiện tại
            attachRemoveEvents();

            // Thêm giảng viên mới
            $('#addScheduleButton').on('click', function() {
                const khoaSelected = $('#khoaSelect').val();
                if (!khoaSelected) {
                    alert('Vui lòng chọn khoa trước!');
                    return;
                }

                var formData = $('#addForm').serialize();
                $.post('', formData, function(data) {
                    var $newContent = $(data);
                    var $lecturerSelect = $newContent.find('#scheduleContainer select').first().clone();
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