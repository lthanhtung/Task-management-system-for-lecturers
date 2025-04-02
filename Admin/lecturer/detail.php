<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

$id = $_GET['MaGiangVien'];
// Lấy thông tin khoa từ cơ sở dữ liệu để hiển thị
$sql = "
    SELECT giangvien.*, lichtiepsinhvien.*
    FROM giangvien 
    JOIN lichtiepsinhvien ON giangvien.MaGiangVien = lichtiepsinhvien.MaGiangVien 
    WHERE giangvien.MaGiangVien = '$id'";
$result = mysqli_query($dbc, $sql);
$rows = mysqli_fetch_array($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách công việc</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
</head>

<body>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content my-2">
            <!-- Default box -->
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <strong class="text-danger">THÔNG TIN GIẢNG VIÊN</strong>
                        </div>

                    </div>
                </div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="row">
                            <!-- Ghi -->
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Mã Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input readonly class="form-control" type="text" name="MaGiangVien"
                                            value="<?php echo $rows['MaGiangVien']; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Họ Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input readonly class="form-control" type="text" name="HoGiangVien"
                                            value="<?php echo $rows['HoGiangVien']; ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tên Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input readonly class="form-control" type="text" name="TenGiangVien"
                                            value="<?php echo $rows['TenGiangVien']; ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Email <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input readonly class="form-control" type="text" name="Email"
                                            value="<?php echo $rows['Email']; ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Thời gian gặp sinh viên <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input readonly class="form-control" type="text" name="ThoiGian"
                                            value="<?php echo $rows['ThuTiepSinhVien'].' vào lúc: '.$rows['GioBatDau'].' đến '.$rows['GioKetThuc'] ; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Địa điểm gặp sinh viên <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input readonly class="form-control" type="text" name="DiaDiem"
                                            value="<?php echo $rows['DiaDiem']; ?>">
                                    </div>
                                </div>


                                <div class="form-group">
                                    <label>Trạng Thái <span class="text-danger">(*)</span></label>
                                    <div class="col-md-3">
                                        <input readonly class="form-control" type="text" name="TrangThai"
                                            value="<?php if ($rows['TrangThai'] == '1') {
                                                        echo 'Đang dạy';
                                                    } elseif ($rows['TrangThai'] == '2') {
                                                        echo 'Về hưu';
                                                    } else {
                                                        echo 'Chuyển truòng';
                                                    } ?>">
                                    </div>
                                </div>
                            </div>
                            <!-- Chọn  -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ngày sinh <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input readonly type="text" class="form-control" name="NgaySinh" required style="width: auto;"
                                            value="<?php echo $rows['NgaySinh']; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Khoa <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input readonly type="text" class="form-control" name="NgaySinh" required style="width: auto;"
                                            value="<?php
                                                    $sql = "
                                                    SELECT khoa.TenKhoa 
                                                    FROM giangvien 
                                                    JOIN khoa ON giangvien.MaKhoa = khoa.MaKhoa 
                                                    WHERE khoa.TrangThai = 1 AND giangvien.MaGiangVien = '$id'";
                                                    $result = mysqli_query($dbc, $sql);
                                                   
                                                    if (mysqli_num_rows($result) > 0) {
                                                        while ($row = mysqli_fetch_array($result)) {
                                                            
                                                            echo $row['TenKhoa'];
                                                        }
                                                    }
                                                    ?>  ">
                                    </div>
                                </div>
                                <div class=" form-group">
                                    <label>Giới tính <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input readonly type="text" class="form-control" name="NgaySinh" required style="width: auto;"
                                            value="<?php echo ($rows['GioiTinh'] == 0) ? 'Nam' : 'Nữ'; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Số điện thoại <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-7">
                                        <input readonly class="form-control" type="text" name="SDT"
                                            value="<?php echo $rows['SoDienThoai']; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Học vị <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input readonly type="text" class="form-control" name="NgaySinh" required style="width: auto;"
                                            value=" <?php echo ($rows['HocVi'] == 'Thạc sĩ') ? 'Thạc sĩ' : 'Tiến sĩ'; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Chức danh <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input readonly type="text" class="form-control" name="ChucDanh" value="<?php echo htmlspecialchars($rows['ChucDanh']); ?>" />
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
            $('#example2').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
            });
        });
    </script>

</body>

</html>

<?php
require_once '../Layout/footer.php';
?>