





<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Đăng nhập</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-red-100 flex items-center justify-center min-h-screen">
    <div style="margin-bottom: 20px;" class="flex flex-col items-center justify-center space-y-6">
        <div class="flex flex-col items-center">
            <img alt="NTU logo" class="w-30 h-30 mb-4" height="100" src="Public/img/LogoNTU.jpg" width="100"/>
            <span class="text-orange-500 text-2xl font-bold">HỆ THỐNG QUẢN LÝ CÔNG VIỆC GIẢNG VIÊN</span>
        </div>
        <div class="bg-white p-10 rounded-lg shadow-lg w-[500px] h-[350px] flex items-center">
            <form action="" method="post" class="grid grid-rows-3 gap-6 w-full">
                <input class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="USERNAME" type="text"/>
                
                <input class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="PASSWORD" type="password"/>
                
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg w-1/2">Log in</button>
                    <a class="text-blue-500 text-sm" href="#">Lost password?</a>
                </div>
            </form>
        </div>
        
    </div>
</body>
</html>