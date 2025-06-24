<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

// Kiểm tra quyền và lấy MaKhoa của Admin
$user_id = $_SESSION['user_id'];
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

// Xử lý xóa
if (isset($_GET['id'])) {
    $maCongViecHanhChinh = mysqli_real_escape_string($dbc, $_GET['id']);
    mysqli_begin_transaction($dbc);
    try {
        $deleteDetailSql = "DELETE FROM thongtincongviechanhchinh WHERE MaCongViecHanhChinh = '$maCongViecHanhChinh'";
        $deleteDetailResult = mysqli_query($dbc, $deleteDetailSql);
        if (!$deleteDetailResult) {
            throw new Exception("Lỗi khi xóa thông tin công việc: " . mysqli_error($dbc));
        }

        $deleteMainSql = "DELETE FROM congviechanhchinh WHERE MaCongViecHanhChinh = '$maCongViecHanhChinh'";
        $deleteMainResult = mysqli_query($dbc, $deleteMainSql);
        if (!$deleteMainResult) {
            throw new Exception("Lỗi khi xóa công việc hành chính: " . mysqli_error($dbc));
        }

        mysqli_commit($dbc);

        $checkMainQuery = "SELECT COUNT(*) as total FROM congviechanhchinh";
        $checkMainResult = mysqli_query($dbc, $checkMainQuery);
        if (mysqli_fetch_assoc($checkMainResult)['total'] == 0) {
            mysqli_query($dbc, "ALTER TABLE congviechanhchinh AUTO_INCREMENT = 1");
        }

        $checkDetailQuery = "SELECT COUNT(*) as total FROM thongtincongviechanhchinh";
        $checkDetailResult = mysqli_query($dbc, $checkDetailQuery);
        if (mysqli_fetch_assoc($checkDetailResult)['total'] == 0) {
            mysqli_query($dbc, "ALTER TABLE thongtincongviechanhchinh AUTO_INCREMENT = 1");
        }

        $_SESSION['success_message'] = "Xóa công việc thành công!";
        header("Location: index.php");
        ob_end_flush();
        exit();
    } catch (Exception $e) {
        mysqli_rollback($dbc);
        $_SESSION['error_message'] = "Lỗi: " . $e->getMessage();
        header("Location: index.php");
        ob_end_flush();
        exit();
    }
}

// Truy vấn dữ liệu
$sql = "SELECT 
            cvc.MaCongViecHanhChinh,
            cvc.TenCongViec,
            gv.HoGiangVien,
            gv.TenGiangVien,
            ttcvc.LoaiCongViec,
            ttcvc.NgayThucHien,
            ttcvc.GioBatDau,
            ttcvc.GioKetThuc,
            ttcvc.DiaDiem,
            ttcvc.TrangThai
        FROM congviechanhchinh cvc
        LEFT JOIN thongtincongviechanhchinh ttcvc ON cvc.MaCongViecHanhChinh = ttcvc.MaCongViecHanhChinh
        LEFT JOIN giangvien gv ON ttcvc.MaGiangVien = gv.MaGiangVien";
if ($quyen === 'Admin' && $ma_khoa) {
    $sql .= " WHERE gv.MaKhoa = '" . mysqli_real_escape_string($dbc, $ma_khoa) . "' OR gv.MaKhoa IS NULL";
}
$result = mysqli_query($dbc, $sql);

