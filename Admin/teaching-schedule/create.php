<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array(); // Initialize an error array.

    //Mã lịch học phần
    if (empty($_POST['MaLichHocPhan'])) {
        $errors['MaLichHocPhan'] = 'Mã lịch học phần không để trống!';
    } else {
        $MaLichHocPhan = mysqli_real_escape_string($dbc, trim($_POST['MaLichHocPhan']));
        $sql = "SELECT * FROM lichhocphan WHERE MaLichHocPhan = '$MaLichHocPhan'";
        $result = mysqli_query($dbc, $sql);

        if (mysqli_num_rows($result) > 0) {
            $errors['MaLichHocPhan'] = 'Mã học phần bị trùng';
        }
    }

    //Lớp học phần
    if (empty($_POST['lophocphan'])) {
        $errors['lophocphan'] = 'Chưa nhập lớp học phần';
    } else {
        $lophocphan = mysqli_real_escape_string($dbc, trim($_POST['lophocphan']));
    }

    //Tên học phần
    if (isset($_POST['TenHocPhan'])) {
        $Mahocphan = mysqli_real_escape_string($dbc, trim($_POST['TenHocPhan']));
    }

    //Thời gian bắt đầu
    if (isset($_POST['DateStart'])) {
        $DateStart = mysqli_real_escape_string($dbc, trim($_POST['DateStart']));
    }

    //Thời gian kết thúc
    if (isset($_POST['DateEnd'])) {
        $DateEnd = mysqli_real_escape_string($dbc, trim($_POST['DateEnd']));
    }

    //Lịch giảng dạy
    $lichgiang = isset($_POST['Lichgiang']) ? $_POST['Lichgiang'] : [];
    $thoigian_batdau = isset($_POST['thoigian_batdau']) ? $_POST['thoigian_batdau'] : [];
    $thoigian_ketthuc = isset($_POST['thoigian_ketthuc']) ? $_POST['thoigian_ketthuc'] : [];

    if (empty($lichgiang) || count($lichgiang) === 0) {
        $errors['lichgiang'] = 'Vui lòng thêm ít nhất một lịch giảng dạy';
    } else {
        foreach ($lichgiang as $index => $ngayday) {
            if (empty($thoigian_batdau[$index]) || empty($thoigian_ketthuc[$index])) {
                $errors['thoigian'] = 'Vui lòng nhập thời gian bắt đầu hoặc kết thúc cho mỗi lịch giảng dạy';
                break;
            }
        }
    }

    // Địa điểm học
    if (empty($_POST['DiaDiem'])) {
        $errors['DiaDiem'] = 'Địa điểm học không để trống';
    } else {
        $DiaDiem = mysqli_real_escape_string($dbc, trim($_POST['DiaDiem']));
    }

    //Trạng thái
    if (isset($_POST['TrangThai'])) {
        if ($_POST['TrangThai'] === 'xuat') {
            $trangthai = 1;
        } else {
            $trangthai = 2;
        }
    }

    if (empty($errors)) {
        // Make the query:
        $qLichHocPhan = "INSERT INTO lichhocphan (MaLichHocPhan, MaHocPhan,LopHocPhan,ThoiGianBatDau,ThoiGianKetThuc,DiaDiem,TrangThai) VALUES ('$MaLichHocPhan', '$Mahocphan','$lophocphan','$DateStart','$DateEnd','$DiaDiem','$trangthai')";
        $r = @mysqli_query($dbc, $qLichHocPhan); // Run the query.

        // Lưu lịch giảng dạy vào bảng lichgiang
        foreach ($lichgiang as $index => $ngayday) {
            $thoigian_batdau = mysqli_real_escape_string($dbc, $thoigian_batdau[$index]);
            $thoigian_ketthuc = mysqli_real_escape_string($dbc, $thoigian_ketthuc[$index]);


            $qLichGiang = "INSERT INTO lichgiangday (MaLichHocPhan, LichGiang, GioBatDau, GioKetThuc) 
                           VALUES ('$MaLichHocPhan', '$ngayday', '$thoigian_batdau', '$thoigian_ketthuc')";
            @mysqli_query($dbc, $qLichGiang);
        }
        session_start(); // Bắt đầu phiên
        if ($r) { // If it ran OK.
            // Print a message:
            $_SESSION['success_message'] = 'Đã thêm lịch học phần thành công!';
            // Chuyển hướng đến index
            header("Location: index.php");
            ob_end_flush();
            exit();
        } else { // If it did not run OK.
            echo '<h1>System Error</h1>
            <p class="error">You could not be registered due to a system error. We apologize for any inconvenience.</p>';
            echo '<p>' . mysqli_error($dbc) . '<br /><br />Query: ' . $qLichHocPhan . '</p>';
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
                                    <label>Mã lịch học phần<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="MaLichHocPhan"
                                            value="<?php echo isset($_POST['MaLichHocPhan']) ? htmlspecialchars($_POST['MaLichHocPhan']) : ''; ?>">
                                        <?php if (isset($errors['MaLichHocPhan'])): ?>
                                            <small class="text-danger"><?php echo $errors['MaLichHocPhan']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Lớp học phần<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="lophocphan"
                                            value="<?php echo isset($_POST['lophocphan']) ? htmlspecialchars($_POST['lophocphan']) : ''; ?>">
                                        <?php if (isset($errors['lophocphan'])): ?>
                                            <small class="text-danger"><?php echo $errors['lophocphan']; ?></small>
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

                                    <?php if (isset($errors['lichgiang']) && isset($errors['thoigian'])): ?>
                                        <small class="text-danger">Vui lòng thêm ít nhất 1 lịch giảng dạy</small>
                                    <?php else: ?>
                                        <?php if (isset($errors['lichgiang'])): ?>
                                            <small class="text-danger"><?php echo $errors['lichgiang']; ?></small>
                                        <?php endif; ?>
                                        <?php if (isset($errors['thoigian'])): ?>
                                            <small class="text-danger"><?php echo $errors['thoigian']; ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label>Địa điểm học<span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="DiaDiem"
                                            value="<?php echo isset($_POST['DiaDiem']) ? htmlspecialchars($_POST['DiaDiem']) : ''; ?>">
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
                                        <input type="date" class="form-control" name="DateStart" required style="width: auto;"
                                            value="<?php echo isset($_POST['DateStart']) ? htmlspecialchars($_POST['DateStart']) : ''; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Thời gian kết thúc <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="date" class="form-control" name="DateEnd" required style="width: auto;"
                                            value="<?php echo isset($_POST['DateEnd']) ? htmlspecialchars($_POST['DateEnd']) : ''; ?>">
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
        const scheduleContainer = document.getElementById('scheduleContainer');

        document.getElementById('addScheduleButton').addEventListener('click', function() {
            addScheduleRow();
        });

        function addScheduleRow(day = '', startTime = '', endTime = '') {
            const newSchedule = document.createElement('div');
            newSchedule.classList.add('row');

            newSchedule.innerHTML = `
            <div class="col-md-2">
                <select class="form-control" name="Lichgiang[]">
                    <option value="">Chọn ngày</option>
                    <option value="2" ${day === '2' ? 'selected' : ''}>Thứ Hai</option>
                    <option value="3" ${day === '3' ? 'selected' : ''}>Thứ Ba</option>
                    <option value="4" ${day === '4' ? 'selected' : ''}>Thứ Tư</option>
                    <option value="5" ${day === '5' ? 'selected' : ''}>Thứ Năm</option>
                    <option value="6" ${day === '6' ? 'selected' : ''}>Thứ Sáu</option>
                    <option value="7" ${day === '7' ? 'selected' : ''}>Thứ Bảy</option>
                    <option value="1" ${day === '1' ? 'selected' : ''}>Chủ Nhật</option>
                </select>
            </div>
            <div class="col-md-2">
                <input class="form-control" type="time" name="thoigian_batdau[]" value="${startTime}">
            </div>
            <p style="margin-top: 10px;">
                <i class="fa fa-arrow-right" aria-hidden="true"></i>
            </p>
            <div class="col-md-2">
                <input class="form-control" type="time" name="thoigian_ketthuc[]" value="${endTime}">
            </div>
            <div class="col-md-offset-2 col-md-2">
                <button type="button" class="btn btn-danger remove-button"><i class="fa-solid fa-trash"></i></button>
            </div>
        `;

            // Thêm mục lịch mới vào container
            scheduleContainer.appendChild(newSchedule);

            // Thêm sự kiện cho nút xóa
            newSchedule.querySelector('.remove-button').addEventListener('click', function() {
                scheduleContainer.removeChild(newSchedule);
            });
        }

        // Khi trang được tải, kiểm tra và thêm lịch giảng dạy nếu có dữ liệu
        <?php if (isset($_POST['Lichgiang'])): ?>
            <?php foreach ($_POST['Lichgiang'] as $index => $day): ?>
                addScheduleRow("<?php echo $day; ?>", "<?php echo isset($_POST['thoigian_batdau'][$index]) ? htmlspecialchars($_POST['thoigian_batdau'][$index]) : ''; ?>", "<?php echo isset($_POST['thoigian_ketthuc'][$index]) ? htmlspecialchars($_POST['thoigian_ketthuc'][$index]) : ''; ?>");
            <?php endforeach; ?>
        <?php endif; ?>
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