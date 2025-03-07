<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

$id = $_GET['MaLichGiang'];
// Lấy thông tin khoa từ cơ sở dữ liệu để hiển thị
$sql = "
    SELECT lichgiangday.*, hocphan.TenHocPhan
    FROM lichgiangday 
    JOIN hocphan ON lichgiangday.MaHocPhan = hocphan.MaHocPhan 
    WHERE lichgiangday.MaLichGiang = '$id'";
$result = mysqli_query($dbc, $sql);
$rows = mysqli_fetch_array($result);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array(); // Initialize an error array.

    //Tên học phần
    if(isset($_POST['TenHocPhan'])){
        $MaHocPhan = mysqli_real_escape_string($dbc, trim($_POST['TenHocPhan']));
    }

    // Kiểm tra Địa điểm
    if (empty($_POST['DiaDiem'])) {
        $errors['DiaDiem'] = 'Chưa nhập địa điểm ';
        $DiaDiem = '';
    } else {
        $DiaDiem = mysqli_real_escape_string($dbc, trim($_POST['DiaDiem']));
    }

    //Kiểm tra Lịch dạy
    if (empty($_POST['LichDay'])) {
        $errors['LichDay'] = 'Chưa nhập Lịch dạy';
        $LichDay = '';
    } else {
        $LichDay = mysqli_real_escape_string($dbc, trim($_POST['LichDay']));
    }

    //Thời gian bắt đầu
    if (isset($_POST['ThoiGianBatDau'])) {
        $ThoiGianBatDau = mysqli_real_escape_string($dbc, trim($_POST['ThoiGianBatDau']));
    }

    if (isset($_POST['ThoiGianKetThuc'])) {
        $ThoiGianKetThuc = mysqli_real_escape_string($dbc, trim($_POST['ThoiGianKetThuc']));
    }
    
    //Kiểm tra trạng thái
    if ($_POST['TrangThai'] == 'xuat') {
        $trangthai = 1;
    } else {
        $trangthai = 2;
    }

    if (empty($errors)) {
        // Make the query:
        $query = "
        UPDATE lichgiangday 
        SET 
            MaHocPhan = '$MaHocPhan',
            LichDay = '$LichDay',
            ThoiGianBatDau ='$ThoiGianBatDau',
            ThoiGianKetThuc ='$ThoiGianKetThuc',
            DiaDiem ='$DiaDiem',
            TrangThai ='$trangthai'
        WHERE MaLichGiang = '$id'";
        $r = @mysqli_query($dbc, $query); // Run the query.
        session_start(); // Bắt đầu phiên
        if ($r) { // If it ran OK.
            // Print a message:
            $_SESSION['success_message'] = 'Cập nhập lịch giảng dạy thành công!';
            // Chuyển hướng đến index
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
                            <strong class="text-danger">CẬP NHẬP LỊCH GIẢNG DẠY</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="./index.php" class="btn-sm btn-info"> <i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                        </div>
                    </div>
                </div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="row">
                            <!-- Ghi -->
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Mã lịch giảng <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input readonly class="form-control" type="text" name="MaLichGiang"
                                            value="<?php if (isset($_POST['MaLichGiang'])) echo $rows['MaLichGiang'];
                                                    else echo $rows['MaLichGiang']; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Tên học phần <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="TenHocPhan" style="width: auto;">
                                            <?php
                                            $sql = "Select * FROM hocphan where TrangThai=1";
                                            $result = mysqli_query($dbc, $sql);
                                            if (mysqli_num_rows($result) <> 0) {
                                                while ($row = mysqli_fetch_array($result)) {
                                                    $selected = ($row['MaHocPhan'] == ($rows['MaHocPhan'] ?? '')) ? 'selected' : '';
                                                    echo "<option value='{$row['MaHocPhan']}' $selected>" . htmlspecialchars($row['TenHocPhan']) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Địa điểm <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="DiaDiem"
                                            value="<?php if (isset($_POST['DiaDiem'])) echo $DiaDiem;
                                                    else echo $rows['DiaDiem']; ?>">
                                        <?php if (isset($errors['DiaDiem'])): ?>
                                            <small class="text-danger"><?php echo $errors['DiaDiem']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Lịch Dạy<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <textarea rows="2" class="form-control" name="LichDay"><?php
                                        if (isset($_POST['LichDay'])) {
                                            echo htmlspecialchars(trim($_POST['LichDay']));
                                        } else {
                                            echo htmlspecialchars(trim($rows['LichDay']));
                                        }    
                                        
                                        ?></textarea>
                                        <?php if (isset($errors['LichDay'])): ?>
                                            <small class="text-danger"><?php echo $errors['LichDay']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>


                            </div>
                            <!-- Chọn  -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Thời gian bắt đầu<span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="date" class="form-control" name="ThoiGianBatDau" required style="width: auto;"
                                            value="<?php if (isset($_POST['ThoiGianBatDau'])) echo $ThoiGianBatDau;
                                                    else echo $rows['ThoiGianBatDau']; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Thời gian kết thúc<span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="date" class="form-control" name="ThoiGianKetThuc" required style="width: auto;"
                                            value="<?php if (isset($_POST['ThoiGianKetThuc'])) echo $ThoiGianKetThuc;
                                                    else echo $rows['ThoiGianKetThuc']; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Trạng Thái <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="TrangThai">
                                            <option value="xuat" <?php echo ($rows['TrangThai'] == '1') ? 'selected' : ''; ?>>Xuất Bản</option>
                                            <option value="an" <?php echo ($rows['TrangThai'] == '2') ? 'selected' : ''; ?>>Ẩn</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-offset-2 col-md-12   ">
                                    <button class="btn-sm btn-success" type="submit" name="create"> Lưu [Thêm] <i class="fa fa-save"></i> </button>
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
require_once '../Layout/footer.php';
?>