<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Handle single record submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $errors = array();

    // Kiểm tra Mã Khoa
    if (empty($_POST['MaKhoa'])) {
        $errors['MaKhoa'] = 'Mã khoa không để trống!';
    } else {
        $MaKhoa = mysqli_real_escape_string($dbc, trim($_POST['MaKhoa']));
        $sql = "SELECT * FROM khoa WHERE MaKhoa = '$MaKhoa'";
        $result = mysqli_query($dbc, $sql);

        if (mysqli_num_rows($result) > 0) {
            $errors['MaKhoa'] = 'Mã khoa bị trùng';
        }
    }

    // Kiểm tra Tên Khoa
    if (empty($_POST['TenKhoa'])) {
        $errors['TenKhoa'] = 'Tên khoa không để trống';
    } else {
        $TenKhoa = mysqli_real_escape_string($dbc, trim($_POST['TenKhoa']));
        $sql = "SELECT * FROM khoa WHERE TenKhoa = '$TenKhoa'";
        $result = mysqli_query($dbc, $sql);
        if (mysqli_num_rows($result) > 0) {
            $errors['TenKhoa'] = 'Tên khoa bị trùng';
        }
    }

    // Kiểm tra Trạng Thái
    if (isset($_POST['TrangThai'])) {
        $trangthai = ($_POST['TrangThai'] === 'xuat') ? 1 : 2;
    } else {
        $errors['TrangThai'] = 'Vui lòng chọn trạng thái';
    }

    if (empty($errors)) {
        $q = "INSERT INTO khoa (MaKhoa, TenKhoa, TrangThai) VALUES ('$MaKhoa', '$TenKhoa', '$trangthai')";
        $r = @mysqli_query($dbc, $q);
        session_start();
        if ($r) {
            $_SESSION['success_message'] = 'Thêm khoa thành công!';
            header("Location: index.php");
            ob_end_flush();
            exit();
        } else {
            echo '<h1>Lỗi Hệ Thống</h1>
                  <p class="error">Không thể thêm bản ghi do lỗi hệ thống. Chúng tôi xin lỗi vì sự bất tiện này.</p>';
            echo '<p>' . mysqli_error($dbc) . '<br /><br />Query: ' . $q . '</p>';
            mysqli_close($dbc);
            exit();
        }
    }
}

