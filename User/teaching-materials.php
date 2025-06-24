<?php
session_start();
require_once '../config.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Hàm chuẩn hóa tên file (dùng cho tên file, không dùng cho tên thư mục)
function sanitizeFileName($string)
{
    // Chuyển tiếng Việt có dấu thành không dấu
    $string = str_replace(
        ['á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'đ', 'é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'í', 'ì', 'ỉ', 'ĩ', 'ị', 'ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ'],
        ['a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'd', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y'],
        strtolower($string)
    );
    // Thay thế dấu cách và ký tự đặc biệt bằng gạch dưới
    $string = preg_replace('/[^A-Za-z0-9]/', '_', $string);
    // Loại bỏ nhiều dấu gạch dưới liên tiếp
    $string = preg_replace('/_+/', '_', $string);
    // Xóa dấu gạch dưới ở đầu và cuối
    return trim($string, '_');
}

// Kích hoạt ghi log lỗi để debug
ini_set('display_errors', 0); // Không hiển thị lỗi trên màn hình
ini_set('log_errors', 1); // Bật ghi log lỗi
ini_set('error_log', BASE_PATH . '/logs/error.log'); // Đặt đường dẫn file log

// Kiểm tra kết nối CSDL
if (!$dbc) {
    error_log("Lỗi kết nối CSDL: " . mysqli_connect_error());
    die("Lỗi kết nối CSDL: " . mysqli_connect_error());
}

// Kiểm tra đăng xuất
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Kiểm tra đăng nhập
$logged_in = isset($_SESSION['user_id']);
if (!$logged_in) {
    header("Location: login.php");
    exit();
}

// Lấy thông tin người dùng và MaKhoa
$user_id = $_SESSION['user_id'];
$query = "SELECT g.HoGiangVien, g.TenGiangVien, g.AnhDaiDien, g.MaKhoa, t.Quyen 
          FROM giangvien g
          JOIN taikhoan t ON g.MaGiangVien = t.MaTaiKhoan
          WHERE t.MaTaiKhoan = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $full_name = $row['HoGiangVien'] . ' ' . $row['TenGiangVien'];
    $avatar = !empty($row['AnhDaiDien']) ? $row['AnhDaiDien'] : '/Public/img/default_avatar.jpg';
    $ma_khoa = $row['MaKhoa'];
    $quyen = $row['Quyen'];
} else {
    $full_name = "Không xác định";
    $avatar = BASE_URL . '/Public/img/default_avatar.jpg';
    $ma_khoa = '';
    $quyen = '';
}
mysqli_stmt_close($stmt);

// Lấy danh sách học phần thuộc khoa của giảng viên
$query = "SELECT hp.MaHocPhan, hp.TenHocPhan 
          FROM hocphan hp 
          JOIN khoa k ON hp.MaKhoa = k.MaKhoa 
          WHERE hp.TrangThai = 1 AND k.TrangThai = 1 AND hp.MaKhoa = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, "s", $ma_khoa);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$subjects = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subjects[] = $row;
}
mysqli_stmt_close($stmt);

