<?php
ob_start();
require_once '../Layout/header.php';

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

// Xây dựng câu query dựa trên quyền
$query = "
    SELECT giangvien.*, khoa.TenKhoa
    FROM giangvien
    JOIN khoa ON giangvien.MaKhoa = khoa.MaKhoa 
    WHERE giangvien.TrangThai = 0";

if ($quyen === 'Admin' && $ma_khoa !== null) {
    // Nếu là Admin, thêm điều kiện lọc theo MaKhoa
    $query .= " AND giangvien.MaKhoa = ?";
    $stmt = $dbc->prepare($query);
    $stmt->bind_param("s", $ma_khoa);
} else {
    // Nếu không phải Admin, lấy tất cả giảng viên
    $stmt = $dbc->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();

// Xử lý Chuyển trạng Thái
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    // Cập nhật trạng thái trong cơ sở dữ liệu
    $stmt_update = $dbc->prepare("UPDATE giangvien SET TrangThai = ? WHERE MaGiangVien = ?");
    $stmt_update->bind_param("is", $status, $id);

    if ($stmt_update->execute()) {
        // Cập nhật thành công
        $_SESSION['message'] = "Khôi phục thành công";
        header("Location: " . $_SERVER['PHP_SELF']);
        ob_end_flush();
        exit();
    } else {
        echo "Lỗi khi cập nhật: " . $stmt_update->error;
    }
    $stmt_update->close();
}

$stmt->close();
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
                                unset($_SESSION['message']);
                            }
                            ?>
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong class="text-blue">DANH SÁCH GIẢNG VIÊN</strong>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="./trash.php" class="btn-sm btn-danger"> <i class="fa fa-trash"></i> Thùng rác</a>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ảnh đại diện</th>
                                            <th>Họ tên giảng viên</th>
                                            <th>Khoa</th>
                                            <th>Học vị</th>
                                            <th>Chức danh</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td style='text-align: center; vertical-align: middle;'>
                                                        <img src='{$row['AnhDaiDien']}' alt='Ảnh đại diện' style='width: 100px; height: auto;'>
                                                      </td>";
                                                echo "<td> {$row['HoGiangVien']} {$row['TenGiangVien']}</td>";
                                                echo "<td>{$row['TenKhoa']}</td>";
                                                echo "<td>{$row['HocVi']}</td>";
                                                echo "<td>{$row['ChucDanh']}</td>";
                                                echo "<td>";
                                                echo "<a href='?id={$row['MaGiangVien']}&status=1' class='btn-sm btn-info'> <i class='fa fa-undo'></i> Khôi phục </a> ";
                                                echo "<a href='delete.php?MaGiangVien={$row['MaGiangVien']}' class='btn-sm btn-danger'> <i class='fa fa-trash'></i> Xóa </a> ";
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
    <!-- <script src="<?php echo BASE_URL ?>/Public/dist/js/adminlte.min.js"></script> -->
    <!-- AdminLTE for demo purposes -->
    <script src="<?php echo BASE_URL ?>/Public/dist/js/demo.js"></script>
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