<?php
$_GET['archived'] = '1';
require_once __DIR__ . '/app/controllers/orders-inbox-controller.php';
orders_inbox_index();
