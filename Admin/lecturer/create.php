<?php
ob_start();
require_once '../Layout/header.php';

// Kiểm tra quyền và lấy MaKhoa của Admin
$user_id = $_SESSION['user_id'];
$quyen = $_SESSION['quyen'] ?? 'Không xác định';
$ma_khoa = null;
$ten_khoa = null;

if ($quyen === 'Admin') {
    // Lấy MaKhoa và TenKhoa của Admin từ bảng giangvien
    $query_khoa = "SELECT g.MaKhoa, k.TenKhoa 
                   FROM giangvien g 
                   JOIN khoa k ON g.MaKhoa = k.MaKhoa 
                   WHERE g.MaGiangVien = ?";
    $stmt_khoa = $dbc->prepare($query_khoa);
    $stmt_khoa->bind_param("s", $user_id);
    $stmt_khoa->execute();
    $result_khoa = $stmt_khoa->get_result();

    if ($row_khoa = $result_khoa->fetch_assoc()) {
        $ma_khoa = $row_khoa['MaKhoa'];
        $ten_khoa = $row_khoa['TenKhoa'];
    }
    $stmt_khoa->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once BASE_PATH . '/Database/connect-database.php';
    $errors = array();

    // Kiểm tra Mã giảng viên
    if (empty($_POST['MaGiangVien'])) {
        $errors['MaGiangVien'] = 'Mã giảng viên không để trống!';
    } else {
        $MaGiangVien = mysqli_real_escape_string($dbc, trim($_POST['MaGiangVien']));
        $MaHoSo = $MaGiangVien;
        $sql = "SELECT * FROM giangvien WHERE MaGiangVien = '$MaGiangVien'";
        $result = mysqli_query($dbc, $sql);

        if (mysqli_num_rows($result) > 0) {
            $errors['MaGiangVien'] = 'Mã giảng viên bị trùng';
        }
    }

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
    $NgaySinh = null; // Khởi tạo mặc định là null
    if (!empty($_POST['NgaySinh'])) {
        $NgaySinhInput = trim($_POST['NgaySinh']);
        if (!preg_match("/^\d{2}\/\d{2}\/\d{4}$/", $NgaySinhInput)) {
            $errors['NgaySinh'] = 'Ngày sinh phải có định dạng dd/mm/yyyy';
        } else {
            $dateParts = explode('/', $NgaySinhInput);
            if (!checkdate($dateParts[1], $dateParts[0], $dateParts[2])) {
                $errors['NgaySinh'] = 'Ngày sinh không hợp lệ';
            } else {
                $NgaySinh = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                $NgaySinh = mysqli_real_escape_string($dbc, $NgaySinh);
            }
        }
    }

    // Giới tính
    if (isset($_POST['GioiTinh'])) {
        $GioiTinh = ($_POST['GioiTinh'] === 'nam') ? 1 : 2;
    }

    // Kiểm tra Email
    if (empty($_POST['Email'])) {
        $errors['Email'] = 'Email không để trống!';
    } else {
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
    $SDT = null; // Khởi tạo mặc định là null
    if (!empty($_POST['SDT'])) {
        $SDT = trim($_POST['SDT']);
        if (!is_numeric($SDT)) {
            $errors['SDT'] = 'Số điện thoại phải là số';
        } else {
            if (substr($SDT, 0, 1) != '0') {
                $errors['SDT'] = 'Số điện thoại phải bắt đầu bằng 0';
            } else {
                if (strlen($SDT) < 10) {
                    $errors['SDT'] = 'Số điện thoại phải có ít nhất 10 số';
                }
            }
        }
        if (!isset($errors['SDT'])) {
            $SDT = mysqli_real_escape_string($dbc, $SDT);
        }
    }

    // Kiểm tra Học vị
    if (isset($_POST['HocVi'])) {
        $hocvi = ($_POST['HocVi'] === 'thac') ? 'Thạc sĩ' : 'Tiến sĩ';
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
    }

    // Xử lý ảnh đại diện
    if (isset($Khoa) && empty($errors)) {
        $MaKhoa = $Khoa;
        $queryKhoa = "SELECT TenKhoa FROM khoa WHERE MaKhoa = '$MaKhoa' AND TrangThai = 1";
        $resultKhoa = mysqli_query($dbc, $queryKhoa);

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

            // Xử lý ảnh
            $anhdaidien = '';
            $defaultAvatar = BASE_PATH . '/Public/img/avatar-default.png';

            if (isset($_FILES['anhdaidien']) && $_FILES['anhdaidien']['error'] == 0) {
                $file = $_FILES['anhdaidien'];
                $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');

                $fileName = $HoGiangVien . '_' . $TenGiangVien . '.' . $fileExtension;
                $destination = $facultyPath . '/' . $fileName;
                $anhdaidien = BASE_URL . '/Public/img/faculty/' . $tenKhoa . '/' . $fileName;

                if (!in_array($fileExtension, $allowedTypes)) {
                    $errors['anhdaidien'] = 'Chỉ chấp nhận file JPG, JPEG, PNG hoặc GIF';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $errors['anhdaidien'] = 'Kích thước file không được vượt quá 5MB';
                } elseif (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $errors['anhdaidien'] = 'Không thể upload ảnh đại diện';
                }
            } else {
                $fileName = $HoGiangVien . '_' . $TenGiangVien . '.png';
                $destination = $facultyPath . '/' . $fileName;
                $anhdaidien = BASE_URL . '/Public/img/faculty/' . $tenKhoa . '/' . $fileName;

                if (file_exists($defaultAvatar)) {
                    if (!copy($defaultAvatar, $destination)) {
                        $errors['anhdaidien'] = 'Không thể sao chép ảnh mặc định';
                    }
                } else {
                    $errors['anhdaidien'] = 'Không tìm thấy ảnh mặc định avatar-default.png';
                }
            }
        } else {
            $errors['Khoa'] = 'Khoa không tồn tại hoặc không hoạt động';
        }
    }

    // Nếu không có lỗi thì insert dữ liệu
    if (empty($errors)) {
        $defaultPassword = '1fFZ8o*J&zTp2L9v';
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

        $queryHoSoDanhGia = "INSERT INTO hosodanhgiavienchuc (MaHoSo) VALUES ('$MaHoSo')";
        $queryGiangVien = "INSERT INTO giangvien (MaGiangVien, HoGiangVien, TenGiangVien, NgaySinh, GioiTinh, Email, SoDienThoai, HocVi, ChucDanh, MaKhoa, TrangThai, AnhDaiDien, MaHoSo) 
                           VALUES ('$MaGiangVien', '$HoGiangVien', '$TenGiangVien', " . ($NgaySinh ? "'$NgaySinh'" : "NULL") . ", '$GioiTinh', '$Email', " . ($SDT ? "'$SDT'" : "NULL") . ", '$hocvi', '$chucdanh', '$Khoa', '$trangthai', '$anhdaidien', '$MaHoSo')";
        $queryTaiKhoanGiangVien = "INSERT INTO taikhoan (MaTaiKhoan, MatKhau, Quyen) VALUES ('$MaGiangVien', '$hashedPassword', 'User')";

        $r1 = @mysqli_query($dbc, $queryHoSoDanhGia);
        $r2 = @mysqli_query($dbc, $queryGiangVien);
        $r4 = @mysqli_query($dbc, $queryTaiKhoanGiangVien);

        if ($r1 && $r2 && $r4) {
            $_SESSION['success_message'] = 'Đã thêm giảng viên thành công!';
            header("Location: index.php");
            ob_end_flush();
            exit();
        } else {
            echo '<h1>System Error</h1>
                  <p class="error">You could not be registered due to a system error. We apologize for any inconvenience.</p>';
            echo '<p>' . mysqli_error($dbc) . '<br /><br />Query: ' . $queryGiangVien . '</p>';
        }
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
    <title>Thêm mới giảng viên</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- Thêm CSS của Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Tùy chỉnh Select2 để không tràn container */
        .select2-container {
            max-width: 100%;
            /* Ngăn dropdown vượt quá container */
            box-sizing: border-box;
            /* Đảm bảo padding và border không làm tràn */
        }

        .select2-container .select2-selection--single {
            height: 38px;
            /* Chiều cao đồng bộ với các input khác */
            line-height: 38px;
            /* Căn giữa nội dung */
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }

        /* Cắt ngắn nội dung dài trong dropdown để tránh tràn */
        .select2-selection__rendered {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Bù padding của container để dropdown hiển thị đúng */
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
                    <div class="row">
                        <div class="col-md-6">
                            <strong class="text-danger">THÊM MỚI GIẢNG VIÊN</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="./index.php" class="btn-sm btn-info"> <i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                        </div>
                    </div>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Mã Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="MaGiangVien" value="<?php echo isset($_POST['MaGiangVien']) ? htmlspecialchars($_POST['MaGiangVien']) : ''; ?>">
                                        <?php if (isset($errors['MaGiangVien'])): ?>
                                            <small class="text-danger"><?php echo $errors['MaGiangVien']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Họ Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="HoGiangVien" value="<?php echo isset($_POST['HoGiangVien']) ? htmlspecialchars($_POST['HoGiangVien']) : ''; ?>">
                                        <?php if (isset($errors['HoGiangVien'])): ?>
                                            <small class="text-danger"><?php echo $errors['HoGiangVien']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tên Giảng Viên <span class="text-danger"> (*):</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="TenGiangVien" value="<?php echo isset($_POST['TenGiangVien']) ? htmlspecialchars($_POST['TenGiangVien']) : ''; ?>">
                                        <?php if (isset($errors['TenGiangVien'])): ?>
                                            <small class="text-danger"><?php echo $errors['TenGiangVien']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Ngày sinh</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="NgaySinh" id="ngaySinh"
                                            value="<?php echo isset($_POST['NgaySinh']) ? htmlspecialchars($_POST['NgaySinh']) : ''; ?>">
                                        <?php if (isset($errors['NgaySinh'])): ?>
                                            <small class="text-danger"><?php echo $errors['NgaySinh']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Email <span class="text-danger"> (*)</span></label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="Email" value="<?php echo isset($_POST['Email']) ? htmlspecialchars($_POST['Email']) : ''; ?>">
                                        <?php if (isset($errors['Email'])): ?>
                                            <small class="text-danger"><?php echo $errors['Email']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Số điện thoại</label>
                                    <div class="col-md-10">
                                        <input class="form-control" type="text" name="SDT" value="<?php echo isset($_POST['SDT']) ? htmlspecialchars($_POST['SDT']) : ''; ?>">
                                        <?php if (isset($errors['SDT'])): ?>
                                            <small class="text-danger"><?php echo $errors['SDT']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ảnh đại diện <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <input type="file" class="form-control" name="anhdaidien" style="width: auto;">
                                        <?php if (isset($errors['anhdaidien'])): ?>
                                            <small class="text-danger"><?php echo $errors['anhdaidien']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($quyen !== 'Admin'): ?>
                                    <div class="form-group">
                                        <label>Khoa <span class="text-danger">(*)</span></label>
                                        <div class="col-md-12 select2-container-parent">
                                            <select class="form-control select2-khoa" name="Khoa">
                                                <option value="">Chọn khoa</option>
                                                <?php
                                                require_once BASE_PATH . '/Database/connect-database.php';
                                                $sql = "SELECT * FROM khoa WHERE TrangThai=1";
                                                $result = mysqli_query($dbc, $sql);
                                                if (mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_array($result)) {
                                                        echo "<option value='$row[MaKhoa]' " . (isset($_POST['Khoa']) && $_POST['Khoa'] == $row['MaKhoa'] ? 'selected' : '') . ">$row[TenKhoa]</option>";
                                                    }
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
                                        <div class="col-md-6">
                                            <input type="hidden" name="Khoa" value="<?php echo htmlspecialchars($ma_khoa); ?>">
                                            <p><?php echo htmlspecialchars($ten_khoa); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group">
                                    <label>Giới tính <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="GioiTinh">
                                            <option value="nam" <?php echo (isset($_POST['GioiTinh']) && $_POST['GioiTinh'] == 'nam') ? 'selected' : ''; ?>>Nam</option>
                                            <option value="nu" <?php echo (isset($_POST['GioiTinh']) && $_POST['GioiTinh'] == 'nu') ? 'selected' : ''; ?>>Nữ</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Học vị <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="HocVi">
                                            <option value="thac" <?php echo (isset($_POST['HocVi']) && $_POST['HocVi'] == 'thac') ? 'selected' : ''; ?>>Thạc sĩ</option>
                                            <option value="tien" <?php echo (isset($_POST['HocVi']) && $_POST['HocVi'] == 'tien') ? 'selected' : ''; ?>>Tiến sĩ</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Chức danh <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="ChucDanh">
                                            <option value="trg" <?php echo (isset($_POST['ChucDanh']) && $_POST['ChucDanh'] == 'trg') ? 'selected' : ''; ?>>Trợ Giảng</option>
                                            <option value="gv" <?php echo (isset($_POST['ChucDanh']) && $_POST['ChucDanh'] == 'gv') ? 'selected' : ''; ?>>Giảng viên</option>
                                            <option value="gvc" <?php echo (isset($_POST['ChucDanh']) && $_POST['ChucDanh'] == 'gvc') ? 'selected' : ''; ?>>Giảng viên chính</option>
                                            <option value="phogs" <?php echo (isset($_POST['ChucDanh']) && $_POST['ChucDanh'] == 'phogs') ? 'selected' : ''; ?>>Phó giáo sư</option>
                                            <option value="gs" <?php echo (isset($_POST['ChucDanh']) && $_POST['ChucDanh'] == 'gs') ? 'selected' : ''; ?>>Giáo sư</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Trạng Thái <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="TrangThai">
                                            <option value="day" <?php echo (isset($_POST['TrangThai']) && $_POST['TrangThai'] == 'day') ? 'selected' : ''; ?>>Đang dạy</option>
                                            <option value="nghi" <?php echo (isset($_POST['TrangThai']) && $_POST['TrangThai'] == 'nghi') ? 'selected' : ''; ?>>Về hưu</option>
                                            <option value="chuyen" <?php echo (isset($_POST['TrangThai']) && $_POST['TrangThai'] == 'chuyen') ? 'selected' : ''; ?>>Chuyển công tác</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-12">
                                    <button class="btn-sm btn-success" type="submit" name="create"> Lưu [Thêm] <i class="fa fa-save"></i> </button>
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
            // Khởi tạo Datepicker
            $("#ngaySinh").datepicker({
                dateFormat: "dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                yearRange: "1900:2100"
            });

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

            // Khởi tạo DataTable (nếu có)
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
require_once BASE_PATH . '/Layout/footer.php';
?>