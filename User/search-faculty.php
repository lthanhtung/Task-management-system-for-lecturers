<?php
require_once '../config.php';
require_once BASE_PATH . '/Database/connect-database.php';

// Hàm chuyển đổi ChucDanh thành viết tắt
function formatChucDanh($chucDanh)
{
    switch (trim($chucDanh)) {
        case 'Giảng viên':
            return 'Gv';
        case 'Giảng viên chính':
            return 'Gvc';
        case 'Phó giáo sư':
            return 'Pgs';
        case 'Giáo sư':
            return 'Gs';
        default:
            return $chucDanh;
    }
}

// Hàm chuyển đổi HocVi thành viết tắt
function formatHocVi($hocVi)
{
    switch (trim($hocVi)) {
        case 'Thạc sĩ':
            return 'Ths';
        case 'Tiến sĩ':
            return 'Ts';
        default:
            return $hocVi;
    }
}

// Hàm chuẩn hóa chuỗi cho URL
function slugify($ho, $ten, $tenKhoa = '')
{
    $string = trim($ho . ' ' . $ten . ($tenKhoa ? ' ' . $tenKhoa : ''));
    $string = str_replace(
        array('à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ', 'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ', 'ì', 'í', 'ị', 'ỉ', 'ĩ', 'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ', 'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ', 'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ', 'đ', 'À', 'Á', 'Ạ', 'Ả', 'Ã', 'Â', 'Ầ', 'Ấ', 'Ậ', 'Ẩ', 'Ẫ', 'Ă', 'Ằ', 'Ắ', 'Ặ', 'Ẳ', 'Ẵ', 'È', 'É', 'Ẹ', 'Ẻ', 'Ẽ', 'Ê', 'Ề', 'Ế', 'Ệ', 'Ể', 'Ễ', 'Ì', 'Í', 'Ị', 'Ỉ', 'Ĩ', 'Ò', 'Ó', 'Ọ', 'Ỏ', 'Õ', 'Ô', 'Ồ', 'Ố', 'Ộ', 'Ổ', 'Ỗ', 'Ơ', 'Ờ', 'Ớ', 'Ợ', 'Ở', 'Ỡ', 'Ù', 'Ú', 'Ụ', 'Ủ', 'Ũ', 'Ư', 'Ừ', 'Ứ', 'Ự', 'Ử', 'Ữ', 'Ỳ', 'Ý', 'Ỵ', 'Ỷ', 'Ỹ', 'Đ'),
        array('a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y', 'd', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'Y', 'Y', 'Y', 'Y', 'Y', 'D'),
        $string
    );
    $string = str_replace(' ', '-', trim($string));
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    $string = preg_replace('/-+/', '-', $string);
    return strtolower($string);
}

// Lấy tham số lọc từ AJAX
$khoa_filter = isset($_GET['khoa']) ? $_GET['khoa'] : '';
$ten_filter = isset($_GET['ten']) ? trim($_GET['ten']) : '';

// Truy vấn danh sách giảng viên với bộ lọc
$query = "
    SELECT g.MaGiangVien, g.HoGiangVien, g.TenGiangVien, g.AnhDaiDien, g.ChucDanh, g.HocVi, k.TenKhoa
    FROM giangvien g
    LEFT JOIN khoa k ON g.MaKhoa = k.MaKhoa
    WHERE g.TrangThai IN (1, 2)
";

$params = [];
$types = '';
if ($khoa_filter) {
    $query .= " AND g.MaKhoa = ?";
    $params[] = $khoa_filter;
    $types .= 's';
}
if ($ten_filter) {
    $query .= " AND LOWER(CONCAT(g.HoGiangVien, ' ', g.TenGiangVien)) LIKE LOWER(?)";
    $params[] = '%' . $ten_filter . '%';
    $types .= 's';
}

$stmt = $dbc->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$giangviens = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$dbc->close();

// Trả về HTML cho danh sách giảng viên
if (empty($giangviens)) {
    echo '<p>Không tìm thấy giảng viên nào phù hợp.</p>';
} else {
    foreach ($giangviens as $gv) {
        $avatar = htmlspecialchars($gv['AnhDaiDien'] ?: '/Public/img/avatar-default.png');
        $full_name = htmlspecialchars(formatChucDanh($gv['ChucDanh'])) . '.' . htmlspecialchars(formatHocVi($gv['HocVi'])) . '. ' . htmlspecialchars($gv['HoGiangVien'] . ' ' . $gv['TenGiangVien']);
        $link = BASE_URL . '/User/faculty-profile.php?giangvien=' . slugify($gv['HoGiangVien'], $gv['TenGiangVien'], $gv['TenKhoa']);
        echo '
        <div class="col">
            <div class="lecturer-card">
                <a href="' . $link . '">
                    <img src="' . $avatar . '" alt="' . $full_name . '">
                    <h5>' . $full_name . '</h5>
                </a>
            </div>
        </div>';
    }
}
