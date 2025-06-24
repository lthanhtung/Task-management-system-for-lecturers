<?php
ob_start();
require_once '../Layout/header.php';

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

// Xây dựng câu query dựa trên quyền
$query = "
    SELECT giangvien.*, khoa.TenKhoa
    FROM giangvien
    JOIN khoa ON giangvien.MaKhoa = khoa.MaKhoa 
    WHERE giangvien.TrangThai = 0";

if ($quyen === 'Admin' && $ma_khoa !== null) {
    // Nếu là Admin, thêm điều kiện lọc theo MaKhoa
    $query .= " AND giangvien.MaKhoa = ?";
    $stmt = $dbc->prepare($query);
    $stmt->bind_param("s", $ma_khoa);
} else {
    // Nếu không phải Admin, lấy tất cả giảng viên
    $stmt = $dbc->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();

// Xử lý yêu cầu xóa
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Bắt đầu giao dịch
    $dbc->begin_transaction();

    try {
        // Kiểm tra sự tồn tại của MaGiangVien trong các bảng liên quan
        $checkQueries = [
            "SELECT COUNT(*) as count FROM thongtincongviechanhchinh WHERE MaGiangVien = ?",
            "SELECT COUNT(*) as count FROM tailieugiangday WHERE MaGiangVien = ?",
            "SELECT COUNT(*) as count FROM huongdansinhvien WHERE MaGiangVien = ?",
            "SELECT COUNT(*) as count FROM lichgiangday WHERE MaGiangVien = ?"
        ];

        $hasRelatedRecords = false;
        $relatedTables = [];

        foreach ($checkQueries as $index => $checkQuery) {
            $stmtCheck = $dbc->prepare($checkQuery);
            $stmtCheck->bind_param("s", $id);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            $rowCheck = $resultCheck->fetch_assoc();

            if ($rowCheck['count'] > 0) {
                $hasRelatedRecords = true;
                // Gán tên bảng để thông báo lỗi
                $tableNames = [
                    'thongtincongviechanhchinh' => 'Thông tin công việc hành chính',
                    'tailieugiangday' => 'Tài liệu giảng dạy',
                    'huongdansinhvien' => 'Hướng dẫn sinh viên',
                    'lichgiangday' => 'Lịch giảng dạy'
                ];
                $relatedTables[] = $tableNames[array_keys($tableNames)[$index]];
            }
            $stmtCheck->close();
        }

        // Nếu có bản ghi liên quan, ném ra ngoại lệ
        if ($hasRelatedRecords) {
            throw new Exception("Không thể xóa giảng viên vì giảng viên này có dữ liệu liên quan trong các bảng: " . implode(", ", $relatedTables) . ".");
        }

        // Truy vấn để lấy đường dẫn ảnh trước khi xóa
        $queryImg = $dbc->prepare("SELECT AnhDaiDien FROM giangvien WHERE MaGiangVien = ?");
        $queryImg->bind_param("s", $id);
        $queryImg->execute();
        $resultImg = $queryImg->get_result();
        $rowImg = $resultImg->fetch_assoc();
        $anhDaiDienPath = BASE_PATH . str_replace(BASE_URL, '', $rowImg['AnhDaiDien']);

        // Xóa file ảnh nếu tồn tại
        if (file_exists($anhDaiDienPath)) {
            if (!unlink($anhDaiDienPath)) {
                throw new Exception("Không thể xóa file ảnh đại diện.");
            }
        }

        // Xóa thông tin từ bảng giangvien_public_settings
        $stmt0 = $dbc->prepare("DELETE FROM giangvien_public_settings WHERE MaGiangVien = ?");
        $stmt0->bind_param("s", $id);
        $stmt0->execute();

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
        $_SESSION['error'] = "Lỗi khi xóa giảng viên: " . $e->getMessage() . ". Vui lòng kiểm tra dữ liệu liên quan.";
        header("Location: " . $_SERVER['PHP_SELF']);
        ob_end_flush();
        exit();
    }
}

$stmt->close();
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
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/distCss/adminlte.min.css">
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
                                unset($_SESSION['message']);
                            }
                            if (isset($_SESSION['error'])) {
                                echo '<div id="error-message" class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                                unset($_SESSION['error']);
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
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
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
    <!-- <script src="<?php echo BASE_URL ?>/Public/dist/js/adminlte.min.js"></script> -->
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