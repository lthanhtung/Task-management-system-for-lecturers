<?php
ob_start();
require_once '../Layout/header.php';
require_once BASE_PATH . './Database/connect-database.php';

$id = $_GET['MaGiangVien'];
// Lấy thông tin giảng viên
$sql = "
    SELECT giangvien.*, khoa.TenKhoa, lichtiepsinhvien.*
    FROM giangvien
    JOIN khoa ON giangvien.MaKhoa = khoa.MaKhoa
    JOIN lichtiepsinhvien ON giangvien.MaGiangVien = lichtiepsinhvien.MaGiangVien
    WHERE giangvien.MaGiangVien = '$id'";
$result = mysqli_query($dbc, $sql);
$rows = mysqli_fetch_array($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array(); // Khởi tạo mảng lỗi

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
    if (isset($_POST['NgaySinh'])) {
        $NgaySinh = mysqli_real_escape_string($dbc, trim($_POST['NgaySinh']));
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
            $sql = "SELECT * FROM giangvien WHERE Email = '$Email' AND MaGiangVien != '$id'";
            $result = mysqli_query($dbc, $sql);
            if (mysqli_num_rows($result) > 0) {
                $errors['Email'] = 'Email đã có người sử dụng';
            }
        }
    }

    // Kiểm tra Số điện thoại
    if (empty($_POST['SDT'])) {
        $errors['SDT'] = 'Số điện thoại không để trống';
    } else {
        $SDT = trim($_POST['SDT']);
        if (!is_numeric($SDT) || substr($SDT, 0, 1) != '0' || strlen($SDT) != 11) {
            $errors['SDT'] = 'Số điện thoại phải là số và bắt đầu bằng 0 với đúng 11 số';
        } else {
            $SDT = mysqli_real_escape_string($dbc, $SDT);
        }
    }

    // Kiểm tra Lịch tiếp sinh viên
    if (empty($_POST['thutiep'])) {
        $errors['thutiep'] = 'Ngày không được để trống';
    } else {
        $thutiep = mysqli_real_escape_string($dbc, $_POST['thutiep']);
    }

    // Kiểm tra Thời gian bắt đầu
    if (empty($_POST['gio_batdau'])) {
        $errors['gio_batdau'] = 'Thời gian bắt đầu không được để trống';
    } else {
        $gio_batdau = mysqli_real_escape_string($dbc, trim($_POST['gio_batdau']));
    }

    // Kiểm tra Thời gian kết thúc
    if (empty($_POST['gio_ketthuc'])) {
        $errors['gio_ketthuc'] = 'Thời gian kết thúc không được để trống';
    } else {
        $gio_ketthuc = mysqli_real_escape_string($dbc, trim($_POST['gio_ketthuc']));
    }

    // Kiểm tra Địa điểm
    if (empty($_POST['diadiem'])) {
        $errors['diadiem'] = 'Địa điểm không để trống';
    } else {
        $diadiem = mysqli_real_escape_string($dbc, trim($_POST['diadiem']));
    }

    // Kiểm tra Học vị
    if (isset($_POST['HocVi'])) {
        $hocvi = ($_POST['HocVi'] === 'thac') ? 'Thạc sĩ' : 'Tiến sĩ';
    }

    // Chức danh
    if (isset($_POST['ChucDanh'])) {
        switch ($_POST['ChucDanh']) {
            case 'trg':
                $chucdanh = 'Trợ giảng';
                break;
            case 'gv':
                $chucdanh = 'Giảng viên';
                break;
            case 'gvc':
                $chucdanh = 'Giảng viên chính';
                break;
            case 'phogs':
                $chucdanh = 'Phó giáo sư';
                break;
            case 'gs':
                $chucdanh = 'Giáo sư';
                break;
        }
    }

    // Khoa
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
        // Cập nhật thông tin giảng viên
        $queryGiangVien = "
        UPDATE giangvien 
        SET 
            HoGiangVien = '$HoGiangVien',
            TenGiangVien = '$TenGiangVien',
            NgaySinh = '$NgaySinh',
            GioiTinh = '$GioiTinh',
            Email = '$Email',
            SoDienThoai = '$SDT',
            HocVi = '$hocvi',
            ChucDanh = '$chucdanh',
            MaKhoa = '$Khoa',
            TrangThai = '$trangthai'
        WHERE MaGiangVien = '$id'";

        // Cập nhật lịch tiếp sinh viên
        $queryLichTiep = "
        UPDATE lichtiepsinhvien 
        SET 
            ThuTiepSinhVien = '$thutiep',
            GioBatDau = '$gio_batdau',
            GioKetThuc = '$gio_ketthuc',
            DiaDiem = '$diadiem'
        WHERE MaGiangVien = '$id'";

        // Thực hiện các truy vấn
        $r1 = @mysqli_query($dbc, $queryGiangVien);
        $r2 = @mysqli_query($dbc, $queryLichTiep);

        session_start();
        if ($r1 && $r2) {
            $_SESSION['success_message'] = 'Cập nhật thông tin giảng viên thành công!';
            header("Location: index.php");
            ob_end_flush();
            exit();
        } else {
            echo '<h1>Lỗi Hệ Thống</h1>';
            echo '<p>' . mysqli_error($dbc) . '<br />Truy vấn: ' . $queryGiangVien . '</p>';
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
    <title>Chỉnh sửa Giảng Viên</title>
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/dist/css/adminlte.min.css">
</head>

<body>
    <div class="content-wrapper">
        <section class="content my-2">
            <div class="card">
                <div class="card-header">
                    <strong class="text-danger">CHỈNH SỬA GIẢNG VIÊN</strong>
                    <a href="./index.php" class="btn-sm btn-info float-right"><i class="fa fa-long-arrow-alt-left"></i> Quay lại</a>
                </div>
                <form action="" method="post">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Mã Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <input class="form-control" type="text" name="MaGiangVien" value="<?php echo $rows['MaGiangVien']; ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Họ Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <input class="form-control" type="text" name="HoGiangVien" value="<?php echo $rows['HoGiangVien']; ?>">
                                    <?php if (isset($errors['HoGiangVien'])): ?>
                                        <small class="text-danger"><?php echo $errors['HoGiangVien']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Tên Giảng Viên <span class="text-danger"> (*)</span></label>
                                    <input class="form-control" type="text" name="TenGiangVien" value="<?php echo $rows['TenGiangVien']; ?>">
                                    <?php if (isset($errors['TenGiangVien'])): ?>
                                        <small class="text-danger"><?php echo $errors['TenGiangVien']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Email <span class="text-danger"> (*)</span></label>
                                    <input class="form-control" type="text" name="Email" value="<?php echo $rows['Email']; ?>">
                                    <?php if (isset($errors['Email'])): ?>
                                        <small class="text-danger"><?php echo $errors['Email']; ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Lịch tiếp sinh viên <span class="text-danger"> (*)</span></label>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <select class="form-control" name="thutiep">
                                                <option value="">Chọn ngày</option>
                                                <option value="2" <?php echo ($rows['ThuTiepSinhVien'] == "Thứ Hai") ? 'selected' : ''; ?>>Thứ Hai</option>
                                                <option value="3" <?php echo ($rows['ThuTiepSinhVien'] == "Thứ Ba") ? 'selected' : ''; ?>>Thứ Ba</option>
                                                <option value="4" <?php echo ($rows['ThuTiepSinhVien'] == "Thứ Tư") ? 'selected' : ''; ?>>Thứ Tư</option>
                                                <option value="5" <?php echo ($rows['ThuTiepSinhVien'] == "Thứ Năm") ? 'selected' : ''; ?>>Thứ Năm</option>
                                                <option value="6" <?php echo ($rows['ThuTiepSinhVien'] == "Thứ Sáu") ? 'selected' : ''; ?>>Thứ Sáu</option>
                                                <option value="7" <?php echo ($rows['ThuTiepSinhVien'] == "Thứ Bảy") ? 'selected' : ''; ?>>Thứ Bảy</option>
                                                <option value="1" <?php echo ($rows['ThuTiepSinhVien'] == "Chủ Nhật") ? 'selected' : ''; ?>>Chủ Nhật</option>
                                            </select>
                                            <?php if (isset($errors['thutiep'])): ?>
                                                <small class="text-danger"><?php echo $errors['thutiep']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <input class="form-control" type="time" name="gio_batdau" value="<?php echo $rows['GioBatDau']; ?>">
                                            <?php if (isset($errors['gio_batdau'])): ?>
                                                <small class="text-danger"><?php echo $errors['gio_batdau']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <input class="form-control" type="time" name="gio_ketthuc" value="<?php echo $rows['GioKetThuc']; ?>">
                                            <?php if (isset($errors['gio_ketthuc'])): ?>
                                                <small class="text-danger"><?php echo $errors['gio_ketthuc']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Địa điểm gặp sinh viên <span class="text-danger"> (*)</span></label>
                                    <input class="form-control" type="text" name="diadiem" value="<?php echo $rows['DiaDiem']; ?>">
                                    <?php if (isset($errors['diadiem'])): ?>
                                        <small class="text-danger"><?php echo $errors['diadiem']; ?></small>
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
                                    <label>Ngày sinh <span class="text-danger">(*)</span></label>
                                    <input type="date" class="form-control" name="NgaySinh" value="<?php echo $rows['NgaySinh']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Khoa <span class="text-danger">(*)</span></label>
                                    <select class="form-control" name="Khoa">
                                        <?php
                                        $sql = "SELECT * FROM khoa WHERE TrangThai=1";
                                        $result = mysqli_query($dbc, $sql);
                                        while ($row = mysqli_fetch_array($result)) {
                                            $selected = ($row['MaKhoa'] == $rows['MaKhoa']) ? 'selected' : '';
                                            echo "<option value='{$row['MaKhoa']}' $selected>{$row['TenKhoa']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Giới tính <span class="text-danger">(*)</span></label>
                                    <select class="form-control" name="GioiTinh">
                                        <option value="nam" <?php echo ($rows['GioiTinh'] == 1) ? 'selected' : ''; ?>>Nam</option>
                                        <option value="nu" <?php echo ($rows['GioiTinh'] == 2) ? 'selected' : ''; ?>>Nữ</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Số điện thoại <span class="text-danger"> (*)</span></label>
                                    <input class="form-control" type="text" name="SDT" value="<?php echo $rows['SoDienThoai']; ?>">
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
                                <div class="col-md-offset-2 col-md-12">
                                    <button class="btn-sm btn-success" type="submit" name="create"> Lưu [Cập nhật] <i class="fa fa-save"></i> </button>
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
    <!--