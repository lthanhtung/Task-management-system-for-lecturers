<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Lấy MaThongTinHoSo từ URL
$maThongTinHoSo = isset($_GET['MaThongTinHoSo']) ? mysqli_real_escape_string($dbc, $_GET['MaThongTinHoSo']) : '';
if (empty($maThongTinHoSo)) {
    header("Location: index.php");
    exit();
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

// Lấy thông tin đánh giá hiện tại
$query = "
    SELECT t.*, g.MaKhoa, g.HoGiangVien, g.TenGiangVien, k.TenKhoa
    FROM thongtinhosodanhgia t
    JOIN hosodanhgiavienchuc h ON t.MaHoSo = h.MaHoSo
    JOIN giangvien g ON h.MaHoSo = g.MaGiangVien
    JOIN khoa k ON g.MaKhoa = k.MaKhoa
    WHERE t.MaThongTinHoSo = '$maThongTinHoSo'";
$result = mysqli_query($dbc, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo '<h1>Lỗi</h1><p class="error">Không tìm thấy thông tin đánh giá.</p>';
    mysqli_close($dbc);
    exit();
}
$currentEvaluation = mysqli_fetch_assoc($result);
$selectedKhoa = $quyen === 'Admin' ? $ma_khoa : $currentEvaluation['MaKhoa'];

// Chuyển định dạng ngày từ yyyy-mm-dd sang dd/mm/yyyy
$ngayXepLoaiDisplay = '';
if (!empty($currentEvaluation['NgayXepLoai'])) {
    $ngayXepLoaiDisplay = date('d/m/Y', strtotime($currentEvaluation['NgayXepLoai']));
}

// Function to get lecturers by faculty
function getGiangVienByKhoa($dbc, $maKhoa)
{
    $sql = "SELECT MaGiangVien, CONCAT(HoGiangVien, ' ', TenGiangVien) AS TenGiangVien 
            FROM giangvien 
            WHERE TrangThai = 1 AND MaKhoa = '$maKhoa'";
    $result = mysqli_query($dbc, $sql);
    $giangvien = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $giangvien[] = $row;
    }
    return $giangvien;
}

