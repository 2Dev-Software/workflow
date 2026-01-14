<?php
require_once __DIR__ . '/src/Services/auth/auth-guard.php';

$teacher_directory_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    'options' => ['default' => 1, 'min_range' => 1],
]);
$teacher_directory_per_page_param = $_GET['per_page'] ?? '10';
if ($teacher_directory_per_page_param === 'all') {
    $teacher_directory_per_page = 'all';
} else {
    $teacher_directory_per_page = filter_var($teacher_directory_per_page_param, FILTER_VALIDATE_INT, [
        'options' => ['default' => 10, 'min_range' => 1],
    ]);
}
$teacher_directory_allowed_per_page = [10, 20, 50, 'all'];
if (!in_array($teacher_directory_per_page, $teacher_directory_allowed_per_page, true)) {
    $teacher_directory_per_page = 10;
}

$teacher_directory_query = trim((string) ($_GET['q'] ?? ''));
$teacher_directory_display_per_page = $teacher_directory_per_page === 'all'
    ? 'ทั้งหมด'
    : (string) $teacher_directory_per_page;

require_once __DIR__ . '/src/Services/teacher/teacher-directory.php';
?>
<!DOCTYPE html>
<html lang="th">
<?php require_once __DIR__ . '/public/components/x-head.php'; ?>

<body>

    <?php require_once __DIR__ . '/public/components/layout/preloader.php'; ?>

    <?php require_once __DIR__ . '/public/components/partials/x-sidebar.php'; ?>

    <section class="main-section">

        <?php require_once __DIR__ . '/public/components/partials/x-navigation.php'; ?>

        <main class="content-wrapper">

            <div class="content-header">
                <h1>ยินดีต้อนรับ</h1>
                <p>สมุดโทรศัพท์</p>
            </div>

            <div class="teacher-phone-table-control">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="search-input" value="<?= htmlspecialchars($teacher_directory_query, ENT_QUOTES, 'UTF-8') ?>" placeholder="ค้นหาด้วย ชื่อจริง-นามสกุล หรือ กลุ่มสาระ หรือ เบอร์โทรศัพท์">
                </div>

                <div class="page-selector">
                    <p>จำนวนต่อ 1 หน้า</p>

                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger">
                            <p id="select-value"><?= htmlspecialchars($teacher_directory_display_per_page, ENT_QUOTES, 'UTF-8') ?></p>
                            <i class="fa-solid fa-caret-down"></i>
                        </div>

                        <div class="custom-options">
                            <div class="custom-option<?= $teacher_directory_per_page === 10 ? ' selected' : '' ?>" data-value="10">10</div>
                            <div class="custom-option<?= $teacher_directory_per_page === 20 ? ' selected' : '' ?>" data-value="20">20</div>
                            <div class="custom-option<?= $teacher_directory_per_page === 50 ? ' selected' : '' ?>" data-value="50">50</div>
                            <div class="custom-option<?= $teacher_directory_per_page === 'all' ? ' selected' : '' ?>" data-value="all">ทั้งหมด</div>
                        </div>

                        <select name="" id="real-page-select">
                            <option value="10" <?= $teacher_directory_per_page === 10 ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= $teacher_directory_per_page === 20 ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= $teacher_directory_per_page === 50 ? 'selected' : '' ?>>50</option>
                            <option value="all" <?= $teacher_directory_per_page === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="teacher-phone-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อจริง-นามสกุล</th>
                            <th>กลุ่มสาระฯ</th>
                            <th>เบอร์โทร</th>
                        </tr>
                    </thead>
                    
                    <tbody id="teacher-table-body" data-endpoint="public/api/teacher-directory-api.php">
                        <?php if (empty($teacher_directory)) : ?>
                            <tr>
                                <td colspan="3" style="text-align:center; padding: 20px;">ไม่พบข้อมูล</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($teacher_directory as $teacher_item) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($teacher_item['fName'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($teacher_item['department_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($teacher_item['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>

            <div class="teacher-phone-footer-control">
                <div class="count-text" id="count-text">
                    <p>จำนวน <?= number_format($teacher_directory_total) ?> รายชื่อ</p>
                </div>
                <div class="teacher-phone-pagination" id="pagination">
                    <?php if ($teacher_directory_per_page !== 'all' && $teacher_directory_total_pages > 1) : ?>
                        <?php
                        $total_pages = $teacher_directory_total_pages;
                        $current_page = $teacher_directory_page;
                        $prev_page = max(1, $current_page - 1);
                        $next_page = min($total_pages, $current_page + 1);
                        ?>
                        <button type="button" data-page="<?= $prev_page ?>" <?= $current_page <= 1 ? 'disabled' : '' ?> aria-label="Previous page">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <?php
                        $start_page = 1;
                        $end_page = $total_pages;

                        if ($total_pages > 7) {
                            if ($current_page <= 4) {
                                $end_page = 5;
                            } elseif ($current_page >= $total_pages - 3) {
                                $start_page = $total_pages - 4;
                            } else {
                                $start_page = $current_page - 2;
                                $end_page = $current_page + 2;
                            }
                        }

                        if ($start_page > 1) {
                            ?>
                            <button type="button" data-page="1" <?= $current_page === 1 ? 'class="active"' : '' ?>>1</button>
                            <?php if ($start_page > 2) : ?>
                                <span style="padding: 0 5px;">...</span>
                            <?php endif; ?>
                            <?php
                        }

                        for ($i = $start_page; $i <= $end_page; $i++) {
                            ?>
                            <button type="button" data-page="<?= $i ?>" <?= $i === $current_page ? 'class="active"' : '' ?>><?= $i ?></button>
                            <?php
                        }

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                ?>
                                <span style="padding: 0 5px;">...</span>
                                <?php
                            }
                            ?>
                            <button type="button" data-page="<?= $total_pages ?>" <?= $current_page === $total_pages ? 'class="active"' : '' ?>><?= $total_pages ?></button>
                            <?php
                        }
                        ?>
                        <button type="button" data-page="<?= $next_page ?>" <?= $current_page >= $total_pages ? 'disabled' : '' ?> aria-label="Next page">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        </main>

        <?php require_once __DIR__ . '/public/components/partials/x-footer.php'; ?>


    </section>


    <?php require_once __DIR__ . '/public/components/x-scripts.php'; ?>
</body>

</html>
