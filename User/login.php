<?php
// Nạp file config để sử dụng đường dẫn đã  BASE_thiết lập
require_once '../config.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Đăng nhập</title>
  <style>
    /* Cài đặt cơ bản cho body */
    body {
      background-color: #fee2e2; /* tương đương bg-red-100 */
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      font-family: Arial, sans-serif;
    }

    /* Container chính chứa logo và form */
    .outer-container {
      margin-bottom: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 24px; /* khoảng cách giữa các phần, tương đương space-y-6 */
    }

    /* Container cho logo và tiêu đề */
    .logo-container {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    /* Style cho logo */
    .logo {
      width: 100px;
      height: 100px;
      margin-bottom: 16px; /* tương đương mb-4 */
    }

    /* Style cho tiêu đề */
    .title {
      color: #f97316; /* tương đương text-orange-500 */
      font-size: 1.5rem; /* tương đương text-2xl */
      font-weight: bold;
      text-align: center;
    }

    /* Hộp chứa form đăng nhập */
    .login-box {
      background-color: #ffffff; /* bg-white */
      padding: 40px; /* p-10: 2.5rem = 40px */
      border-radius: 8px; /* rounded-lg: khoảng 0.5rem */
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 
                  0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-lg */
      width: 450px;
      height: 250px;
      display: flex;
      align-items: center; /* căn giữa theo chiều dọc */
    }

    /* Form đăng nhập với bố cục grid */
    .login-form {
      display: grid;
      grid-template-rows: repeat(3, auto);
      gap: 24px; /* khoảng cách giữa các hàng, tương đương gap-6 */
      width: 100%;
    }

    /* Style cho các ô input */
    .input-field {
      width: 100%;
      padding: 12px; /* p-3: 0.75rem ~12px */
      border: 1px solid #d1d5db; /* border-gray-300 */
      border-radius: 8px; /* rounded-lg */
      font-size: 1rem;
      box-sizing: border-box;
    }

    /* Hiệu ứng focus cho ô input */
    .input-field:focus {
      outline: none;
      box-shadow: 0 0 0 2px #3b82f6; /* focus:ring-2 focus:ring-blue-500 */
    }

    /* Container cho nút và link */
    .action-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* Style cho nút đăng nhập */
    .login-button {
      background-color: #3b82f6; /* blue-500 */
      color: #ffffff;
      padding: 8px 24px; /* py-2 (8px) và px-6 (24px) */
      border: none;
      border-radius: 8px;
      width: 50%; /* w-1/2 */
      cursor: pointer;
      font-size: 1rem;
    }

    /* Style cho link "Lost password?" */
    .lost-password {
      color: #3b82f6; /* blue-500 */
      font-size: 0.875rem; /* text-sm */
      text-decoration: none;
    }
    
    .lost-password:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="outer-container">
    <div class="logo-container">
      <img src="<?php echo BASE_URL.'/Public/img/LogoNTU.jpg' ?>" alt="NTU logo" class="logo" />
      <span class="title">HỆ THỐNG QUẢN LÝ CÔNG VIỆC GIẢNG VIÊN</span>
    </div>
    <div class="login-box">
      <form action="" method="post" class="login-form">
        <input type="text" placeholder="USERNAME" class="input-field" />
        <input type="password" placeholder="PASSWORD" class="input-field" />
        <div class="action-container">
          <button type="submit" class="login-button">Log in</button>
          <a href="#" class="lost-password">Lost password?</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
