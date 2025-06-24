<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Lấy danh sách khoa
$khoa_sql = "SELECT * FROM khoa WHERE TrangThai = 1";
$khoa_result = mysqli_query($dbc, $khoa_sql);
$khoa_options = [];
while ($row = mysqli_fetch_array($khoa_result)) {
    $khoa_options[$row['MaKhoa']] = $row['TenKhoa'];
}

// Xử lý khi form được gửi (khoa hoặc giảng viên thay đổi)
$selected_ma_khoa = isset($_POST['MaKhoa']) ? $_POST['MaKhoa'] : '';
$giangvien_options = [];
if (!empty($selected_ma_khoa)) {
    $giangvien_sql = "SELECT MaGiangVien, HoGiangVien, TenGiangVien 
                      FROM giangvien 
                      WHERE MaKhoa = ? AND TrangThai = 1";
    $stmt = mysqli_prepare($dbc, $giangvien_sql);
    mysqli_stmt_bind_param($stmt, "s", $selected_ma_khoa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $giangvien_options[$row['MaGiangVien']] = $row['HoGiangVien'] . ' ' . $row['TenGiangVien'];
    }
}

$selected_ma_giangvien = isset($_POST['MaGiangVien']) ? $_POST['MaGiangVien'] : '';
$selected_quyen = '';
if (!empty($selected_ma_giangvien)) {
    $quyen_sql = "SELECT Quyen FROM taikhoan WHERE MaTaiKhoan = ?";
    $stmt = mysqli_prepare($dbc, $quyen_sql);
    mysqli_stmt_bind_param($stmt, "s", $selected_ma_giangvien);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $selected_quyen = $row ? $row['Quyen'] : '';
}

// Xử lý khi submit để cập nhật
if (isset($_POST['create'])) {
    $errors = array();

    $maGiangVien = $_POST['MaGiangVien'];
    $quyen = $_POST['Quyen'];
    $maKhoa = $_POST['MaKhoa'];

    if (empty($maGiangVien)) $errors['MaGiangVien'] = "Vui lòng chọn giảng viên";
    if (empty($quyen)) $errors['Quyen'] = "Vui lòng chọn quyền";
    if (empty($maKhoa)) $errors['MaKhoa'] = "Vui lòng chọn khoa";

    if (empty($errors)) {
        $sql = "UPDATE taikhoan SET Quyen = ? WHERE MaTaiKhoan = ?";
        $stmt = mysqli_prepare($dbc, $sql);
        if ($stmt === false) {
            $errors['database'] = "Lỗi prepare statement: " . mysqli_error($dbc);
        } else {
            mysqli_stmt_bind_param($stmt, "ss", $quyen, $maGiangVien);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: ./index.php");
                exit();
            } else {
                $errors['database'] = "Có lỗi xảy ra: " . mysqli_error($dbc);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân quyền</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
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
                            <strong class="text-danger">CẬP NHẬT QUYỀN</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="./index.php" class="btn-sm btn-info"><i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                        </div>
                    </div>
                </div>
                <form action="" method="post" id="updateForm">
                    <div class="card-body">
                        <?php if (isset($errors['database'])): ?>
                            <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Khoa <span class="text-danger">(*)</span></label>
                                    <div class="select2-container-parent">
                                        <select class="form-control select2-khoa" name="MaKhoa" id="MaKhoa">
                                            <option value="">-- Chọn khoa --</option>
                                            <?php foreach ($khoa_options as $maKhoa => $tenKhoa): ?>
                                                <option value="<?php echo htmlspecialchars($maKhoa); ?>"
                                                    <?php echo $maKhoa == $selected_ma_khoa ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($tenKhoa); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php if (isset($errors['MaKhoa'])): ?>
                                        <small class="text-danger"><?php echo htmlspecialchars($errors['MaKhoa']); ?></small>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label>Họ tên giảng viên <span class="text-danger">(*)</span></label>
                                    <div class="select2-container-parent">
                                        <select class="form-control select2-giangvien" name="MaGiangVien" id="MaGiangVien">
                                            <option value="">-- Chọn giảng viên --</option>
                                            <?php foreach ($giangvien_options as $maGiangVien => $tenGiangVien): ?>
                                                <option value="<?php echo htmlspecialchars($maGiangVien); ?>"
                                                    <?php echo $maGiangVien == $selected_ma_giangvien ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($tenGiangVien); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php if (isset($errors['MaGiangVien'])): ?>
                                        <small class="text-danger"><?php echo htmlspecialchars($errors['MaGiangVien']); ?></small>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label>Quyền <span class="text-danger">(*)</span></label>
                                    <select class="form-control" name="Quyen" id="Quyen">
                                        <option value="">-- Chọn quyền --</option>
                                        <option value="User" <?php echo $selected_quyen == 'User' ? 'selected' : ''; ?>>User</option>
                                        <option value="Admin" <?php echo $selected_quyen == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="Super Admin" <?php echo $selected_quyen == 'Super Admin' ? 'selected' : ''; ?>>SuperAdmin</option>
                                    </select>
                                    <?php if (isset($errors['Quyen'])): ?>
                                        <small class="text-danger"><?php echo htmlspecialchars($errors['Quyen']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button class="btn-sm btn-success" type="submit" name="create">Cập nhật <i class="fa fa-save"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <!-- jQuery -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables & Plugins -->
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
    <!-- AdminLTE App -->
    <!-- AdminLTE for demo purposes -->
    <script src="<?php echo BASE_URL ?>/Public/dist/js/demo.js"></script>
    <!-- Thêm JS của Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Page specific script -->
    <script>
        $(document).ready(function() {
            // Khởi tạo Select2 cho dropdown Khoa
            $('#MaKhoa').select2({
                placeholder: "-- Chọn khoa --",
                allowClear: false,
                width: 'resolve', // Tự động điều chỉnh chiều rộng
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                }
            });

            // Khởi tạo Select2 cho dropdown Giảng viên
            $('#MaGiangVien').select2({
                placeholder: "-- Chọn giảng viên --",
                allowClear: false,
                width: 'resolve', // Tự động điều chỉnh chiều rộng
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                }
            });

            // Khi chọn khoa
            $('#MaKhoa').on('change', function(e) {
                e.preventDefault();
                var formData = $('#updateForm').serialize();
                // Reset cả giảng viên và quyền ngay lập tức
                $('#MaGiangVien').empty().append('<option value="">-- Chọn giảng viên --</option>').trigger('change');
                $('#Quyen').val('');
                $.post('', formData, function(data) {
                    // Cập nhật danh sách giảng viên từ response
                    var $newContent = $(data);
                    $('#MaGiangVien').html($newContent.find('#MaGiangVien').html());
                    // Khởi tạo lại Select2 cho giảng viên sau khi cập nhật
                    $('#MaGiangVien').select2({
                        placeholder: "-- Chọn giảng viên --",
                        allowClear: false,
                        width: 'resolve',
                        language: {
                            noResults: function() {
                                return "Không có dữ liệu";
                            }
                        }
                    });
                });
            });

            // Khi chọn giảng viên
            $('#MaGiangVien').on('change', function(e) {
                e.preventDefault();
                $.post('', $('#updateForm').serialize(), function(data) {
                    var $newContent = $(data);
                    $('#Quyen').html($newContent.find('#Quyen').html());
                });
            });

            // Chỉ cho phép reload khi nhấn "Cập nhật"
            $('#updateForm').on('submit', function(e) {
                if (!$(e.target).find('button[name="create"]').is(':focus')) {
                    e.preventDefault(); // Ngăn reload trừ khi nhấn "Cập nhật"
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