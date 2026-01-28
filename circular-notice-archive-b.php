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

            <div class="circular-notice-archive-notice-content">
                <header class="header-circular-notice-archive outside-person">
                    <div class="circular-notice-archive-control outside-person">

                        <div class="page-selector">
                            <p>แสดงตามประเภทหนังสือ</p>
                            <div class="checkbox-group">
                                <div class="">
                                    <input type="checkbox" class="archive-control-checkbox" name="" id="">
                                    <p>ภายนอก</p>
                                </div>
                                <div class="">
                                    <input type="checkbox" class="archive-control-checkbox" name="" id="">
                                    <p>ภายใน</p>
                                </div>
                            </div>
                        </div>

                        <div class="page-selector">
                            <p>แสดงตามสถานะหนังสือ</p>
                            <div class="checkbox-group">
                                <div class="">
                                    <input type="checkbox" class="archive-control-checkbox" name="" id="">
                                    <p>อ่านแล้ว</p>
                                </div>
                                <div class="">
                                    <input type="checkbox" class="archive-control-checkbox" name="" id="">
                                    <p>ยังไม่อ่าน</p>
                                </div>
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

                <section class="content-circular-notice-archive outside-person">
                    <div class="search-bar">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-input" value="" placeholder="ค้นหาข้อความด้วย...">
                        </div>
                    </div>

                    <div class="table-circular-notice-archive outside-person">
                        <table>
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" class="check-table checkall" name="" id="">
                                    </th>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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
                                    <td>
                                        <input type="checkbox" class="check-table" name="" id="">
                                    </td>
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

                    

                </section>
            </div>

            <div class="button-circular-notice-archive outside-person">
                <div class="button-keep">
                    <i class="fa-solid fa-file-import"></i>
                    <p>จัดเก็บ</p>
                </div>
            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>