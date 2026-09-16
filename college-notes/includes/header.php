<?php
/**
 * Reusable Header Layout
 * Variables available: $pageTitle, $customCss (array)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$title = isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME . ' - Academic Resource Portal';
$user = currentUser();
$role = currentRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($title) ?></title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6.5.2 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Core App CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

    <?php if (isset($customCss) && is_array($customCss)): ?>
        <?php foreach ($customCss as $css): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/<?= e($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?= $user ? 'dashboard-body' : 'public-body' ?>">
