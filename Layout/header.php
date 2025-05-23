<?php
// Nạp file config để sử dụng đường dẫn đã  BASE_thiết lập
require_once '../config.php';
?>

<!doctype html>
<html lang="en">
<head>
    <title>Layout Header User</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="<?php echo BASE_URL ?>/Public/style/style.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>


<body>
    
    <header>
    <div class="row logo">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-1">
                    <img src="<?php echo BASE_URL?> /Public/img/LogoNTU.jpg" alt="logo" width="100px">
                </div>
                <div class="col-md-11" style="padding-top: 10px;">
                    <H3 style="font-size: 23px;color: #FFFFFF;">
                        HỆ THỐNG QUẢN LÝ CÔNG VIỆC GIẢNG VIÊN
                    </H3>
                    <table style="width:100%;">
                        <tr>
                            <td >
                                <h3 style="color:#FFFFFF; font-size: 25px;">
                                    Đại học nha trang
                                </h3> 
                            </td>
                        </tr>
                        <tr>
                            <td class="chuc-vu">
                                Giảng Viên
                            </td>
                        </tr>
                    </table>                    
                </div>

            </div>
        </div>
    </div>
    </header>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
        crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
        crossorigin="anonymous"></script>                
</body>

</html>