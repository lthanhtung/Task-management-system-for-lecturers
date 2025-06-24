<?php
require_once '../config.php';
require_once BASE_PATH . '/Database/connect-database.php';
require_once BASE_PATH . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Hàm gửi email nhắc nhở
function sendReminderEmail($email, $subject, $event_start, $event_details)
{
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        file_put_contents(BASE_PATH . '/reminder.log', date('Y-m-d H:i:s') . " - Email không hợp lệ: $email\n", FILE_APPEND);
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tung.lt.63cntt@ntu.edu.vn';
        $mail->Password = 'hpss hfkw rwwl rnrz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom('tung.lt.63cntt@ntu.edu.vn', 'Hệ thống Quản lý Giảng viên');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Nhắc nhở Sự kiện sắp diễn ra';
        $mail->Body = "
            <h3>Nhắc nhở Sự kiện</h3>
            <p><strong>Sự kiện:</strong> {$subject}</p>
            <p><strong>Thời gian:</strong> " . date('d/m/Y H:i', strtotime($event_start)) . "</p>
            {$event_details}
            <p>Vui lòng chuẩn bị cho sự kiện này!</p>
        ";
        $mail->AltBody = "Sự kiện: {$subject}\nThời gian: " . date('d/m/Y H:i', strtotime($event_start)) . "\n{$event_details}\nVui lòng chuẩn bị!";

        $mail->send();
        file_put_contents(BASE_PATH . '/reminder.log', date('Y-m-d H:i:s') . " - Gửi email thành công tới $email cho sự kiện: $subject\n", FILE_APPEND);
        return true;
    } catch (Exception $e) {
        file_put_contents(BASE_PATH . '/reminder.log', date('Y-m-d H:i:s') . " - Gửi email thất bại tới $email: {$mail->ErrorInfo}\n", FILE_APPEND);
        return false;
    }
}

