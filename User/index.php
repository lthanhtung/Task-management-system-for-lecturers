<?php
require_once '../config.php';
include(BASE_PATH.'/Layout/header.php');
?>

<head>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css' rel='stylesheet' />
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js'></script>
</head>

<body style="background-color: #f1f1f1;">
    <div class="Menu-container">
        <div class="col-md-12">
            <div class="row">
                <!-- Menu Chính (Bên Trái) -->
                <div class="col-md-6">
                    <nav>
                        <ul class="menu">
                            <li class="dropdown">
                                <a href="#">Lịch làm việc</a>
                                <ul class="submenu">
                                    <li><a href="lich_lam_viec.php">Lịch dạy</a></li>
                                    <li><a href="lich_hoc.php">Lịch công việc</a></li>
                                </ul>
                            </li>
                            <li><a href="#">Danh sách học phần</a></li>
                        </ul>
                    </nav>
                </div>

                <!-- Menu Bên Phải -->
                <div class="col-md-6 d-flex justify-content-end">
                    <nav>
                        <ul class="menu-right">
                            <li class="dropdown">
                                <a href="#">
                                    <b>Lê Thanh Tùng </b>
                                    <img src="<?php echo BASE_URL?>/Public/img/LogoNTU.jpg">
                                </a>
                                <ul class="submenu">
                                    <li><a href="logout.php">Đăng xuất</a></li>
                                </ul>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Phần Nội dung Website  -->
    <div class="container">
        <ul>
            <li>
            <h4>Lịch làm việc</h4>
            </li>
        </ul>
        <div style="width: 90%;margin: 0 auto;" id='calendar'></div>
    </div>

   <script>
    $(document).ready(function() {
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'agendaDay,agendaWeek,month'
            },
            defaultView: 'agendaDay',
            buttonText: {
            today: 'Hôm nay',
            month: 'Tháng',
            week: 'Tuần',
            day: 'Ngày'
            },
            events: [
                {
                    title: 'Môn 1',
                    start: '2025-02-22T10:00:00',
                    end: '2025-02-22T12:00:00'
                },
                {
                    title: 'Môn 1',
                    start: '2025-02-22T10:00:00',
                    end: '2025-02-22T12:00:00'
                },
                {
                    title: 'Môn 2',
                    start: '2025-02-23T14:00:00',
                    end: '2025-02-23T16:00:00'
                }
                // Thêm các sự kiện khác ở đây
            ],
            eventRender: function(event, element) {
            element.find('.fc-title').html('<strong>' + event.title + '</strong>'); // In đậm tiêu đề
                // // Hiển thị thời gian bắt đầu và kết thúc
                // var start = moment(event.start).format('HH:mm');
                // var end = moment(event.end).format('HH:mm');
                // element.find('.fc-title').append('<br/>' + start + ' - ' + end);
            }
        });
    });
</script>
</body>
<?php
include(BASE_PATH.'/Layout/footer.php');
?>