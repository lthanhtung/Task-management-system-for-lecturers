<?php
ob_start();
session_start(); // Bắt đầu phiên;
require_once '../Layout/header.php'
?>

<?php
require BASE_PATH . './Database/connect-database.php';
$query = "SELECT lichhocphan.*, hocphan.TenHocPhan 
          FROM lichhocphan 
          JOIN hocphan 
          ON lichhocphan.MaHocPhan = hocphan.MaHocPhan 
          WHERE lichhocphan.TrangThai IN (1, 2)";
$result = $dbc->query($query);

// Xử lý Chuyển trạng Thái
// Kiểm tra nếu có yêu cầu cập nhật trạng thái
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    // Cập nhật trạng thái trong cơ sở dữ liệu
    $stmt = $dbc->prepare("UPDATE lichhocphan SET TrangThai = ? WHERE MaLichHocPhan = ?");
    $stmt->bind_param("is", $status, $id); // 'i' cho integer, 's' cho string

    if ($stmt->execute()) {
        // Cập nhật thành công
        if ($status == 0) {
            $_SESSION['success_message'] = "Đã chuyển vào thùng rác!";
        } else {
            $_SESSION['success_message'] = "Cập nhật trạng thái thành công!";
        }
        header("Location: " . $_SERVER['PHP_SELF']); // Trở lại trang hiện tại
        ob_end_flush();
        exit();
    } else {
        // Xử lý lỗi nếu cập nhật không thành công
        echo "Lỗi khi cập nhật: " . $stmt->error;
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
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content my-2">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <?php
                            if (isset($_SESSION['success_message'])) {
                                echo '<div id="success-message" class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                                unset($_SESSION['success_message']); // Xóa thông báo sau khi hiển thị
                            }
                            ?>
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong class="text-blue">LỊCH GIẢNG DẠY<N></N></strong>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="./trash.php" class="btn-sm btn-danger"> <i class="fa fa-trash"></i>Thùng rác</a>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%;">Tên học phần</th>
                                            <th style="width: 10%;">Lớp học phần </th>
                                            <th style="width: 12%;">Thời gian bắt đầu</th>
                                            <th style="width: 12%;">Thời gian két thúc</th>
                                            <th style="width: 10%;">Địa điểm </th>
                                            <th style="width: 14%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_array($result)) {
                                                echo "<tr>";
                                                echo "<td >{$row['TenHocPhan']}</td>";
                                                echo "<td >{$row['LopHocPhan']}</td>";

                                                echo "<td>{$row['ThoiGianBatDau']}</td>";
                                                echo "<td>{$row['ThoiGianKetThuc']}</td>";
                                                echo "<td>{$row['DiaDiem']}</td>";
                                                echo "<td>";
                                                // Chuyển trạng thái
                                                if ($row['TrangThai'] == 1) {
                                                    // Nếu trạng thái là 1, hiển thị nút "Bật"
                                                    echo "<a href='?id={$row['MaLichHocPhan']}&status=2' class='btn-sm btn-success'><i class='fa fa-toggle-on'></i></a>&nbsp;&nbsp;";
                                                } else {
                                                    // Nếu trạng thái không phải là 1, hiển thị nút "Tắt"
                                                    echo "<a href='?id={$row['MaLichHocPhan']}&status=1' class='btn-sm btn-danger'><i class='fa fa-toggle-off'></i></a>&nbsp;&nbsp;";
                                                }

                                                echo "<a href='edit.php?MaLichHocPhan={$row[0]}' class='btn-sm btn-info'> <i class='fa fa-edit'></i> Cập nhập </a>&nbsp;&nbsp;";
                                                echo "<a href='?id={$row['MaLichHocPhan']}&status=0' class='btn-sm btn-danger'> <i class='fa fa-trash'></i> Xóa </a>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>

                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>

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
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": true,
                "responsive": true,
            });
        });
    </script>

</body>

</html>



<?php
require_once '../Layout/footer.php'
?>