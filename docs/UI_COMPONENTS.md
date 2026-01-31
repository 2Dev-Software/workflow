# UI Components

## วิธีเรียกใช้
```php
<?php component_render('button', [
  'label' => 'บันทึก',
  'variant' => 'primary',
]); ?>
```

## รายการ Components

### button
Params:
- `label` (string)
- `variant` (primary|secondary|danger|ghost)
- `size` (sm|md)
- `href` (string) ถ้ามีจะเป็น `<a>`
- `icon` (string) เช่น `fa-solid fa-plus`

Example:
```php
<?php component_render('button', [
  'label' => 'เพิ่มข้อมูล',
  'variant' => 'primary',
  'icon' => 'fa-solid fa-plus'
]); ?>
```

### input
Params: `name`, `type`, `value`, `label`, `placeholder`, `required`

### select
Params: `name`, `label`, `options` (array), `selected`

### textarea
Params: `name`, `label`, `value`, `rows`

### card
Params: `title`, `subtitle`, `content`

### table
Params: `headers` (array), `rows` (array of array), `empty_text`

### badge
Params: `label`, `variant` (neutral|success|warning|danger)

### alert
Params: `type` (info|success|warning|danger), `title`, `message`

### empty-state
Params: `title`, `message`, `action` (array for button)

### pagination
Params: `page`, `total_pages`, `base_url`

### modal
Params: `id`, `title`, `body`, `actions` (array of button config)

### toast
ใช้แค่ container แล้วเรียกผ่าน data-attributes
```php
<?php component_render('toast'); ?>
```
