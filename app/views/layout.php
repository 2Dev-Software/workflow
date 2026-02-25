<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/view.php';
?>
<!DOCTYPE html>
<html lang="th">
<?php require __DIR__ . '/../../public/components/x-head.php'; ?>
<body<?= component_attr($body_attrs ?? []) ?>>
<?php require __DIR__ . '/../../public/components/layout/preloader.php'; ?>

    <?php if (!empty($alert)) : ?>
        <?php $alert = $alert; ?>
        <?php require __DIR__ . '/../../public/components/x-alert.php'; ?>
    <?php endif; ?>

    <?php require __DIR__ . '/../../public/components/partials/x-sidebar.php'; ?>

    <section class="main-section">
        <?php require __DIR__ . '/../../public/components/partials/x-navigation.php'; ?>

        <main class="content-wrapper">
            <?= $content ?? '' ?>
        </main>

        <?php require __DIR__ . '/../../public/components/partials/x-footer.php'; ?>
    </section>

    <?php component_render('toast'); ?>
    <?php require __DIR__ . '/../../public/components/x-scripts.php'; ?>
</body>
</html>
