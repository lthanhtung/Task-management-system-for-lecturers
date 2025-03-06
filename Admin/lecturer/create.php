<?php
ob_start();
require_once '../Layout/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once BASE_PATH . './Database/connect-database.php';
    $errors = array(); // Initialize an error array.

    // Kiểm tra Mã học Phần
    if (empty($_POST['MaGiangVien'])) {
        $errors['MaGiangVien'] = 'Mã giảng viên không để trống!';
    } else {
        $MaGiangVien = mysqli_real_escape_string($dbc, trim($_POST['MaGiangVien']));
        $sql = "SELECT * FROM giangvien WHERE MaGiangVien = '$MaGiangVien'";
        $result = mysqli_query($dbc, $sql);

        if (mysqli_num_rows($result) > 0) {
            $errors['MaGiangVien'] = 'Mã giảng viên bị trùng';
        }
    }

    //Kiểm tra họ giảng viên
    if (empty($_POST['HoGiangVien'])) {
        $errors['HoGiangVien'] = 'Họ giảng viên không để trống';
    } else {
        $HoGiangVien = mysqli_real_escape_string($dbc, trim($_POST['HoGiangVien']));
    }

    // Kiểm tra Tên giảng Viên
    if (empty($_POST['TenGiangVien'])) {
        $errors['TenGiangVien'] = 'Tên giảng viên không để trống';
    } else {
        $TenGiangVien = mysqli_real_escape_string($dbc, trim($_POST['TenGiangVien']));
    }

    //Ngày sinh
    //Khoa
    if (isset($_POST['NgaySinh'])) {
        $NgaySinh = mysqli_real_escape_string($dbc, trim($_POST['NgaySinh']));
    }

    //Giới tính
    if (isset($_POST['GioiTinh'])) {
        if ($_POST['GioiTinh'] === 'nam') {
            $GioiTinh = 1;
        } else {
            $GioiTinh = 2;
        }
    }

    //Kiểm tra Email
    if (empty($_POST['Email'])) {
        $errors['Email'] = 'Email không để trống!';
    } else {
        // Kiểm tra ký tự @
        if (strpos($_POST['Email'], '@') === false) {
            $errors['Email'] = 'Email phải chứa ký tự @!';
        } else {
            $Email = mysqli_real_escape_string($dbc, trim($_POST['Email']));
            $sql = "SELECT * FROM giangvien WHERE Email = '$Email'";
            $result = mysqli_query($dbc, $sql);

            if (mysqli_num_rows($result) > 0) {
                $errors['Email'] = 'Email đã tồn tại';
            }
        }
    }

    // Kiểm tra Số điện thoại
    if (empty($_POST['SDT'])) {
        $errors['SDT'] = 'Số điện thoại không để trống';
    } else {
        $SDT = trim($_POST['SDT']);

        // Kiểm tra xem có phải là số không
        if (!is_numeric($SDT)) {
            $errors['SDT'] = 'Số điện thoại phải là số';
        } else {
            // Kiểm tra xem có bắt đầu bằng 0 không
            if (substr($SDT, 0, 1) != '0') {
                $errors['SDT'] = 'Số điện thoại phải bắt đầu bằng 0';
            } else {
                // Kiểm tra độ dài phải là 11 số
                if (strlen($SDT) < 11) {
                    $errors['SDT'] = 'Số điện thoại phải có 11 số';
                }
            }
        }

        // Nếu không có lỗi, mới xử lý dữ liệu
        if (!isset($errors['SDT'])) {
            $SDT = mysqli_real_escape_string($dbc, $SDT);
        }
    }

    //Kiểm tra thời gian
    if (empty($_POST['thoigian'])) {
        $errors['thoigian'] = 'Tên giảng viên không để trống';
    } else {
        $thoigian = mysqli_real_escape_string($dbc, trim($_POST['thoigian']));
    }

    //Kiểm tra Địa điểm
    if (empty($_POST['diadiem'])) {
        $errors['diadiem'] = 'Tên giảng viên không để trống';
    } else {
        $diadiem = mysqli_real_escape_string($dbc, trim($_POST['diadiem']));
    }

    // Kiểm tra Học vị
    if (isset($_POST['HocVi'])) {
        if ($_POST['HocVi'] === 'thac') {
            $hocvi = 'Thạc sĩ';
        } else {
            $hocvi = 'Tiến sĩ';
        }
    }

    //Chức danh
    if (isset($_POST['ChucDanh'])) {
        if ($_POST['ChucDanh'] === 'trg') {
            $chucdanh = 'Trợ giảng';
        } elseif ($_POST['ChucDanh'] === 'gv') {
            $chucdanh = 'Giảng viên';
        } elseif ($_POST['ChucDanh'] === 'gvc') {
            $chucdanh = 'Giảng viên chính';
        } elseif ($_POST['ChucDanh'] === 'phogs') {
            $chucdanh = 'Phó giáo sư';
        } else {
            $chucdanh = 'Giáo sư';
        }
    }

    //Khoa
    if (isset($_POST['Khoa'])) {
        $Khoa = mysqli_real_escape_string($dbc, trim($_POST['Khoa']));
    }

    //Kiểm tra trạng thái
    if (isset($_POST['TrangThai'])) {
        if ($_POST['TrangThai'] == 'day') {
            $trangthai = 1;
        } elseif ($_POST['TrangThai'] == 'nghi') {
            $trangthai = 2;
        } else {
            $trangthai = 3;
        }
    }

    if (empty($errors)) {
        // Make the query:
        $queryGiangVien = "INSERT INTO giangvien (MaGiangVien, HoGiangVien,TenGiangVien,NgaySinh,GioiTinh,Email,SoDienThoai,HocVi,ChucDanh,MaKhoa,TrangThai) 
        VALUES ('$MaGiangVien', '$HoGiangVien','$TenGiangVien','$NgaySinh','$GioiTinh','$Email','$SDT','$hocvi','$chucdanh','$Khoa','$trangthai')";
        $queryLichTiep = "INSERT INTO lichtiepsinhvien (MaGiangVien, ThoiGian,DiaDiem) VALUES ('$MaGiangVien', '$thoigian','$diadiem')";
        $r1 = @mysqli_query($dbc, $queryGiangVien); // Run the query.
        $r2 = @mysqli_query($dbc, $queryLichTiep); // Run the query.
        session_start(); // Bắt đầu phiên
        if ($r1 && $r2) { // If it ran OK.
            // Print a message:
            $_SESSION['success_message'] = 'Đã thêm giảng viên thành công!';
            // Chuyển hướng đến index
            header("Location: index.php");
            ob_end_flush();
            exit();
        } else { // If it did not run OK.
            echo '<h1>System Error</h1>
            <p class="error">You could not be registered due to a system error. We apologize for any inconvenience.</p>';
            echo '<p>' . mysqli_error($dbc) . '<br /><br />Query: ' . $q . '</p>';
        }
        mysqli_close($dbc); // Close the database connection.
        exit();
    }
}
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
                            <strong class="text-danger">THÊM MỚI GIẢNG VIÊN</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="./index.php" class="btn-sm btn-info"> <i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
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
                                        <input class="form-control" type="text" name="MaGiangVien" value="">
                                        <?php if (isset($errors['MaGiangVien'])): ?>
                                            <small class=" text-danger"><?php echo $errors['MaGiangVien']; ?></small>
                                    <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Họ Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="HoGiangVien" value="">
                                        <?php if (isset($errors['HoGiangVien'])): ?>
                                            <small class="text-danger"><?php echo $errors['HoGiangVien']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tên Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="TenGiangVien" value="">
                                        <?php if (isset($errors['TenGiangVien'])): ?>
                                            <small class="text-danger"><?php echo $errors['TenGiangVien']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Email <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="Email" value="">
                                        <?php if (isset($errors['Email'])): ?>
                                            <small class="text-danger"><?php echo $errors['Email']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Thời gian gặp sinh viên <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="thoigian" value="">
                                        <?php if (isset($errors['thoigian'])): ?>
                                            <small class="text-danger"><?php echo $errors['thoigian']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Địa điểm gặp sinh viên <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="diadiem" value="">
                                        <?php if (isset($errors['diadiem'])): ?>
                                            <small class="text-danger"><?php echo $errors['diadiem']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>


                                <div class="form-group">
                                    <label>Trạng Thái <span class="text-danger">(*)</span></label>
                                    <div class="col-md-3">
                                        <select class="form-control" name="TrangThai">
                                            <option value="day" selected>Đang dạy</option>
                                            <option value="nghi">Về hưu</option>
                                            <option value="chuyen">Chuyển công tác</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!-- Chọn  -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ngày sinh <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="date" class="form-control" name="NgaySinh" required style="width: auto;">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Khoa <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="Khoa" style="width: auto;">
                                            <?php
                                            require_once BASE_PATH . './Database/connect-database.php';
                                            $sql = "Select * FROM khoa where TrangThai=1";
                                            $result = mysqli_query($dbc, $sql);
                                            if (mysqli_num_rows($result) <> 0) {
                                                while ($row = mysqli_fetch_array($result)) {
                                                    echo "	<option value='$row[MaKhoa]'>$row[TenKhoa]</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Giới tính <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="Gioitinh">
                                            <option value="nam" selected>Nam</option>
                                            <option value="nu">Nữ</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Số điện thoại <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-7">
                                        <input class="form-control" type="text" name="SDT" value="">
                                        <?php if (isset($errors['SDT'])): ?>
                                            <small class="text-danger"><?php echo $errors['SDT']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Học vị <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="HocVi">
                                            <option value="thac" selected>Thạc sĩ</option>
                                            <option value="tien">Tiến sĩ</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Chức danh <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="ChucDanh">
                                            <option value="trg" selected>Trợ Giảng</option>
                                            <option value="gv">Giảng viên</option>
                                            <option value="gvc">Giảng viên chính</option>
                                            <option value="phogs">Phó giáo sư</option>
                                            <option value="gs">Giáo sư</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-offset-2 col-md-12   ">
                                    <button class="btn-sm btn-success" type="submit" name="create"> Lưu [Thêm] <i class="fa fa-save"></i> </button>
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