// Handle bulk submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_submit'])) {
    $errors = array();
    $success_count = 0;

    $ma_khoas = $_POST['MaKhoa'];
    $ten_khoas = $_POST['TenKhoa'];
    $trang_thais = $_POST['TrangThai'];

    mysqli_begin_transaction($dbc);

    try {
        for ($i = 0; $i < count($ma_khoas); $i++) {
            $errors[$i] = array();

            // Kiểm tra Mã Khoa
            if (empty($ma_khoas[$i])) {
                $errors[$i]['MaKhoa'] = 'Mã khoa không để trống!';
            } else {
                $MaKhoa = mysqli_real_escape_string($dbc, trim($ma_khoas[$i]));
                $sql = "SELECT * FROM khoa WHERE MaKhoa = '$MaKhoa'";
                $result = mysqli_query($dbc, $sql);

                if (mysqli_num_rows($result) > 0) {
                    $errors[$i]['MaKhoa'] = 'Mã khoa bị trùng';
                }
            }

            // Kiểm tra Tên Khoa
            if (empty($ten_khoas[$i])) {
                $errors[$i]['TenKhoa'] = 'Tên khoa không để trống';
            } else {
                $TenKhoa = mysqli_real_escape_string($dbc, trim($ten_khoas[$i]));
                $sql = "SELECT * FROM khoa WHERE TenKhoa = '$TenKhoa'";
                $result = mysqli_query($dbc, $sql);
                if (mysqli_num_rows($result) > 0) {
                    $errors[$i]['TenKhoa'] = 'Tên khoa bị trùng';
                }
            }

            // Kiểm tra Trạng Thái
            if (!empty($trang_thais[$i])) {
                $trangthai = ($trang_thais[$i] === 'xuat') ? 1 : 2;
            } else {
                $errors[$i]['TrangThai'] = 'Vui lòng chọn trạng thái';
            }

            if (empty($errors[$i])) {
                $q = "INSERT INTO khoa (MaKhoa, TenKhoa, TrangThai) VALUES ('$MaKhoa', '$TenKhoa', '$trangthai')";
                $r = @mysqli_query($dbc, $q);
                if ($r) {
                    $success_count++;
                } else {
                    throw new Exception("Lỗi khi thêm khoa: " . mysqli_error($dbc));
                }
            }
        }

        if ($success_count > 0) {
            mysqli_commit($dbc);
            session_start();
            $_SESSION['success_message'] = "Đã thêm $success_count khoa thành công!";
            header("Location: index.php");
            ob_end_flush();
            exit();
        } else {
            mysqli_rollback($dbc);
        }
    } catch (Exception $e) {
        mysqli_rollback($dbc);
        $errors['System'] = "Lỗi hệ thống: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm mới khoa</title>
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
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
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

        .card-body,
        .col-md-7 {
            overflow: visible !important;
            position: relative;
        }

        .bulk-input-table {
            width: 100%;
            margin-bottom: 1rem;
        }

        .bulk-input-table th,
        .bulk-input-table td {
            padding: 8px;
            vertical-align: middle;
        }

        .bulk-input-table input,
        .bulk-input-table select {
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
                            <strong class="text-danger">THÊM MỚI KHOA</strong>
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
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Mã Khoa <span class="text-danger">(*)</span></label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="MaKhoa" value="<?php echo isset($_POST['MaKhoa']) ? htmlspecialchars($_POST['MaKhoa']) : ''; ?>">
                                        <?php if (isset($errors['MaKhoa'])): ?>
                                            <small class="text-danger"><?php echo $errors['MaKhoa']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Tên Khoa <span class="text-danger">(*)</span></label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="TenKhoa" value="<?php echo isset($_POST['TenKhoa']) ? htmlspecialchars($_POST['TenKhoa']) : ''; ?>">
                                        <?php if (isset($errors['TenKhoa'])): ?>
                                            <small class="text-danger"><?php echo $errors['TenKhoa']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Trạng Thái <span class="text-danger">(*)</span></label>
                                    <div class="col-md-7">
                                        <select class="form-control select2-trangthai" name="TrangThai">
                                            <option value="xuat" <?php echo (isset($_POST['TrangThai']) && $_POST['TrangThai'] == 'xuat') ? 'selected' : ''; ?>>Xuất bản</option>
                                            <option value="an" <?php echo (isset($_POST['TrangThai']) && $_POST['TrangThai'] == 'an') ? 'selected' : ''; ?>>Ẩn</option>
                                        </select>
                                        <?php if (isset($errors['TrangThai'])): ?>
                                            <small class="text-danger"><?php echo $errors['TrangThai']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-offset-2 col-md-10">
                                <button class="btn-sm btn-success" type="submit" name="create"> Lưu [Thêm] <i class="fa fa-save"></i> </button>
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
                            <h5 class="modal-title" id="bulkAddModalLabel">Thêm nhiều khoa</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="" method="post">
                            <div class="modal-body">
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
                                            <th>Mã Khoa</th>
                                            <th>Tên Khoa</th>
                                            <th>Trạng Thái</th>
                                            <th>Xóa</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bulkInputTableBody">
                                        <tr>
                                            <td><input type="text" name="MaKhoa[]" class="form-control"></td>
                                            <td><input type="text" name="TenKhoa[]" class="form-control"></td>
                                            <td>
                                                <select class="form-control select2-trangthai-bulk" name="TrangThai[]">
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
                                            <?php if (isset($errors['System'])): ?>
                                                <li>Lỗi: <?php echo $errors['System']; ?></li>
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
    <script src="<?php echo BASE_URL ?>/Public/dist/js/adminlte.min.js"></script>
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

            // Khởi tạo Select2 cho Trạng Thái
            $('.select2-trangthai').select2({
                placeholder: "Chọn trạng thái",
                allowClear: false,
                minimumResultsForSearch: 0,
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                },
                width: 'resolve',
                dropdownParent: $('.col-md-7')
            });

            $('.select2-trangthai-bulk').select2({
                placeholder: "Chọn trạng thái",
                allowClear: false,
                minimumResultsForSearch: 0,
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                },
                width: 'resolve',
                dropdownParent: $('#bulkAddModal')
            });

            // Thêm hàng mới
            $('#addRowBtn').click(function() {
                let newRow = `
                    <tr>
                        <td><input type="text" name="MaKhoa[]" class="form-control"></td>
                        <td><input type="text" name="TenKhoa[]" class="form-control"></td>
                        <td>
                            <select class="form-control select2-trangthai-bulk" name="TrangThai[]">
                                <option value="xuat">Xuất bản</option>
                                <option value="an">Ẩn</option>
                            </select>
                        </td>
                        <td class="remove-row"><i class="fas fa-trash"></i></td>
                    </tr>`;
                $('#bulkInputTableBody').append(newRow);
                $('.select2-trangthai-bulk').select2({
                    placeholder: "Chọn trạng thái",
                    allowClear: false,
                    minimumResultsForSearch: 0,
                    language: {
                        noResults: function() {
                            return "Không có dữ liệu";
                        }
                    },
                    width: 'resolve',
                    dropdownParent: $('#bulkAddModal')
                });
            });

            // Tạo số hàng
            $('#generateRowsBtn').click(function() {
                let rowCount = parseInt($('#rowCountInput').val());
                if (rowCount < 1 || isNaN(rowCount)) {
                    alert('Vui lòng nhập số bản ghi hợp lệ (tối thiểu 1).');
                    return;
                }

                $('#bulkInputTableBody').empty();
                for (let i = 0; i < rowCount; i++) {
                    let newRow = `
                        <tr>
                            <td><input type="text" name="MaKhoa[]" class="form-control"></td>
                            <td><input type="text" name="TenKhoa[]" class="form-control"></td>
                            <td>
                                <select class="form-control select2-trangthai-bulk" name="TrangThai[]">
                                    <option value="xuat">Xuất bản</option>
                                    <option value="an">Ẩn</option>
                                </select>
                            </td>
                            <td class="remove-row"><i class="fas fa-trash"></i></td>
                        </tr>`;
                    $('#bulkInputTableBody').append(newRow);
                }
                $('.select2-trangthai-bulk').select2({
                    placeholder: "Chọn trạng thái",
                    allowClear: false,
                    minimumResultsForSearch: 0,
                    language: {
                        noResults: function() {
                            return "Không có dữ liệu";
                        }
                    },
                    width: 'resolve',
                    dropdownParent: $('#bulkAddModal')
                });
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
mysqli_close($dbc);
?>