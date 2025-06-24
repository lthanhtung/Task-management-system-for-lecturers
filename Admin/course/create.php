
<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . '/Database/connect-database.php';

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_submit'])) {
    $errors = array();
    $success_count = 0;

    // Lấy dữ liệu từ form nhập nhiều bản ghi
    $ma_hoc_phans = $_POST['MaHocPhan'];
    $ten_hoc_phans = $_POST['TenHocPhan'];
    $trang_thais = $_POST['TrangThai'];

    // Kiểm tra Khoa
    if ($quyen === 'Admin' && $ma_khoa !== null) {
        $Khoa = $ma_khoa;
    } elseif (!empty($_POST['Khoa'])) {
        $Khoa = mysqli_real_escape_string($dbc, trim($_POST['Khoa']));
    } else {
        $errors['Khoa'] = 'Vui lòng chọn khoa';
    }

    if (empty($errors['Khoa'])) {
        for ($i = 0; $i < count($ma_hoc_phans); $i++) {
            $errors[$i] = array();

            // Kiểm tra Mã học Phần
            if (empty($ma_hoc_phans[$i])) {
                $errors[$i]['MaHocPhan'] = 'Mã học phần không để trống!';
            } else {
                $MaHocPhan = mysqli_real_escape_string($dbc, trim($ma_hoc_phans[$i]));
                $sql = "SELECT * FROM hocphan WHERE MaHocPhan = '$MaHocPhan'";
                $result = mysqli_query($dbc, $sql);

                if (mysqli_num_rows($result) > 0) {
                    $errors[$i]['MaHocPhan'] = 'Mã học phần bị trùng';
                }
            }

            // Kiểm tra Tên học Phần
            if (empty($ten_hoc_phans[$i])) {
                $errors[$i]['TenHocPhan'] = 'Tên học phần không để trống';
            } else {
                $TenHocPhan = mysqli_real_escape_string($dbc, trim($ten_hoc_phans[$i]));
                $sql = "SELECT * FROM hocphan WHERE TenHocPhan = '$TenHocPhan'";
                $result = mysqli_query($dbc, $sql);
                if (mysqli_num_rows($result) > 0) {
                    $errors[$i]['TenHocPhan'] = 'Học phần bị trùng';
                }
            }

            // Trạng thái
            $trangthai = ($trang_thais[$i] === 'xuat') ? 1 : 2;

            if (empty($errors[$i])) {
                $q = "INSERT INTO hocphan (MaHocPhan, TenHocPhan, MaKhoa, TrangThai) VALUES ('$MaHocPhan', '$TenHocPhan', '$Khoa', '$trangthai')";
                $r = @mysqli_query($dbc, $q);
                if ($r) {
                    $success_count++;
                } else {
                    $errors[$i]['System'] = 'Lỗi hệ thống khi thêm bản ghi: ' . mysqli_error($dbc);
                }
            }
        }
    }

    if ($success_count > 0) {
        $_SESSION['success_message'] = "Đã thêm $success_count học phần thành công!";
        header("Location: index.php");
        ob_end_flush();
        exit();
    }
}

