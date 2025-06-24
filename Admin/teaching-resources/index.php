<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

// Kiểm tra quyền và lấy MaKhoa của Admin
$user_id = $_SESSION['user_id'] ?? '';
$quyen = $_SESSION['quyen'] ?? 'Không xác định';
$ma_khoa = null;

if ($quyen === 'Admin') {
    // Lấy MaKhoa của Admin từ bảng giangvien
    $query_khoa = "SELECT MaKhoa FROM giangvien WHERE MaGiangVien = ?";
    $stmt_khoa = $dbc->prepare($query_khoa);
    $stmt_khoa->bind_param("s", $user_id);
    $stmt_khoa->execute();
    $result_khoa = $stmt_khoa->get_result();

    if ($row_khoa = $result_khoa->fetch_assoc()) {
        $ma_khoa = $row_khoa['MaKhoa'];
    }
    $stmt_khoa->close();
}

// Truy vấn lấy danh sách tài liệu giảng dạy
$query = "
    SELECT 
        tl.MaTaiLieu, 
        tl.TenTaiLieu, 
        tl.LoaiTaiLieu, 
        tl.LuuTru, 
        hp.TenHocPhan, 
        CONCAT(gv.HoGiangVien, ' ', gv.TenGiangVien) AS TenGiangVien
    FROM 
        tailieugiangday tl
        INNER JOIN hocphan hp ON tl.MaHocPhan = hp.MaHocPhan
        INNER JOIN giangvien gv ON tl.MaGiangVien = gv.MaGiangVien
";
if ($quyen === 'Admin' && $ma_khoa) {
    $query .= " WHERE hp.MaKhoa = ?";
    $stmt = $dbc->prepare($query);
    $stmt->bind_param("s", $ma_khoa);
} else {
    $stmt = $dbc->prepare($query);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách tài liệu giảng dạy</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body>
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content my-2">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <?php
                            if (isset($_SESSION['success_message'])) {
                                echo '<div id="success-message" class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                                unset($_SESSION['success_message']);
                            }
                            if (isset($_SESSION['error_message'])) {
                                echo '<div id="error-message" class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                                unset($_SESSION['error_message']);
                            }
                            ?>
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong class="text-blue">DANH SÁCH TÀI LIỆU GIẢNG DẠY</strong>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 20%;">Tên tài liệu</th>
                                            <th style="width: 15%;">Loại tài liệu</th>
                                            <th style="width: 20%;">Tên học phần</th>
                                            <th style="width: 20%;">Giảng viên</th>
                                            <th style="width: 25%;">Lưu trữ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['TenTaiLieu']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['LoaiTaiLieu']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['TenHocPhan']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['TenGiangVien']) . "</td>";
                                                echo "<td>";

                                                // Xử lý hiển thị và tải file
                                                $file_path = BASE_PATH . $row['LuuTru'];
                                                $url = BASE_URL . $row['LuuTru'];
                                                $file_ext = strtolower(pathinfo($row['LuuTru'], PATHINFO_EXTENSION));
                                                $original_file_name = basename($row['LuuTru']);

                                                if (!empty($row['LuuTru']) && file_exists($file_path)) {
                                                    if ($file_ext === 'pdf') {
                                                        echo '<a href="#" class="view-file" data-type="pdf" data-url="' . htmlspecialchars($url) . '">Xem PDF</a> | ';
                                                        echo '<a href="' . htmlspecialchars($url) . '" download="' . htmlspecialchars($original_file_name) . '">Tải xuống</a>';
                                                    } elseif (in_array($file_ext, ['mp4', 'avi', 'mov'])) {
                                                        echo '<a href="#" class="play-video" data-url="' . htmlspecialchars($url) . '" data-type="video">Phát Video</a> | ';
                                                        echo '<a href="' . htmlspecialchars($url) . '" download="' . htmlspecialchars($original_file_name) . '">Tải xuống</a>';
                                                    } else {
                                                        echo '<a href="' . htmlspecialchars($url) . '" download="' . htmlspecialchars($original_file_name) . '">Tải xuống</a>';
                                                    }
                                                } else {
                                                    echo '<span class="text-danger">Tệp không tồn tại</span>';
                                                }

                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>

    <!-- jQuery -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?php echo BASE_URL ?>/Public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables & Plugins -->
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
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Page specific script -->
    <script>
        $(function() {
            $("#example1").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

            // Xử lý xem file (PDF)
            $(document).on('click', '.view-file', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                Swal.fire({
                    title: 'Xem PDF',
                    html: `<iframe src="${url}" width="100%" height="500px"></iframe>`,
                    width: '80%',
                    showCloseButton: true,
                    showConfirmButton: false
                });
            });

            // Xử lý phát video (MP4, AVI, MOV)
            $(document).on('click', '.play-video', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                Swal.fire({
                    title: 'Phát Video',
                    html: `<video width="100%" controls><source src="${url}" type="video/mp4">Trình duyệt của bạn không hỗ trợ video.</video>`,
                    width: '80%',
                    showCloseButton: true,
                    showConfirmButton: false
                });
            });
        });
    </script>
</body>

</html>

<?php
require_once '../Layout/footer.php';
mysqli_close($dbc);
?>