<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body>

    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>

    <?php require_once __DIR__ . '/public/components/partials/x-sidebar.php'; ?>

    <section class="dashboard-main-section">

        <?php require_once __DIR__ . '/public/components/partials/x-navigation.php'; ?>

        <div class="content-wrapper">

            <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>สมุดโทรศัพท์</p>
            </div>

            <div class="teacher-phone-table-control">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="search-input" placeholder="ค้นหาด้วย ชื่อจริง-นามสกุล หรือ ตำแหน่ง">
                </div>

                <div class="page-selector">
                    <p>จำนวนต่อ 1 หน้า</p>

                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger">
                            <p id="select-value">10</p>
                            <i class="fa-solid fa-caret-down"></i>
                        </div>

                        <div class="custom-options">
                            <div class="custom-option selected" data-value="10">10</div>
                            <div class="custom-option" data-value="20">20</div>
                            <div class="custom-option" data-value="50">50</div>
                            <div class="custom-option" data-value="all">ทั้งหมด</div>
                        </div>

                        <select name="" id="real-page-select">
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="all">ทั้งหมด</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="teacher-phone-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อจริง-นามสกุล</th>
                            <th>ตำแหน่ง</th>
                            <th>เบอร์โทร</th>
                        </tr>
                    </thead>
                    <tbody id="teacher-table-body">
                        <tr>
                            <td>สมชาย ใจดี</td>
                            <td>Software Engineer</td>
                            <td>081-111-1111</td>
                        </tr>
                        <tr>
                            <td>สมหญิง รักเรียน</td>
                            <td>HR Manager</td>
                            <td>081-222-2222</td>
                        </tr>
                        <tr>
                            <td>วิชัย เก่งกาจ</td>
                            <td>Sales Executive</td>
                            <td>089-333-3333</td>
                        </tr>
                        <tr>
                            <td>มานะ อดทน</td>
                            <td>Accountant</td>
                            <td>086-444-4444</td>
                        </tr>
                        <tr>
                            <td>ดารณี มีทรัพย์</td>
                            <td>Marketing</td>
                            <td>081-555-5555</td>
                        </tr>
                        <tr>
                            <td>กิติยา พาเพลิน</td>
                            <td>Designer</td>
                            <td>082-666-6666</td>
                        </tr>
                        <tr>
                            <td>ณัฐพล คนขยัน</td>
                            <td>Developer</td>
                            <td>083-777-7777</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>สมชาย ใจดี</td>
                            <td>Software Engineer</td>
                            <td>081-111-1111</td>
                        </tr>
                        <tr>
                            <td>สมหญิง รักเรียน</td>
                            <td>HR Manager</td>
                            <td>081-222-2222</td>
                        </tr>
                        <tr>
                            <td>วิชัย เก่งกาจ</td>
                            <td>Sales Executive</td>
                            <td>089-333-3333</td>
                        </tr>
                        <tr>
                            <td>มานะ อดทน</td>
                            <td>Accountant</td>
                            <td>086-444-4444</td>
                        </tr>
                        <tr>
                            <td>ดารณี มีทรัพย์</td>
                            <td>Marketing</td>
                            <td>081-555-5555</td>
                        </tr>
                        <tr>
                            <td>กิติยา พาเพลิน</td>
                            <td>Designer</td>
                            <td>082-666-6666</td>
                        </tr>
                        <tr>
                            <td>ณัฐพล คนขยัน</td>
                            <td>Developer</td>
                            <td>083-777-7777</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>สมชาย ใจดี</td>
                            <td>Software Engineer</td>
                            <td>081-111-1111</td>
                        </tr>
                        <tr>
                            <td>สมหญิง รักเรียน</td>
                            <td>HR Manager</td>
                            <td>081-222-2222</td>
                        </tr>
                        <tr>
                            <td>วิชัย เก่งกาจ</td>
                            <td>Sales Executive</td>
                            <td>089-333-3333</td>
                        </tr>
                        <tr>
                            <td>มานะ อดทน</td>
                            <td>Accountant</td>
                            <td>086-444-4444</td>
                        </tr>
                        <tr>
                            <td>ดารณี มีทรัพย์</td>
                            <td>Marketing</td>
                            <td>081-555-5555</td>
                        </tr>
                        <tr>
                            <td>กิติยา พาเพลิน</td>
                            <td>Designer</td>
                            <td>082-666-6666</td>
                        </tr>
                        <tr>
                            <td>ณัฐพล คนขยัน</td>
                            <td>Developer</td>
                            <td>083-777-7777</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>ธีระศักดิ์ รักงาน</td>
                            <td>Director</td>
                            <td>084-888-8888</td>
                        </tr>
                        <tr>
                            <td>พิมพา งามตา</td>
                            <td>Secretary</td>
                            <td>085-999-9999</td>
                        </tr>
                        <tr>
                            <td>ประวิทย์ คิดไว</td>
                            <td>Support</td>
                            <td>087-000-0001</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>
                        <tr>
                            <td>สุดา พาเจริญ</td>
                            <td>Cleaner</td>
                            <td>087-000-0004</td>
                        </tr>
                        <tr>
                            <td>John Doe</td>
                            <td>Consultant</td>
                            <td>090-123-4567</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>UX Researcher</td>
                            <td>090-987-6543</td>
                        </tr>
                        <tr>
                            <td>อาทิตย์ สดใส</td>
                            <td>Intern</td>
                            <td>091-111-2222</td>
                        </tr>
                        <tr>
                            <td>อารี เอื้อเฟื้อ</td>
                            <td>Driver</td>
                            <td>087-000-0002</td>
                        </tr>
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>                                           
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>                                           
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>                                           
                        <tr>
                            <td>วันชัย ใจซื่อ</td>
                            <td>Security</td>
                            <td>087-000-0003</td>
                        </tr>                                           
                    </tbody>
                </table>
            </div>

            <div class="teacher-phone-footer-control">
                <div class="count-text" id="count-text">
                    <p>จำนวน 0 รายชื่อ</p>
                </div>
                <div class="teacher-phone-pagination" id="pagination"></div>
            </div>

        </div>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>