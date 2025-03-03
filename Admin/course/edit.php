<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

// Lấy mã khoa cần edit từ GET
$id = $_GET['MaHocPhan'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array(); // Initialize an error array.

    $MaHocPhan = $_POST['MaHocPhan'];
    $TenHocPhan = $_POST['TenHocPhan'];

    // Kiểm tra Mã Khoa
    if (empty($MaHocPhan)) {
        $errors['MaHocPhan'] = 'Mã khoa không để trống!';
    } else {
        $MaHocPhan = mysqli_real_escape_string($dbc, trim($MaHocPhan));
        $sql = "SELECT * FROM hocphan WHERE MaHocPhan = '$MaHocPhan' AND MaHocPhan != '$id'";
        $result = mysqli_query($dbc, $sql);

        if (mysqli_num_rows($result) > 0) {
            $errors['MaHocPhan'] = 'Mã khoa bị trùng';
        }
    }

    // Kiểm tra Tên khoa
    if (empty($TenHocPhan)) {
        $errors['TenHocPhan'] = 'Tên khoa không để trống';
    } else {
        $TenHocPhan = mysqli_real_escape_string($dbc, trim($TenHocPhan));
        $sql = "SELECT * FROM hocphan WHERE TenHocPhan = '$TenHocPhan'";
        $result = mysqli_query($dbc, $sql);
        if (mysqli_num_rows($result) > 0) {
            $TenHocPhan = mysqli_real_escape_string($dbc, trim($TenHocPhan));;
        }
    }

    if (isset($_POST['TrangThai'])) {
        $trangthai = ($_POST['TrangThai'] === 'xuat') ? 1 : 2;
    }

    if (empty($errors)) {
        // Make the query:
        $q = "UPDATE hocphan SET 
        MaHocPhan ='$MaHocPhan',
        TenHocPhan ='$TenHocPhan',
        TrangThai ='$trangthai'		
        WHERE MaHocPhan ='$id'"; // Sử dụng biến $id để cập nhật

        $r = @mysqli_query($dbc, $q); // Run the query.
        session_start(); // Bắt đầu phiên
        if ($r) { // If it ran OK.
            $_SESSION['success_message'] = 'Cập nhật khoa thành công!';
            header("Location: index.php");
            ob_end_flush();
            exit();
        } else { // If it did not run OK.
            echo '<h1>System Error</h1>
            <p class="error">You could not be registered due to a system error. We apologize for any inconvenience.</p>';
            echo '<p>' . mysqli_error($dbc) . '<br /><br />Query: ' . $q . '</p>';
        }
        mysqli_close($dbc); // Close the database connection.
        exit();
    }
} else {
    // Lấy thông tin khoa từ cơ sở dữ liệu để hiển thị
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
    <title>Danh sách công việc</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
</head>

<body>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content my-2">
            <!-- Default box -->
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <strong class="text-danger">CẬP NHẬP HỌC PHẦN</strong>
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
                                    <label>Mã Khoa <span class="text-danger"> (*)</span></label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="MaHocPhan"
                                            value="<?php if (isset($_POST['MaHocPhan'])) echo $MaHocPhan;
                                                    else echo $rows['MaHocPhan']; ?>">

                                        <!-- Validation -->
                                        <?php if (isset($errors['MaHocPhan'])): ?>
                                            <small class="text-danger"><?php echo $errors['MaHocPhan']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tên Khoa <span class="text-danger"> (*)</span></label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="TenHocPhan"
                                            value="<?php if (isset($_POST['TenHocPhan'])) echo $TenHocPhan;
                                                    else echo $rows['TenHocPhan']; ?>">
                                        <!-- validation -->
                                        <?php if (isset($errors['TenHocPhan'])): ?>
                                            <small class="text-danger"><?php echo $errors['TenHocPhan']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Khoa <span class="text-danger">(*)</span></label>
                                    <div class="col-md-7">
                                        <select class="form-control" name="Khoa" style="width: auto;">
                                            <?php
                                            require_once BASE_PATH . './Database/connect-database.php';
                                            $sql = "Select * FROM khoa where TrangThai=1";
                                            $result = mysqli_query($dbc, $sql);
                                            if (mysqli_num_rows($result) <> 0) {
                                                while ($row = mysqli_fetch_array($result)) {
                                                    echo "	<option value='$row[MaKhoa]'>$row[TenKhoa]</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Trạng Thái <span class="text-danger">(*)</span></label>
                                    <div class="col-md-5">
                                        <select class="form-control" name="TrangThai">
                                            <option value="xuat" selected>Xuất bản</option>
                                            <option value="an">Ẩn</option>
                                        </select>
                                    </div>
                                </div>  
                            </div>
                            <div class="form-group">
                                <div class="col-md-offset-2 col-md-12   ">
                                    <button class="btn-sm btn-success" type="submit" name="create"> Cập nhập <i class="fa fa-save"></i> </button>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.card-body -->
                </form>
            </div><!-- /.card -->
        </section><!-- /.content -->
    </div><!-- /.content-wrapper -->

    <!-- jQuery -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables  & Plugins -->
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
    <script src="<?php echo BASE_URL ?>/dist/js/adminlte.min.js"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="<?php echo BASE_URL ?>/dist/js/demo.js"></script>
    <!-- Page specific script -->
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
        });
    </script>

</body>

</html>

<?php
require_once '../Layout/footer.php'
?>