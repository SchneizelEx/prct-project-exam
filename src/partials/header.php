<?php
/** @var array|null $user ต้องกำหนดก่อน include (จาก current_user()) */
$user = $user ?? current_user();
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle ?? 'ระบบลงทะเบียนสอบนำเสนอโครงงาน') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= $user ? role_home_path($user['role']) : base_url('login.php') ?>">
            ระบบลงทะเบียนสอบนำเสนอโครงงาน
        </a>
        <?php if ($user): ?>
        <div class="d-flex align-items-center gap-3">
            <span class="text-light small"><?= h($user['full_name']) ?> (<?= h($user['role']) ?>)</span>
            <a href="<?= base_url('logout.php') ?>" class="btn btn-outline-light btn-sm">ออกจากระบบ</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
<div class="container pb-5">
    <?php foreach (flash_take() as $f): ?>
        <div class="alert alert-<?= h($f['type']) ?>"><?= h($f['message']) ?></div>
    <?php endforeach; ?>
</div>
