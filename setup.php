<?php
/**
 * Run this ONCE in your browser to create the database and tables.
 * Delete or protect this file afterwards.
 */

$conn = new mysqli('localhost', 'root', '');
if ($conn->connect_error) {
    die('<p style="color:red;font-family:sans-serif;padding:20px">Connection failed: '
        . htmlspecialchars($conn->connect_error) . '</p>');
}

$queries = [
    "CREATE DATABASE IF NOT EXISTS `wedding_rsvp`
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

    "USE `wedding_rsvp`",

    "CREATE TABLE IF NOT EXISTS `rsvps` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `name`       VARCHAR(255)        NOT NULL,
        `email`      VARCHAR(255)        NOT NULL,
        `attending`  ENUM('yes','no')    NOT NULL,
        `guests`     TINYINT UNSIGNED    NOT NULL DEFAULT 1,
        `dietary`    VARCHAR(500)        DEFAULT NULL,
        `message`    TEXT                DEFAULT NULL,
        `created_at` TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

$errors = [];
foreach ($queries as $sql) {
    if (!$conn->query($sql)) {
        $errors[] = htmlspecialchars($conn->error);
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Setup</title>
<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#fdf8f2}
.box{max-width:480px;padding:2rem;border-radius:8px;text-align:center}
.ok{background:#d4edda;color:#155724}.err{background:#f8d7da;color:#721c24}</style>
</head>
<body>
<?php if (empty($errors)): ?>
<div class="box ok">
    <h2>&#10003; Setup complete!</h2>
    <p>Database <strong>wedding_rsvp</strong> and table <strong>rsvps</strong> are ready.</p>
    <p><strong>Delete or rename this file before going live.</strong></p>
    <a href="index.php" style="color:inherit">Go to the site &rarr;</a>
</div>
<?php else: ?>
<div class="box err">
    <h2>&#10007; Setup failed</h2>
    <ul style="text-align:left"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
</body>
</html>
