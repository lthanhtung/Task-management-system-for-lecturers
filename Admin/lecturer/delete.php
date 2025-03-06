<?php
ob_start();
session_start(); // Bắt đầu phiên;
require_once '../Layout/header.php';
require BASE_PATH . './Database/connect-database.php';

// Truy vấn chỉ lấy các bản ghi có trạng thái == 0
$query = "
SELECT giangvien.MaGiangVien,giangvien.HoGiangVien, giangvien.TenGiangVien, giangvien.HocVi, giangvien.ChucDanh, khoa.TenKhoa,giangvien.TrangThai
FROM giangvien
JOIN khoa ON giangvien.MaKhoa = khoa.MaKhoa 
WHERE giangvien.TrangThai =0";
$result = $dbc->query($query);

// Xử lý yêu cầu xóa
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Xóa bản ghi trong cơ sở dữ liệu
    $stmt = $dbc->prepare("DELETE FROM giangvien WHERE MaGiangVien = ?");
    $stmt->bind_param("s", $id); // 's' cho string

    if ($stmt->execute()) {
        // Xóa thành công
        $_SESSION['message'] = "Xóa giảng viên thành công!";
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
                                        <strong class="text-blue">XÓA GIẢNG VIÊN<N></N></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%;">Họ giảng viên</th>
                                            <th style="display: flex;justify-content: center;align-content: center; width: 15%;"">Tên giảng viên</th>
                                            <th style=" width: 8% ;display: flex;justify-content: center;align-content: center;">Học Vị</th>
                                            <th style=" display: flex;justify-content: center;align-content: center; width: 10%;">Chức Danh</th>
                                            <th style="display: flex;justify-content: center;align-content: center; width: 10%;">Khoa</th>
                                            <th style="width: 20%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_array($result)) {
                                                echo "<tr>";
                                                echo "<td>{$row['HoGiangVien']}</td>";
                                                echo "<td>{$row['TenGiangVien']}</td>";
                                                echo "<td>{$row['HocVi']}</td>";
                                                echo "<td>{$row['ChucDanh']}</td>";
                                                echo "<td>{$row['TenKhoa']}</td>"; // Nếu bạn cũng muốn hiển thị tên khoa
                                                echo "<td>";
                                                echo "<a href='?id={$row['MaGiangVien']}' class='btn-sm btn-danger' onclick='return confirm(\"Bạn có chắc chắn muốn xóa không?\");'> <i class='fa fa-trash'></i> Xác Nhận Xóa </a>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <div class="col-md-6 text-left">
                                    <a href="./index.php" class="btn-sm btn-info"> <i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
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