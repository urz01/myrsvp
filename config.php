<?php
// ─── Database ─────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wedding_rsvp');

// ─── Wedding Details — Customise these! ───────────────────────────────────────
define('GROOM_NAME',     'Uriel');
define('BRIDE_NAME',     'Jobelle');
define('WEDDING_DATE',   'September 01, 2026');
define('WEDDING_DATE_ISO', '2026-09-01T16:00:00'); // used by countdown timer
define('WEDDING_TIME',   '9:00 AM');
define('CEREMONY',     "Kingdom Hall of Jehovah's Witnesses Roxas");
define('RECEPTION',     "L.D. Ignacio's Island Hotel and Resort");
define('VENUE_NAME',    "L.D. Ignacio's Island Hotel and Resort");
define('VENUE_ADDRESS',  'Sta. Fe, Roxas, Oriental Mindoro');
define('RSVP_DEADLINE',  'August 15, 2026');
define('HASHTAG',        '#URIEL&JOBELLE');

// ─── Dress Code & Motif ───────────────────────────────────────────────────────
define('DRESS_CODE_TYPE', 'Semi-Formal');
define('DRESS_CODE_DESC', 'We kindly ask our guests to wear semi-formal or formal attire and observe our motif colors to keep our celebration beautifully coordinated.');
define('MOTIF_THEME',     'Dusty Rose & Gold');

// ─── Map & Directions ─────────────────────────────────────────────────────────
define('MAP_LINK',        'https://maps.google.com/?q=Sta.+Fe+Roxas+Oriental+Mindoro'); // Update with exact venue pin
define('MAP_EMBED_URL',   ''); // Paste your Google Maps embed src URL here

// ─── Gift Note ────────────────────────────────────────────────────────────────
define('GIFT_NOTE',  'Your presence at our wedding is the greatest gift we could ever ask for. For those who wish to send a monetary gift, it may be given through our principal sponsors on the day of the event. We are deeply grateful for your love and generosity.');
define('GIFT_GCASH', ''); // Optional: e.g. '0917-000-0000'
define('GIFT_BANK',  ''); // Optional: e.g. 'BDO Savings · 1234 5678 9012'

// ─── Admin ────────────────────────────────────────────────────────────────────
// IMPORTANT: Change this password before going live!
define('ADMIN_PASSWORD', 'ChangeMe123!');

// ─── DB helper ────────────────────────────────────────────────────────────────
function getDB(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
