<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . '/Database/connect-database.php';

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

// Kiểm tra quyền và lấy MaKhoa của Admin
$user_id = $_SESSION['user_id'];
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
}

// Lấy danh sách khoa
$khoa_sql = "SELECT * FROM khoa WHERE TrangThai = 1";
$khoa_result = mysqli_query($dbc, $khoa_sql);
$khoa_options = [];
while ($row = mysqli_fetch_array($khoa_result)) {
    $khoa_options[$row['MaKhoa']] = $row['TenKhoa'];
}

// Xử lý khi chọn khoa
$selectedKhoa = $quyen === 'Admin' ? $ma_khoa : (isset($_POST['Khoa']) ? $_POST['Khoa'] : '');
$giangVienList = [];
if (!empty($selectedKhoa)) {
    $giangVienList = getGiangVienByKhoa($dbc, mysqli_real_escape_string($dbc, $selectedKhoa));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $errors = array();

    // Validate required fields
    if (empty($_POST['tengiangvien'])) {
        $errors['tengiangvien'] = 'Vui lòng chọn giảng viên';
    }
    if (empty($_POST['XepLoai'])) {
        $errors['XepLoai'] = 'Vui lòng chọn xếp loại';
    }
    if (empty($_POST['NgayXepLoai'])) {
        $errors['NgayXepLoai'] = 'Vui lòng chọn ngày xếp loại';
    } else {
        // Kiểm tra định dạng ngày dd/mm/yyyy
        $ngayXepLoaiInput = trim($_POST['NgayXepLoai']);
        if (!preg_match("/^\d{2}\/\d{2}\/\d{4}$/", $ngayXepLoaiInput)) {
            $errors['NgayXepLoai'] = 'Ngày xếp loại phải có định dạng dd/mm/yyyy';
        } else {
            // Chuyển từ dd/mm/yyyy sang yyyy-mm-dd
            $dateParts = explode('/', $ngayXepLoaiInput);
            if (!checkdate($dateParts[1], $dateParts[0], $dateParts[2])) {
                $errors['NgayXepLoai'] = 'Ngày xếp loại không hợp lệ';
            } else {
                $ngayXepLoai = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0]; // Định dạng yyyy-mm-dd
                $ngayXepLoai = mysqli_real_escape_string($dbc, $ngayXepLoai);
            }
        }
    }

    // Kiểm tra trùng lặp đánh giá trong cùng ngày
    if (!empty($_POST['tengiangvien']) && !empty($ngayXepLoai) && empty($errors['NgayXepLoai'])) {
        $maGiangVien = mysqli_real_escape_string($dbc, $_POST['tengiangvien']);
        $checkDuplicate = "SELECT * FROM thongtinhosodanhgia WHERE MaHoSo = '$maGiangVien' AND NgayXepLoai = '$ngayXepLoai'";
        $resultDuplicate = mysqli_query($dbc, $checkDuplicate);
        if (mysqli_num_rows($resultDuplicate) > 0) {
            $errors['tengiangvien'] = 'Giảng viên này đã được đánh giá trong ngày này';
        }
    }

    if (empty($errors)) {
        $maGiangVien = mysqli_real_escape_string($dbc, $_POST['tengiangvien']);
        $xepLoai = mysqli_real_escape_string($dbc, $_POST['XepLoai']);

        mysqli_begin_transaction($dbc);

        try {
            // Kiểm tra xem hồ sơ đánh giá đã tồn tại chưa
            $checkHoSo = "SELECT MaHoSo FROM hosodanhgiavienchuc WHERE MaHoSo = '$maGiangVien'";
            $resultHoSo = mysqli_query($dbc, $checkHoSo);
            if (mysqli_num_rows($resultHoSo) == 0) {
                // Tạo mới hồ sơ đánh giá
                $insertHoSo = "INSERT INTO hosodanhgiavienchuc (MaHoSo) VALUES ('$maGiangVien')";
                if (!mysqli_query($dbc, $insertHoSo)) {
                    throw new Exception("Lỗi khi tạo hồ sơ đánh giá: " . mysqli_error($dbc));
                }
            }

            // Thêm thông tin đánh giá
            $insertDanhGia = "INSERT INTO thongtinhosodanhgia (MaHoSo, XepLoai, NgayXepLoai) 
                              VALUES ('$maGiangVien', '$xepLoai', '$ngayXepLoai')";
            if (!mysqli_query($dbc, $insertDanhGia)) {
                throw new Exception("Lỗi khi thêm thông tin đánh giá: " . mysqli_error($dbc));
            }

            mysqli_commit($dbc);

            session_start();
            $_SESSION['success_message'] = 'Đã thêm đánh giá thành công!';
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
    <title>Đánh giá viên chức</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
    <!-- Thêm CSS của Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- jQuery UI CSS cho Datepicker -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <style>
        /* Tùy chỉnh Select2 để không tràn container */
        .select2-container {
            max-width: 100%;
            box-sizing: border-box;
        }

        .select2-container .select2-selection--single {
            height: 38px;
            line-height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }

        .select2-selection__rendered {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

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
                            <strong class="text-blue">ĐÁNH GIÁ VIÊN CHỨC</strong>
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
                                    <div class="col-md-7 select2-container-parent">
                                        <?php if ($quyen === 'Admin' && $ma_khoa): ?>
                                            <input type="hidden" name="Khoa" value="<?php echo htmlspecialchars($ma_khoa); ?>">
                                            <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($khoa_options[$ma_khoa]); ?>">
                                        <?php else: ?>
                                            <select class="form-control select2-khoa" name="Khoa" id="khoaSelect">
                                                <option value="">Chọn khoa</option>
                                                <?php foreach ($khoa_options as $maKhoa => $tenKhoa): ?>
                                                    <option value="<?php echo htmlspecialchars($maKhoa); ?>" <?php echo $selectedKhoa == $maKhoa ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($tenKhoa); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Tên giảng viên <span class="text-danger">(*)</span></label>
                                    <div class="col-md-7 select2-container-parent">
                                        <select class="form-control select2-giangvien" name="tengiangvien" id="giangVienSelect">
                                            <option value="">Chọn giảng viên</option>
                                            <?php foreach ($giangVienList as $lecturer): ?>
                                                <option value="<?php echo htmlspecialchars($lecturer['MaGiangVien']); ?>"
                                                    <?php echo (isset($_POST['tengiangvien']) && $_POST['tengiangvien'] == $lecturer['MaGiangVien']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($lecturer['HoGiangVien'] . ' ' . $lecturer['TenGiangVien']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errors['tengiangvien'])): ?>
                                            <small class="text-danger"><?php echo $errors['tengiangvien']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Xếp loại <span class="text-danger">(*)</span></label>
                                    <div class="col-md-12">
                                        <select class="form-control" name="XepLoai">
                                            <option value="">Chọn xếp loại</option>
                                            <option value="Hoàn thành xuất sắc" <?php echo (isset($_POST['XepLoai']) && $_POST['XepLoai'] == 'Hoàn thành xuất sắc') ? 'selected' : ''; ?>>Hoàn thành xuất sắc</option>
                                            <option value="Hoàn thành tốt" <?php echo (isset($_POST['XepLoai']) && $_POST['XepLoai'] == 'Hoàn thành tốt') ? 'selected' : ''; ?>>Hoàn thành tốt</option>
                                            <option value="Hoàn thành" <?php echo (isset($_POST['XepLoai']) && $_POST['XepLoai'] == 'Hoàn thành') ? 'selected' : ''; ?>>Hoàn thành</option>
                                            <option value="Không hoàn thành" <?php echo (isset($_POST['XepLoai']) && $_POST['XepLoai'] == 'Không hoàn thành') ? 'selected' : ''; ?>>Không hoàn thành</option>
                                        </select>
                                        <?php if (isset($errors['XepLoai'])): ?>
                                            <small class="text-danger"><?php echo $errors['XepLoai']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Ngày xếp loại <span class="text-danger">(*)</span></label>
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" name="NgayXepLoai" id="ngayXepLoai" required
                                            value="<?php echo isset($_POST['NgayXepLoai']) ? htmlspecialchars($_POST['NgayXepLoai']) : ''; ?>">
                                        <?php if (isset($errors['NgayXepLoai'])): ?>
                                            <small class="text-danger"><?php echo $errors['NgayXepLoai']; ?></small>
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
    <script src="<?php echo BASE_URL ?>/dist/js/adminlte.min.js"></script>
    <script src="<?php echo BASE_URL ?>/dist/js/demo.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!-- Thêm JS của Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Khởi tạo Datepicker cho Ngày xếp loại
            $("#ngayXepLoai").datepicker({
                dateFormat: "dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                yearRange: "1900:2100"
            });

            // Khởi tạo Select2 cho dropdown Khoa và Tên giảng viên
            $('.select2-khoa').select2({
                placeholder: "Chọn khoa",
                allowClear: false,
                width: 'resolve',
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                }
            });

            $('.select2-giangvien').select2({
                placeholder: "Chọn giảng viên",
                allowClear: false,
                width: 'resolve',
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                }
            });

            // Khi chọn khoa (chỉ áp dụng cho non-Admin)
            <?php if ($quyen !== 'Admin'): ?>
                $('#khoaSelect').on('change', function(e) {
                    e.preventDefault();
                    var formData = $('#addForm').serialize();

                    // Gửi POST để lấy danh sách giảng viên mới
                    $.post('', formData, function(data) {
                        var $newContent = $(data);
                        var $newGiangVienSelect = $newContent.find('select[name="tengiangvien"]').html();
                        $('select[name="tengiangvien"]').html($newGiangVienSelect).trigger('change.select2');
                    });
                });
            <?php endif; ?>

            // Chỉ cho phép reload khi nhấn "Lưu [Thêm]"
            $('#addForm').on('submit', function(e) {
                if (!$(e.target).find('button[name="create"]').is(':focus')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>

<?php
require_once '../Layout/footer.php';
mysqli_close($dbc);
?>