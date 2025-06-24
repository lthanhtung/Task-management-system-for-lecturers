<?php
session_start();
require_once '../config.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Vui lòng đăng nhập để truy cập trang này.";
    header("Location: login.php");
    exit();
}

// Lấy thông tin giảng viên
$user_id = $_SESSION['user_id'];
$query = "SELECT g.MaGiangVien, g.HoGiangVien, g.TenGiangVien, g.NgaySinh, g.GioiTinh, g.HocVi, g.ChucDanh, 
                 g.Email, g.SoDienThoai, g.MaKhoa, g.AnhDaiDien, g.GioiThieu, g.TrangThai, 
                 k.TenKhoa, t.Quyen
          FROM giangvien g
          JOIN khoa k ON g.MaKhoa = k.MaKhoa
          JOIN taikhoan t ON g.MaGiangVien = t.MaTaiKhoan
          WHERE t.MaTaiKhoan = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $ho_giang_vien = $row['HoGiangVien'];
    $ten_giang_vien = $row['TenGiangVien'];
    $avatar = !empty($row['AnhDaiDien']) ? $row['AnhDaiDien'] : BASE_URL . '/Public/img/default_avatar.jpg';
    $ngay_sinh = $row['NgaySinh'];
    $gioi_tinh = $row['GioiTinh'];
    $hoc_vi = $row['HocVi'];
    $chuc_danh = $row['ChucDanh'];
    $email = $row['Email'];
    $so_dien_thoai = $row['SoDienThoai'];
    $gioi_thieu = $row['GioiThieu'];
    $ma_khoa = $row['MaKhoa'];
    $ten_khoa = $row['TenKhoa'];
} else {
    $_SESSION['error_message'] = "Không tìm thấy thông tin giảng viên.";
    header("Location: profile.php");
    exit();
}

// Lấy danh sách thành tựu
$thanh_tuu_query = "SELECT tt.MaThanhTuu, tt.TenThanhTuu, tttt.NamDat
                    FROM thanhtuugiangvien tt
                    JOIN thongtinthanhtuugiangvien tttt ON tt.MaThanhTuu = tttt.MaThanhTuu
                    WHERE tttt.MaGiangVien = ?
                    ORDER BY tttt.NamDat DESC";
$thanh_tuu_stmt = mysqli_prepare($dbc, $thanh_tuu_query);
mysqli_stmt_bind_param($thanh_tuu_stmt, "s", $user_id);
mysqli_stmt_execute($thanh_tuu_stmt);
$thanh_tuu_result = mysqli_stmt_get_result($thanh_tuu_stmt);
$thanh_tuu_list = [];
while ($row = mysqli_fetch_assoc($thanh_tuu_result)) {
    $thanh_tuu_list[] = $row;
}
mysqli_stmt_close($thanh_tuu_stmt);