// Xử lý thêm, cập nhật, xóa tài liệu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Đường dẫn thư mục gốc
        $upload_dir = BASE_PATH . '/Document/';
        // Đảm bảo thư mục gốc tồn tại và có quyền đúng
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true) || !is_writable($upload_dir)) {
                error_log("Không thể tạo hoặc ghi vào thư mục gốc: $upload_dir");
                $_SESSION['error_message'] = "Không thể tạo thư mục lưu trữ.";
                header("Location: teaching-materials.php");
                exit();
            }
            chown($upload_dir, 'www-data');
            chgrp($upload_dir, 'www-data');
        }

        if ($_POST['action'] === 'add') {
            $subject_id = trim($_POST['subject_id'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $description = $_POST['description'] ?? '';

            // Kiểm tra dữ liệu đầu vào
            if (empty($subject_id) || empty($title)) {
                error_log("Dữ liệu đầu vào không hợp lệ: subject_id=$subject_id, title=$title");
                $_SESSION['error_message'] = "Vui lòng điền đầy đủ học phần và tên tài liệu.";
                header("Location: teaching-materials.php");
                exit();
            }

            // Lấy tên học phần và tên khoa từ MaHocPhan
            $query = "SELECT hp.TenHocPhan, k.TenKhoa 
                      FROM hocphan hp 
                      JOIN khoa k ON hp.MaKhoa = k.MaKhoa 
                      WHERE hp.MaHocPhan = ?";
            $stmt = mysqli_prepare($dbc, $query);
            if (!$stmt) {
                error_log("Lỗi chuẩn bị truy vấn (lấy tên học phần và khoa): " . mysqli_error($dbc));
                die("Lỗi chuẩn bị truy vấn: " . mysqli_error($dbc));
            }
            mysqli_stmt_bind_param($stmt, "s", $subject_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $subject_name = $row['TenHocPhan'];
                $faculty_name = $row['TenKhoa'];
            } else {
                error_log("Không tìm thấy học phần hoặc khoa cho MaHocPhan: $subject_id");
                $_SESSION['error_message'] = "Không tìm thấy học phần hoặc khoa.";
                header("Location: teaching-materials.php");
                exit();
            }
            mysqli_stmt_close($stmt);

            // Xử lý file tải lên
            if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['file_upload']['tmp_name'];
                $file_name = basename($_FILES['file_upload']['name']);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                // Kiểm tra đuôi file
                if (empty($file_ext)) {
                    error_log("Không thể xác định loại file: $file_name");
                    $_SESSION['error_message'] = "Không thể xác định loại file.";
                    header("Location: teaching-materials.php");
                    exit();
                }

                // Chuẩn hóa tên file
                $new_file_name = sanitizeFileName($title) . '.' . $file_ext;

                // Tạo thư mục con với cấu trúc [TenKhoa]/[TenHocPhan]
                $faculty_dir = $upload_dir . $faculty_name . '/';
                $subject_dir = $faculty_dir . $subject_name . '/';
                if (!is_dir($faculty_dir)) {
                    if (!mkdir($faculty_dir, 0755, true) || !is_writable($faculty_dir)) {
                        error_log("Không thể tạo hoặc ghi vào thư mục khoa: $faculty_dir");
                        $_SESSION['error_message'] = "Không thể tạo thư mục khoa.";
                        header("Location: teaching-materials.php");
                        exit();
                    }
                    chown($faculty_dir, 'www-data');
                    chgrp($faculty_dir, 'www-data');
                }
                if (!is_dir($subject_dir)) {
                    if (!mkdir($subject_dir, 0755, true) || !is_writable($subject_dir)) {
                        error_log("Không thể tạo hoặc ghi vào thư mục học phần: $subject_dir");
                        $_SESSION['error_message'] = "Không thể tạo thư mục học phần.";
                        header("Location: teaching-materials.php");
                        exit();
                    }
                    chown($subject_dir, 'www-data');
                    chgrp($subject_dir, 'www-data');
                }

                // Đường dẫn lưu trong CSDL
                $file_path = '/Document/' . $faculty_name . '/' . $subject_name . '/' . $new_file_name;

                // Kiểm tra loại file
                $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'mp4', 'avi', 'mov'];
                if (!in_array($file_ext, $allowed_types)) {
                    error_log("Loại file không được phép: $file_ext");
                    $_SESSION['error_message'] = "Loại file không được phép. Chỉ chấp nhận: " . implode(', ', $allowed_types);
                    header("Location: teaching-materials.php");
                    exit();
                }

                // Ánh xạ đuôi file với loại tài liệu
                $fileTypeMap = [
                    'pdf' => 'Giáo trình',
                    'doc' => 'Giáo trình',
                    'docx' => 'Giáo trình',
                    'ppt' => 'Slide',
                    'pptx' => 'Slide',
                    'mp4' => 'Video',
                    'avi' => 'Video',
                    'mov' => 'Video'
                ];
                $type = isset($fileTypeMap[$file_ext]) ? $fileTypeMap[$file_ext] : 'Khác';

                // Kiểm tra xem file đã tồn tại chưa, nếu có thì thêm hậu tố
                $base_file_name = sanitizeFileName($title);
                $counter = 1;
                while (file_exists($subject_dir . $new_file_name)) {
                    $new_file_name = $base_file_name . '_' . $counter . '.' . $file_ext;
                    $file_path = '/Document/' . $faculty_name . '/' . $subject_name . '/' . $new_file_name;
                    $counter++;
                }

                // Di chuyển file vào thư mục học phần
                if (!move_uploaded_file($file_tmp, $subject_dir . $new_file_name)) {
                    error_log("Lỗi khi di chuyển file: $file_tmp -> $subject_dir$new_file_name");
                    $_SESSION['error_message'] = "Lỗi khi tải file lên. Kiểm tra quyền thư mục hoặc hỗ trợ ký tự Unicode.";
                    header("Location: teaching-materials.php");
                    exit();
                }

                if (!file_exists($subject_dir . $new_file_name)) {
                    error_log("Tệp đã di chuyển nhưng không tồn tại: $subject_dir$new_file_name");
                    $_SESSION['error_message'] = "Tệp đã di chuyển nhưng không tồn tại trong thư mục đích.";
                    header("Location: teaching-materials.php");
                    exit();
                }

                // Lưu thông tin vào cơ sở dữ liệu
                $query = "INSERT INTO tailieugiangday (MaHocPhan, MaGiangVien, TenTaiLieu, LoaiTaiLieu, ThongTinHoTroSinhVIen, LuuTru) 
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($dbc, $query);
                if (!$stmt) {
                    error_log("Lỗi chuẩn bị truy vấn (thêm tài liệu): " . mysqli_error($dbc));
                    die("Lỗi chuẩn bị truy vấn: " . mysqli_error($dbc));
                }
                mysqli_stmt_bind_param($stmt, "ssssss", $subject_id, $user_id, $title, $type, $description, $file_path);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success_message'] = "Tài liệu đã được thêm thành công!";
                } else {
                    error_log("Lỗi khi thêm tài liệu vào CSDL: " . mysqli_error($dbc));
                    $_SESSION['error_message'] = "Lỗi khi thêm tài liệu: " . mysqli_error($dbc);
                }
                mysqli_stmt_close($stmt);
            } else {
                error_log("Không có file được tải lên hoặc lỗi upload: " . ($_FILES['file_upload']['error'] ?? 'Unknown'));
                $_SESSION['error_message'] = "Vui lòng chọn một file để tải lên.";
            }
        } elseif ($_POST['action'] === 'update') {
            $id = (int)$_POST['id'];
            $subject_id = trim($_POST['subject_id'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $description = $_POST['description'] ?? '';
            $file_path = $_POST['file_name'];
            $type = $_POST['type'];

            // Kiểm tra dữ liệu đầu vào
            if (empty($subject_id) || empty($title)) {
                error_log("Dữ liệu đầu vào không hợp lệ: subject_id=$subject_id, title=$title");
                $_SESSION['error_message'] = "Vui lòng điền đầy đủ học phần và tên tài liệu.";
                header("Location: teaching-materials.php");
                exit();
            }

            // Lấy tên học phần và tên khoa từ MaHocPhan
            $query = "SELECT hp.TenHocPhan, k.TenKhoa 
                      FROM hocphan hp 
                      JOIN khoa k ON hp.MaKhoa = k.MaKhoa 
                      WHERE hp.MaHocPhan = ?";
            $stmt = mysqli_prepare($dbc, $query);
            if (!$stmt) {
                error_log("Lỗi chuẩn bị truy vấn (lấy tên học phần và khoa): " . mysqli_error($dbc));
                die("Lỗi chuẩn bị truy vấn: " . mysqli_error($dbc));
            }
            mysqli_stmt_bind_param($stmt, "s", $subject_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $subject_name = $row['TenHocPhan'];
                $faculty_name = $row['TenKhoa'];
            } else {
                error_log("Không tìm thấy học phần hoặc khoa cho MaHocPhan: $subject_id");
                $_SESSION['error_message'] = "Không tìm thấy học phần hoặc khoa.";
                header("Location: teaching-materials.php");
                exit();
            }
            mysqli_stmt_close($stmt);

            // Xử lý file mới nếu có
            if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['file_upload']['tmp_name'];
                $file_name = basename($_FILES['file_upload']['name']);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                // Kiểm tra đuôi file
                if (empty($file_ext)) {
                    error_log("Không thể xác định loại file: $file_name");
                    $_SESSION['error_message'] = "Không thể xác định loại file.";
                    header("Location: teaching-materials.php");
                    exit();
                }

                // Chuẩn hóa tên file
                $new_file_name = sanitizeFileName($title) . '.' . $file_ext;

                // Tạo thư mục con với cấu trúc [TenKhoa]/[TenHocPhan]
                $faculty_dir = $upload_dir . $faculty_name . '/';
                $subject_dir = $faculty_dir . $subject_name . '/';
                if (!is_dir($faculty_dir)) {
                    if (!mkdir($faculty_dir, 0755, true) || !is_writable($faculty_dir)) {
                        error_log("Không thể tạo hoặc ghi vào thư mục khoa: $faculty_dir");
                        $_SESSION['error_message'] = "Không thể tạo thư mục khoa.";
                        header("Location: teaching-materials.php");
                        exit();
                    }
                    chown($faculty_dir, 'www-data');
                    chgrp($faculty_dir, 'www-data');
                }
                if (!is_dir($subject_dir)) {
                    if (!mkdir($subject_dir, 0755, true) || !is_writable($subject_dir)) {
                        error_log("Không thể tạo hoặc ghi vào thư mục học phần: $subject_dir");
                        $_SESSION['error_message'] = "Không thể tạo thư mục học phần.";
                        header("Location: teaching-materials.php");
                        exit();
                    }
                    chown($subject_dir, 'www-data');
                    chgrp($subject_dir, 'www-data');
                }

                // Đường dẫn lưu trong CSDL
                $file_path = '/Document/' . $faculty_name . '/' . $subject_name . '/' . $new_file_name;

                // Kiểm tra loại file
                $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'mp4', 'avi', 'mov'];
                if (!in_array($file_ext, $allowed_types)) {
                    error_log("Loại file không được phép: $file_ext");
                    $_SESSION['error_message'] = "Loại file không được phép. Chỉ chấp nhận: " . implode(', ', $allowed_types);
                    header("Location: teaching-materials.php");
                    exit();
                }

                // Ánh xạ đuôi file với loại tài liệu
                $fileTypeMap = [
                    'pdf' => 'Giáo trình',
                    'doc' => 'Giáo trình',
                    'docx' => 'Giáo trình',
                    'ppt' => 'Slide',
                    'pptx' => 'Slide',
                    'mp4' => 'Video',
                    'avi' => 'Video',
                    'mov' => 'Video'
                ];
                $type = isset($fileTypeMap[$file_ext]) ? $fileTypeMap[$file_ext] : 'Khác';

                // Kiểm tra xem file đã tồn tại chưa, nếu có thì thêm hậu tố
                $base_file_name = sanitizeFileName($title);
                $counter = 1;
                while (file_exists($subject_dir . $new_file_name)) {
                    $new_file_name = $base_file_name . '_' . $counter . '.' . $file_ext;
                    $file_path = '/Document/' . $faculty_name . '/' . $subject_name . '/' . $new_file_name;
                    $counter++;
                }

                // Di chuyển file vào thư mục học phần
                if (!move_uploaded_file($file_tmp, $subject_dir . $new_file_name)) {
                    error_log("Lỗi khi di chuyển file: $file_tmp -> $subject_dir$new_file_name");
                    $_SESSION['error_message'] = "Lỗi khi tải file lên. Kiểm tra quyền thư mục hoặc hỗ trợ ký tự Unicode.";
                    header("Location: teaching-materials.php");
                    exit();
                }

                // Xóa file cũ nếu tồn tại
                $old_file = BASE_PATH . $_POST['file_name'];
                if (file_exists($old_file) && $old_file !== $file_path) {
                    if (!unlink($old_file)) {
                        error_log("Lỗi khi xóa file cũ: $old_file");
                    }
                }
            }

            // Cập nhật thông tin tài liệu
            $query = "UPDATE tailieugiangday 
                      SET MaHocPhan = ?, TenTaiLieu = ?, LoaiTaiLieu = ?, ThongTinHoTroSinhVIen = ?, LuuTru = ? 
                      WHERE MaTaiLieu = ? AND MaGiangVien = ?";
            $stmt = mysqli_prepare($dbc, $query);
            if (!$stmt) {
                error_log("Lỗi chuẩn bị truy vấn (cập nhật tài liệu): " . mysqli_error($dbc));
                die("Lỗi chuẩn bị truy vấn: " . mysqli_error($dbc));
            }
            mysqli_stmt_bind_param($stmt, "sssssis", $subject_id, $title, $type, $description, $file_path, $id, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Tài liệu đã được cập nhật thành công!";
            } else {
                error_log("Lỗi khi cập nhật tài liệu: " . mysqli_error($dbc));
                $_SESSION['error_message'] = "Lỗi khi cập nhật tài liệu: " . mysqli_error($dbc);
            }
            mysqli_stmt_close($stmt);
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];

            // Lấy đường dẫn file để xóa
            $query = "SELECT LuuTru FROM tailieugiangday WHERE MaTaiLieu = ? AND MaGiangVien = ?";
            $stmt = mysqli_prepare($dbc, $query);
            if (!$stmt) {
                error_log("Lỗi chuẩn bị truy vấn (xóa tài liệu): " . mysqli_error($dbc));
                die("Lỗi chuẩn bị truy vấn: " . mysqli_error($dbc));
            }
            mysqli_stmt_bind_param($stmt, "is", $id, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $file_path = BASE_PATH . $row['LuuTru'];
                if (file_exists($file_path)) {
                    if (!unlink($file_path)) {
                        error_log("Lỗi khi xóa file: $file_path");
                    }
                }
            }
            mysqli_stmt_close($stmt);

            // Xóa tài liệu khỏi cơ sở dữ liệu
            $query = "DELETE FROM tailieugiangday WHERE MaTaiLieu = ? AND MaGiangVien = ?";
            $stmt = mysqli_prepare($dbc, $query);
            if (!$stmt) {
                error_log("Lỗi chuẩn bị truy vấn (xóa tài liệu): " . mysqli_error($dbc));
                die("Lỗi chuẩn bị truy vấn: " . mysqli_error($dbc));
            }
            mysqli_stmt_bind_param($stmt, "is", $id, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Tài liệu đã được xóa thành công!";
            } else {
                error_log("Lỗi khi xóa tài liệu: " . mysqli_error($dbc));
                $_SESSION['error_message'] = "Lỗi khi xóa tài liệu: " . mysqli_error($dbc);
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: teaching-materials.php");
    exit();
}

