<?php
ob_start();
require_once '../Layout/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once BASE_PATH . './Database/connect-database.php';
    $errors = array();

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

    // Kiểm tra họ giảng viên
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

    // Ngày sinh
    if (isset($_POST['NgaySinh'])) {
        $NgaySinh = mysqli_real_escape_string($dbc, trim($_POST['NgaySinh']));
    }

    // Giới tính
    if (isset($_POST['GioiTinh'])) {
        if ($_POST['GioiTinh'] === 'nam') {
            $GioiTinh = 1;
        } else {
            $GioiTinh = 2;
        }
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
    if (empty($_POST['SDT'])) {
        $errors['SDT'] = 'Số điện thoại không để trống';
    } else {
        $SDT = trim($_POST['SDT']);
        if (!is_numeric($SDT)) {
            $errors['SDT'] = 'Số điện thoại phải là số';
        } else {
            if (substr($SDT, 0, 1) != '0') {
                $errors['SDT'] = 'Số điện thoại phải bắt đầu bằng 0';
            } else {
                if (strlen($SDT) < 11) {
                    $errors['SDT'] = 'Số điện thoại phải có 11 số';
                }
            }
        }
        if (!isset($errors['SDT'])) {
            $SDT = mysqli_real_escape_string($dbc, $SDT);
        }
    }

    // Kiểm tra thứ tiếp sinh viên
    if (empty($_POST['thutiep'])) {
        $errors['thutiep'] = 'Vui lòng chọn ngày tiếp sinh viên';
    } else {
        $thutiep = mysqli_real_escape_string($dbc, $_POST['thutiep']);
        if ($_POST['thutiep'] === '2') {
            $thutiep = 'Thứ Hai';
        } elseif ($_POST['thutiep'] === '3') {
            $thutiep = 'Thứ Ba';
        } elseif ($_POST['thutiep'] === '4') {
            $thutiep = 'Thứ Tư';
        } elseif ($_POST['thutiep'] === '5') {
            $thutiep = 'Thứ Năm';
        } elseif ($_POST['thutiep'] === '6') {
            $thutiep = 'Thứ Sáu';
        } elseif ($_POST['thutiep'] === '7') {
            $thutiep = 'Thứ Bảy';
        } elseif ($_POST['thutiep'] === '1') {
            $thutiep = 'Chủ Nhật';
        }
    }

    // Kiểm tra thời gian bắt đầu
    if (empty($_POST['gio_batdau'])) {
        $errors['gio_batdau'] = 'Thời gian bắt đầu không để trống';
    } else {
        $gio_batdau = mysqli_real_escape_string($dbc, trim($_POST['gio_batdau']));
    }

    // Kiểm tra thời gian kết thúc
    if (empty($_POST['gio_ketthuc'])) {
        $errors['gio_ketthuc'] = 'Thời gian kết thúc không để trống';
    } else {
        $gio_ketthuc = mysqli_real_escape_string($dbc, trim($_POST['gio_ketthuc']));
    }

    // Kiểm tra Địa điểm tiếp sinh viên
    if (empty($_POST['diadiem'])) {
        $errors['diadiem'] = 'Địa điểm không để trống';
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

    // Chức danh
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

    // Khoa
    if (isset($_POST['Khoa'])) {
        $Khoa = mysqli_real_escape_string($dbc, trim($_POST['Khoa']));
    }

    // Kiểm tra trạng thái
    if (isset($_POST['TrangThai'])) {
        if ($_POST['TrangThai'] == 'day') {
            $trangthai = 1;
        } elseif ($_POST['TrangThai'] == 'nghi') {
            $trangthai = 2;
        } else {
            $trangthai = 3;
        }
    }

    // Xử lý ảnh đại diện
    if (isset($_POST['Khoa']) && empty($errors)) {
        $MaKhoa = mysqli_real_escape_string($dbc, trim($_POST['Khoa']));
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
                // Dùng ảnh mặc định nếu không chọn ảnh
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
        $queryGiangVien = "INSERT INTO giangvien (MaGiangVien, HoGiangVien, TenGiangVien, NgaySinh, GioiTinh, Email, SoDienThoai, HocVi, ChucDanh, MaKhoa, TrangThai, AnhDaiDien) 
        VALUES ('$MaGiangVien', '$HoGiangVien', '$TenGiangVien', '$NgaySinh', '$GioiTinh', '$Email', '$SDT', '$hocvi', '$chucdanh', '$Khoa', '$trangthai', '$anhdaidien')";

        $queryLichTiep = "INSERT INTO lichtiepsinhvien (MaGiangVien, ThuTiepSinhVien, GioBatDau, GioKetThuc, DiaDiem) 
        VALUES ('$MaGiangVien', '$thutiep', '$gio_batdau', '$gio_ketthuc', '$diadiem')";

        $queryTaiKhoanGiangVien = "INSERT INTO taikhoan (MaTaiKhoan, MatKhau, Quyen) 
        VALUES ('$MaGiangVien', '1fFZ8o*J&zTp2L9v', 'User')";

        $r1 = @mysqli_query($dbc, $queryGiangVien);
        $r2 = @mysqli_query($dbc, $queryLichTiep);
        $r3 = @mysqli_query($dbc, $queryTaiKhoanGiangVien);

        session_start();
        if ($r1 && $r2 && $r3) {
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
    <title>Danh sách công việc</title>
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
                                        <input class="form-control" type="text" name="MaGiangVien" value="">
                                        <?php if (isset($errors['MaGiangVien'])): ?>
                                            <small class="text-danger"><?php echo $errors['MaGiangVien']; ?></small>
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
                                    <label>Lịch tiếp sinh viên <span class="text-danger"> (*)</span></label>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <select class="form-control" name="thutiep">
                                                <option value="">Chọn ngày</option>
                                                <option value="2">Thứ Hai</option>
                                                <option value="3">Thứ Ba</option>
                                                <option value="4">Thứ Tư</option>
                                                <option value="5">Thứ Năm</option>
                                                <option value="6">Thứ Sáu</option>
                                                <option value="7">Thứ Bảy</option>
                                                <option value="1">Chủ Nhật</option>
                                            </select>
                                            <?php if (isset($errors['thutiep'])): ?>
                                                <small class="text-danger"><?php echo $errors['thutiep']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <input class="form-control" type="time" name="gio_batdau" value="">
                                            <?php if (isset($errors['gio_batdau'])): ?>
                                                <small class="text-danger"><?php echo $errors['gio_batdau']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <p style="margin-top: 10px;">
                                            <i class="fa fa-arrow-right" aria-hidden="true"></i>
                                        </p>
                                        <div class="col-md-2">
                                            <input class="form-control" type="time" name="gio_ketthuc" value="">
                                            <?php if (isset($errors['gio_ketthuc'])): ?>
                                                <small class="text-danger"><?php echo $errors['gio_ketthuc']; ?></small>
                                            <?php endif; ?>
                                        </div>
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
                                            $sql = "SELECT * FROM khoa WHERE TrangThai=1";
                                            $result = mysqli_query($dbc, $sql);
                                            if (mysqli_num_rows($result) <> 0) {
                                                while ($row = mysqli_fetch_array($result)) {
                                                    echo "<option value='$row[MaKhoa]'>$row[TenKhoa]</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Giới tính <span class="text-danger">(*)</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="GioiTinh">
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
                                <div class="col-md-offset-2 col-md-12">
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
    <script src="<?php echo BASE_URL ?>/dist/js/adminlte.min.js"></script>
    <script src="<?php echo BASE_URL ?>/dist/js/demo.js"></script>
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