// Xử lý form thêm từng bản ghi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['bulk_submit'])) {
    $errors = array();

    // Kiểm tra Mã học Phần
    if (empty($_POST['MaHocPhan'])) {
        $errors['MaHocPhan'] = 'Mã học phần không để trống!';
    } else {
        $MaHocPhan = mysqli_real_escape_string($dbc, trim($_POST['MaHocPhan']));
        $sql = "SELECT * FROM hocphan WHERE MaHocPhan = '$MaHocPhan'";
        $result = mysqli_query($dbc, $sql);

        if (mysqli_num_rows($result) > 0) {
            $errors['MaHocPhan'] = 'Mã học phần bị trùng';
        }
    }

    // Kiểm tra Tên học Phần
    if (empty($_POST['TenHocPhan'])) {
        $errors['TenHocPhan'] = 'Tên học phần không để trống';
    } else {
        $TenHocPhan = mysqli_real_escape_string($dbc, trim($_POST['TenHocPhan']));
        $sql = "SELECT * FROM hocphan WHERE TenHocPhan = '$TenHocPhan'";
        $result = mysqli_query($dbc, $sql);
        if (mysqli_num_rows($result) > 0) {
            $errors['TenHocPhan'] = 'Học phần bị trùng';
        }
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
        $q = "INSERT INTO hocphan (MaHocPhan, TenHocPhan, MaKhoa, TrangThai) VALUES ('$MaHocPhan', '$TenHocPhan', '$Khoa', '$trangthai')";
        $r = @mysqli_query($dbc, $q);
        session_start();
        if ($r) {
            $_SESSION['success_message'] = 'Đã thêm học phần thành công!';
            header("Location: index.php");
            ob_end_flush();
            exit();
        } else {
            echo '<h1>Lỗi Hệ Thống</h1>
                  <p class="error">Không thể thêm bản ghi do lỗi hệ thống. Chúng tôi xin lỗi vì sự bất tiện này.</p>';
            echo '<p>' . mysqli_error($dbc) . '<br /><br />Query: ' . $q . '</p>';
        }
        mysqli_close($dbc);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm mới học phần</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container {
            width: 100% !important;
            max-width: none !important;
        }
        .select2-container .select2-selection--single {
            height: 38px;
            line-height: 38px;
            min-width: 200px;
            white-space: nowrap;
            overflow: visible;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
            white-space: nowrap;
            overflow: visible;
            text-overflow: clip;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }
        .col-md-7 .select2-container {
            box-sizing: border-box;
            width: auto !important;
        }
        .select2-container .select2-dropdown {
            width: auto !important;
            min-width: 200px;
            z-index: 1051;
            position: absolute;
        }
        .card-body, .col-md-7 {
            overflow: visible !important;
            position: relative;
        }
        /* Style cho bảng trong modal */
        .bulk-input-table {
            width: 100%;
            margin-bottom: 1rem;
        }
        .bulk-input-table th, .bulk-input-table td {
            padding: 8px;
            vertical-align: middle;
        }
        .bulk-input-table input, .bulk-input-table select {
            width: 100%;
            padding: 5px;
            box-sizing: border-box;
        }
        .bulk-input-table .remove-row {
            color: red;
            cursor: pointer;
            text-align: center;
        }
        .row-count-input {
            width: 100px;
            display: inline-block;
            margin-right: 10px;
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
                            <strong class="text-danger">THÊM MỚI HỌC PHẦN</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="./index.php" class="btn-sm btn-info"> <i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                            <button class="btn-sm btn-primary" data-toggle="modal" data-target="#bulkAddModal"> <i class="fa fa-plus"></i> Thêm nhiều</button>
                        </div>
                    </div>
                </div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Mã Học Phần <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="MaHocPhan" value="<?php echo isset($_POST['MaHocPhan']) ? htmlspecialchars($_POST['MaHocPhan']) : ''; ?>">
                                        <?php if (isset($errors['MaHocPhan'])): ?>
                                            <small class="text-danger"><?php echo $errors['MaHocPhan']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tên Học Phần <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="TenHocPhan" value="<?php echo isset($_POST['TenHocPhan']) ? htmlspecialchars($_POST['TenHocPhan']) : ''; ?>">
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
                                                        echo "<option value='$row[MaKhoa]' " . (isset($_POST['Khoa']) && $_POST['Khoa'] == $row['MaKhoa'] ? 'selected' : '') . ">$row[TenKhoa]</option>";
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
                                            <option value="xuat" <?php echo (isset($_POST['TrangThai']) && $_POST['TrangThai'] == 'xuat') ? 'selected' : ''; ?>>Xuất bản</option>
                                            <option value="an" <?php echo (isset($_POST['TrangThai']) && $_POST['TrangThai'] == 'an') ? 'selected' : ''; ?>>Ẩn</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-offset-2 col-md-12">
                                    <button class="btn-sm btn-success" type="submit" name="create"> Lưu [Thêm] <i class="fa fa-save"></i> </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal cho Thêm nhiều -->
            <div class="modal fade" id="bulkAddModal" tabindex="-1" role="dialog" aria-labelledby="bulkAddModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="bulkAddModalLabel">Thêm nhiều học phần</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="" method="post">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Khoa <span class="text-danger">(*)</span></label>
                                    <?php if ($quyen !== 'Admin'): ?>
                                        <select class="form-control select2-khoa-bulk" name="Khoa">
                                            <?php
                                            $sql = "SELECT * FROM khoa WHERE TrangThai=1";
                                            $result = mysqli_query($dbc, $sql);
                                            if (mysqli_num_rows($result) > 0) {
                                                while ($row = mysqli_fetch_array($result)) {
                                                    echo "<option value='$row[MaKhoa]'>$row[TenKhoa]</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                        <?php if (isset($errors['Khoa'])): ?>
                                            <small class="text-danger"><?php echo $errors['Khoa']; ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <input type="hidden" name="Khoa" value="<?php echo htmlspecialchars($ma_khoa); ?>">
                                        <p><?php echo htmlspecialchars($ten_khoa); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Số bản ghi muốn thêm <span class="text-danger">(*)</span></label>
                                    <div class="input-group" style="width: 200px;">
                                        <input type="number" class="form-control row-count-input" id="rowCountInput" min="1" value="1">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-sm btn-primary" id="generateRowsBtn">Tạo hàng</button>
                                        </div>
                                    </div>
                                </div>
                                <table class="bulk-input-table">
                                    <thead>
                                        <tr>
                                            <th>Mã Học Phần</th>
                                            <th>Tên Học Phần</th>
                                            <th>Trạng Thái</th>
                                            <th>Xóa</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bulkInputTableBody">
                                        <tr>
                                            <td><input type="text" name="MaHocPhan[]" class="form-control"></td>
                                            <td><input type="text" name="TenHocPhan[]" class="form-control"></td>
                                            <td>
                                                <select class="form-control" name="TrangThai[]">
                                                    <option value="xuat">Xuất bản</option>
                                                    <option value="an">Ẩn</option>
                                                </select>
                                            </td>
                                            <td class="remove-row"><i class="fas fa-trash"></i></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-sm btn-primary" id="addRowBtn">Thêm hàng</button>
                                <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger mt-3">
                                        <ul>
                                            <?php if (isset($errors['Khoa'])): ?>
                                                <li>Lỗi: <?php echo $errors['Khoa']; ?></li>
                                            <?php endif; ?>
                                            <?php foreach ($errors as $index => $error): ?>
                                                <?php if (is_numeric($index) && !empty($error)): ?>
                                                    <li>Bản ghi <?php echo ($index + 1); ?>: 
                                                        <?php echo implode(', ', array_values($error)); ?>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                                <button type="submit" class="btn btn-success" name="bulk_submit">Lưu tất cả</button>
                            </div>
                        </form>
                    </div>
                </div>
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

            // Khởi tạo Select2 cho dropdown Khoa trong form chính
            $('.select2-khoa').select2({
                placeholder: "Chọn 1 khoa",
                allowClear: false,
                minimumResultsForSearch: 0, // Bật tìm kiếm
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                },
                width: 'resolve',
                dropdownParent: $('.col-md-7')
            });

            // Khởi tạo Select2 cho dropdown Khoa trong modal
            $('.select2-khoa-bulk').select2({
                placeholder: "Chọn 1 khoa",
                allowClear: false,
                minimumResultsForSearch: 0, // Bật tìm kiếm
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                },
                width: 'resolve',
                dropdownParent: $('#bulkAddModal') // Đặt parent là modal để tránh lỗi z-index
            });

            // Thêm hàng mới vào bảng
            $('#addRowBtn').click(function() {
                let newRow = `
                    <tr>
                        <td><input type="text" name="MaHocPhan[]" class="form-control"></td>
                        <td><input type="text" name="TenHocPhan[]" class="form-control"></td>
                        <td>
                            <select class="form-control" name="TrangThai[]">
                                <option value="xuat">Xuất bản</option>
                                <option value="an">Ẩn</option>
                            </select>
                        </td>
                        <td class="remove-row"><i class="fas fa-trash"></i></td>
                    </tr>`;
                $('#bulkInputTableBody').append(newRow);
            });

            // Tạo số hàng theo số lượng nhập
            $('#generateRowsBtn').click(function() {
                let rowCount = parseInt($('#rowCountInput').val());
                if (rowCount < 1 || isNaN(rowCount)) {
                    alert('Vui lòng nhập số bản ghi hợp lệ (tối thiểu 1).');
                    return;
                }

                // Xóa tất cả hàng hiện tại
                $('#bulkInputTableBody').empty();

                // Thêm số hàng theo số lượng nhập
                for (let i = 0; i < rowCount; i++) {
                    let newRow = `
                        <tr>
                            <td><input type="text" name="MaHocPhan[]" class="form-control"></td>
                            <td><input type="text" name="TenHocPhan[]" class="form-control"></td>
                            <td>
                                <select class="form-control" name="TrangThai[]">
                                    <option value="xuat">Xuất bản</option>
                                    <option value="an">Ẩn</option>
                                </select>
                            </td>
                            <td class="remove-row"><i class="fas fa-trash"></i></td>
                        </tr>`;
                    $('#bulkInputTableBody').append(newRow);
                }
            });

            // Xóa hàng
            $(document).on('click', '.remove-row', function() {
                if ($('#bulkInputTableBody tr').length > 1) {
                    $(this).closest('tr').remove();
                }
            });
        });
    </script>
</body>

</html>

<?php
require_once '../Layout/footer.php';
?>