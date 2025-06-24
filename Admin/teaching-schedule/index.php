<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

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

// Truy vấn dữ liệu
$query = "
SELECT lichhocphan.*, hocphan.TenHocPhan, 
       GROUP_CONCAT(DISTINCT CONCAT(giangvien.HoGiangVien, ' ', giangvien.TenGiangVien)) AS TenGiangVien,
       GROUP_CONCAT(CONCAT('Thứ ', lichgiangday.LichGiang, ': ', lichgiangday.GioBatDau, ' - ', lichgiangday.GioKetThuc) 
                    ORDER BY lichgiangday.LichGiang, lichgiangday.GioBatDau 
                    SEPARATOR ', ') AS ThoiGian,
       CONCAT(DATE_FORMAT(lichhocphan.ThoiGianBatDau, '%d-%m-%Y'), ' - ', DATE_FORMAT(lichhocphan.ThoiGianKetThuc, '%d-%m-%Y')) AS ThoiGianHoc
FROM lichhocphan 
JOIN hocphan ON lichhocphan.MaHocPhan = hocphan.MaHocPhan 
LEFT JOIN lichgiangday ON lichgiangday.MaLichHocPhan = lichhocphan.MaLichHocPhan
LEFT JOIN giangvien ON lichgiangday.MaGiangVien = giangvien.MaGiangVien";
if ($quyen === 'Admin' && $ma_khoa) {
    $query .= " WHERE lichhocphan.TrangThai IN (1, 2) AND hocphan.MaKhoa = '" . mysqli_real_escape_string($dbc, $ma_khoa) . "'";
} else {
    $query .= " WHERE lichhocphan.TrangThai IN (1, 2)";
}
$query .= " GROUP BY lichhocphan.MaLichHocPhan";
$result = $dbc->query($query);

// Xử lý Chuyển trạng Thái
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
    <title>Lịch học phần</title>
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
                                unset($_SESSION['success_message']); // Xóa thông báo sau khi hiển thị
                            }
                            ?>
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong class="text-blue">DANH SÁCH LỊCH GIẢNG DẠY</strong>
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
                                            <th style="width: 12%;">Tên học phần</th>
                                            <th style="width: 12%;">Lớp học phần</th>
                                            <th style="width: 15%;">Thời gian học</th>
                                            <th style="width: 10%;">Địa điểm</th>
                                            <th style="width: 16%;">Thời gian</th>
                                            <th style="width: 12%;">Giảng viên</th>
                                            <th style="width: 23%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_array($result)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['TenHocPhan']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['LopHocPhan']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['ThoiGianHoc']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['DiaDiem']) . "</td>";
                                                echo "<td>" . ($row['ThoiGian'] ? str_replace(', ', '<br>', htmlspecialchars($row['ThoiGian'])) : 'Chưa có lịch') . "</td>";
                                                echo "<td>" . ($row['TenGiangVien'] ? htmlspecialchars($row['TenGiangVien']) : 'Chưa có giảng viên') . "</td>";
                                                echo "<td>";
                                                if ($row['TrangThai'] == 1) {
                                                    echo "<a href='?id=" . htmlspecialchars($row['MaLichHocPhan']) . "&status=2' class='btn-sm btn-success'><i class='fa fa-toggle-on'></i></a> ";
                                                } else {
                                                    echo "<a href='?id=" . htmlspecialchars($row['MaLichHocPhan']) . "&status=1' class='btn-sm btn-danger'><i class='fa fa-toggle-off'></i></a> ";
                                                }
                                                echo "<a href='edit.php?MaLichHocPhan=" . htmlspecialchars($row['MaLichHocPhan']) . "' class='btn-sm btn-info'> <i class='fa fa-edit'></i> Cập nhật </a> ";
                                                echo "<a href='?id=" . htmlspecialchars($row['MaLichHocPhan']) . "&status=0' class='btn-sm btn-danger'> <i class='fa fa-trash'></i> Xóa </a>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7' class='text-center'>Không có dữ liệu</td></tr>";
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
    <!-- <script src="<?php echo BASE_URL ?>/dist/js/adminlte.min.js"></script> -->
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
        });
    </script>
</body>

</html>

<?php
require_once '../Layout/footer.php';
mysqli_close($dbc);
?>