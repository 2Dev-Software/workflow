<?php
$params = $params ?? [];
$headers = (array) ($params['headers'] ?? []);
$rows = (array) ($params['rows'] ?? []);
$empty_text = (string) ($params['empty_text'] ?? 'ไม่มีข้อมูล');
$extra_class = (string) ($params['class'] ?? '');
$wrap_class = (string) ($params['wrap_class'] ?? '');
$attrs = (array) ($params['attrs'] ?? []);

$attrs['class'] = trim('c-table custom-table booking-table ' . $extra_class);
?>
<div class="<?= h(trim('c-table__wrap table-responsive ' . $wrap_class)) ?>">
    <table<?= component_attr($attrs) ?>>
        <?php if (!empty($headers)) : ?>
            <thead>
                <tr>
                    <?php foreach ($headers as $header) : ?>
                        <th><?= h((string) $header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
        <?php endif; ?>
        <tbody>
            <?php if (empty($rows)) : ?>
                <tr>
                    <td colspan="<?= h((string) max(1, count($headers))) ?>" class="c-table__empty booking-empty">
                        <?= h($empty_text) ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <?php foreach ((array) $row as $cell) : ?>
                            <td>
                                <?php if (is_array($cell) && isset($cell['link'])) : ?>
                                    <?php $link = (array) $cell['link']; ?>
                                    <a class="c-link" href="<?= h((string) ($link['href'] ?? '#')) ?>"><?= h((string) ($link['label'] ?? '')) ?></a>
                                <?php elseif (is_array($cell) && isset($cell['component'])) : ?>
                                    <?php $component = (array) $cell['component']; ?>
                                    <?php component_render((string) ($component['name'] ?? ''), (array) ($component['params'] ?? [])); ?>
                                <?php elseif (is_array($cell) && isset($cell['form'])) : ?>
                                    <?php $form = (array) $cell['form']; ?>
                                    <form class="c-table__form" method="<?= h((string) ($form['method'] ?? 'post')) ?>" action="<?= h((string) ($form['action'] ?? '')) ?>">
                                        <?php if (function_exists('csrf_field')) : ?>
                                            <?= csrf_field() ?>
                                        <?php endif; ?>
                                        <?php foreach ((array) ($form['hidden'] ?? []) as $name => $value) : ?>
                                            <input type="hidden" name="<?= h((string) $name) ?>" value="<?= h((string) $value) ?>">
                                        <?php endforeach; ?>
                                        <?php if (!empty($form['button'])) : ?>
                                            <?php component_render('button', (array) $form['button']); ?>
                                        <?php endif; ?>
                                    </form>
                                <?php else : ?>
                                    <?= h((string) $cell) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
