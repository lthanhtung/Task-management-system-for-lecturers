<?php
ob_start(); // Bắt đầu output buffering
session_start(); // Khởi động session để lưu thông báo
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

// Xử lý xóa khi có yêu cầu qua GET
if (isset($_GET['id']) && isset($_GET['status']) && $_GET['status'] == 0) {
    $maCongViecHanhChinh = mysqli_real_escape_string($dbc, $_GET['id']);

    // Bắt đầu transaction để đảm bảo xóa đồng bộ
    mysqli_begin_transaction($dbc);
    try {
        // Xóa tất cả bản ghi trong thongtincongviechanhchinh liên quan đến MaCongViecHanhChinh
        $deleteDetailSql = "DELETE FROM thongtincongviechanhchinh WHERE MaCongViecHanhChinh = '$maCongViecHanhChinh'";
        $deleteDetailResult = mysqli_query($dbc, $deleteDetailSql);

        if (!$deleteDetailResult) {
            throw new Exception("Lỗi khi xóa thông tin công việc: " . mysqli_error($dbc));
        }

        // Xóa bản ghi trong congviechanhchinh
        $deleteMainSql = "DELETE FROM congviechanhchinh WHERE MaCongViecHanhChinh = '$maCongViecHanhChinh'";
        $deleteMainResult = mysqli_query($dbc, $deleteMainSql);

        if (!$deleteMainResult) {
            throw new Exception("Lỗi khi xóa công việc hành chính: " . mysqli_error($dbc));
        }

        // Commit transaction nếu không có lỗi
        mysqli_commit($dbc);

        // Lưu thông báo thành công vào session
        $_SESSION['success_message'] = "Xóa công việc thành công!";
        header("Location: index.php"); // Chuyển hướng sau khi xóa
        ob_end_flush();
        exit();
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        mysqli_rollback($dbc);
        echo "<p class='text-danger'>" . $e->getMessage() . "</p>";
    }
}

// Lấy dữ liệu từ hai bảng bằng cách join
$sql = "SELECT 
            cvc.MaCongViecHanhChinh,
            cvc.TenCongViec,
            ttcvc.MaThongTinCongViec,
            ttcvc.MaGiangVien,
            gv.HoGiangVien,
            gv.TenGiangVien,
            ttcvc.LoaiCongViec,
            ttcvc.NgayThucHien,
            ttcvc.GioBatDau,
            ttcvc.DiaDiem
        FROM congviechanhchinh cvc
        LEFT JOIN thongtincongviechanhchinh ttcvc ON cvc.MaCongViecHanhChinh = ttcvc.MaCongViecHanhChinh
        LEFT JOIN giangvien gv ON ttcvc.MaGiangVien = gv.MaGiangVien";
$result = mysqli_query($dbc, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách công việc</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
</head>

<body>
    <div class="content-wrapper">
        <section class="content-header"></section>
        <section class="content">
            <div class="container-fluid">
                <!-- Hiển thị thông báo thành công -->
                <?php
                if (isset($_SESSION['success_message'])) {
                    echo '<div id="success-message" class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';                    unset($_SESSION['success_message']); // Xóa thông báo sau khi hiển thị
                }
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong class="text-blue">DANH SÁCH CÔNG VIỆC</strong>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="../lecturer/trash.php" class="btn-sm btn-danger"> <i class="fa fa-trash"></i> Thùng rác</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mã Công Việc</th>
                                            <th>Tên Công Việc</th>
                                            <th>Giảng Viên</th>
                                            <th>Loại Công Việc</th>
                                            <th>Ngày Thực Hiện</th>
                                            <th>Giờ Bắt Đầu</th>
                                            <th>Địa Điểm</th>
                                            <th>Hành Động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['MaCongViecHanhChinh']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['TenCongViec']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['HoGiangVien'] . " " . $row['TenGiangVien']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['LoaiCongViec']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['NgayThucHien']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['GioBatDau']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['DiaDiem']) . "</td>";
                                                echo "<td>";
                                                echo "<a href='#' class='btn-sm btn-danger delete-btn' data-id='{$row['MaCongViecHanhChinh']}'> <i class='fa fa-trash'></i> Xóa </a>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='8' class='text-center'>Không có dữ liệu</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
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
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

            // Xử lý nút Xóa với confirm
            $('.delete-btn').on('click', function(e) {
                e.preventDefault(); // Ngăn chặn hành vi mặc định của link
                var id = $(this).data('id'); // Lấy ID từ data-id
                if (confirm('Bạn có chắc chắn muốn xóa công việc này không?')) {
                    window.location.href = '?id=' + id + '&status=0'; // Chuyển hướng để xóa
                }
            });

            // Hiển thị và ẩn thông báo thành công
            var successMessage = $('#success-message');
            if (successMessage.length) {
                successMessage.show(); // Hiển thị thông báo
                setTimeout(function() {
                    successMessage.hide(); // Ẩn sau 3 giây
                }, 3000);
            }
        });
    </script>
</body>

</html>

<?php
ob_end_flush(); // Kết thúc buffering và gửi output
require_once '../Layout/footer.php';
mysqli_close($dbc);
?>