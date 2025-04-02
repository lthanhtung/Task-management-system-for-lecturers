<?php
ob_start();
session_start(); // Bắt đầu phiên;
require_once '../Layout/header.php';
require BASE_PATH . './Database/connect-database.php';

// Truy vấn chỉ lấy các bản ghi có trạng thái == 0
$query = "SELECT lichhocphan.*, hocphan.TenHocPhan 
          FROM lichhocphan 
          JOIN hocphan 
          ON lichhocphan.MaHocPhan = hocphan.MaHocPhan 
          WHERE lichhocphan.TrangThai = 0";
$result = $dbc->query($query);

// Xử lý yêu cầu xó<a href=""></a>
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Xóa bản ghi trong cơ sở dữ liệu
    $stmt = $dbc->prepare("DELETE FROM lichhocphan WHERE MaLichHocPhan = ?");
    $stmt->bind_param("s", $id); // 's' cho string

    if ($stmt->execute()) {
        // Xóa thành công
        $_SESSION['message'] = "Xóa lịch giảng dạy thành công!";
        header("Location: " . $_SERVER['PHP_SELF']); // Trở lại trang hiện tại
        ob_end_flush();
        exit();
    } else {
        // Xử lý lỗi nếu xóa không thành công
        echo "Lỗi khi xóa: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách khoa</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
</head>

<body>
    <div class="content-wrapper">
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
                                        <strong class="text-blue">XÓA LỊCH GIẢNG DẠY</strong>
                                    </div>
                                </div>
                            </div>
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
                                                echo "<td>{$row['LopHocPhan']}</td>";
                                                echo "<td>{$row['ThoiGianBatDau']}</td>";
                                                echo "<td>{$row['ThoiGianKetThuc']}</td>";
                                                echo "<td>{$row['DiaDiem']}</td>";
                                                echo "<td>";
                                                echo "<a href='?id={$row['MaLichHocPhan']}' class='btn-sm btn-danger' onclick='return confirm(\"Bạn có chắc chắn muốn xóa không?\");'> <i class='fa fa-trash'></i> Xác Nhận Xóa </a>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <div class="col-md-6 text-left">
                                    <a href="./trash.php" class="btn-sm btn-info"> <i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                                </div>
                            </div>
                        </div>
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
    <script src="<?php echo BASE_URL ?>/Public/dist/js/adminlte.min.js"></script>
    <script>
        $(function() {
            $("#example1").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
            });
        });
    </script>

</body>

</html>

<?php
require_once '../Layout/footer.php';
?>