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

// Xử lý Chuyển trạng thái
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = (int)$_GET['status'];

    // Kiểm tra xem lịch học phần có thuộc khoa của Admin không (nếu là Admin)
    if ($quyen === 'Admin' && $ma_khoa) {
        $query_check_faculty = "SELECT lichhocphan.MaLichHocPhan 
                                FROM lichhocphan 
                                JOIN hocphan ON lichhocphan.MaHocPhan = hocphan.MaHocPhan 
                                WHERE lichhocphan.MaLichHocPhan = ? AND hocphan.MaKhoa = ?";
        $stmt_check_faculty = $dbc->prepare($query_check_faculty);
        $stmt_check_faculty->bind_param("ss", $id, $ma_khoa);
        $stmt_check_faculty->execute();
        $result_check_faculty = $stmt_check_faculty->get_result();

        if ($result_check_faculty->num_rows === 0) {
            $_SESSION['error_message'] = "Bạn không có quyền khôi phục lịch học phần này.";
            header("Location: " . $_SERVER['PHP_SELF']);
            ob_end_flush();
            exit();
        }
        $stmt_check_faculty->close();
    }

    // Cập nhật trạng thái trong cơ sở dữ liệu
    $stmt = $dbc->prepare("UPDATE lichhocphan SET TrangThai = ? WHERE MaLichHocPhan = ?");
    $stmt->bind_param("is", $status, $id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Khôi phục thành công!";
        header("Location: " . $_SERVER['PHP_SELF']);
        ob_end_flush();
        exit();
    } else {
        $_SESSION['error_message'] = "Lỗi khi khôi phục: " . $stmt->error;
    }
    $stmt->close();
}

// Lấy danh sách lịch giảng dạy trong thùng rác
$query = "SELECT lichhocphan.*, hocphan.TenHocPhan 
          FROM lichhocphan 
          JOIN hocphan ON lichhocphan.MaHocPhan = hocphan.MaHocPhan 
          WHERE lichhocphan.TrangThai = 0";
if ($quyen === 'Admin' && $ma_khoa) {
    $query .= " AND hocphan.MaKhoa = ?";
    $stmt = $dbc->prepare($query);
    $stmt->bind_param("s", $ma_khoa);
} else {
    $stmt = $dbc->prepare($query);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thùng rác lịch giảng dạy</title>
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
                                        <strong class="text-blue">THÙNG RÁC LỊCH GIẢNG DẠY</strong>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="./index.php" class="btn-sm btn-info"><i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%;">Tên học phần</th>
                                            <th style="width: 15%;">Lớp học phần</th>
                                            <th style="width: 15%;">Thời gian bắt đầu</th>
                                            <th style="width: 15%;">Thời gian kết thúc</th>
                                            <th style="width: 15%;">Địa điểm</th>
                                            <th style="width: 25%;">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['TenHocPhan']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['LopHocPhan']) . "</td>";
                                                echo "<td>" . ($row['ThoiGianBatDau'] ? htmlspecialchars(date('d-m-Y', strtotime($row['ThoiGianBatDau']))) : '') . "</td>";
                                                echo "<td>" . ($row['ThoiGianKetThuc'] ? htmlspecialchars(date('d-m-Y', strtotime($row['ThoiGianKetThuc']))) : '') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['DiaDiem']) . "</td>";
                                                echo "<td>";
                                                echo "<a href='?id=" . htmlspecialchars($row['MaLichHocPhan']) . "&status=1' class='btn-sm btn-info'><i class='fa fa-undo'></i> Khôi phục</a> ";
                                                echo "<a href='delete.php?MaLichHocPhan=" . htmlspecialchars($row['MaLichHocPhan']) . "' class='btn-sm btn-danger'><i class='fa fa-trash'></i> Xóa vĩnh viễn</a>";
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
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
        });
    </script>
</body>

</html>

<?php
require_once '../Layout/footer.php';
mysqli_close($dbc);
?>