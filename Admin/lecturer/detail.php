<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

if (!isset($_GET['MaGiangVien'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['MaGiangVien'];
// Fetch lecturer information
$sql = "
    SELECT giangvien.*, khoa.TenKhoa, lichtiepsinhvien.ThuTiepSinhVien, lichtiepsinhvien.GioBatDau, lichtiepsinhvien.GioKetThuc, lichtiepsinhvien.DiaDiem
    FROM giangvien 
    JOIN khoa ON giangvien.MaKhoa = khoa.MaKhoa 
    LEFT JOIN lichtiepsinhvien ON giangvien.MaGiangVien = lichtiepsinhvien.MaGiangVien 
    WHERE giangvien.MaGiangVien = '$id'";
$result = mysqli_query($dbc, $sql);

if (!$result) {
    die("Lỗi truy vấn SQL: " . mysqli_error($dbc));
}

$rows = mysqli_fetch_array($result);

if (!$rows) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Giảng Viên</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
</head>
<body>
    <div class="content-wrapper">
        <section class="content my-2">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <strong class="text-danger">THÔNG TIN GIẢNG VIÊN</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="./index.php" class="btn-sm btn-info"><i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                        </div>
                    </div>
                </div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Mã Giảng Viên <span class="text-danger">(*)</span></label>
                                    <input readonly class="form-control" type="text" name="MaGiangVien" value="<?php echo htmlspecialchars($rows['MaGiangVien']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Họ Giảng Viên <span class="text-danger">(*)</span></label>
                                    <input readonly class="form-control" type="text" name="HoGiangVien" value="<?php echo htmlspecialchars($rows['HoGiangVien']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Tên Giảng Viên <span class="text-danger">(*)</span></label>
                                    <input readonly class="form-control" type="text" name="TenGiangVien" value="<?php echo htmlspecialchars($rows['TenGiangVien']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Email <span class="text-danger">(*)</span></label>
                                    <input readonly class="form-control" type="text" name="Email" value="<?php echo htmlspecialchars($rows['Email']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Số Điện Thoại <span class="text-danger">(*)</span></label>
                                    <input readonly class="form-control" type="text" name="SDT" value="<?php echo htmlspecialchars($rows['SoDienThoai']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Thời Gian Gặp Sinh Viên</label>
                                    <input readonly class="form-control" type="text" name="ThoiGian" value="<?php
                                        if (!empty($rows['ThuTiepSinhVien']) && !empty($rows['GioBatDau']) && !empty($rows['GioKetThuc'])) {
                                            echo htmlspecialchars($rows['ThuTiepSinhVien'] . ' vào lúc: ' . $rows['GioBatDau'] . ' đến ' . $rows['GioKetThuc']);
                                        } else {
                                            echo 'Đang cập nhật';
                                        }
                                    ?>">
                                </div>
                                <div class="form-group">
                                    <label>Địa Điểm Gặp Sinh Viên</label>
                                    <input readonly class="form-control" type="text" name="DiaDiem" value="<?php
                                        echo !empty($rows['DiaDiem']) ? htmlspecialchars($rows['DiaDiem']) : 'Đang cập nhập';
                                    ?>">
                                </div>
                            </div>
                            <!-- Right Column -->
                            <div class="col-md-6">
                                <!-- Ảnh đại diện -->
                                <div class="form-group">
                                    <label>Ảnh đại diện</label>
                                    <div class="col-md-10">
                                        <?php if (!empty($rows['AnhDaiDien'])): ?>
                                            <img src="<?php echo htmlspecialchars($rows['AnhDaiDien']); ?>" alt="Ảnh đại diện" style="width: 100px; height: auto;">
                                        <?php else: ?>
                                            <p>Không có ảnh đại diện</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Ngày Sinh <span class="text-danger">(*)</span></label>
                                    <input readonly class="form-control" type="text" name="NgaySinh" value="<?php echo htmlspecialchars($rows['NgaySinh']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Giới Tính <span class="text-danger">(*)</span></label>
                                    <input readonly class="form-control" type="text" name="GioiTinh" value="<?php echo ($rows['GioiTinh'] == 1) ? 'Nam' : 'Nữ'; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Khoa <span class="text-danger">(*)</span></label>
                                    <input readonly class="form-control" type="text" name="Khoa" value="<?php echo htmlspecialchars($rows['TenKhoa']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Học Vị <span class="text-danger">(*)</span></label>
                                    <input readonly class="form-control" type="text" name="HocVi" value="<?php echo htmlspecialchars($rows['HocVi']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Chức Danh <span class="text-danger">(*)</span></label>
                                    <input readonly class="form-control" type="text" name="ChucDanh" value="<?php echo htmlspecialchars($rows['ChucDanh']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Trạng Thái <span class="text-danger">(*)</span></label>
                                    <input readonly class="form-control" type="text" name="TrangThai" value="<?php
                                        switch ($rows['TrangThai']) {
                                            case 1: echo 'Đang dạy'; break;
                                            case 2: echo 'Về hưu'; break;
                                            default: echo 'Chuyển công tác';
                                        }
                                    ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <a href="./index.php" class="btn-sm btn-info"><i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <script src="<?php echo BASE_URL ?>/Public/plugins/jquery/jquery.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/dist/js/adminlte.min.js"></script>
</body>
</html>

<?php
require_once '../Layout/footer.php';
?>