// Lấy danh sách giảng viên theo khoa
$giangVienList = getGiangVienByKhoa($dbc, mysqli_real_escape_string($dbc, $selectedKhoa));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
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
        $ngayXepLoaiInput = $_POST['NgayXepLoai'];
        if (!preg_match("/^\d{2}\/\d{2}\/\d{4}$/", $ngayXepLoaiInput)) {
            $errors['NgayXepLoai'] = 'Ngày xếp loại phải có định dạng dd/mm/yyyy';
        } else {
            // Chuyển từ dd/mm/yyyy sang yyyy-mm-dd
            $dateParts = explode('/', $ngayXepLoaiInput);
            if (!checkdate($dateParts[1], $dateParts[0], $dateParts[2])) {
                $errors['NgayXepLoai'] = 'Ngày xếp loại không hợp lệ';
            }
        }
    }

    // Kiểm tra trùng lặp đánh giá trong cùng ngày (loại trừ bản ghi hiện tại)
    if (!empty($_POST['tengiangvien']) && !empty($_POST['NgayXepLoai']) && empty($errors['NgayXepLoai'])) {
        $maGiangVien = mysqli_real_escape_string($dbc, $_POST['tengiangvien']);
        $dateParts = explode('/', $_POST['NgayXepLoai']);
        $ngayXepLoai = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0]; // Chuyển sang yyyy-mm-dd
        $checkDuplicate = "SELECT * FROM thongtinhosodanhgia 
                          WHERE MaHoSo = '$maGiangVien' 
                          AND NgayXepLoai = '$ngayXepLoai' 
                          AND MaThongTinHoSo != '$maThongTinHoSo'";
        $resultDuplicate = mysqli_query($dbc, $checkDuplicate);
        if (mysqli_num_rows($resultDuplicate) > 0) {
            $errors['tengiangvien'] = 'Giảng viên này đã được đánh giá trong ngày này';
        }
    }

    if (empty($errors)) {
        $maGiangVien = mysqli_real_escape_string($dbc, $_POST['tengiangvien']);
        $xepLoai = mysqli_real_escape_string($dbc, $_POST['XepLoai']);
        $ngayXepLoai = mysqli_real_escape_string($dbc, $ngayXepLoai); // Đã ở định dạng yyyy-mm-dd

        mysqli_begin_transaction($dbc);

        try {
            // Cập nhật thông tin đánh giá
            $updateDanhGia = "UPDATE thongtinhosodanhgia 
SET MaHoSo = ?, 
                                  XepLoai = ?, 
                                  NgayXepLoai = ? 
                              WHERE MaThongTinHoSo = ?";
            $stmt = mysqli_prepare($dbc, $updateDanhGia);
            mysqli_stmt_bind_param($stmt, "sssi", $maGiangVien, $xepLoai, $ngayXepLoai, $maThongTinHoSo);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Lỗi khi cập nhật thông tin đánh giá: " . mysqli_error($dbc));
            }
            mysqli_stmt_close($stmt);

            mysqli_commit($dbc);

            $_SESSION['success_message'] = 'Đã cập nhật đánh giá thành công!';
            header("Location: index.php");
            ob_end_flush();
            exit();
        } catch (Exception $e) {
            mysqli_rollback($dbc);
            echo '<h1>Error</h1><p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
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
    <title>Chỉnh sửa đánh giá viên chức</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.css">
    <!-- Thêm CSS cho Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- jQuery UI CSS cho Datepicker -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

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
                            <strong class="text-blue">CHỈNH SỬA ĐÁNH GIÁ VIÊN CHỨC</strong>
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
                                        <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($currentEvaluation['TenKhoa']); ?>">
                                        <input type="hidden" name="Khoa" value="<?php echo htmlspecialchars($selectedKhoa); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Tên giảng viên <span class="text-danger">(*)</span></label>
                                    <div class="col-md-7 select2-container-parent">
                                        <select class="form-control select2-giangvien" name="tengiangvien" id="giangVienSelect">
                                            <option value="">Chọn giảng viên</option>
                                            <?php foreach ($giangVienList as $lecturer): ?>
                                                <option value="<?php echo htmlspecialchars($lecturer['MaGiangVien']); ?>"
                                                    <?php echo ($lecturer['MaGiangVien'] == $currentEvaluation['MaHoSo']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($lecturer['TenGiangVien']); ?>
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
                                            <option value="Hoàn thành xuất sắc" <?php echo $currentEvaluation['XepLoai'] == 'Hoàn thành xuất sắc' ? 'selected' : ''; ?>>Hoàn thành xuất sắc</option>
                                            <option value="Hoàn thành tốt" <?php echo $currentEvaluation['XepLoai'] == 'Hoàn thành tốt' ? 'selected' : ''; ?>>Hoàn thành tốt</option>
                                            <option value="Hoàn thành" <?php echo $currentEvaluation['XepLoai'] == 'Hoàn thành' ? 'selected' : ''; ?>>Hoàn thành</option>
                                            <option value="Không hoàn thành" <?php echo $currentEvaluation['XepLoai'] == 'Không hoàn thành' ? 'selected' : ''; ?>>Không hoàn thành</option>
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
                                            value="<?php echo htmlspecialchars($ngayXepLoaiDisplay); ?>">
                                        <?php if (isset($errors['NgayXepLoai'])): ?>
                                            <small class="text-danger"><?php echo $errors['NgayXepLoai']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-12">
                                    <button class="btn-sm btn-success" type="submit" name="update"> Lưu [Cập nhật] <i class="fa fa-save"></i> </button>
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
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <!-- Thêm JS cho Select2 -->
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

            // Khởi tạo Select2 cho dropdown Tên giảng viên
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

            // Chỉ cho phép reload khi nhấn "Lưu [Cập nhật]"
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
require_once BASE_PATH . '/layout/footer.php';
mysqli_close($dbc);
?>