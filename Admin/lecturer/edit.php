<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . '/Database/connect-database.php';

if (!isset($_GET['MaGiangVien']) || empty($_GET['MaGiangVien'])) {
    header("Location: index.php");
    exit;
}
$id = mysqli_real_escape_string($dbc, $_GET['MaGiangVien']);

// Kiểm tra quyền và lấy MaKhoa của Admin
$user_id = $_SESSION['user_id'] ?? null;
$quyen = $_SESSION['quyen'] ?? 'Không xác định';
$ma_khoa = null;
$ten_khoa = null;

if ($quyen === 'Admin' && $user_id) {
    $query_khoa = "SELECT g.MaKhoa, k.TenKhoa 
                   FROM giangvien g 
                   JOIN khoa k ON g.MaKhoa = k.MaKhoa 
                   WHERE g.MaGiangVien = ?";
    $stmt_khoa = mysqli_prepare($dbc, $query_khoa);
    mysqli_stmt_bind_param($stmt_khoa, "s", $user_id);
    mysqli_stmt_execute($stmt_khoa);
    $result_khoa = mysqli_stmt_get_result($stmt_khoa);

    if ($row_khoa = mysqli_fetch_assoc($result_khoa)) {
        $ma_khoa = $row_khoa['MaKhoa'];
        $ten_khoa = $row_khoa['TenKhoa'];
    }
    mysqli_stmt_close($stmt_khoa);
}

// Lấy thông tin giảng viên
$sql = "SELECT giangvien.*, khoa.TenKhoa
        FROM giangvien
        JOIN khoa ON giangvien.MaKhoa = khoa.MaKhoa
        WHERE giangvien.MaGiangVien = ?";
