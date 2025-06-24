<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Lấy mã học phần cần edit từ GET
$id = $_GET['MaHocPhan'];

// Kiểm tra quyền và lấy MaKhoa của Admin
$user_id = $_SESSION['user_id'];
$quyen = $_SESSION['quyen'] ?? 'Không xác định';
$ma_khoa = null;
$ten_khoa = null;

if ($quyen === 'Admin') {
    // Lấy MaKhoa và TenKhoa của Admin từ bảng giangvien
    $query_khoa = "SELECT g.MaKhoa, k.TenKhoa 
                   FROM giangvien g 
                   JOIN khoa k ON g.MaKhoa = k.MaKhoa 
                   WHERE g.MaGiangVien = ?";
    $stmt_khoa = $dbc->prepare($query_khoa);
    $stmt_khoa->bind_param("s", $user_id);
    $stmt_khoa->execute();
    $result_khoa = $stmt_khoa->get_result();

    if ($row_khoa = $result_khoa->fetch_assoc()) {
        $ma_khoa = $row_khoa['MaKhoa'];
        $ten_khoa = $row_khoa['TenKhoa'];
    }
    $stmt_khoa->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array();

    // Kiểm tra Mã học phần
    if (empty($_POST['MaHocPhan'])) {
        $errors['MaHocPhan'] = 'Mã học phần không để trống!';
    } else {
        $MaHocPhan = mysqli_real_escape_string($dbc, trim($_POST['MaHocPhan']));
        $sql = "SELECT * FROM hocphan WHERE MaHocPhan = '$MaHocPhan' AND MaHocPhan != '$id'";
        $result = mysqli_query($dbc, $sql);

        if (mysqli_num_rows($result) > 0) {
            $errors['MaHocPhan'] = 'Mã học phần bị trùng';
        }
    }

    // Kiểm tra Tên học phần
    if (empty($_POST['TenHocPhan'])) {
        $errors['TenHocPhan'] = 'Tên học phần không để trống';
    } else {
        $TenHocPhan = mysqli_real_escape_string($dbc, trim($_POST['TenHocPhan']));
    }

    // Khoa
    if ($quyen === 'Admin' && $ma_khoa !== null) {
        $Khoa = $ma_khoa;
    } elseif (isset($_POST['Khoa'])) {
        $Khoa = mysqli_real_escape_string($dbc, trim($_POST['Khoa']));
    } else {
        $errors['Khoa'] = 'Vui lòng chọn khoa';
    }

    // Trạng thái
    if (isset($_POST['TrangThai'])) {
        $trangthai = ($_POST['TrangThai'] === 'xuat') ? 1 : 2;
    }

    if (empty($errors)) {
        $q = "UPDATE hocphan SET 
              MaHocPhan = '$MaHocPhan',
              TenHocPhan = '$TenHocPhan',
              MaKhoa = '$Khoa',
              TrangThai = '$trangthai'		
              WHERE MaHocPhan = '$id'";
        $r = @mysqli_query($dbc, $q);
        session_start();
        if ($r) {
            $_SESSION['success_message'] = 'Cập nhật học phần thành công!';
            header("Location: index.php");
            ob_end_flush();
            exit();
        } else {
            echo '<h1>System Error</h1>
                  <p class="error">You could not be registered due to a system error. We apologize for any inconvenience.</p>';
            echo '<p>' . mysqli_error($dbc) . '<br /><br />Query: ' . $q . '</p>';
        }
        mysqli_close($dbc);
        exit();
    }
} else {
    // Lấy thông tin học phần từ cơ sở dữ liệu để hiển thị
    $sql = "SELECT * FROM hocphan WHERE MaHocPhan = '$id'";
    $result = mysqli_query($dbc, $sql);
    $rows = mysqli_fetch_array($result);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật học phần</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
    <!-- Thêm CSS của Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Tùy chỉnh chiều rộng của Select2 để hiển thị toàn bộ tên */
        .select2-container {
            width: 100% !important; /* Đảm bảo Select2 chiếm toàn bộ chiều rộng của vùng chứa */
            max-width: none !important; /* Loại bỏ giới hạn chiều rộng tối đa */
        }

        /* Đảm bảo vùng chứa của Select2 không vượt quá kích thước của .col-md-7 nhưng cho phép mở rộng nội dung */
        .select2-container .select2-selection--single {
            height: 38px; /* Chiều cao đồng bộ với các input khác */
            line-height: 38px; /* Căn giữa nội dung */
            min-width: 200px; /* Đặt chiều rộng tối thiểu để hiển thị đầy đủ */
            white-space: nowrap; /* Ngăn cắt dòng */
            overflow: visible; /* Cho phép hiển thị toàn bộ nội dung */
        }

        /* Căn chỉnh nội dung bên trong dropdown */
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
            white-space: nowrap; /* Ngăn cắt dòng */
            overflow: visible; /* Cho phép hiển thị toàn bộ */
            text-overflow: clip; /* Loại bỏ dấu ... khi quá dài */
        }

        /* Căn chỉnh mũi tên dropdown */
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }

        /* Đảm bảo vùng chứa của Select2 không bị tràn */
        .col-md-7 .select2-container {
            box-sizing: border-box;
            width: auto !important; /* Cho phép tự điều chỉnh chiều rộng */
        }

        /* Đảm bảo dropdown hiển thị đầy đủ và không bị cắt */
        .select2-container .select2-dropdown {
            width: auto !important; /* Cho phép dropdown mở rộng theo nội dung */
            min-width: 200px; /* Đặt chiều rộng tối thiểu */
            z-index: 1051; /* Đảm bảo dropdown không bị che bởi các phần tử khác */
            position: absolute; /* Đảm bảo vị trí cố định */
        }

        /* Ngăn vùng chứa cha cắt nội dung dropdown */
        .card-body, .col-md-7 {
            overflow: visible !important; /* Cho phép dropdown hiển thị vượt ra ngoài vùng chứa */
            position: relative; /* Đảm bảo vị trí tương đối để dropdown định vị đúng */
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
                            <strong class="text-danger">CẬP NHẬT HỌC PHẦN</strong>
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
                                    <label>Mã học phần <span class="text-danger"> (*)</span></label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="MaHocPhan"
                                            value="<?php if (isset($_POST['MaHocPhan'])) echo htmlspecialchars($_POST['MaHocPhan']);
                                                    else echo htmlspecialchars($rows['MaHocPhan']); ?>">
                                        <?php if (isset($errors['MaHocPhan'])): ?>
                                            <small class="text-danger"><?php echo $errors['MaHocPhan']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tên học phần <span class="text-danger"> (*)</span></label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="TenHocPhan"
                                            value="<?php if (isset($_POST['TenHocPhan'])) echo htmlspecialchars($_POST['TenHocPhan']);
                                                    else echo htmlspecialchars($rows['TenHocPhan']); ?>">
                                        <?php if (isset($errors['TenHocPhan'])): ?>
                                            <small class="text-danger"><?php echo $errors['TenHocPhan']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <?php if ($quyen !== 'Admin'): ?>
                                    <div class="form-group">
                                        <label>Khoa <span class="text-danger">(*)</span></label>
                                        <div class="col-md-7">
                                            <select class="form-control select2-khoa" name="Khoa">
                                                <?php
                                                $sql = "SELECT * FROM khoa WHERE TrangThai=1";
                                                $result = mysqli_query($dbc, $sql);
                                                if (mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_array($result)) {
                                                        $selected = ($row['MaKhoa'] == $rows['MaKhoa']) ? 'selected' : '';
                                                        echo "<option value='$row[MaKhoa]' $selected>$row[TenKhoa]</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                            <?php if (isset($errors['Khoa'])): ?>
                                                <small class="text-danger"><?php echo $errors['Khoa']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="form-group">
                                        <label>Khoa <span class="text-danger">(*)</span></label>
                                        <div class="col-md-7">
                                            <input type="hidden" name="Khoa" value="<?php echo htmlspecialchars($ma_khoa); ?>">
                                            <p><?php echo htmlspecialchars($ten_khoa); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group">
                                    <label>Trạng Thái <span class="text-danger">(*)</span></label>
                                    <div class="col-md-5">
                                        <select class="form-control" name="TrangThai">
                                            <option value="xuat" <?php echo ($rows['TrangThai'] == 1) ? 'selected' : ''; ?>>Xuất bản</option>
                                            <option value="an" <?php echo ($rows['TrangThai'] == 2) ? 'selected' : ''; ?>>Ẩn</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-offset-2 col-md-12">
                                    <button class="btn-sm btn-success" type="submit" name="create"> Cập nhật <i class="fa fa-save"></i> </button>
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
    <script>
        $(function() {
            $("#example1").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
            $('#example2').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
            });

            // Khởi tạo Select2 cho dropdown Khoa
            $('.select2-khoa').select2({
                placeholder: "Chọn 1 khoa",
                allowClear: false,
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                },
                width: 'resolve', // Tự động điều chỉnh chiều rộng theo nội dung
                dropdownParent: $('.col-md-7') // Đặt vùng chứa cha để đảm bảo vị trí dropdown
            });
        });
    </script>
</body>

</html>

<?php
require_once '../Layout/footer.php';
?>