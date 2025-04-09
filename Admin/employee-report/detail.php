<?php
ob_start();
session_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

if (!isset($_GET['MaThongTinHoSo'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['MaThongTinHoSo'];
$sql = "
    SELECT thongtinhosodanhgia.*, hosodanhgiavienchuc.MaHoSo
    FROM thongtinhosodanhgia 
    JOIN hosodanhgiavienchuc ON thongtinhosodanhgia.MaHoSo = hosodanhgiavienchuc.MaHoSo 
    WHERE thongtinhosodanhgia.MaThongTinHoSo = '$id'";
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
    <title>Chi tiết thông tin hồ sơ đánh giá</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
</head>

<body>
    <div class="content-wrapper">
        <section class="content my-2">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <strong class="text-danger">THÔNG TIN HỒ SƠ ĐÁNH GIÁ</strong>
                        </div>
                    </div>
                </div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="row">
                            <!-- Cột trái -->
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Mã Thông Tin Hồ Sơ <span class="text-danger">(*)</span></label>
                                    <div class="col-md-10">
                                        <input readonly class="form-control" type="text" name="MaThongTinHoSo"
                                            value="<?php echo htmlspecialchars($rows['MaThongTinHoSo']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Mã Hồ Sơ <span class="text-danger">(*)</span></label>
                                    <div class="col-md-10">
                                        <input readonly class="form-control" type="text" name="MaHoSo"
                                            value="<?php echo htmlspecialchars($rows['MaHoSo']); ?>">
                                    </div>
                                </div>


                            </div>

                            <!-- Cột phải -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Xếp Loại <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input readonly class="form-control" type="text" name="XepLoai"
                                            value="<?php echo htmlspecialchars($rows['XepLoai']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Ngày Xếp Loại <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input readonly type="text" class="form-control" name="NgayXepLoai" required style="width: auto;"
                                            value="<?php echo htmlspecialchars($rows['NgayXepLoai']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-12">
                                    <a href="./index.php" class="btn-sm btn-info"> <i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.card-body -->
                </form>
            </div><!-- /.card -->
        </section><!-- /.content -->
    </div><!-- /.content-wrapper -->

    <script src="<?php echo BASE_URL ?>/Public/plugins/jquery/jquery.min.js"></script>
    <script src="<?php echo BASE_URL ?>/Public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
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
    <script src="<?php echo BASE_URL ?>/dist/js/adminlte.min.js"></script>
    <script src="<?php echo BASE_URL ?>/dist/js/demo.js"></script>
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
?>