// Hàm kiểm tra và gửi nhắc nhở
function checkAndSendReminders($dbc)
{
    if (!$dbc || $dbc->connect_error) {
        file_put_contents(BASE_PATH . '/reminder.log', date('Y-m-d H:i:s') . " - Lỗi kết nối database: " . ($dbc ? $dbc->connect_error : 'No connection') . "\n", FILE_APPEND);
        return false;
    }

    $current_time = new DateTime();
    $reminder_window_start = (clone $current_time)->modify('+4 minutes')->format('Y-m-d H:i:s');
    $reminder_window_end = (clone $current_time)->modify('+6 minutes')->format('Y-m-d H:i:s');
    $today = $current_time->format('Y-m-d');

    file_put_contents(BASE_PATH . '/reminder.log', date('Y-m-d H:i:s') . " - Kiểm tra nhắc nhở từ $reminder_window_start đến $reminder_window_end\n", FILE_APPEND);

    $query_giangvien = "SELECT MaGiangVien, Email FROM giangvien WHERE TrangThai = 1";
    $result_giangvien = mysqli_query($dbc, $query_giangvien);

    if (!$result_giangvien) {
        file_put_contents(BASE_PATH . '/reminder.log', date('Y-m-d H:i:s') . " - Lỗi truy vấn giangvien: " . mysqli_error($dbc) . "\n", FILE_APPEND);
        return false;
    }

    $email_sent = false;
    while ($giangvien = mysqli_fetch_assoc($result_giangvien)) {
        $ma_giang_vien = $giangvien['MaGiangVien'];
        $email = $giangvien['Email'];

        // Lấy lịch giảng dạy
        $query_teaching = "
            SELECT hp.TenHocPhan, lhp.LopHocPhan, lhp.ThoiGianBatDau, lhp.ThoiGianKetThuc, lhp.DiaDiem,
                   lgd.LichGiang, lgd.GioBatDau, lgd.GioKetThuc
            FROM lichgiangday lgd
            JOIN lichhocphan lhp ON lgd.MaLichHocPhan = lhp.MaLichHocPhan
            JOIN hocphan hp ON lhp.MaHocPhan = hp.MaHocPhan
            WHERE lgd.MaGiangVien = ? AND lhp.TrangThai = 1 AND hp.TrangThai = 1
            AND lhp.ThoiGianBatDau <= ? AND lhp.ThoiGianKetThuc >= ?";
        $stmt_teaching = mysqli_prepare($dbc, $query_teaching);
        mysqli_stmt_bind_param($stmt_teaching, "sss", $ma_giang_vien, $today, $today);
        mysqli_stmt_execute($stmt_teaching);
        $result_teaching = mysqli_stmt_get_result($stmt_teaching);

        while ($row = mysqli_fetch_assoc($result_teaching)) {
            $start_date = new DateTime($row['ThoiGianBatDau']);
            $end_date = new DateTime($row['ThoiGianKetThuc']);
            $day_of_week = $row['LichGiang'];
            $start_time = $row['GioBatDau'];

            $dow_map = [1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => 4, 6 => 5, 7 => 6];
            $dow = $dow_map[$day_of_week];

            $current_date = clone $start_date;
            while ($current_date <= $end_date) {
                $day_of_week_current = $current_date->format('N');
                $adjusted_dow = $day_of_week_current == 7 ? 0 : $day_of_week_current;
                if ($adjusted_dow == $dow) {
                    $event_date = $current_date->format('Y-m-d');
                    $event_start = "$event_date $start_time";
                    if ($event_start >= $reminder_window_start && $event_start <= $reminder_window_end) {
                        $event_details = "
                            <p><strong>Lớp:</strong> {$row['LopHocPhan']}</p>
                            <p><strong>Địa điểm:</strong> " . $row['DiaDiem'] . "</p>";
                        if (sendReminderEmail(
                            $email,
                            'Giảng dạy môn ' . $row['TenHocPhan'],
                            $event_start,
                            $event_details
                        )) {
                            $email_sent = true;
                        }
                    }
                }
                $current_date->modify('+1 day');
            }
        }
        mysqli_stmt_close($stmt_teaching);

        // Lấy công việc hành chính
        $query_admin = "
            SELECT cvc.TenCongViec, ttcvc.LoaiCongViec, ttcvc.NgayThucHien, ttcvc.GioBatDau, ttcvc.GioKetThuc, ttcvc.DiaDiem
            FROM thongtincongviechanhchinh ttcvc
            JOIN congviechanhchinh cvc ON ttcvc.MaCongViecHanhChinh = cvc.MaCongViecHanhChinh
            WHERE ttcvc.MaGiangVien = ? AND ttcvc.TrangThai = 1 AND ttcvc.NgayThucHien = ?";
        $stmt_admin = mysqli_prepare($dbc, $query_admin);
        mysqli_stmt_bind_param($stmt_admin, "ss", $ma_giang_vien, $today);
        mysqli_stmt_execute($stmt_admin);
        $result_admin = mysqli_stmt_get_result($stmt_admin);

        while ($row = mysqli_fetch_assoc($result_admin)) {
            $event_start = $row['NgayThucHien'] . ' ' . $row['GioBatDau'];
            if ($event_start >= $reminder_window_start && $event_start <= $reminder_window_end) {
                $event_details = "<p><strong>Chi tiết công việc:</strong><br>Loại: " . $row['LoaiCongViec'] . "<br>Địa điểm: " . $row['DiaDiem'] . "</p>";
                if (sendReminderEmail(
                    $email,
                    $row['TenCongViec'],
                    $event_start,
                    $event_details
                )) {
                    $email_sent = true;
                }
            }
        }
        mysqli_stmt_close($stmt_admin);
    }
    return $email_sent;
}

// Kiểm tra token để bảo mật
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'your_secret_token_123') {
    file_put_contents(BASE_PATH . '/reminder.log', date('Y-m-d H:i:s') . " - Token không hợp lệ\n", FILE_APPEND);
    exit('Token không hợp lệ');
}

// Gọi hàm gửi nhắc nhở
$success = checkAndSendReminders($dbc);

// Ghi log kết quả
file_put_contents(BASE_PATH . '/reminder.log', date('Y-m-d H:i:s') . " - Kết quả: " . ($success ? 'Thành công' : 'Thất bại') . "\n", FILE_APPEND);

// Đóng kết nối
if (isset($dbc) && $dbc) {
    mysqli_close($dbc);
}

// Echo ra thời gian hiện tại
echo "Thời gian hiện tại: " . date('Y-m-d H:i:s') . "\n";
echo $success ? 'Nhắc nhở đã được gửi' : 'Không có nhắc nhở nào được gửi';
