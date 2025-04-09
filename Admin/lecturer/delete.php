<?php
ob_start();
session_start(); // Bắt đầu phiên
require_once '../Layout/header.php';
require BASE_PATH . './Database/connect-database.php';

// Truy vấn chỉ lấy các bản ghi có trạng thái == 0
$query = "
SELECT giangvien.*, khoa.TenKhoa
FROM giangvien
JOIN khoa ON giangvien.MaKhoa = khoa.MaKhoa 
WHERE giangvien.TrangThai = 0";
$result = $dbc->query($query);

// Xử lý yêu cầu xóa
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Bắt đầu giao dịch
    $dbc->begin_transaction();

    try {
        // Truy vấn để lấy đường dẫn ảnh trước khi xóa (tùy chọn)
        $queryImg = $dbc->prepare("SELECT AnhDaiDien FROM giangvien WHERE MaGiangVien = ?");
        $queryImg->bind_param("s", $id);
        $queryImg->execute();
        $resultImg = $queryImg->get_result();
        $rowImg = $resultImg->fetch_assoc();
        $anhDaiDienPath = BASE_PATH . str_replace(BASE_URL, '', $rowImg['AnhDaiDien']);

        // Xóa file ảnh nếu tồn tại (tùy chọn)
        if (file_exists($anhDaiDienPath)) {
            if (!unlink($anhDaiDienPath)) {
                throw new Exception("Không thể xóa file ảnh đại diện.");
            }
        }

        // Xóa thông tin từ bảng thongtinhosodanhgia
        $stmt1 = $dbc->prepare("DELETE FROM thongtinhosodanhgia WHERE MaHoSo = ?");
        $stmt1->bind_param("s", $id);
        $stmt1->execute();

        // Xóa thông tin từ bảng hosodanhgiavienchuc
        $stmt2 = $dbc->prepare("DELETE FROM hosodanhgiavienchuc WHERE MaHoSo = ?");
        $stmt2->bind_param("s", $id);
        $stmt2->execute();

        // Xóa thông tin từ bảng lichtiepsinhvien
        $stmt3 = $dbc->prepare("DELETE FROM lichtiepsinhvien WHERE MaGiangVien = ?");
        $stmt3->bind_param("s", $id);
        $stmt3->execute();

        // Xóa thông tin từ bảng taikhoan
        $stmt4 = $dbc->prepare("DELETE FROM taikhoan WHERE MaTaiKhoan = ?");
        $stmt4->bind_param("s", $id);
        $stmt4->execute();

        // Xóa thông tin từ bảng giangvien
        $stmt5 = $dbc->prepare("DELETE FROM giangvien WHERE MaGiangVien = ?");
        $stmt5->bind_param("s", $id);
        $stmt5->execute();

        // Nếu tất cả thành công, commit giao dịch
        $dbc->commit();
        $_SESSION['message'] = "Xóa giảng viên và tất cả thông tin liên quan thành công!";
        header("Location: " . $_SERVER['PHP_SELF']);
        ob_end_flush();
        exit();
    } catch (Exception $e) {
        // Nếu có lỗi, rollback giao dịch
        $dbc->rollback();
        echo "Lỗi khi xóa: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách giảng viên</title>
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
                                        <strong class="text-blue">XÓA GIẢNG VIÊN</strong>
                                    </div>
                                </div>
                            </div>
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
                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_array($result)) {
                                                echo "<tr>";
                                                echo "<td style='text-align: center; vertical-align: middle;'>
                                                <img src='{$row['AnhDaiDien']}' alt='Ảnh đại diện' style='width: 100px; height: auto;'>
                                                </td>";
                                                echo "<td> {$row['HoGiangVien']} {$row['TenGiangVien']}</td>";
                                                echo "<td>{$row['TenKhoa']}</td>";
                                                echo "<td>{$row['HocVi']}</td>";
                                                echo "<td>{$row['ChucDanh']}</td>";
                                                echo "<td>";
                                                echo "<a href='?id={$row['MaGiangVien']}' class='btn-sm btn-danger' onclick='return confirm(\"Bạn có chắc chắn muốn xóa không? Tất cả thông tin liên quan (bao gồm ảnh đại diện) sẽ bị xóa.\");'> <i class='fa fa-trash'></i> Xác Nhận Xóa </a>";
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