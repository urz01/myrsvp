<?php
require_once 'config.php';
session_start();

$loginError = '';

// ─── Logout ───────────────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ─── Login ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    }
    $loginError = 'Incorrect password.';
}

// ─── Delete RSVP ─────────────────────────────────────────────────────────────
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'delete'
    && isset($_SESSION['admin'])
) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $db   = getDB();
        $stmt = $db->prepare('DELETE FROM rsvps WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $db->close();
    }
    header('Location: admin.php');
    exit;
}

// ─── Fetch data ───────────────────────────────────────────────────────────────
$stats = ['yes' => ['count' => 0, 'guests' => 0], 'no' => ['count' => 0, 'guests' => 0]];
$rsvps = [];

if (isset($_SESSION['admin'])) {
    $db = getDB();

    $res = $db->query(
        'SELECT attending, COUNT(*) AS cnt, SUM(guests) AS total
         FROM rsvps GROUP BY attending'
    );
    while ($row = $res->fetch_assoc()) {
        $stats[$row['attending']]['count']  = (int)$row['cnt'];
        $stats[$row['attending']]['guests'] = (int)$row['total'];
    }

    $search  = trim($_GET['q'] ?? '');
    $filter  = $_GET['filter'] ?? '';
    $baseSQL = 'SELECT * FROM rsvps';
    $where   = [];
    $params  = [];
    $types   = '';

    if ($search !== '') {
        $where[]  = '(name LIKE ? OR email LIKE ?)';
        $like     = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $types   .= 'ss';
    }
    if (in_array($filter, ['yes', 'no'], true)) {
        $where[]  = 'attending = ?';
        $params[] = $filter;
        $types   .= 's';
    }

    $sql  = $baseSQL . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC';
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rsvps[] = $row;
    }
    $stmt->close();
    $db->close();
}

$totalAttending = $stats['yes']['guests'];
$totalRSVPs     = $stats['yes']['count'] + $stats['no']['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?= BRIDE_NAME ?> &amp; <?= GROOM_NAME ?> Wedding</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">

<?php if (!isset($_SESSION['admin'])): ?>
<!-- ─── Login Page ──────────────────────────────────────────────────────────── -->
<div class="login-wrap">
    <div class="login-card">
        <h1 class="login-title">Admin</h1>
        <p class="login-subtitle"><?= BRIDE_NAME ?> &amp; <?= GROOM_NAME ?></p>
        <?php if ($loginError): ?>
            <div class="login-error"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <label class="login-label">Password</label>
            <input
                type="password"
                name="password"
                autofocus
                required
                class="login-input"
                placeholder="Enter admin password"
            >
            <button type="submit" class="login-btn">Sign In</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ─── Dashboard ─────────────────────────────────────────────────────────── -->
<header class="admin-header">
    <div class="admin-header-inner">
        <div>
            <div class="admin-brand-name"><?= BRIDE_NAME ?> &amp; <?= GROOM_NAME ?></div>
            <div class="admin-brand-sub">RSVP Dashboard</div>
        </div>
        <a href="?logout" class="admin-logout">Sign out</a>
    </div>
</header>

<main class="admin-main">

    <!-- Stats -->
    <div class="stats-grid">
        <?php
        $cards = [
            ['label' => 'Total RSVPs',  'value' => $totalRSVPs,            'bg' => 'bg-white'],
            ['label' => 'Attending',    'value' => $stats['yes']['count'],  'bg' => 'bg-green'],
            ['label' => 'Not Attending','value' => $stats['no']['count'],   'bg' => 'bg-red'],
            ['label' => 'Total Guests', 'value' => $totalAttending,         'bg' => 'bg-blush'],
        ];
        foreach ($cards as $c):
        ?>
        <div class="stat-card <?= $c['bg'] ?>">
            <div class="stat-num"><?= $c['value'] ?></div>
            <div class="stat-label"><?= $c['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filter / Search bar -->
    <form method="GET" class="filter-bar">
        <input
            type="text"
            name="q"
            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
            placeholder="Search by name or email…"
            class="filter-input"
        >
        <select name="filter" class="filter-select">
            <option value="">All Responses</option>
            <option value="yes" <?= ($_GET['filter'] ?? '') === 'yes' ? 'selected' : '' ?>>Attending</option>
            <option value="no"  <?= ($_GET['filter'] ?? '') === 'no'  ? 'selected' : '' ?>>Not Attending</option>
        </select>
        <button type="submit" class="filter-btn">Filter</button>
        <?php if (!empty($_GET['q']) || !empty($_GET['filter'])): ?>
            <a href="admin.php" class="filter-clear">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <?php if (empty($rsvps)): ?>
        <div class="empty-state">No RSVPs found.</div>
    <?php else: ?>
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="rsvp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Attending</th>
                        <th>Guests</th>
                        <th>Dietary</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rsvps as $r): ?>
                    <tr>
                        <td class="td-id"><?= $r['id'] ?></td>
                        <td class="td-name"><?= htmlspecialchars($r['name']) ?></td>
                        <td class="td-email"><?= htmlspecialchars($r['email']) ?></td>
                        <td>
                            <?php if ($r['attending'] === 'yes'): ?>
                                <span class="badge badge-yes">✓ Yes</span>
                            <?php else: ?>
                                <span class="badge badge-no">✕ No</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-center"><?= $r['guests'] > 0 ? $r['guests'] : '—' ?></td>
                        <td class="td-truncate" title="<?= htmlspecialchars($r['dietary'] ?? '') ?>">
                            <?= $r['dietary'] ? htmlspecialchars($r['dietary']) : '—' ?>
                        </td>
                        <td class="td-truncate-wide" title="<?= htmlspecialchars($r['message'] ?? '') ?>">
                            <?= $r['message'] ? htmlspecialchars($r['message']) : '—' ?>
                        </td>
                        <td class="td-date"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this RSVP?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id"     value="<?= $r['id'] ?>">
                                <button type="submit" class="delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            Showing <?= count($rsvps) ?> record<?= count($rsvps) !== 1 ? 's' : '' ?>
        </div>
    </div>
    <?php endif; ?>

</main>
<?php endif; ?>
</body>
</html>
