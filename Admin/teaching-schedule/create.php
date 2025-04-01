<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array(); // Initialize an error array.

    // Kiểm tra Mã học Phần
    if (empty($_POST['MaLichGiang'])) {
        $errors['MaLichGiang'] = 'Mã lịch giảng không để trống!';
    } else {
        $MaLichGiang = mysqli_real_escape_string($dbc, trim($_POST['MaLichGiang']));
        $sql = "SELECT * FROM lichhocphan WHERE MaLichGiang = '$MaLichGiang'";
        $result = mysqli_query($dbc, $sql);

        if (mysqli_num_rows($result) > 0) {
            $errors['MaLichGiang'] = 'Mã học phần bị trùng';
        }
    }

    if (isset($_POST['TenHocPhan'])) {
        $Mahocphan = mysqli_real_escape_string($dbc, trim($_POST['TenHocPhan']));
    }

    if (isset($_POST['DateStart'])) {
        $DateStart = mysqli_real_escape_string($dbc, trim($_POST['DateStart']));
    }

    if (isset($_POST['DateEnd'])) {
        $DateEnd = mysqli_real_escape_string($dbc, trim($_POST['DateEnd']));
    }

    if (empty($_POST['LichDay'])) {
        $errors['LichDay'] = 'Vui lòng chọn lịch dạy';
    } else {
        if ($_POST['LichDay'] === '2') {
            $LichDay = 'Thứ Hai';
        } elseif ($_POST['LichDay'] === '3') {
            $LichDay = 'Thứ Ba';
        } elseif ($_POST['LichDay'] === '4') {
            $LichDay = 'Thứ Tư';
        } elseif ($_POST['LichDay'] === '5') {
            $LichDay = 'Thứ Năm';
        } elseif ($_POST['LichDay'] === '6') {
            $LichDay = 'Thứ Sáu';
        } elseif ($_POST['LichDay'] === '7') {
            $LichDay = 'Thứ Bảy';
        } elseif ($_POST['LichDay'] === '1') {
            $LichDay = 'Chủ Nhật';
        }
    }

    // Kiểm tra Mã họ
    if (empty($_POST['MaLichGiang'])) {
        $errors['MaLichGiang'] = 'Mã lịch giảng không để trống!';
    } else {
        $MaLichGiang = mysqli_real_escape_string($dbc, trim($_POST['MaLichGiang']));
        $sql = "SELECT * FROM lichhocphan WHERE MaLichGiang = '$MaLichGiang'";
        $result = mysqli_query($dbc, $sql);

        if (mysqli_num_rows($result) > 0) {
            $errors['MaLichGiang'] = 'Mã học phần bị trùng';
        }
    }

    if (isset($_POST['TrangThai'])) {
        if ($_POST['TrangThai'] === 'xuat') {
            $trangthai = 1;
        } else {
            $trangthai = 2;
        }
    }

    if (empty($errors)) {
        // Make the query:
        $q = "INSERT INTO lichhocphan (MaLichGiang, MaHocPhan,LichDay,ThoiGianBatDau,ThoiGianKetThuc,TrangThai) VALUES ('$MaLichGiang', '$Mahocphan','$LichDay','$DateStart','$DateEnd','$trangthai')";
        $r = @mysqli_query($dbc, $q); // Run the query.
        session_start(); // Bắt đầu phiên
        if ($r) { // If it ran OK.
            // Print a message:
            $_SESSION['success_message'] = 'Đã thêm học phần thành công!';
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
                            <strong class="text-blue">THÊM LỊCH GIẢNG DẠY</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="./index.php" class="btn-sm btn-info"> <i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                        </div>
                    </div>
                </div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Mã lịch giảng<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="MaLichGiang" value="">
                                        <?php if (isset($errors['MaLichGiang'])): ?>
                                            <small class="text-danger"><?php echo $errors['MaLichGiang']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Tên học phần <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="TenHocPhan" style="width: auto;">
                                            <?php
                                            $sql = "Select * FROM hocphan where TrangThai=1";
                                            $result = mysqli_query($dbc, $sql);
                                            if (mysqli_num_rows($result) <> 0) {
                                                while ($row = mysqli_fetch_array($result)) {
                                                    echo "	<option value='$row[MaHocPhan]'>$row[TenHocPhan]</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Lịch dạy <span class="text-danger"> (*)</span></label>
                                    <button type="button" id="addScheduleButton">Thêm lịch dạy</button>
                                    <div id="scheduleContainer"></div>
                                </div>

                                <div class="form-group">
                                    <label>Địa điểm<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="DiaDiem" value="">
                                        <?php if (isset($errors['DiaDiem'])): ?>
                                            <small class="text-danger"><?php echo $errors['DiaDiem']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>


                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Thời gian bắt đầu <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="date" class="form-control" name="DateStart" required style="width: auto;">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Thời gian kết thúc <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="date" class="form-control" name="DateEnd" required style="width: auto;">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Trạng Thái <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="TrangThai">
                                            <option value="xuat" selected>Xuất bản</option>
                                            <option value="an">Ẩn</option>
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

    <!-- Chức năng thêm trường nhập để thêm lịch giảng dạy -->
    <script>
        document.getElementById('addScheduleButton').addEventListener('click', function() {
            const scheduleContainer = document.getElementById('scheduleContainer');
            const newSchedule = document.createElement('div');
            newSchedule.classList.add('row');

            newSchedule.innerHTML = `
            <div class="col-md-2">
                <select class="form-control" name="Lichgiang[]">
                    <option value="">Chọn ngày</option>
                    <option value="2">Thứ Hai</option>
                    <option value="3">Thứ Ba</option>
                    <option value="4">Thứ Tư</option>
                    <option value="5">Thứ Năm</option>
                    <option value="6">Thứ Sáu</option>
                    <option value="7">Thứ Bảy</option>
                    <option value="1">Chủ Nhật</option>
                </select>
            </div>
            <div class="col-md-2">
                <input class="form-control" type="time" name="thoigian_batdau[]">
            </div>
            <p style="margin-top: 10px;">
                <i class="fa fa-arrow-right" aria-hidden="true"></i>
            </p>
            <div class="col-md-2">
                <input class="form-control" type="time" name="thoigian_ketthuc[]">
            </div>
        `;

            scheduleContainer.appendChild(newSchedule);
        });
    </script>



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