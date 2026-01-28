<?php

?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body>

    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>

    <?php //require_once __DIR__ . '/public/components/partials/x-sidebar.php'; 
    ?>

    <section class="main-section">

        <?php //require_once __DIR__ . '/public/components/partials/x-navigation.php'; 
        ?>

        <main class="content-wrapper">

            <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>หนังสือเวียน / หนังสือเวียนที่จัดเก็บ</p>
            </div>

            <header class="header-circular-notice-index outside-person">
                <div class="circular-notice-index-control">

                    <div class="page-selector">
                        <p>แสดงตามประเภทหนังสือ</p>

                        <div class="custom-select-wrapper">
                            <div class="custom-select-trigger">
                                <p id="select-value">ทั้งหมด</p>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>

                            <div class="custom-options">
                                <div class="custom-option" data-value="">ภายนอก</div>
                                <div class="custom-option" data-value="">ภายใน</div>
                                <div class="custom-option" data-value="">ทั้งหมด</div>
                            </div>

                            <select name="" id="real-page-select">
                                <option value="">ภายนอก</option>
                                <option value="">ภายใน</option>
                                <option value="">ทั้งหมด</option>
                            </select>
                        </div>
                    </div>
                    <div class="page-selector">
                        <p>แสดงตามสถานะหนังสือ</p>

                        <div class="custom-select-wrapper">
                            <div class="custom-select-trigger">
                                <p id="select-value">ทั้งหมด</p>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>

                            <div class="custom-options">
                                <div class="custom-option" data-value="">อ่านแล้ว</div>
                                <div class="custom-option" data-value="">ยังไม่อ่าน</div>
                                <div class="custom-option" data-value="">ทั้งหมด</div>
                            </div>

                            <select name="" id="real-page-select">
                                <option value="">อ่านแล้ว</option>
                                <option value="">ยังไม่อ่าน</option>
                                <option value="">ทั้งหมด</option>
                            </select>
                        </div>
                    </div>
                    <div class="page-selector">
                        <p>แสดงตาม</p>

                        <div class="custom-select-wrapper">
                            <div class="custom-select-trigger">
                                <p id="select-value">ทั้งหมด</p>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>

                            <div class="custom-options">
                                <div class="custom-option" data-value="">ใหม่ไปเก่า</div>
                                <div class="custom-option" data-value="">เก่าไปใหม่</div>
                            </div>

                            <select name="" id="real-page-select">
                                <option value="">ใหม่ไปเก่า</option>
                                <option value="">เก่าไปใหม่</option>
                            </select>
                        </div>
                    </div>   
                </div>

                <div class="table-change">
                    <p>ตาราง</p>
                    <div class="button-table" id="">
                        <button class="active">ตาราง 1</button>
                        <button class="">ตาราง 2</button>
                    </div>
                </div>

            </header>

            <section class="content-circular-notice-index">
                <div class="search-bar">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="search-input" value="" placeholder="ค้นหาข้อความด้วย...">
                    </div>
                </div>

                <div class="table-circular-notice-index outside-person">
                    <table>
                        <thead>
                            <tr>
                                <th>วันที่รับ</th>
                                <th>เลขที่ / เรื่อง</th>
                                <th>ความเร่งด่วน</th>
                                <th>สถานะปัจุบัน</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <p>12 ตุลาคม 2568</p>
                                    <p>12:00 น.</p>
                                </td>
                                <td>
                                    <p>ศธ.9877 / 1223</p>
                                    <p>ขอเชิญนายรัชพล ธุปทอง มาม่า</p>
                                </td>
                                <td><button class="urgency-status normal"><p>ปกติ</p></button></td>
                                <td>Lorem ipsum dollate eieiei hello Lorem ipsum dollate</td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>
                                    <p>12 ตุลาคม 2568</p>
                                    <p>12:00 น.</p>
                                </td>
                                <td>
                                    <p>ศธ.9877 / 1223</p>
                                    <p>ขอเชิญนายรัชพล ธุปทอง มาม่า</p>
                                </td>
                                <td><button class="urgency-status urgen"><p>ด่วน</p></button></td>
                                <td>Lorem ipsum dollate eieiei hello Lorem ipsum dollate</td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>
                                    <p>12 ตุลาคม 2568</p>
                                    <p>12:00 น.</p>
                                </td>
                                <td>
                                    <p>ศธ.9877 / 1223</p>
                                    <p>ขอเชิญนายรัชพล ธุปทอง มาม่า</p>
                                </td>
                                <td><button class="urgency-status very-urgen"><p>ด่วนมาก</p></button></td>
                                <td>Lorem ipsum dollate eieiei hello Lorem ipsum dollate</td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>
                                    <p>12 ตุลาคม 2568</p>
                                    <p>12:00 น.</p>
                                </td>
                                <td>
                                    <p>ศธ.9877 / 1223</p>
                                    <p>ขอเชิญนายรัชพล ธุปทอง มาม่า</p>
                                </td>
                                <td><button class="urgency-status extremly-urgen"><p>ด่วนที่สุด</p></button></td>
                                <td>Lorem ipsum dollate eieiei hello Lorem ipsum dollate</td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="modal-overlay-circular-notice-index outside-person" id="modalNoticeKeepOverlay">
                    <div class="modal-content">
                        <div class="header-modal">
                            <div class="first-header">
                                <p>แสดงข้อความรายละเอียดหนังสือเวียน</p>
                            </div>
                            <div class="sec-header">
                                <div class="consider-status considering">กำลังเสนอ</div>
                                <i class="fa-solid fa-xmark" id="closeModalNoticeKeep"></i>
                            </div>
                        </div>

                        <div class="content-modal">
                            <div class="content-topic-sec">
                                <p><strong>ความเร่งด่วน :</strong></p>
                                <button class="urgency-status urgen"><p>ด่วน</p></button>
                            </div>
                            <div class="content-topic-sec">
                                <div class="more-details">
                                    <p><strong>เลขที่หนังสือ :</strong></p>
                                    <input type="text" name="" id="" placeholder="ศธ.2222 / 01234" disabled>
                                </div>
                                <div class="more-details">
                                    <p><strong>ลงวันที่ : </strong></p>
                                    <input type="text" name="" id="" placeholder="วันที่ 19 ตุลาคม พ.ศ.2561" disabled>
                                </div>
                                <div class="more-details">
                                    <p><strong>จาก : </strong></p>
                                    <input type="text" name="" id="" placeholder="สพฐ." disabled>
                                </div>
                                <div class="more-details">
                                    <p><strong>ลงวันที่ : </strong></p>
                                    <input type="text" name="" id="" placeholder="วันที่ 19 ตุลาคม พ.ศ.2561" disabled>
                                </div>
                                <div class="more-details">
                                    <p><strong>ถึง : </strong></p>
                                    <input type="text" name="" id="" placeholder="นายดลลยวัฒน์ สันติพิทักษ์" disabled>
                                </div>
                            </div>
                            
                            <div class="content-details-sec">
                                <p><strong>หัวเรื่อง :</strong></p>
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec iaculis, est vel molestie vehicula, libero leo aliquam sem,</p>
                            </div>
                            <div class="content-details-sec">
                                <p><strong>รายละเอียดเพิ่มเติม</strong></p>
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur at nibh ante. Sed interdum gravida rhoncus. Duis eu ex luctus, mattis enim ullamcorper, pellentesque arcu. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque maximus tempor dapibus. Nullam euismod commodo dignissim. Duis condimentum dignissim euismod. Proin tincidunt rutrum quam, at luctus quam mollis vitae. Sed imperdiet malesuada tellus.</p>
                            </div>

                            <div class="content-file-sec">
                                <p>ไฟล์เอกสารแนบจากระบบ</p>
                                <div class="file-section">
                                    <div class="file-banner">

                                        <div class="file-info">
                                            <div class="file-icon">
                                                <i class="fa-solid fa-file-image"></i>
                                            </div>
                                            <div class="file-text">
                                                <span class="file-name">animals.png</span>
                                                <span class="file-type">application/png</span>
                                            </div>
                                        </div>

                                        <div class="file-actions">
                                            <a href="">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </div>

                                        <div class="file-actions">
                                            <a href="">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        </div>

                                    </div>
                                    <div class="file-banner">
                                        <div class="file-info">
                                            <div class="file-icon">
                                                <i class="fa-solid fa-file-image"></i>
                                            </div>
                                            <div class="file-text">
                                                <span class="file-name">animals.png</span>
                                                <span class="file-type">application/png</span>
                                            </div>
                                        </div>
                                        <div class="file-actions">
                                            <a href="">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </div>
                                        <div class="file-actions">
                                            <a href="">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="file-banner">
                                        <div class="file-info">
                                            <div class="file-icon">
                                                <i class="fa-solid fa-file-image"></i>
                                            </div>
                                            <div class="file-text">
                                                <span class="file-name">animals.png</span>
                                                <span class="file-type">application/png</span>
                                            </div>
                                        </div>
                                        <div class="file-actions">
                                            <a href="">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </div>
                                        <div class="file-actions">
                                            <a href="">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="file-banner">
                                        <div class="file-info">
                                            <div class="file-icon">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </div>
                                            <div class="file-text">
                                                <span class="file-name">animals.pdf</span>
                                                <span class="file-type">application/pdf</span>
                                            </div>
                                        </div>
                                        <div class="file-actions">
                                            <a href="">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </div>
                                        <div class="file-actions">
                                            <a href="">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="file-banner">
                                        <div class="file-info">
                                            <div class="file-icon">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </div>
                                            <div class="file-text">
                                                <span class="file-name">animals.pdf</span>
                                                <span class="file-type">application/pdf</span>
                                            </div>
                                        </div>
                                        <div class="file-actions">
                                            <a href="">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </div>
                                        <div class="file-actions">
                                            <a href="">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                
                                </div>

                            </div>

                            <div class="content-time-and-considered-sec">
                                <div class="more-details">
                                    <p><strong>รับหนังสือเข้าระบบ : </strong></p>
                                    <input type="text" name="" id="" placeholder="16:57 น." disabled>
                                </div>
                                <div class="more-details">
                                    <p><strong>รอพิจารณา : </strong></p>
                                    <input type="text" name="" id="" placeholder="นายดลวัฒน์  สันติพิทักษ์" disabled>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

            </section>

            <div class="button-circular-notice-index">

            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>