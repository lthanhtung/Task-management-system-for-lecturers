<?php
ob_start();
session_start();
require_once '../Layout/header.php';
require BASE_PATH . './Database/connect-database.php';

if (!isset($_GET['MaThongTinHoSo'])) {
    header("Location: index.php");
    exit();
}

$maThongTinHoSo = $_GET['MaThongTinHoSo'];

// Lấy dữ liệu hiện tại
$query = "SELECT thongtinhosodanhgia.*, hosodanhgiavienchuc.MaHoSo 
          FROM thongtinhosodanhgia 
          JOIN hosodanhgiavienchuc ON thongtinhosodanhgia.MaHoSo = hosodanhgiavienchuc.MaHoSo 
          WHERE thongtinhosodanhgia.MaThongTinHoSo = ?";
$stmt = $dbc->prepare($query);
$stmt->bind_param("s", $maThongTinHoSo);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    header("Location: index.php");
    exit();
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maHoSo = $_POST['MaHoSo'];
    $xepLoai = $_POST['XepLoai'];
    $ngayXepLoai = $_POST['NgayXepLoai'];

    $updateQuery = "UPDATE thongtinhosodanhgia SET MaHoSo = ?, XepLoai = ?, NgayXepLoai = ? WHERE MaThongTinHoSo = ?";
    $updateStmt = $dbc->prepare($updateQuery);
    $updateStmt->bind_param("ssss", $maHoSo, $xepLoai, $ngayXepLoai, $maThongTinHoSo);

    if ($updateStmt->execute()) {
        $_SESSION['success_message'] = "Cập nhật thành công!";
        header("Location: index.php");
        exit();
    } else {
        $error = "Lỗi khi cập nhật: " . $dbc->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật thông tin hồ sơ đánh giá</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
</head>

<body>
    <div class="content-wrapper">
        <section class="content my-2">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">CẬP NHẬT THÔNG TIN HỒ SƠ ĐÁNH GIÁ</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>Mã thông tin hồ sơ</label>
                                <input type="text" class="form-control" value="<?php echo $row['MaThongTinHoSo']; ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Mã hồ sơ</label>
                                <input type="text" name="MaHoSo" class="form-control" value="<?php echo $row['MaHoSo']; ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Xếp loại</label>
                                <input type="text" name="XepLoai" class="form-control" value="<?php echo $row['XepLoai']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Ngày xếp loại</label>
                                <input type="date" name="NgayXepLoai" class="form-control" value="<?php echo $row['NgayXepLoai']; ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                            <a href="index.php" class="btn btn-secondary">Hủy</a>
                        </form>
                    </div>
                </div>
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