// Nhóm dữ liệu
$groupedData = [];
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $maCongViec = $row['MaCongViecHanhChinh'];
        if (!isset($groupedData[$maCongViec])) {
            $groupedData[$maCongViec] = [
                'MaCongViecHanhChinh' => $row['MaCongViecHanhChinh'],
                'TenCongViec' => $row['TenCongViec'],
                'GiangVien' => [],
                'LoaiCongViec' => $row['LoaiCongViec'],
                'NgayThucHien' => $row['NgayThucHien'],
                'GioBatDau' => $row['GioBatDau'],
                'GioKetThuc' => $row['GioKetThuc'],
                'DiaDiem' => $row['DiaDiem'],
                'TrangThai' => $row['TrangThai']
            ];
        }
        if (!empty($row['HoGiangVien']) && !empty($row['TenGiangVien'])) {
            $groupedData[$maCongViec]['GiangVien'][] = $row['HoGiangVien'] . ' ' . $row['TenGiangVien'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách công việc</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
    <style>
        .alert-success,
        .alert-danger {
            position: relative;
            padding-right: 40px;
        }

        .close-btn {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #155724;
        }

        .alert-danger .close-btn {
            color: #721c24;
        }
    </style>
</head>

<body>
    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid">
                <?php
                if (isset($_SESSION['success_message'])) {
                    echo '<div id="success-message" class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '<button class="close-btn">×</button></div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div id="error-message" class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '<button class="close-btn">×</button></div>';
                    unset($_SESSION['error_message']);
                }
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong class="text-blue">DANH SÁCH CÔNG VIỆC</strong>
                                    </div>
                                    <!-- <div class="col-md-6 text-right">
                                        <a href="../lecturer/trash.php" class="btn-sm btn-danger"> <i class="fa fa-trash"></i> Thùng rác</a>
                                    </div> -->
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tên Công Việc</th>
                                            <th>Giảng Viên</th>
                                            <th>Loại Công Việc</th>
                                            <th>Ngày Thực Hiện</th>
                                            <th>Thời Gian</th>
                                            <th>Địa Điểm</th>
                                            <th>Trạng Thái</th>
                                            <th>Hành Động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (!empty($groupedData)) {
                                            foreach ($groupedData as $data) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($data['TenCongViec']) . "</td>";
                                                echo "<td>";
                                                if (!empty($data['GiangVien'])) {
                                                    foreach ($data['GiangVien'] as $index => $giangVien) {
                                                        echo htmlspecialchars($giangVien);
                                                        if ($index < count($data['GiangVien']) - 1) {
                                                            echo "<br>";
                                                        }
                                                    }
                                                } else {
                                                    echo "Không có giảng viên";
                                                }
                                                echo "</td>";
                                                echo "<td>" . htmlspecialchars($data['LoaiCongViec']) . "</td>";
                                                echo "<td>" . htmlspecialchars($data['NgayThucHien']) . "</td>";
                                                echo "<td>";
                                                $thoiGian = '';
                                                if (!empty($data['GioBatDau']) && !empty($data['GioKetThuc'])) {
                                                    $thoiGian = htmlspecialchars('Từ ' . $data['GioBatDau'] . ' đến ' . $data['GioKetThuc']);
                                                } elseif (!empty($data['GioBatDau'])) {
                                                    $thoiGian = htmlspecialchars($data['GioBatDau']) . ' - Không xác định';
                                                }
                                                echo $thoiGian;
                                                echo "</td>";
                                                echo "<td>" . htmlspecialchars($data['DiaDiem']) . "</td>";
                                                echo "<td>";
                                                if ($data['TrangThai'] == 0) {
                                                    echo "Chờ xác nhận";
                                                } elseif ($data['TrangThai'] == 1) {
                                                    echo "Xác nhận";
                                                } else {
                                                    echo "Bận";
                                                }
                                                echo "</td>";
                                                echo "<td>";
                                                echo "<a href='#' class='btn-sm btn-danger delete-btn' data-id='{$data['MaCongViecHanhChinh']}'> <i class='fa fa-trash'></i> Xóa </a>";
                                                echo "<a href='edit.php?id={$data['MaCongViecHanhChinh']}' class='btn-sm btn-info'> <i class='fa fa-edit'></i> Chỉnh sửa </a>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='8' class='text-center'>Không có dữ liệu</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

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
        $(document).ready(function() {
            $("#example1").DataTable({
                responsive: true,
                lengthChange: false,
                autoWidth: false,
                buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if (confirm('Bạn có chắc chắn muốn xóa công việc này không?')) {
                    window.location.href = 'index.php?id=' + id;
                }
            });

            var interval = setInterval(function() {
                var message = $('#success-message, #error-message');
                if (message.length) {
                    message.show();
                    setTimeout(function() {
                        message.fadeOut('slow');
                    }, 3000);
                    clearInterval(interval);
                }
            }, 100);

            $(document).on('click', '.close-btn', function() {
                $(this).closest('.alert').fadeOut('slow');
                clearInterval(interval);
            });
        });
    </script>
</body>

</html>

<?php
ob_end_flush();
require_once '../Layout/footer.php';
mysqli_close($dbc);
?>