// Lấy danh sách lịch tiếp sinh viên
$lich_tiep_query = "SELECT ThuTiepSinhVien, GioBatDau, GioKetThuc, DiaDiem 
                    FROM lichtiepsinhvien 
                    WHERE MaGiangVien = ? 
                    ORDER BY FIELD(ThuTiepSinhVien, 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật')";
$lich_tiep_stmt = mysqli_prepare($dbc, $lich_tiep_query);
mysqli_stmt_bind_param($lich_tiep_stmt, "s", $user_id);
mysqli_stmt_execute($lich_tiep_stmt);
$lich_tiep_result = mysqli_stmt_get_result($lich_tiep_stmt);
$lich_tiep_list = [];
while ($row = mysqli_fetch_assoc($lich_tiep_result)) {
    $lich_tiep_list[] = $row;
}
mysqli_stmt_close($lich_tiep_stmt);

// Lấy danh sách công bố khoa học
$nckh_query = "SELECT nckh.MaNguyenCuuKhoaHoc, nckh.TenNguyenCuuKhoaHoc, nckh.HinhThuc
               FROM nguyencuukhoahoc nckh
               JOIN thongtinnguyencuukhoahoc ttnckh ON nckh.MaNguyenCuuKhoaHoc = ttnckh.MaNguyenCuuKhoaHoc
               WHERE ttnckh.MaGiangVien = ?
               ORDER BY nckh.MaNguyenCuuKhoaHoc DESC";
$nckh_stmt = mysqli_prepare($dbc, $nckh_query);
mysqli_stmt_bind_param($nckh_stmt, "s", $user_id);
mysqli_stmt_execute($nckh_stmt);
$nckh_result = mysqli_stmt_get_result($nckh_stmt);
$nckh_list = [];
while ($row = mysqli_fetch_assoc($nckh_result)) {
    $nckh_list[] = $row;
}
mysqli_stmt_close($nckh_stmt);

// Lấy danh sách kế hoạch hướng dẫn với IsPublic = 1
$huongdan_query = "SELECT MaHuongDan, KeHoachHuongDan, ThoiGianHuongDan
                   FROM huongdansinhvien
                   WHERE MaGiangVien = ? AND ThoiGianHuongDan IS NOT NULL AND IsPublic = 1
                   ORDER BY ThoiGianHuongDan DESC";
$huongdan_stmt = mysqli_prepare($dbc, $huongdan_query);
mysqli_stmt_bind_param($huongdan_stmt, "s", $user_id);
mysqli_stmt_execute($huongdan_stmt);
$huongdan_result = mysqli_stmt_get_result($huongdan_stmt);
$huongdan_list = [];
while ($row = mysqli_fetch_assoc($huongdan_result)) {
    $huongdan_list[] = $row;
}
mysqli_stmt_close($huongdan_stmt);

// Lấy cài đặt hiển thị công khai
$public_settings_query = "SELECT ShowNgaySinh, ShowGioiTinh, ShowEmail, ShowSoDienThoai, ShowThanhTuu, ShowLichTiep, ShowNguyenCuuKhoaHoc 
                         FROM giangvien_public_settings 
                         WHERE MaGiangVien = ?";
$public_settings_stmt = mysqli_prepare($dbc, $public_settings_query);
mysqli_stmt_bind_param($public_settings_stmt, "s", $user_id);
mysqli_stmt_execute($public_settings_stmt);
$public_settings_result = mysqli_stmt_get_result($public_settings_stmt);
$public_settings = $public_settings_result->fetch_assoc() ?: [
    'ShowNgaySinh' => 1,
    'ShowGioiTinh' => 1,
    'ShowEmail' => 1,
    'ShowSoDienThoai' => 1,
    'ShowThanhTuu' => 1,
    'ShowLichTiep' => 1,
    'ShowNguyenCuuKhoaHoc' => 1
];
mysqli_stmt_close($public_settings_stmt);

// Xử lý form khi gửi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = true;
    $has_changes = false;

    // Cập nhật thông tin cá nhân
    $ho_giang_vien = trim($_POST['ho_giang_vien']);
    $ten_giang_vien = trim($_POST['ten_giang_vien']);
    $ngay_sinh = trim($_POST['ngay_sinh']);
    $gioi_tinh = isset($_POST['gioi_tinh']) ? (int)$_POST['gioi_tinh'] : null;
    $email = trim($_POST['email']);
    $so_dien_thoai = trim($_POST['so_dien_thoai']);
    $gioi_thieu = trim($_POST['gioi_thieu']);
    $show_ngay_sinh = isset($_POST['show_ngay_sinh']) ? 1 : 0;
    $show_gioi_tinh = isset($_POST['show_gioi_tinh']) ? 1 : 0;
    $show_email = isset($_POST['show_email']) ? 1 : 0;
    $show_so_dien_thoai = isset($_POST['show_so_dien_thoai']) ? 1 : 0;
    $show_thanh_tuu = isset($_POST['show_thanh_tuu']) ? 1 : 0;
    $show_lich_tiep = isset($_POST['show_lich_tiep']) ? 1 : 0;
    $show_nckh = isset($_POST['show_nckh']) ? 1 : 0;

    // Validate và chuyển đổi định dạng ngày sinh
    if (!empty($ngay_sinh)) {
        // Mong đợi định dạng dd/mm/yyyy
        if (preg_match("/^(\d{2})\/(\d{2})\/(\d{4})$/", $ngay_sinh, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            if (checkdate($month, $day, $year)) {
                $ngay_sinh_db = "$year-$month-$day"; // Chuyển sang yyyy-mm-dd cho cơ sở dữ liệu
            } else {
                $errors[] = "Ngày sinh không hợp lệ. Vui lòng nhập theo định dạng dd/mm/yyyy.";
            }
        } else {
            $errors[] = "Ngày sinh phải có định dạng dd/mm/yyyy.";
        }
    } else {
        $ngay_sinh_db = null; // Cho phép null nếu ngày sinh không bắt buộc
    }

    // Xử lý ảnh đại diện
    $imagePathForDB = $avatar;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $file = $_FILES['avatar'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');

        $fileName = $ho_giang_vien . '_' . $ten_giang_vien . '.' . $fileExtension;
        $facultyPath = BASE_PATH . '/Public/img/faculty/' . $ten_khoa;
        $destination = $facultyPath . '/' . $fileName;
        $imagePathForDB = BASE_URL . '/Public/img/faculty/' . $ten_khoa . '/' . $fileName;

        if (!file_exists($facultyPath)) {
            if (!mkdir($facultyPath, 0777, true)) {
                $errors['system'] = 'Không thể tạo thư mục cho khoa ' . $ten_khoa;
            }
        }

        if (!in_array($fileExtension, $allowedTypes)) {
            $errors['avatar'] = 'Chỉ chấp nhận file JPG, JPEG, PNG hoặc GIF';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors['avatar'] = 'Kích thước file không được vượt quá 5MB';
        } else {
            $oldImagePath = BASE_PATH . str_replace(BASE_URL, '', $avatar);
            if (file_exists($oldImagePath) && strpos($oldImagePath, 'default_avatar.jpg') === false) {
                unlink($oldImagePath);
            }

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors['avatar'] = 'Không thể upload ảnh đại diện';
            } else {
                $has_changes = true;
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['error_message'] = implode(', ', array_values($errors));
    } else {
        // Cập nhật thông tin giảng viên
        $update_query = "UPDATE giangvien 
                         SET HoGiangVien = ?, TenGiangVien = ?, NgaySinh = ?, GioiTinh = ?, Email = ?, 
                             SoDienThoai = ?, GioiThieu = ?, AnhDaiDien = ?
                         WHERE MaGiangVien = ?";
        $update_stmt = mysqli_prepare($dbc, $update_query);
        mysqli_stmt_bind_param(
            $update_stmt,
            "sssisssss",
            $ho_giang_vien,
            $ten_giang_vien,
            $ngay_sinh_db,
            $gioi_tinh,
            $email,
            $so_dien_thoai,
            $gioi_thieu,
            $imagePathForDB,
            $user_id
        );
        if (mysqli_stmt_execute($update_stmt)) {
            if (mysqli_stmt_affected_rows($update_stmt) > 0) {
                $has_changes = true;
            }
        } else {
            $success = false;
            $errors[] = "Lỗi khi cập nhật thông tin giảng viên.";
        }
        mysqli_stmt_close($update_stmt);

        // Cập nhật cài đặt công khai
        $settings_query = "INSERT INTO giangvien_public_settings (MaGiangVien, ShowNgaySinh, ShowGioiTinh, ShowEmail, ShowSoDienThoai, ShowThanhTuu, ShowLichTiep, ShowNguyenCuuKhoaHoc) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           ShowNgaySinh = ?, ShowGioiTinh = ?, ShowEmail = ?, ShowSoDienThoai = ?, ShowThanhTuu = ?, ShowLichTiep = ?, ShowNguyenCuuKhoaHoc = ?";
        $settings_stmt = mysqli_prepare($dbc, $settings_query);
        mysqli_stmt_bind_param(
            $settings_stmt,
            "siiiiiiiiiiiiii",
            $user_id,
            $show_ngay_sinh,
            $show_gioi_tinh,
            $show_email,
            $show_so_dien_thoai,
            $show_thanh_tuu,
            $show_lich_tiep,
            $show_nckh,
            $show_ngay_sinh,
            $show_gioi_tinh,
            $show_email,
            $show_so_dien_thoai,
            $show_thanh_tuu,
            $show_lich_tiep,
            $show_nckh
        );
        if (mysqli_stmt_execute($settings_stmt)) {
            if (mysqli_stmt_affected_rows($settings_stmt) > 0) {
                $has_changes = true;
            }
        } else {
            $success = false;
            $errors[] = "Lỗi khi cập nhật cài đặt công khai.";
        }
        mysqli_stmt_close($settings_stmt);

        // Xử lý thành tựu
        if (!empty($_POST['new_thanh_tuu']) && is_array($_POST['new_thanh_tuu'])) {
            foreach ($_POST['new_thanh_tuu'] as $index => $ten_thanh_tuu) {
                $ten_thanh_tuu = trim($ten_thanh_tuu);
                $ngay_dat = isset($_POST['new_ngay_dat'][$index]) ? trim($_POST['new_ngay_dat'][$index]) : '';

                // Validate năm đạt
                if (!empty($ngay_dat)) {
                    if (preg_match("/^\d{4}$/", $ngay_dat) && $ngay_dat >= 1901 && $ngay_dat <= date('Y')) {
                        $ngay_dat_db = $ngay_dat;
                    } else {
                        $success = false;
                        $errors[] = "Năm đạt thành tựu phải là một năm hợp lệ (1901-" . date('Y') . ").";
                        continue;
                    }
                }

                if (!empty($ten_thanh_tuu) && !empty($ngay_dat)) {
                    $insert_thanh_tuu_query = "INSERT INTO thanhtuugiangvien (TenThanhTuu) VALUES (?)";
                    $insert_thanh_tuu_stmt = mysqli_prepare($dbc, $insert_thanh_tuu_query);
                    mysqli_stmt_bind_param($insert_thanh_tuu_stmt, "s", $ten_thanh_tuu);
                    if (mysqli_stmt_execute($insert_thanh_tuu_stmt)) {
                        $new_ma_thanh_tuu = mysqli_insert_id($dbc);

                        $insert_info_query = "INSERT INTO thongtinthanhtuugiangvien (MaThanhTuu, MaGiangVien, NamDat) VALUES (?, ?, ?)";
                        $insert_info_stmt = mysqli_prepare($dbc, $insert_info_query);
                        mysqli_stmt_bind_param($insert_info_stmt, "iss", $new_ma_thanh_tuu, $user_id, $ngay_dat_db);
                        if (mysqli_stmt_execute($insert_info_stmt)) {
                            $has_changes = true;
                        } else {
                            $success = false;
                            $errors[] = "Lỗi khi thêm thông tin thành tựu.";
                        }
                        mysqli_stmt_close($insert_info_stmt);
                    } else {
                        $success = false;
                        $errors[] = "Lỗi khi thêm thành tựu.";
                    }
                    mysqli_stmt_close($insert_thanh_tuu_stmt);
                }
            }
        }

        if (!empty($_POST['thanh_tuu'])) {
            foreach ($_POST['thanh_tuu'] as $ma_thanh_tuu => $data) {
                $ten_thanh_tuu = trim($data['ten']);
                $ngay_dat = trim($data['ngay_dat']);

                // Validate năm đạt
                if (!empty($ngay_dat)) {
                    if (preg_match("/^\d{4}$/", $ngay_dat) && $ngay_dat >= 1901 && $ngay_dat <= date('Y')) {
                        $ngay_dat_db = $ngay_dat;
                    } else {
                        $success = false;
                        $errors[] = "Năm đạt thành tựu phải là một năm hợp lệ (1901-" . date('Y') . ").";
                        continue;
                    }
                }

                if (!empty($ten_thanh_tuu) && !empty($ngay_dat)) {
                    $update_thanh_tuu_query = "UPDATE thanhtuugiangvien SET TenThanhTuu = ? WHERE MaThanhTuu = ?";
                    $update_thanh_tuu_stmt = mysqli_prepare($dbc, $update_thanh_tuu_query);
                    mysqli_stmt_bind_param($update_thanh_tuu_stmt, "si", $ten_thanh_tuu, $ma_thanh_tuu);
                    if (mysqli_stmt_execute($update_thanh_tuu_stmt)) {
                        if (mysqli_stmt_affected_rows($update_thanh_tuu_stmt) > 0) {
                            $has_changes = true;
                        }
                    } else {
                        $success = false;
                        $errors[] = "Lỗi khi cập nhật thành tựu.";
                    }
                    mysqli_stmt_close($update_thanh_tuu_stmt);

                    $update_info_query = "UPDATE thongtinthanhtuugiangvien SET NamDat = ? WHERE MaThanhTuu = ? AND MaGiangVien = ?";
                    $update_info_stmt = mysqli_prepare($dbc, $update_info_query);
                    mysqli_stmt_bind_param($update_info_stmt, "sis", $ngay_dat_db, $ma_thanh_tuu, $user_id);
                    if (mysqli_stmt_execute($update_info_stmt)) {
                        if (mysqli_stmt_affected_rows($update_info_stmt) > 0) {
                            $has_changes = true;
                        }
                    } else {
                        $success = false;
                        $errors[] = "Lỗi khi cập nhật thông tin thành tựu.";
                    }
                    mysqli_stmt_close($update_info_stmt);
                }
            }
        }

        if (!empty($_POST['delete_thanh_tuu'])) {
            foreach ($_POST['delete_thanh_tuu'] as $ma_thanh_tuu) {
                $delete_info_query = "DELETE FROM thongtinthanhtuugiangvien WHERE MaThanhTuu = ? AND MaGiangVien = ?";
                $delete_info_stmt = mysqli_prepare($dbc, $delete_info_query);
                mysqli_stmt_bind_param($delete_info_stmt, "is", $ma_thanh_tuu, $user_id);
                if (mysqli_stmt_execute($delete_info_stmt)) {
                    if (mysqli_stmt_affected_rows($delete_info_stmt) > 0) {
                        $has_changes = true;
                    }
                } else {
                    $success = false;
                    $errors[] = "Lỗi khi xóa thông tin thành tựu.";
                }
                mysqli_stmt_close($delete_info_stmt);

                $delete_thanh_tuu_query = "DELETE FROM thanhtuugiangvien WHERE MaThanhTuu = ?";
                $delete_thanh_tuu_stmt = mysqli_prepare($dbc, $delete_thanh_tuu_query);
                mysqli_stmt_bind_param($delete_thanh_tuu_stmt, "i", $ma_thanh_tuu);
                if (mysqli_stmt_execute($delete_thanh_tuu_stmt)) {
                    if (mysqli_stmt_affected_rows($delete_thanh_tuu_stmt) > 0) {
                        $has_changes = true;
                    }
                } else {
                    $success = false;
                    $errors[] = "Lỗi khi xóa thành tựu.";
                }
                mysqli_stmt_close($delete_thanh_tuu_stmt);
            }
        }

        // Xử lý công bố khoa học
        if (!empty($_POST['new_nckh']) && is_array($_POST['new_nckh'])) {
            foreach ($_POST['new_nckh'] as $index => $ten_nckh) {
                $ten_nckh = trim($ten_nckh);
                $hinh_thuc = isset($_POST['new_hinh_thuc'][$index]) ? trim($_POST['new_hinh_thuc'][$index]) : '';

                if (!empty($ten_nckh) && !empty($hinh_thuc)) {
                    $insert_nckh_query = "INSERT INTO nguyencuukhoahoc (TenNguyenCuuKhoaHoc, HinhThuc) VALUES (?, ?)";
                    $insert_nckh_stmt = mysqli_prepare($dbc, $insert_nckh_query);
                    mysqli_stmt_bind_param($insert_nckh_stmt, "ss", $ten_nckh, $hinh_thuc);
                    if (mysqli_stmt_execute($insert_nckh_stmt)) {
                        $new_ma_nckh = mysqli_insert_id($dbc);

                        $insert_info_query = "INSERT INTO thongtinnguyencuukhoahoc (MaNguyenCuuKhoaHoc, MaGiangVien) VALUES (?, ?)";
                        $insert_info_stmt = mysqli_prepare($dbc, $insert_info_query);
                        mysqli_stmt_bind_param($insert_info_stmt, "is", $new_ma_nckh, $user_id);
                        if (mysqli_stmt_execute($insert_info_stmt)) {
                            $has_changes = true;
                        } else {
                            $success = false;
                            $errors[] = "Lỗi khi thêm thông tin công bố khoa học.";
                        }
                        mysqli_stmt_close($insert_info_stmt);
                    } else {
                        $success = false;
                        $errors[] = "Lỗi khi thêm công bố khoa học.";
                    }
                    mysqli_stmt_close($insert_nckh_stmt);
                }
            }
        }

        if (!empty($_POST['nckh'])) {
            foreach ($_POST['nckh'] as $ma_nckh => $data) {
                $ten_nckh = trim($data['ten']);
                $hinh_thuc = trim($data['hinh_thuc']);

                if (!empty($ten_nckh) && !empty($hinh_thuc)) {
                    $update_nckh_query = "UPDATE nguyencuukhoahoc SET TenNguyenCuuKhoaHoc = ?, HinhThuc = ? WHERE MaNguyenCuuKhoaHoc = ?";
                    $update_nckh_stmt = mysqli_prepare($dbc, $update_nckh_query);
                    mysqli_stmt_bind_param($update_nckh_stmt, "ssi", $ten_nckh, $hinh_thuc, $ma_nckh);
                    if (mysqli_stmt_execute($update_nckh_stmt)) {
                        if (mysqli_stmt_affected_rows($update_nckh_stmt) > 0) {
                            $has_changes = true;
                        }
                    } else {
                        $success = false;
                        $errors[] = "Lỗi khi cập nhật công bố khoa học.";
                    }
                    mysqli_stmt_close($update_nckh_stmt);
                }
            }
        }

        if (!empty($_POST['delete_nckh'])) {
            foreach ($_POST['delete_nckh'] as $ma_nckh) {
                $delete_info_query = "DELETE FROM thongtinnguyencuukhoahoc WHERE MaNguyenCuuKhoaHoc = ? AND MaGiangVien = ?";
                $delete_info_stmt = mysqli_prepare($dbc, $delete_info_query);
                mysqli_stmt_bind_param($delete_info_stmt, "is", $ma_nckh, $user_id);
                if (mysqli_stmt_execute($delete_info_stmt)) {
                    if (mysqli_stmt_affected_rows($delete_info_stmt) > 0) {
                        $has_changes = true;
                    }
                } else {
                    $success = false;
                    $errors[] = "Lỗi khi xóa thông tin công bố khoa học.";
                }
                mysqli_stmt_close($delete_info_stmt);

                $delete_nckh_query = "DELETE FROM nguyencuukhoahoc WHERE MaNguyenCuuKhoaHoc = ?";
                $delete_nckh_stmt = mysqli_prepare($dbc, $delete_nckh_query);
                mysqli_stmt_bind_param($delete_nckh_stmt, "i", $ma_nckh);
                if (mysqli_stmt_execute($delete_nckh_stmt)) {
                    if (mysqli_stmt_affected_rows($delete_nckh_stmt) > 0) {
                        $has_changes = true;
                    }
                } else {
                    $success = false;
                    $errors[] = "Lỗi khi xóa công bố khoa học.";
                }
                mysqli_stmt_close($delete_nckh_stmt);
            }
        }

        // Xử lý lịch tiếp sinh viên
        if (!empty($_POST['new_lich_tiep']) && is_array($_POST['new_lich_tiep'])) {
            $check_count_query = "SELECT COUNT(*) as count FROM lichtiepsinhvien WHERE MaGiangVien = ?";
            $check_count_stmt = mysqli_prepare($dbc, $check_count_query);
            mysqli_stmt_bind_param($check_count_stmt, "s", $user_id);
            mysqli_stmt_execute($check_count_stmt);
            $count_result = mysqli_stmt_get_result($check_count_stmt);
            $row = mysqli_fetch_assoc($count_result);
            mysqli_stmt_close($check_count_stmt);

            if ($row['count'] == 0) {
                $lich = $_POST['new_lich_tiep'][0];
                $thu_tiep = trim($lich['thu_tiep']);
                $gio_bat_dau = trim($lich['gio_bat_dau']);
                $gio_ket_thuc = trim($lich['gio_ket_thuc']);
                $dia_diem = trim($lich['dia_diem']);

                if (!empty($thu_tiep) && !empty($gio_bat_dau) && !empty($gio_ket_thuc)) {
                    $insert_lich_query = "INSERT INTO lichtiepsinhvien (MaGiangVien, ThuTiepSinhVien, GioBatDau, GioKetThuc, DiaDiem) 
                                          VALUES (?, ?, ?, ?, ?)";
                    $insert_lich_stmt = mysqli_prepare($dbc, $insert_lich_query);
                    mysqli_stmt_bind_param($insert_lich_stmt, "sssss", $user_id, $thu_tiep, $gio_bat_dau, $gio_ket_thuc, $dia_diem);
                    if (mysqli_stmt_execute($insert_lich_stmt)) {
                        $has_changes = true;
                    } else {
                        $success = false;
                        $errors[] = "Lỗi khi thêm lịch tiếp sinh viên.";
                    }
                    mysqli_stmt_close($insert_lich_stmt);
                }
            } else {
                $errors[] = "Mỗi giảng viên chỉ được phép có một lịch tiếp sinh viên.";
            }
        }

        if (!empty($_POST['lich_tiep'])) {
            foreach ($_POST['lich_tiep'] as $index => $lich) {
                $old_thu_tiep = trim($lich['old_thu_tiep']);
                $old_gio_bat_dau = trim($lich['old_gio_bat_dau']);
                $old_gio_ket_thuc = trim($lich['old_gio_ket_thuc']);
                $thu_tiep = trim($lich['thu_tiep']);
                $gio_bat_dau = trim($lich['gio_bat_dau']);
                $gio_ket_thuc = trim($lich['gio_ket_thuc']);
                $dia_diem = trim($lich['dia_diem']);

                if (!empty($thu_tiep) && !empty($gio_bat_dau) && !empty($gio_ket_thuc)) {
                    $update_lich_query = "UPDATE lichtiepsinhvien 
                                          SET ThuTiepSinhVien = ?, GioBatDau = ?, GioKetThuc = ?, DiaDiem = ? 
                                          WHERE MaGiangVien = ? AND ThuTiepSinhVien = ? AND GioBatDau = ? AND GioKetThuc = ?";
                    $update_lich_stmt = mysqli_prepare($dbc, $update_lich_query);
                    mysqli_stmt_bind_param(
                        $update_lich_stmt,
                        "ssssssss",
                        $thu_tiep,
                        $gio_bat_dau,
                        $gio_ket_thuc,
                        $dia_diem,
                        $user_id,
                        $old_thu_tiep,
                        $old_gio_bat_dau,
                        $old_gio_ket_thuc
                    );
                    if (mysqli_stmt_execute($update_lich_stmt)) {
                        if (mysqli_stmt_affected_rows($update_lich_stmt) > 0) {
                            $has_changes = true;
                        }
                    } else {
                        $success = false;
                        $errors[] = "Lỗi khi cập nhật lịch tiếp sinh viên.";
                    }
                    mysqli_stmt_close($update_lich_stmt);
                }
            }
        }

        if (!empty($_POST['delete_lich_tiep'])) {
            foreach ($_POST['delete_lich_tiep'] as $lich) {
                $parts = explode('|', $lich);
                $thu_tiep = $parts[0];
                $gio_bat_dau = $parts[1];
                $gio_ket_thuc = $parts[2];

                $delete_lich_query = "DELETE FROM lichtiepsinhvien 
                                      WHERE MaGiangVien = ? AND ThuTiepSinhVien = ? AND GioBatDau = ? AND GioKetThuc = ?";
                $delete_lich_stmt = mysqli_prepare($dbc, $delete_lich_query);
                mysqli_stmt_bind_param($delete_lich_stmt, "ssss", $user_id, $thu_tiep, $gio_bat_dau, $gio_ket_thuc);
                if (mysqli_stmt_execute($delete_lich_stmt)) {
                    if (mysqli_stmt_affected_rows($delete_lich_stmt) > 0) {
                        $has_changes = true;
                    }
                } else {
                    $success = false;
                    $errors[] = "Lỗi khi xóa lịch tiếp sinh viên.";
                }
                mysqli_stmt_close($delete_lich_stmt);
            }
        }

        // Kiểm tra kết quả và gửi thông báo
        if ($success && empty($errors) && $has_changes) {
            $_SESSION['success_message'] = "Cập nhật thông tin, thành tựu, lịch tiếp sinh viên và công bố khoa học thành công!";
        } elseif ($success && empty($errors)) {
            $_SESSION['success_message'] = "Không có thay đổi nào được thực hiện.";
        } else {
            $_SESSION['error_message'] = !empty($errors) ? implode(', ', $errors) : "Có lỗi xảy ra khi cập nhật. Vui lòng thử lại.";
        }

        header("Location: profile.php");
        exit();
    }
}

mysqli_close($dbc);

include(BASE_PATH . '/Layout/header.php');
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa thông tin cá nhân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .update-form {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .update-form h2,
        .update-form h4,
        .update-form h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            font-size: 16px;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-back {
            background-color: #2C69A0;
            color: #fff;
            border-radius: 10px;
            padding: 12px 24px;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-back:hover {
            background-color: #1a4e80;
            color: #fff;
            text-decoration: none;
        }

        .avatar-preview img {
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e0e0e0;
            width: 150px;
            height: 150px;
        }

        .form-check-label {
            font-size: 14px;
            color: #34495e;
            margin-left: 10px;
        }

        .table-container {
            margin-bottom: 20px;
        }

        .table {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .table th,
        .table td {
            vertical-align: middle;
            padding: 12px;
            font-size: 14px;
        }

        .table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }

        .table td input,
        .table td select,
        .table td textarea {
            width: 100%;
            padding: 6px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            font-size: 14px;
        }

        .table td .form-check {
            margin: 0;
        }

        .btn-add {
            background-color: #28a745;
            color: #fff;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .btn-add:hover {
            background-color: #218838;
        }

        .btn-remove {
            background-color: #dc3545;
            color: #fff;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-remove:hover {
            background-color: #c82333;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .field-with-toggle {
            margin-bottom: 15px;
        }

        .field-with-toggle .form-check.form-switch {
            margin-top: 8px;
            padding-left: 2.5em;
        }

        .field-with-toggle .form-check-input {
            height: 24px;
            width: 44px;
            margin-top: 0;
            cursor: pointer;
        }

        .field-with-toggle .form-check-label {
            font-size: 14px;
            color: #34495e;
        }

        @media (max-width: 768px) {
            .update-form {
                padding: 20px;
            }

            .avatar-preview img {
                width: 120px;
                height: 120px;
            }

            .table th,
            .table td {
                font-size: 12px;
                padding: 8px;
            }

            .table td input,
            .table td select,
            .table td textarea {
                font-size: 12px;
                padding: 4px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .field-with-toggle .form-check.form-switch {
                padding-left: 2em;
            }

            .field-with-toggle .form-check-input {
                height: 20px;
                width: 40px;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="update-form">
            <a href="profile.php" class="btn-back mb-4"><i class="fas fa-arrow-left"></i> Quay lại hồ sơ</a>
            <h2>Chỉnh sửa thông tin cá nhân</h2>
            <form method="POST" enctype="multipart/form-data" id="profileForm">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="avatar-preview mb-3">
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Ảnh đại diện" class="img-fluid">
                        </div>
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ho_giang_vien" class="form-label">Họ</label>
                                <input type="text" class="form-control" id="ho_giang_vien" name="ho_giang_vien"
                                    value="<?php echo htmlspecialchars($ho_giang_vien); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ten_giang_vien" class="form-label">Tên</label>
                                <input type="text" class="form-control" id="ten_giang_vien" name="ten_giang_vien"
                                    value="<?php echo htmlspecialchars($ten_giang_vien); ?>" required>
                            </div>
                        </div>
                        <div class="field-with-toggle">
                            <label for="ngay_sinh" class="form-label">Ngày sinh</label>
                            <input type="text" class="form-control datepicker" id="ngay_sinh" name="ngay_sinh"
                                value="<?php echo !empty($ngay_sinh) ? htmlspecialchars(date('d/m/Y', strtotime($ngay_sinh))) : ''; ?>" placeholder="dd/mm/yyyy">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="show_ngay_sinh" name="show_ngay_sinh"
                                    <?php echo $public_settings['ShowNgaySinh'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="show_ngay_sinh">Hiển thị công khai</label>
                            </div>
                        </div>
                        <div class="field-with-toggle">
                            <label for="gioi_tinh" class="form-label">Giới tính</label>
                            <select class="form-select" id="gioi_tinh" name="gioi_tinh">
                                <option value="1" <?php echo $gioi_tinh == 1 ? 'selected' : ''; ?>>Nam</option>
                                <option value="2" <?php echo $gioi_tinh == 2 ? 'selected' : ''; ?>>Nữ</option>
                            </select>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="show_gioi_tinh" name="show_gioi_tinh"
                                    <?php echo $public_settings['ShowGioiTinh'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="show_gioi_tinh">Hiển thị công khai</label>
                            </div>
                        </div>
                        <div class="field-with-toggle">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo htmlspecialchars($email); ?>" required>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="show_email" name="show_email"
                                    <?php echo $public_settings['ShowEmail'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="show_email">Hiển thị công khai</label>
                            </div>
                        </div>
                        <div class="field-with-toggle">
                            <label for="so_dien_thoai" class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control" id="so_dien_thoai" name="so_dien_thoai"
                                value="<?php echo htmlspecialchars($so_dien_thoai); ?>">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="show_so_dien_thoai" name="show_so_dien_thoai"
                                    <?php echo $public_settings['ShowSoDienThoai'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="show_so_dien_thoai">Hiển thị công khai</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="gioi_thieu" class="form-label">Giới thiệu</label>
                            <textarea class="form-control" id="gioi_thieu" name="gioi_thieu" rows="5"><?php echo htmlspecialchars($gioi_thieu); ?></textarea>
                        </div>

                        <!-- Quản lý thành tựu -->
                        <div class="section-header">
                            <h4>Quản lý thành tựu</h4>
                            <div class="d-flex align-items-center gap-3">
                                <button type="button" class="btn btn-add" onclick="addNewThanhTuuRow()">
                                    <i class="fas fa-plus"></i> Thêm thành tựu
                                </button>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_thanh_tuu" name="show_thanh_tuu"
                                        <?php echo $public_settings['ShowThanhTuu'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_thanh_tuu">Hiển thị công khai</label>
                                </div>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 50%;">Tên thành tựu</th>
                                        <th style="width: 20%;">Năm đạt</th>
                                        <th style="width: 10%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="existing-thanh-tuu">
                                    <?php if (!empty($thanh_tuu_list)): ?>
                                        <?php foreach ($thanh_tuu_list as $thanh_tuu): ?>
                                            <tr data-id="<?php echo $thanh_tuu['MaThanhTuu']; ?>">
                                                <td>
                                                    <input type="text" class="form-control"
                                                        name="thanh_tuu[<?php echo $thanh_tuu['MaThanhTuu']; ?>][ten]"
                                                        value="<?php echo htmlspecialchars($thanh_tuu['TenThanhTuu']); ?>" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control"
                                                        name="thanh_tuu[<?php echo $thanh_tuu['MaThanhTuu']; ?>][ngay_dat]"
                                                        value="<?php echo !empty($thanh_tuu['NamDat']) ? htmlspecialchars($thanh_tuu['NamDat']) : ''; ?>" required
                                                        min="1901" max="<?php echo date('Y'); ?>" placeholder="YYYY">
                                                </td>
                                                <td>
                                                    <div class="form-check">
                                                        <input style="width: 10%;" type="checkbox" class="form-check-input delete-thanh-tuu"
                                                            name="delete_thanh_tuu[]"
                                                            value="<?php echo $thanh_tuu['MaThanhTuu']; ?>">
                                                        <label class="form-check-label">Xóa</label>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Chưa có thành tựu nào.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tbody id="new-thanh-tuu"></tbody>
                            </table>
                        </div>

                        <!-- Quản lý lịch tiếp sinh viên -->
                        <div class="section-header">
                            <h4>Quản lý lịch tiếp sinh viên</h4>
                            <div class="d-flex align-items-center gap-3">
                                <?php if (empty($lich_tiep_list)): ?>
                                    <button type="button" class="btn btn-add" id="add-lich-tiep-btn" onclick="addNewLichTiepRow()">
                                        <i class="fas fa-plus"></i> Thêm lịch tiếp
                                    </button>
                                <?php endif; ?>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_lich_tiep" name="show_lich_tiep"
                                        <?php echo $public_settings['ShowLichTiep'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_lich_tiep">Hiển thị công khai</label>
                                </div>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 20%;">Thứ</th>
                                        <th style="width: 20%;">Giờ bắt đầu</th>
                                        <th style="width: 20%;">Giờ kết thúc</th>
                                        <th style="width: 20%;">Địa điểm</th>
                                        <th style="width: 10%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="existing-lich-tiep">
                                    <?php if (!empty($lich_tiep_list)): ?>
                                        <?php foreach ($lich_tiep_list as $index => $lich): ?>
                                            <tr data-index="<?php echo $index; ?>">
                                                <td>
                                                    <select class="form-control" name="lich_tiep[<?php echo $index; ?>][thu_tiep]" required>
                                                        <option value="Thứ 2" <?php echo $lich['ThuTiepSinhVien'] == 'Thứ 2' ? 'selected' : ''; ?>>Thứ 2</option>
                                                        <option value="Thứ 3" <?php echo $lich['ThuTiepSinhVien'] == 'Thứ 3' ? 'selected' : ''; ?>>Thứ 3</option>
                                                        <option value="Thứ 4" <?php echo $lich['ThuTiepSinhVien'] == 'Thứ 4' ? 'selected' : ''; ?>>Thứ 4</option>
                                                        <option value="Thứ 5" <?php echo $lich['ThuTiepSinhVien'] == 'Thứ 5' ? 'selected' : ''; ?>>Thứ 5</option>
                                                        <option value="Thứ 6" <?php echo $lich['ThuTiepSinhVien'] == 'Thứ 6' ? 'selected' : ''; ?>>Thứ 6</option>
                                                        <option value="Thứ 7" <?php echo $lich['ThuTiepSinhVien'] == 'Thứ 7' ? 'selected' : ''; ?>>Thứ 7</option>
                                                        <option value="Chủ Nhật" <?php echo $lich['ThuTiepSinhVien'] == 'Chủ Nhật' ? 'selected' : ''; ?>>Chủ Nhật</option>
                                                    </select>
                                                    <input type="hidden" name="lich_tiep[<?php echo $index; ?>][old_thu_tiep]"
                                                        value="<?php echo htmlspecialchars($lich['ThuTiepSinhVien']); ?>">
                                                </td>
                                                <td>
                                                    <input type="time" class="form-control"
                                                        name="lich_tiep[<?php echo $index; ?>][gio_bat_dau]"
                                                        value="<?php echo htmlspecialchars($lich['GioBatDau']); ?>" required>
                                                    <input type="hidden" name="lich_tiep[<?php echo $index; ?>][old_gio_bat_dau]"
                                                        value="<?php echo htmlspecialchars($lich['GioBatDau']); ?>">
                                                </td>
                                                <td>
                                                    <input type="time" class="form-control"
                                                        name="lich_tiep[<?php echo $index; ?>][gio_ket_thuc]"
                                                        value="<?php echo htmlspecialchars($lich['GioKetThuc']); ?>" required>
                                                    <input type="hidden" name="lich_tiep[<?php echo $index; ?>][old_gio_ket_thuc]"
                                                        value="<?php echo htmlspecialchars($lich['GioKetThuc']); ?>">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control"
                                                        name="lich_tiep[<?php echo $index; ?>][dia_diem]"
                                                        value="<?php echo htmlspecialchars($lich['DiaDiem']); ?>">
                                                </td>
                                                <td>
                                                    <div class="form-check">
                                                        <input style="width: 10%;" type="checkbox" class="form-check-input delete-lich-tiep"
                                                            name="delete_lich_tiep[]"
                                                            value="<?php echo htmlspecialchars($lich['ThuTiepSinhVien'] . '|' . $lich['GioBatDau'] . '|' . $lich['GioKetThuc']); ?>">
                                                        <label class="form-check-label">Xóa</label>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Chưa có lịch tiếp sinh viên nào.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tbody id="new-lich-tiep"></tbody>
                            </table>
                        </div>

                        <!-- Quản lý công bố khoa học -->
                        <div class="section-header">
                            <h4>Quản lý công bố khoa học</h4>
                            <div class="d-flex align-items-center gap-3">
                                <button type="button" class="btn btn-add" onclick="addNewNckhRow()">
                                    <i class="fas fa-plus"></i> Thêm công bố khoa học
                                </button>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_nckh" name="show_nckh"
                                        <?php echo $public_settings['ShowNguyenCuuKhoaHoc'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_nckh">Hiển thị công khai</label>
                                </div>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 70%;">Tên công bố khoa học</th>
                                        <th style="width: 20%;">Hình thức</th>
                                        <th style="width: 10%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="existing-nckh">
                                    <?php if (!empty($nckh_list)): ?>
                                        <?php foreach ($nckh_list as $nckh): ?>
                                            <tr data-id="<?php echo $nckh['MaNguyenCuuKhoaHoc']; ?>">
                                                <td>
                                                    <input type="text" class="form-control"
                                                        name="nckh[<?php echo $nckh['MaNguyenCuuKhoaHoc']; ?>][ten]"
                                                        value="<?php echo htmlspecialchars($nckh['TenNguyenCuuKhoaHoc']); ?>" required>
                                                </td>
                                                <td>
                                                    <select class="form-control" name="nckh[<?php echo $nckh['MaNguyenCuuKhoaHoc']; ?>][hinh_thuc]" required>
                                                        <option value="Bài báo" <?php echo $nckh['HinhThuc'] == 'Bài báo' ? 'selected' : ''; ?>>Bài báo</option>
                                                        <option value="Báo cáo Hội thảo" <?php echo $nckh['HinhThuc'] == 'Báo cáo Hội thảo' ? 'selected' : ''; ?>>Báo cáo Hội thảo</option>
                                                        <option value="Hội nghị" <?php echo $nckh['HinhThuc'] == 'Hội nghị' ? 'selected' : ''; ?>>Hội nghị</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="form-check">
                                                        <input style="width: 10%;" type="checkbox" class="form-check-input delete-nckh"
                                                            name="delete_nckh[]"
                                                            value="<?php echo $nckh['MaNguyenCuuKhoaHoc']; ?>">
                                                        <label class="form-check-label">Xóa</label>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Chưa có công bố khoa học nào.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tbody id="new-nckh"></tbody>
                            </table>
                        </div>

                        <!-- Quản lý kế hoạch hướng dẫn -->
                        <div class="section-header">
                            <h4>Quản lý kế hoạch hướng dẫn</h4>
                        </div>
                        <div class="table-container">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 70%;">Kế hoạch hướng dẫn</th>
                                        <th style="width: 30%;">Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody id="existing-huongdan">
                                    <?php if (!empty($huongdan_list)): ?>
                                        <?php foreach ($huongdan_list as $huongdan): ?>
                                            <tr data-id="<?php echo $huongdan['MaHuongDan']; ?>">
                                                <td>
                                                    <textarea class="form-control" readonly><?php echo htmlspecialchars($huongdan['KeHoachHuongDan']); ?></textarea>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control datepicker" value="<?php echo !empty($huongdan['ThoiGianHuongDan']) ? htmlspecialchars(date('d/m/Y', strtotime($huongdan['ThoiGianHuongDan']))) : ''; ?>" readonly>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center">Chưa có kế hoạch hướng dẫn công khai nào.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">Lưu thay đổi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Khởi tạo Datepicker cho các trường ngày tháng (ngoại trừ cột năm đạt thành tựu)
            $(".datepicker").not("[name*='ngay_dat']").datepicker({
                dateFormat: "dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                yearRange: "1900:<?php echo date('Y'); ?>",
                maxDate: new Date()
            });
        });

        // Hiển thị thông báo nếu có
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '<?php echo addslashes($_SESSION['success_message']); ?>',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: '<?php echo addslashes($_SESSION['error_message']); ?>',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        // Thêm hàng thành tựu mới
        let thanhTuuIndex = 0;

        function addNewThanhTuuRow() {
            const tbody = document.getElementById('new-thanh-tuu');
            const newRow = document.createElement('tr');
            newRow.className = 'new-thanh-tuu-row';
            newRow.innerHTML = `
                <td>
                    <input type="text" class="form-control" name="new_thanh_tuu[${thanhTuuIndex}]" required>
                </td>
                <td>
                    <input type="number" class="form-control" name="new_ngay_dat[${thanhTuuIndex}]" required min="1901" max="<?php echo date('Y'); ?>" placeholder="YYYY">
                </td>
                <td>
                    <button type="button" class="btn btn-remove btn-sm" onclick="removeRow(this)">Xóa</button>
                </td>
            `;
            tbody.appendChild(newRow);
            thanhTuuIndex++;
        }

        // Thêm hàng lịch tiếp sinh viên mới
        function addNewLichTiepRow() {
            const tbody = document.getElementById('new-lich-tiep');
            const newRow = document.createElement('tr');
            newRow.className = 'new-lich-tiep-row';
            newRow.innerHTML = `
                <td>
                    <select class="form-control" name="new_lich_tiep[0][thu_tiep]" required>
                        <option value="Thứ 2">Thứ 2</option>
                        <option value="Thứ 3">Thứ 3</option>
                        <option value="Thứ 4">Thứ 4</option>
                        <option value="Thứ 5">Thứ 5</option>
                        <option value="Thứ 6">Thứ 6</option>
                        <option value="Thứ 7">Thứ 7</option>
                        <option value="Chủ Nhật">Chủ Nhật</option>
                    </select>
                </td>
                <td>
                    <input type="time" class="form-control" name="new_lich_tiep[0][gio_bat_dau]" required>
                </td>
                <td>
                    <input type="time" class="form-control" name="new_lich_tiep[0][gio_ket_thuc]" required>
                </td>
                <td>
                    <input type="text" class="form-control" name="new_lich_tiep[0][dia_diem]">
                </td>
                <td>
                    <button type="button" class="btn btn-remove btn-sm" onclick="removeRow(this)">Xóa</button>
                </td>
            `;
            tbody.appendChild(newRow);

            const addButton = document.getElementById('add-lich-tiep-btn');
            if (addButton) {
                addButton.style.display = 'none';
            }
        }

        // Thêm hàng công bố khoa học mới
        let nckhIndex = 0;

        function addNewNckhRow() {
            const tbody = document.getElementById('new-nckh');
            const newRow = document.createElement('tr');
            newRow.className = 'new-nckh-row';
            newRow.innerHTML = `
                <td>
                    <input type="text" class="form-control" name="new_nckh[${nckhIndex}]" required>
                </td>
                <td>
                    <select class="form-control" name="new_hinh_thuc[${nckhIndex}]" required>
                        <option value="Bài báo">Bài báo</option>
                        <option value="Báo cáo Hội thảo">Báo cáo Hội thảo</option>
                        <option value="Hội nghị">Hội nghị</option>
                    </select>
                </td>
                <td>
                    <button type="button" class="btn btn-remove btn-sm" onclick="removeRow(this)">Xóa</button>
                </td>
            `;
            tbody.appendChild(newRow);
            nckhIndex++;
        }

        // Xóa hàng
        function removeRow(button) {
            const row = button.closest('tr');
            const tbody = row.parentElement;
            row.remove();

            if (row.classList.contains('new-lich-tiep-row')) {
                const addButton = document.getElementById('add-lich-tiep-btn');
                if (addButton) {
                    addButton.style.display = 'inline-flex';
                }
            }

            if (tbody.querySelectorAll('tr').length === 0 && tbody.id !== 'existing-thanh-tuu' && tbody.id !== 'existing-lich-tiep' && tbody.id !== 'existing-nckh' && tbody.id !== 'existing-huongdan') {
                tbody.innerHTML = '';
            }
        }

        // Gửi form với xác nhận nếu có dữ liệu được đánh dấu xóa
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const deleteThanhTuuCheckboxes = document.querySelectorAll('.delete-thanh-tuu:checked');
            const deleteLichTiepCheckboxes = document.querySelectorAll('.delete-lich-tiep:checked');
            const deleteNckhCheckboxes = document.querySelectorAll('.delete-nckh:checked');

            if (deleteThanhTuuCheckboxes.length > 0 || deleteLichTiepCheckboxes.length > 0 || deleteNckhCheckboxes.length > 0) {
                e.preventDefault();
                Swal.fire({
                    title: 'Bạn có chắc chắn muốn lưu các thay đổi?',
                    text: 'Các thay đổi bao gồm xóa sẽ được áp dụng!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            }
        });
    </script>
</body>

</html>

<?php include(BASE_PATH . '/Layout/footer.php'); ?>