// Lấy danh sách tài liệu
$query = "SELECT tl.MaTaiLieu, tl.MaHocPhan, tl.TenTaiLieu, tl.LoaiTaiLieu, tl.ThongTinHoTroSinhVIen, tl.LuuTru, hp.TenHocPhan 
          FROM tailieugiangday tl 
          JOIN hocphan hp ON tl.MaHocPhan = hp.MaHocPhan 
          WHERE tl.MaGiangVien = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$materials = [];
while ($row = mysqli_fetch_assoc($result)) {
    $materials[] = $row;
}
mysqli_stmt_close($stmt);

include(BASE_PATH . '/Layout/header.php');
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài liệu giảng dạy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Thêm CSS của Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Thêm JS của Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        /* Tùy chỉnh giao diện Select2 để phù hợp với Bootstrap */
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }
    </style>
</head>

<body style="background-color: #f1f1f1;">
    <div class="menu-container">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 col-6">
                    <nav class="main-menu">
                        <button class="menu-toggle">☰</button>
                        <ul class="menu" style="padding-top: 10px;">
                            <li class="dropdown"><a href="./index.php">Lịch làm việc</a></li>
                            <li class="dropdown">
                                <a href="#">Danh sách</a>
                                <ul class="submenu">
                                    <li><a href="./course-list.php">Danh sách học phần</a></li>
                                    <li><a href="./works-list.php">Danh sách công việc hành chính</a></li>
                                </ul>
                            </li>
                            <li class="dropdown"><a href="./guide-students.php">Hướng dẫn sinh viên</a></li>
                        </ul>
                    </nav>
                </div>
                <div class="col-md-6 col-6 d-flex justify-content-end">
                    <nav>
                        <ul class="menu-right">
                            <li class="dropdown">
                                <a href="#">
                                    <b><?php echo htmlspecialchars($full_name); ?></b>
                                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                </a>
                                <ul class="submenu">
                                    <?php if ($quyen === 'Admin' || $quyen === 'Super Admin'): ?>
                                        <li><a href="<?php echo BASE_URL; ?>/Admin/work/index.php">Trang Quản Trị</a></li>
                                    <?php endif; ?>
                                    <li><a href="./profile.php">Trang Cá Nhân</a></li>
                                    <li><a href="./teaching-materials.php">Tài liệu giảng dạy</a></li>
                                    <li><a href="./change-password.php">Đổi mật khẩu</a></li>
                                    <li><a href="?action=logout">Đăng xuất</a></li>
                                </ul>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container content mt-4">
        <h4>Tài liệu giảng dạy</h4>
        <div class="mb-4">
            <button class="btn btn-primary" id="showAddForm">Thêm tài liệu</button>
        </div>
        <div class="card mb-4" id="materialFormContainer" style="display: none;">
            <div class="card-header">Thêm/Cập nhật Tài liệu</div>
            <div class="card-body">
                <form id="materialForm" method="POST" action="teaching-materials.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="materialId">
                    <input type="hidden" name="file_name" id="file_name">
                    <div class="mb-3">
                        <label for="subject_id" class="form-label">Học phần</label>
                        <select class="form-control" id="subject_id" name="subject_id" required>
                            <option value="">Chọn học phần</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['MaHocPhan']); ?>">
                                    <?php echo htmlspecialchars($subject['TenHocPhan']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Tên tài liệu</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Loại tài liệu</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="">Chọn loại tài liệu</option>
                            <option value="Giáo trình">Giáo trình</option>
                            <option value="Slide">Slide</option>
                            <option value="Bài tập">Bài tập</option>
                            <option value="Video">Video</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Thông tin hỗ trợ sinh viên</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="file_upload" class="form-label">Tệp tài liệu</label>
                        <input type="file" class="form-control" id="file_upload" name="file_upload">
                    </div>
                    <button type="submit" class="btn btn-primary" id="submitButton">Thêm Tài liệu</button>
                    <button type="button" class="btn btn-secondary" id="cancelUpdate" style="display: none;">Hủy</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Danh sách Tài liệu</div>
            <div class="card-body">
                <?php if (empty($materials)): ?>
                    <p>Chưa có tài liệu nào.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Học phần</th>
                                    <th>Tên tài liệu</th>
                                    <th>Loại tài liệu</th>
                                    <th>Thông tin hỗ trợ sinh viên</th>
                                    <th>Tệp</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materials as $material): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($material['TenHocPhan']); ?></td>
                                        <td title="<?php echo htmlspecialchars($material['TenTaiLieu']); ?>">
                                            <?php echo htmlspecialchars(substr($material['TenTaiLieu'], 0, 50) . (strlen($material['TenTaiLieu']) > 50 ? '...' : '')); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($material['LoaiTaiLieu']); ?></td>
                                        <td title="<?php echo htmlspecialchars($material['ThongTinHoTroSinhVIen']); ?>">
                                            <?php echo htmlspecialchars(substr($material['ThongTinHoTroSinhVIen'], 0, 50) . (strlen($material['ThongTinHoTroSinhVIen']) > 50 ? '...' : '') ?: 'Không có'); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $file_path = BASE_PATH . $material['LuuTru'];
                                            $url_parts = explode('/', $material['LuuTru']);
                                            $encoded_url = '/Document/' . rawurlencode($url_parts[2]) . '/' . rawurlencode($url_parts[3]) . '/' . $url_parts[4];
                                            $url = BASE_URL . $encoded_url;
                                            $file_ext = strtolower(pathinfo($material['LuuTru'], PATHINFO_EXTENSION));

                                            if (!empty($material['LuuTru']) && file_exists($file_path)) {
                                                if ($file_ext === 'pdf') {
                                                    echo '<a href="#" class="view-file" data-type="pdf" data-url="' . htmlspecialchars($url) . '">Xem PDF</a> | ';
                                                    $original_file_name = basename($material['LuuTru']);
                                                    echo '<a href="' . htmlspecialchars($url) . '" download="' . htmlspecialchars($original_file_name) . '" target="_blank">Tải xuống</a>';
                                                } elseif (in_array($file_ext, ['mp4', 'avi', 'mov'])) {
                                                    echo '<a href="#" class="play-video" data-url="' . htmlspecialchars($url) . '" data-type="video">Phát Video</a> | ';
                                                    $original_file_name = basename($material['LuuTru']);
                                                    echo '<a href="' . htmlspecialchars($url) . '" download="' . htmlspecialchars($original_file_name) . '" target="_blank">Tải xuống</a>';
                                                } else {
                                                    $original_file_name = basename($material['LuuTru']);
                                                    echo '<a href="' . htmlspecialchars($url) . '" download="' . htmlspecialchars($original_file_name) . '" target="_blank">Tải xuống</a>';
                                                }
                                            } else {
                                                echo 'Tệp không tồn tại';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning edit-material"
                                                data-id="<?php echo $material['MaTaiLieu']; ?>"
                                                data-subject_id="<?php echo htmlspecialchars($material['MaHocPhan']); ?>"
                                                data-title="<?php echo htmlspecialchars($material['TenTaiLieu']); ?>"
                                                data-type="<?php echo htmlspecialchars($material['LoaiTaiLieu']); ?>"
                                                data-description="<?php echo htmlspecialchars($material['ThongTinHoTroSinhVIen']); ?>"
                                                data-file_name="<?php echo htmlspecialchars($material['LuuTru']); ?>">Sửa</button>
                                            <button class="btn btn-sm btn-danger delete-material"
                                                data-id="<?php echo $material['MaTaiLieu']; ?>"
                                                data-description="<?php echo htmlspecialchars($material['ThongTinHoTroSinhVIen']); ?>">Xóa</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Khởi tạo Select2 cho select box Học phần
            $('#subject_id').select2({
                placeholder: "Chọn học phần",
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Không tìm thấy học phần";
                    }
                }
            });

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

            $(document).on('click', '.view-file', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                window.open(url, '_blank');
            });

            $(document).on('click', '.play-video', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                window.open(url, '_blank');
            });

            $('#showAddForm').click(function() {
                $('#materialFormContainer').slideDown();
                $('#materialForm')[0].reset();
                $('#subject_id').val('').trigger('change'); // Reset Select2
                $('#formAction').val('add');
                $('#materialId').val('');
                $('#file_name').val('');
                $('#submitButton').text('Thêm Tài liệu');
                $('#cancelUpdate').show();
                $('#file_upload').attr('required', 'required');
                $(this).hide();
            });

            $('#file_upload').change(function() {
                const fileName = $(this).val();
                if (fileName) {
                    const fileExt = fileName.split('.').pop().toLowerCase();
                    let fileType = 'Khác';
                    const fileTypeMap = {
                        'pdf': 'Giáo trình',
                        'doc': 'Giáo trình',
                        'docx': 'Giáo trình',
                        'ppt': 'Slide',
                        'pptx': 'Slide',
                        'mp4': 'Video',
                        'avi': 'Video',
                        'mov': 'Video'
                    };
                    if (fileTypeMap[fileExt]) {
                        fileType = fileTypeMap[fileExt];
                    }
                    $('#type').val(fileType);
                } else {
                    $('#type').val('');
                }
            });

            $('.edit-material').click(function() {
                const id = $(this).data('id');
                const subject_id = $(this).data('subject_id');
                const title = $(this).data('title');
                const type = $(this).data('type');
                const description = $(this).data('description');
                const file_name = $(this).data('file_name');

                $('#formAction').val('update');
                $('#materialId').val(id);
                $('#subject_id').val(subject_id).trigger('change'); // Cập nhật Select2
                $('#title').val(title);
                $('#type').val(type);
                $('#description').val(description);
                $('#file_name').val(file_name);
                $('#submitButton').text('Cập nhật Tài liệu');
                $('#cancelUpdate').show();
                $('#file_upload').removeAttr('required');
                $('#materialFormContainer').slideDown();
                $('#showAddForm').hide();
            });

            $('#cancelUpdate').click(function() {
                $('#materialForm')[0].reset();
                $('#subject_id').val('').trigger('change'); // Reset Select2
                $('#formAction').val('add');
                $('#materialId').val('');
                $('#file_name').val('');
                $('#submitButton').text('Thêm Tài liệu');
                $('#cancelUpdate').hide();
                $('#file_upload').attr('required', 'required');
                $('#materialFormContainer').slideUp();
                $('#showAddForm').show();
            });

            $('.delete-material').click(function() {
                const id = $(this).data('id');
                const description = $(this).data('description');
                Swal.fire({
                    title: 'Bạn có chắc chắn?',
                    text: 'Tài liệu này sẽ bị xóa vĩnh viễn!' + (description ? '\nGhi chú: ' + description : ''),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Xóa',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('<form>', {
                            method: 'POST',
                            action: 'teaching-materials.php'
                        }).append(
                            $('<input>', {
                                type: 'hidden',
                                name: 'action',
                                value: 'delete'
                            }),
                            $('<input>', {
                                type: 'hidden',
                                name: 'id',
                                value: id
                            })
                        );
                        $('body').append(form);
                        form.submit();
                    }
                });
            });

            $('.menu-toggle').click(function() {
                $('.main-menu .menu').slideToggle();
            });

            $(document).click(function(e) {
                if (!$(e.target).closest('.dropdown').length && $(window).width() <= 768) {
                    $('.submenu').hide();
                }
            });

            $('.main-menu .dropdown > a, .menu-right .dropdown > a').click(function(e) {
                if ($(window).width() <= 768) {
                    var $submenu = $(this).siblings('.submenu');
                    if ($submenu.length > 0) {
                        e.preventDefault();
                        if ($submenu.is(':visible')) {
                            $submenu.hide();
                        } else {
                            $('.submenu').hide();
                            $submenu.show();
                        }
                    }
                }
            });
        });
    </script>
    <?php
    mysqli_close($dbc);
    include(BASE_PATH . '/Layout/footer.php');
    ?>
</body>

</html>