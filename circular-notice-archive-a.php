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

            <header class="header-circular-notice-keep">
                <div class="circular-notice-keep-control">

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
            </header>

            <section class="content-circular-notice-keep">
                <div class="search-bar">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="search-input" value="" placeholder="ค้นหาข้อความด้วย...">
                    </div>
                </div>

                <div class="table-circular-notice-keep">
                    <table>
                        <thead>
                            <tr>
                                <th>ประเภทหนังสือ</th>
                                <th>หัวเรื่อง</th>
                                <th>ผู้ส่ง</th>
                                <th>วันที่ส่ง</th>
                                <th>สถานะ</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ภายนอก</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem ipsum dollate eieiei eieieieieieieieieieieiei</td>
                                <td>นายรัชพลพลภร ธูปทองเงินทองแดง</td>
                                <td>12/01/68</td>
                                <td><span class="status-badge read">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายนอก</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem ipsum dollate eieiei</td>
                                <td>นายรัชพลพลภร ธูปทองเงินทองแดง</td>
                                <td>12/01/68</td>
                                <td><span class="status-badge read">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายนอก</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem ipsum dollate eieiei</td>
                                <td>นายรัชพลพลภร ธูปทองเงินทองแดง</td>
                                <td>12/01/68</td>
                                <td><span class="status-badge read">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายนอก</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem ipsum dollate eieiei</td>
                                <td>นายรัชพลพลภร ธูปทองเงินทองแดง</td>
                                <td>12/01/68</td>
                                <td><span class="status-badge read">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายนอก</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem ipsum dollate eieiei</td>
                                <td>นายรัชพลพลภร ธูปทองเงินทองแดง</td>
                                <td>12/01/68</td>
                                <td><span class="status-badge read">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายนอก</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem ipsum dollate eieiei</td>
                                <td>นายรัชพลพลภร ธูปทองเงินทองแดง</td>
                                <td>12/01/68</td>
                                <td><span class="status-badge read">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                            <tr>
                                <td>ภายใน</td>
                                <td>Lorem ipsum dollate eieiei hello Lorem</td>
                                <td>นายพลภร เงินทองแดง</td>
                                <td>02/01/68</td>
                                <td><span class="status-badge unread">อ่านแล้ว</span></td>
                                <td><button class="button-more-details" id="modalNoticeKeep">
                                        <p>รายละเอียด</p>
                                    </button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="modal-overlay-circular-notice-keep" id="modalNoticeKeepOverlay">
                    <div class="modal-content">
                        <div class="header-modal">
                            <p>ประเภทหนังสือ</p>
                            <i class="fa-solid fa-xmark" id="closeModalNoticeKeep"></i>
                        </div>

                        <div class="content-modal">
                            <div class="content-topic-sec">
                                <p><strong>หัวเรื่อง :</strong></p>
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec iaculis, est vel molestie vehicula, libero leo aliquam sem, a sollicitudin nibh leo nec ex. Praesent at porttitor dolor, et malesuada magna. Suspendisse ornare euismod quam nec posuere. Ut ac magna semper, convallis dui auctor.</p>
                            </div>
                            <div class="content-topic-sec">
                                <p><strong>ผู้ส่ง :</strong></p>
                                <p>นายรัชพล ธูปทอง</p>
                            </div>
                            <div class="content-topic-sec">
                                <p><strong>วันที่ส่ง :</strong></p>
                                <p>วันศุกร์ ที่ 14 มกราคม พ.ศ. 2555</p>
                            </div>

                            <div class="content-details-sec">
                                <p><strong>รายละเอียดเพิ่มเติม</strong></p>
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur at nibh ante. Sed interdum gravida rhoncus. Duis eu ex luctus, mattis enim ullamcorper, pellentesque arcu. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque maximus tempor dapibus. Nullam euismod commodo dignissim. Duis condimentum dignissim euismod. Proin tincidunt rutrum quam, at luctus quam mollis vitae. Sed imperdiet malesuada tellus.</p>
                            </div>
                            <div class="content-details-sec">
                                <p><strong>ลิ้งก์แนบจากระบบ</strong></p>
                                <p>https://drive.google.com/drive/my-drive</p>
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
                                </div>

                            </div>

                        </div>

                    </div>
                </div>

            </section>

            <div class="button-circular-notice-keep">

            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>