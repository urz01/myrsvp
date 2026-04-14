<?php
require_once 'config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ─── Sanitise inputs ──────────────────────────────────────────────────────────
$name      = trim($_POST['name']      ?? '');
$email     = trim($_POST['email']     ?? '');
$attending = trim($_POST['attending'] ?? '');
$guests    = (int)($_POST['guests']   ?? 1);
$dietary   = trim($_POST['dietary']   ?? '');
$message   = trim($_POST['message']   ?? '');

// ─── Validate ─────────────────────────────────────────────────────────────────
$errors = [];

if (mb_strlen($name) < 2) {
    $errors[] = 'Please enter your full name.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if (!in_array($attending, ['yes', 'no'], true)) {
    $errors[] = 'Please indicate whether you will attend.';
}
if ($attending === 'yes' && ($guests < 1 || $guests > 10)) {
    $errors[] = 'Please enter a valid number of guests (1–10).';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$db = getDB();

// ─── Duplicate check ──────────────────────────────────────────────────────────
$stmt = $db->prepare('SELECT id FROM rsvps WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $db->close();
    echo json_encode(['success' => false, 'message' => 'An RSVP with this email already exists. Please contact us if you need to make changes.']);
    exit;
}
$stmt->close();

// ─── Insert ───────────────────────────────────────────────────────────────────
$guestCount = ($attending === 'yes') ? $guests : 0;

$stmt = $db->prepare(
    'INSERT INTO rsvps (name, email, attending, guests, dietary, message)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('sssiss', $name, $email, $attending, $guestCount, $dietary, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Thank you! Your RSVP has been received. We can\'t wait to celebrate with you!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}

$stmt->close();
$db->close();
