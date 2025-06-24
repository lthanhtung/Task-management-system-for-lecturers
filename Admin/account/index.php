<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . '/Database/connect-database.php'; // Đảm bảo kết nối cơ sở dữ liệu
?>

<?php
$query = "SELECT * FROM taikhoan";
$result = $dbc->query($query);

// Kiểm tra nếu có yêu cầu cập nhật mật khẩu
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_password = '1fFZ8o*J&zTp2L9v'; // Mật khẩu mới
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT); // Mã hóa mật khẩu

    // Cập nhật mật khẩu đã mã hóa trong cơ sở dữ liệu
    $stmt = $dbc->prepare("UPDATE taikhoan SET MatKhau = ? WHERE MaTaiKhoan = ?");
    $stmt->bind_param("ss", $hashed_password, $id); // 's' cho string

    if ($stmt->execute()) {
        // Cập nhật thành công
        $_SESSION['message'] = "Khôi phục mật khẩu thành công";
        header("Location: " . $_SERVER['PHP_SELF']); // Trở lại trang hiện tại
        ob_end_flush();
        exit();
    } else {
        // Xử lý lỗi nếu cập nhật không thành công
        echo "Lỗi khi cập nhật: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách giảng viên</title>
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
                            if (isset($_SESSION['message'])) {
                                echo '<div id="success-message" class="alert alert-success">' . $_SESSION['message'] . '</div>';
                                unset($_SESSION['message']); // Xóa thông báo sau khi hiển thị
                            }
                            ?>
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong class="text-blue">DANH SÁCH TÀI KHOẢN GIẢNG VIÊN</strong>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mã tài khoản</th>
                                            <th>Mật khẩu</th>
                                            <th>Quyền</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_array($result)) {
                                                echo "<tr>";
                                                echo "<td>{$row['MaTaiKhoan']}</td>";
                                                echo "
                                                <td>
                                                    <div style='display: flex; justify-content: space-between; align-items: center;'>
                                                        <span class='password-display' data-password='{$row['MatKhau']}'>••••••••</span>
                                                        <button class='btn btn-link toggle-password' style='padding: 0; text-decoration: underline; cursor: pointer;'>Hiện</button>
                                                    </div>
                                                </td>";
                                                echo "<td>{$row['Quyen']}</td>";
                                                echo "<td>";
                                                echo "<a href='?id={$row['MaTaiKhoan']}' class='btn-sm btn-info'> <i class='fa fa-undo'></i>Reset password</a> ";
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
    <!-- Ẩn hiện mật khẩu -->
    <script>
        // Đợi DOM được tải hoàn toàn
        document.addEventListener('DOMContentLoaded', function() {
            // Lấy tất cả các nút toggle-password
            const toggleButtons = document.querySelectorAll('.toggle-password');

            // Lặp qua từng nút và thêm sự kiện click
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Lấy phần tử span chứa mật khẩu gần nhất
                    const passwordDisplay = this.previousElementSibling;
                    const currentText = this.textContent;

                    // Toggle giữa hiển thị và ẩn mật khẩu
                    if (currentText === 'Hiện') {
                        // Hiển thị mật khẩu gốc
                        passwordDisplay.textContent = passwordDisplay.getAttribute('data-password') || passwordDisplay.textContent;
                        this.textContent = 'Ẩn';
                    } else {
                        // Lưu mật khẩu gốc nếu chưa có
                        if (!passwordDisplay.getAttribute('data-password')) {
                            passwordDisplay.setAttribute('data-password', passwordDisplay.textContent);
                        }
                        // Ẩn mật khẩu bằng dấu sao
                        passwordDisplay.textContent = '••••••••';
                        this.textContent = 'Hiện';
                    }
                });
            });
        });
    </script>

    <!-- Page specific script (giao diện cho table) -->
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