<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

// Kiểm tra quyền và lấy MaKhoa của Admin
$user_id = $_SESSION['user_id'] ?? '';
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

// Truy vấn lấy danh sách hướng dẫn sinh viên
$sql = "
    SELECT 
        h.KeHoachHuongDan, 
        t.MaSinhVien, 
        t.HoSinhVien, 
        t.TenSinhVien, 
        t.TenDeTai, 
        t.DiemTongKet, 
        g.MaGiangVien, 
        CONCAT(g.HoGiangVien, ' ', g.TenGiangVien) AS TenGiangVien
    FROM 
        huongdansinhvien h
        JOIN thongtinhuongdansinhvien t ON h.MaHuongDan = t.MaHuongDan
        JOIN giangvien g ON h.MaGiangVien = g.MaGiangVien
";
if ($quyen === 'Admin' && $ma_khoa) {
    $sql .= " WHERE g.MaKhoa = ?";
    $stmt = $dbc->prepare($sql);
    $stmt->bind_param("s", $ma_khoa);
} else {
    $stmt = $dbc->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách hướng dẫn sinh viên</title>
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
                                echo '<div id="success-message" class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                                unset($_SESSION['success_message']);
                            }
                            if (isset($_SESSION['error_message'])) {
                                echo '<div id="error-message" class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                                unset($_SESSION['error_message']);
                            }
                            ?>
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong class="text-blue">DANH SÁCH HƯỚNG DẪN SINH VIÊN</strong>
                                    </div>
                                    
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 20%;">Kế hoạch hướng dẫn</th>
                                            <th style="width: 15%;">MSSV</th>
                                            <th style="width: 20%;">Họ tên sinh viên</th>
                                            <th style="width: 20%;">Giảng viên hướng dẫn</th>
                                            <th style="width: 15%;">Tên đề tài</th>
                                            <th style="width: 10%;">Điểm tổng kết</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['KeHoachHuongDan']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['MaSinhVien']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['HoSinhVien'] . ' ' . $row['TenSinhVien']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['TenGiangVien']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['TenDeTai']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['DiemTongKet']) . "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center'>Không có dữ liệu</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
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
    <script src="<?php echo BASE_URL ?>/Public/dist/js/adminlte.min.js"></script>
    <!-- Page specific script -->
    <script>
        $(function() {
            $("#example1").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

            // Hiển thị và ẩn thông báo
            var successMessage = $('#success-message');
            var errorMessage = $('#error-message');
            if (successMessage.length) {
                successMessage.show();
                setTimeout(function() {
                    successMessage.fadeOut();
                }, 3000);
            }
            if (errorMessage.length) {
                errorMessage.show();
                setTimeout(function() {
                    errorMessage.fadeOut();
                }, 3000);
            }
        });
    </script>
</body>

</html>

<?php
require_once '../Layout/footer.php';
mysqli_close($dbc);
?>