$stmt = mysqli_prepare($dbc, $sql);
mysqli_stmt_bind_param($stmt, "s", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rows = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$rows) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array();

    // Kiểm tra họ giảng viên
    if (empty($_POST['HoGiangVien'])) {
        $errors['HoGiangVien'] = 'Họ giảng viên không để trống';
    } else {
        $HoGiangVien = mysqli_real_escape_string($dbc, trim($_POST['HoGiangVien']));
    }

    // Kiểm tra Tên giảng viên
    if (empty($_POST['TenGiangVien'])) {
        $errors['TenGiangVien'] = 'Tên giảng viên không để trống';
    } else {
        $TenGiangVien = mysqli_real_escape_string($dbc, trim($_POST['TenGiangVien']));
    }

    // Ngày sinh
    $NgaySinh = null;
    if (!empty($_POST['NgaySinh'])) {
        $NgaySinhInput = trim($_POST['NgaySinh']);
        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $NgaySinhInput)) {
            $errors['NgaySinh'] = 'Ngày sinh không hợp lệ';
        } else {
            $NgaySinh = mysqli_real_escape_string($dbc, $NgaySinhInput);
        }
    }

    // Giới tính
    if (isset($_POST['GioiTinh'])) {
        $GioiTinh = ($_POST['GioiTinh'] === 'nam') ? 1 : 2;
    } else {
        $errors['GioiTinh'] = 'Vui lòng chọn giới tính';
    }

    // Kiểm tra Email
    if (empty($_POST['Email'])) {
        $errors['Email'] = 'Email không để trống!';
    } else {
        if (strpos($_POST['Email'], '@') === false) {
            $errors['Email'] = 'Email phải chứa ký tự @!';
        } else {
            $Email = mysqli_real_escape_string($dbc, trim($_POST['Email']));
            $sql = "SELECT * FROM giangvien WHERE Email = ? AND MaGiangVien != ?";
            $stmt = mysqli_prepare($dbc, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $Email, $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $errors['Email'] = 'Email đã có người sử dụng';
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Kiểm tra Số điện thoại
    $SDT = null;
    if (!empty($_POST['SDT'])) {
        $SDT = trim($_POST['SDT']);
        if (!is_numeric($SDT) || substr($SDT, 0, 1) != '0' || strlen($SDT) < 10) {
            $errors['SDT'] = 'Số điện thoại phải là số, bắt đầu bằng 0 và có ít nhất 10 số';
        } else {
            $SDT = mysqli_real_escape_string($dbc, $SDT);
        }
    }

    // Kiểm tra Học vị
    if (isset($_POST['HocVi'])) {
        $hocvi = ($_POST['HocVi'] === 'thac') ? 'Thạc sĩ' : 'Tiến sĩ';
    } else {
        $errors['HocVi'] = 'Vui lòng chọn học vị';
    }

    // Chức danh
    if (isset($_POST['ChucDanh'])) {
        $chucdanh_options = [
            'trg' => 'Trợ giảng',
            'gv' => 'Giảng viên',
            'gvc' => 'Giảng viên chính',
            'phogs' => 'Phó giáo sư',
            'gs' => 'Giáo sư'
        ];
        $chucdanh = $chucdanh_options[$_POST['ChucDanh']] ?? 'Giảng viên';
    } else {
        $errors['ChucDanh'] = 'Vui lòng chọn chức danh';
    }

    // Khoa
    if ($quyen === 'Admin' && $ma_khoa !== null) {
        $Khoa = $ma_khoa;
        $tenKhoa = $ten_khoa;
    } elseif (isset($_POST['Khoa'])) {
        $Khoa = mysqli_real_escape_string($dbc, trim($_POST['Khoa']));
    } else {
        $errors['Khoa'] = 'Vui lòng chọn khoa';
    }

    // Kiểm tra trạng thái
    if (isset($_POST['TrangThai'])) {
        $trangthai_options = [
            'day' => 1,
            'nghi' => 2,
            'chuyen' => 3
        ];
        $trangthai = $trangthai_options[$_POST['TrangThai']] ?? 1;
    } else {
        $errors['TrangThai'] = 'Vui lòng chọn trạng thái';
    }

    // Xử lý ảnh đại diện
    $imagePathForDB = $rows['AnhDaiDien'];
    if (isset($Khoa) && empty($errors)) {
        $MaKhoa = $Khoa;
        $queryKhoa = "SELECT TenKhoa FROM khoa WHERE MaKhoa = ? AND TrangThai = 1";
        $stmt_khoa = mysqli_prepare($dbc, $queryKhoa);
        mysqli_stmt_bind_param($stmt_khoa, "s", $MaKhoa);
        mysqli_stmt_execute($stmt_khoa);
        $resultKhoa = mysqli_stmt_get_result($stmt_khoa);

        if ($resultKhoa && mysqli_num_rows($resultKhoa) > 0) {
            $rowKhoa = mysqli_fetch_assoc($resultKhoa);
            $tenKhoa = $rowKhoa['TenKhoa'];

            // Đường dẫn thư mục khoa
            $facultyPath = BASE_PATH . '/Public/img/faculty/' . $tenKhoa;

            // Tạo thư mục nếu chưa tồn tại
            if (!file_exists($facultyPath)) {
                if (!mkdir($facultyPath, 0777, true)) {
                    $errors['system'] = 'Không thể tạo thư mục cho khoa ' . $tenKhoa;
                }
            }

            // Xử lý ảnh nếu người dùng chọn ảnh mới
            if (isset($_FILES['anhdaidien']) && $_FILES['anhdaidien']['error'] == 0) {
                $file = $_FILES['anhdaidien'];
                $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');

                $fileName = $HoGiangVien . '_' . $TenGiangVien . '.' . $fileExtension;
                $destination = $facultyPath . '/' . $fileName;
                $imagePathForDB = BASE_URL . '/Public/img/faculty/' . $tenKhoa . '/' . $fileName;

                if (!in_array($fileExtension, $allowedTypes)) {
                    $errors['anhdaidien'] = 'Chỉ chấp nhận file JPG, JPEG, PNG hoặc GIF';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $errors['anhdaidien'] = 'Kích thước file không được vượt quá 5MB';
                } else {
                    // Xóa ảnh cũ nếu tồn tại (trừ ảnh mặc định)
                    $oldImagePath = BASE_PATH . str_replace(BASE_URL, '', $rows['AnhDaiDien']);
                    if (file_exists($oldImagePath) && strpos($oldImagePath, 'avatar-default.png') === false) {
                        unlink($oldImagePath);
                    }

                    // Upload ảnh mới
                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                        $errors['anhdaidien'] = 'Không thể upload ảnh đại diện';
                    }
                }
            }
        } else {
            $errors['Khoa'] = 'Khoa không tồn tại hoặc không hoạt động';
        }
        mysqli_stmt_close($stmt_khoa);
    }

    if (empty($errors)) {
        // Cập nhật thông tin giảng viên
        $queryGiangVien = "
        UPDATE giangvien 
        SET 
            HoGiangVien = ?,
            TenGiangVien = ?,
            NgaySinh = " . ($NgaySinh ? "?" : "NULL") . ",
            GioiTinh = ?,
            Email = ?,
            SoDienThoai = " . ($SDT ? "?" : "NULL") . ",
            HocVi = ?,
            ChucDanh = ?,
            MaKhoa = ?,
            TrangThai = ?,
            AnhDaiDien = ?
        WHERE MaGiangVien = ?";

        $stmt = mysqli_prepare($dbc, $queryGiangVien);
        if ($NgaySinh && $SDT) {
            mysqli_stmt_bind_param($stmt, "ssssssssssss", $HoGiangVien, $TenGiangVien, $NgaySinh, $GioiTinh, $Email, $SDT, $hocvi, $chucdanh, $Khoa, $trangthai, $imagePathForDB, $id);
        } elseif ($NgaySinh) {
            mysqli_stmt_bind_param($stmt, "sssssssssss", $HoGiangVien, $TenGiangVien, $NgaySinh, $GioiTinh, $Email, $hocvi, $chucdanh, $Khoa, $trangthai, $imagePathForDB, $id);
        } elseif ($SDT) {
            mysqli_stmt_bind_param($stmt, "sssssssssss", $HoGiangVien, $TenGiangVien, $GioiTinh, $Email, $SDT, $hocvi, $chucdanh, $Khoa, $trangthai, $imagePathForDB, $id);
        } else {
            mysqli_stmt_bind_param($stmt, "ssssssssss", $HoGiangVien, $TenGiangVien, $GioiTinh, $Email, $hocvi, $chucdanh, $Khoa, $trangthai, $imagePathForDB, $id);
        }

        $r1 = mysqli_stmt_execute($stmt);

        if ($r1) {
            $_SESSION['success_message'] = 'Cập nhật thông tin giảng viên thành công!';
            header("Location: index.php");
            ob_end_flush();
            exit();
        } else {
            echo '<h1>Lỗi Hệ Thống</h1>';
            echo '<p>' . mysqli_error($dbc) . '<br />Truy vấn: ' . $queryGiangVien . '</p>';
        }
        mysqli_stmt_close($stmt);
        mysqli_close($dbc);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa Giảng Viên</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
    <!-- Thêm CSS của Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Tùy chỉnh Select2 để không tràn container */
        .select2-container {
            max-width: 100%;
            box-sizing: border-box;
        }

        .select2-container .select2-selection--single {
            height: 38px;
            line-height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }

        .select2-selection__rendered {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .select2-container-parent {
            padding-left: 0;
            padding-right: 0;
        }
    </style>
</head>

<body>
    <div class="content-wrapper">
        <section class="content my-2">
            <div class="card">
                <div class="card-header">
                    <strong class="text-danger">CHỈNH SỬA GIẢNG VIÊN</strong>
                    <a href="./index.php" class="btn-sm btn-info float-right"><i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Mã Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <input class="form-control" type="text" name="MaGiangVien" value="<?php echo htmlspecialchars($rows['MaGiangVien']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Họ Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <input class="form-control" type="text" name="HoGiangVien" value="<?php echo htmlspecialchars($rows['HoGiangVien']); ?>">
                                    <?php if (isset($errors['HoGiangVien'])): ?>
                                        <small class="text-danger"><?php echo $errors['HoGiangVien']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Tên Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <input class="form-control" type="text" name="TenGiangVien" value="<?php echo htmlspecialchars($rows['TenGiangVien']); ?>">
                                    <?php if (isset($errors['TenGiangVien'])): ?>
                                        <small class="text-danger"><?php echo $errors['TenGiangVien']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Email <span class="text-danger"> (*)</span></label>
                                    <input class="form-control" type="text" name="Email" value="<?php echo htmlspecialchars($rows['Email']); ?>">
                                    <?php if (isset($errors['Email'])): ?>
                                        <small class="text-danger"><?php echo $errors['Email']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Trạng Thái <span class="text-danger">(*)</span></label>
                                    <select class="form-control" name="TrangThai">
                                        <option value="day" <?php echo ($rows['TrangThai'] == 1) ? 'selected' : ''; ?>>Đang dạy</option>
                                        <option value="nghi" <?php echo ($rows['TrangThai'] == 2) ? 'selected' : ''; ?>>Về hưu</option>
                                        <option value="chuyen" <?php echo ($rows['TrangThai'] == 3) ? 'selected' : ''; ?>>Chuyển công tác</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ảnh đại diện <span class="text-danger">(*)</span></label>
                                    <div class="col-md-12">
                                        <img src="<?php echo htmlspecialchars($rows['AnhDaiDien']); ?>" alt="Ảnh đại diện" style="width: 100px; height: auto; margin-bottom: 10px;">
                                        <input type="file" class="form-control" name="anhdaidien">
                                        <?php if (isset($errors['anhdaidien'])): ?>
                                            <small class="text-danger"><?php echo $errors['anhdaidien']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Ngày sinh</label>
                                    <input type="date" class="form-control" name="NgaySinh" value="<?php echo $rows['NgaySinh'] ? htmlspecialchars($rows['NgaySinh']) : ''; ?>">
                                    <?php if (isset($errors['NgaySinh'])): ?>
                                        <small class="text-danger"><?php echo $errors['NgaySinh']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php if ($quyen !== 'Admin'): ?>
                                    <div class="form-group">
                                        <label>Khoa <span class="text-danger">(*)</span></label>
                                        <div class="select2-container-parent">
                                            <select class="form-control select2-khoa" name="Khoa">
                                                <option value="">Chọn khoa</option>
                                                <?php
                                                $sql = "SELECT * FROM khoa WHERE TrangThai=1";
                                                $result = mysqli_query($dbc, $sql);
                                                while ($row = mysqli_fetch_array($result)) {
                                                    $selected = ($row['MaKhoa'] == $rows['MaKhoa']) ? 'selected' : '';
                                                    echo "<option value='" . htmlspecialchars($row['MaKhoa']) . "' $selected>" . htmlspecialchars($row['TenKhoa']) . "</option>";
                                                }
                                                ?>
                                            </select>
                                            <?php if (isset($errors['Khoa'])): ?>
                                                <small class="text-danger"><?php echo $errors['Khoa']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="form-group">
                                        <label>Khoa <span class="text-danger">(*)</span></label>
                                        <input type="hidden" name="Khoa" value="<?php echo htmlspecialchars($ma_khoa); ?>">
                                        <p><?php echo htmlspecialchars($ten_khoa); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group">
                                    <label>Giới tính <span class="text-danger">(*)</span></label>
                                    <select class="form-control" name="GioiTinh">
                                        <option value="nam" <?php echo ($rows['GioiTinh'] == 1) ? 'selected' : ''; ?>>Nam</option>
                                        <option value="nu" <?php echo ($rows['GioiTinh'] == 2) ? 'selected' : ''; ?>>Nữ</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Số điện thoại</label>
                                    <input class="form-control" type="text" name="SDT" value="<?php echo htmlspecialchars($rows['SoDienThoai'] ?? ''); ?>">
                                    <?php if (isset($errors['SDT'])): ?>
                                        <small class="text-danger"><?php echo $errors['SDT']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Học vị <span class="text-danger">(*)</span></label>
                                    <select class="form-control" name="HocVi">
                                        <option value="thac" <?php echo ($rows['HocVi'] == 'Thạc sĩ') ? 'selected' : ''; ?>>Thạc sĩ</option>
                                        <option value="tien" <?php echo ($rows['HocVi'] == 'Tiến sĩ') ? 'selected' : ''; ?>>Tiến sĩ</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Chức danh <span class="text-danger">(*)</span></label>
                                    <select class="form-control" name="ChucDanh">
                                        <option value="trg" <?php echo ($rows['ChucDanh'] == 'Trợ giảng') ? 'selected' : ''; ?>>Trợ giảng</option>
                                        <option value="gv" <?php echo ($rows['ChucDanh'] == 'Giảng viên') ? 'selected' : ''; ?>>Giảng viên</option>
                                        <option value="gvc" <?php echo ($rows['ChucDanh'] == 'Giảng viên chính') ? 'selected' : ''; ?>>Giảng viên chính</option>
                                        <option value="phogs" <?php echo ($rows['ChucDanh'] == 'Phó giáo sư') ? 'selected' : ''; ?>>Phó giáo sư</option>
                                        <option value="gs" <?php echo ($rows['ChucDanh'] == 'Giáo sư') ? 'selected' : ''; ?>>Giáo sư</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-12">
                                    <button class="btn-sm btn-success" type="submit" name="update"> Lưu [Cập nhật] <i class="fa fa-save"></i> </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>

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
    <script src="<?php echo BASE_URL ?>/Public/dist/js/demo.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!-- Thêm JS của Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Khởi tạo Select2 cho dropdown Khoa
            $('.select2-khoa').select2({
                placeholder: "Chọn khoa",
                allowClear: false,
                width: 'resolve',
                language: {
                    noResults: function() {
                        return "Không có dữ liệu";
                    }
                }
            });
        });
    </script>
</body>

</html>

<?php
require_once BASE_PATH . '/Layout/footer.php';
mysqli_close($dbc);
?>