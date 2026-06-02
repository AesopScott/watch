<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sessions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// ── Auth ──────────────────────────────────────────────────────────────────────

$auth_error = '';

if (isset($_POST['admin_password'])) {
    if (password_verify($_POST['admin_password'], ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_authenticated'] = true;
    } else {
        $auth_error = 'Incorrect password.';
    }
}

if (isset($_GET['logout_admin'])) {
    unset($_SESSION['admin_authenticated']);
}

$authenticated = $_SESSION['admin_authenticated'] ?? false;

// ── Data helpers ──────────────────────────────────────────────────────────────

function sessions_json(): string { return __DIR__ . '/../data/sessions.json'; }
function recordings_json(): string { return __DIR__ . '/../data/recordings.json'; }

function read_sessions(): array {
    $d = json_decode(file_get_contents(sessions_json()), true);
    return $d['sessions'] ?? [];
}

function write_sessions(array $sessions): void {
    file_put_contents(sessions_json(), json_encode(['sessions' => array_values($sessions)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function read_recordings(): array {
    $d = json_decode(file_get_contents(recordings_json()), true);
    return $d['recordings'] ?? [];
}

function write_recordings(array $recordings): void {
    file_put_contents(recordings_json(), json_encode(['recordings' => array_values($recordings)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// ── POST actions ──────────────────────────────────────────────────────────────

$flash = '';

if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_session') {
        $sessions = read_sessions();
        $id = 'session-' . date('Ymd') . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
        $sessions[] = [
            'id'          => $id,
            'date'        => trim($_POST['date'] ?? ''),
            'title'       => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'zoom_url'    => trim($_POST['zoom_url'] ?? ''),
        ];
        write_sessions($sessions);
        $flash = 'Session added.';
    }

    if ($action === 'delete_session') {
        $del_id   = $_POST['id'] ?? '';
        $sessions = array_filter(read_sessions(), fn($s) => $s['id'] !== $del_id);
        write_sessions($sessions);
        $flash = 'Session deleted.';
    }

    if ($action === 'add_recording') {
        $recs = read_recordings();
        $id   = 'rec-' . date('Ymd') . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
        $recs[] = [
            'id'          => $id,
            'date'        => trim($_POST['date'] ?? ''),
            'title'       => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'url'         => trim($_POST['url'] ?? ''),
        ];
        write_recordings($recs);
        $flash = 'Recording added.';
    }

    if ($action === 'delete_recording') {
        $del_id = $_POST['id'] ?? '';
        $recs   = array_filter(read_recordings(), fn($r) => $r['id'] !== $del_id);
        write_recordings($recs);
        $flash = 'Recording deleted.';
    }
}

// ── Data for view ─────────────────────────────────────────────────────────────

$sessions    = $authenticated ? read_sessions() : [];
$recordings  = $authenticated ? read_recordings() : [];

// Subscribers (read-only)
function read_subscribers(): array {
    $path = __DIR__ . '/../data/subscribers.json';
    if (!file_exists($path)) return [];
    $d = json_decode(file_get_contents($path), true);
    return $d['subscribers'] ?? [];
}
$subscribers = $authenticated ? read_subscribers() : [];

// Sort sessions ascending by date
usort($sessions, fn($a, $b) => strtotime($a['date']) <=> strtotime($b['date']));
// Sort recordings descending by date
usort($recordings, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Watch Me Build AI</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .admin-table { width:100%; border-collapse:collapse; font-size:13px; }
        .admin-table th, .admin-table td { text-align:left; padding:10px 12px; border-bottom:1px solid var(--border); }
        .admin-table th { color:var(--text-muted); font-weight:600; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; }
        .admin-table td { color:var(--text); }
        .admin-table tr:last-child td { border-bottom:none; }
        .form-row { display:grid; gap:12px; margin-bottom:12px; }
        .form-row-2 { grid-template-columns:1fr 1fr; }
        .form-row-3 { grid-template-columns:1fr 1fr 1fr; }
        input[type=text], input[type=datetime-local], textarea {
            width:100%; padding:8px 12px; background:var(--bg);
            border:1px solid var(--border); border-radius:var(--radius);
            color:var(--text); font-size:13px; font-family:var(--font);
            outline:none; transition:border-color 0.15s;
        }
        input:focus, textarea:focus { border-color:var(--accent); }
        textarea { resize:vertical; min-height:60px; }
        .card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); padding:28px; margin-bottom:28px; }
        .card h2 { font-size:18px; font-weight:700; margin-bottom:20px; }
        .tab-bar { display:flex; gap:4px; margin-bottom:28px; border-bottom:1px solid var(--border); }
        .tab { padding:10px 18px; font-size:14px; font-weight:500; cursor:pointer; border-bottom:2px solid transparent; color:var(--text-muted); transition:all 0.15s; }
        .tab.active { color:var(--text); border-bottom-color:var(--accent); }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
        .sub-status-active { color:var(--green); font-weight:600; }
        .sub-status-inactive { color:var(--text-muted); }
        .sub-status-cancelled { color:var(--red); }
    </style>
</head>
<body>

<nav class="nav">
    <div class="nav-inner">
        <a href="/" class="nav-logo">Watch Me Build AI</a>
        <div class="nav-links">
            <?php if ($authenticated): ?>
            <a href="?logout_admin=1" class="btn btn-sm btn-outline">Log Out</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
<div class="page-header">
    <h1>Admin</h1>
</div>

<?php if (!$authenticated): ?>

<!-- ── Login form ── -->
<div class="login-page" style="min-height:auto;padding:40px 0">
    <div class="login-box">
        <h1>Admin Login</h1>
        <?php if ($auth_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($auth_error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label>Password</label>
            <input type="password" name="admin_password" autofocus required style="width:100%;padding:10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);color:var(--text);font-size:15px;margin-bottom:16px;outline:none;">
            <button type="submit" style="width:100%;padding:12px;background:var(--accent);color:#fff;border:none;border-radius:var(--radius);font-size:15px;font-weight:600;cursor:pointer;">Log In</button>
        </form>
    </div>
</div>

<?php else: ?>

<?php if ($flash): ?>
<div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<!-- ── Tabs ── -->
<div class="tab-bar">
    <div class="tab active" onclick="switchTab('sessions')">Sessions</div>
    <div class="tab" onclick="switchTab('recordings')">Recordings</div>
    <div class="tab" onclick="switchTab('subscribers')">Subscribers</div>
</div>

<!-- ── Sessions tab ── -->
<div id="tab-sessions" class="tab-content active">
    <div class="card">
        <h2>Add Session</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_session">
            <div class="form-row form-row-2">
                <div>
                    <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px">Date &amp; Time</label>
                    <input type="datetime-local" name="date" required>
                </div>
                <div>
                    <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px">Zoom URL</label>
                    <input type="text" name="zoom_url" placeholder="https://zoom.us/j/...">
                </div>
            </div>
            <div class="form-row" style="margin-bottom:12px">
                <div>
                    <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px">Title</label>
                    <input type="text" name="title" required placeholder="What are we building?">
                </div>
            </div>
            <div class="form-row" style="margin-bottom:16px">
                <div>
                    <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px">Description (optional)</label>
                    <textarea name="description" placeholder="Brief description of what will be covered..."></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Add Session</button>
        </form>
    </div>

    <div class="card">
        <h2>Scheduled Sessions (<?= count($sessions) ?>)</h2>
        <?php if ($sessions): ?>
        <table class="admin-table">
            <thead>
                <tr><th>Date</th><th>Title</th><th>Zoom</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                <tr>
                    <td style="font-family:var(--mono);white-space:nowrap"><?= htmlspecialchars($s['date']) ?></td>
                    <td>
                        <?= htmlspecialchars($s['title']) ?>
                        <?php if (!empty($s['description'])): ?>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($s['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($s['zoom_url'])): ?>
                        <a href="<?= htmlspecialchars($s['zoom_url']) ?>" target="_blank" style="font-size:12px">link</a>
                        <?php else: ?>
                        <span style="color:var(--text-muted);font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Delete this session?')">
                            <input type="hidden" name="action" value="delete_session">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($s['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--red);border-color:var(--red)">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:var(--text-muted);font-size:14px">No sessions scheduled.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ── Recordings tab ── -->
<div id="tab-recordings" class="tab-content">
    <div class="card">
        <h2>Add Recording</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_recording">
            <div class="form-row form-row-2">
                <div>
                    <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px">Date</label>
                    <input type="datetime-local" name="date" required>
                </div>
                <div>
                    <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px">Recording URL</label>
                    <input type="text" name="url" required placeholder="https://zoom.us/rec/...">
                </div>
            </div>
            <div class="form-row" style="margin-bottom:12px">
                <div>
                    <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px">Title</label>
                    <input type="text" name="title" required placeholder="Session title">
                </div>
            </div>
            <div class="form-row" style="margin-bottom:16px">
                <div>
                    <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px">Description (optional)</label>
                    <textarea name="description"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Add Recording</button>
        </form>
    </div>

    <div class="card">
        <h2>Recordings Library (<?= count($recordings) ?>)</h2>
        <?php if ($recordings): ?>
        <table class="admin-table">
            <thead>
                <tr><th>Date</th><th>Title</th><th>URL</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($recordings as $r): ?>
                <tr>
                    <td style="font-family:var(--mono);white-space:nowrap"><?= htmlspecialchars($r['date']) ?></td>
                    <td>
                        <?= htmlspecialchars($r['title']) ?>
                        <?php if (!empty($r['description'])): ?>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($r['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><a href="<?= htmlspecialchars($r['url']) ?>" target="_blank" style="font-size:12px">watch</a></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Delete this recording?')">
                            <input type="hidden" name="action" value="delete_recording">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--red);border-color:var(--red)">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:var(--text-muted);font-size:14px">No recordings yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ── Subscribers tab ── -->
<div id="tab-subscribers" class="tab-content">
    <div class="card">
        <h2>Subscribers (<?= count($subscribers) ?>)</h2>
        <?php if ($subscribers): ?>
        <table class="admin-table">
            <thead>
                <tr><th>Email</th><th>Status</th><th>Trial</th><th>Started</th><th>Ends / Cancelled</th></tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $email => $sub): ?>
                <tr>
                    <td style="font-family:var(--mono)"><?= htmlspecialchars($email) ?></td>
                    <td>
                        <span class="sub-status-<?= htmlspecialchars($sub['status']) ?>">
                            <?= htmlspecialchars($sub['status']) ?>
                        </span>
                    </td>
                    <td><?= ($sub['trial'] ?? false) ? 'Yes' : '—' ?></td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($sub['started_at'] ?? '—') ?></td>
                    <td style="font-size:12px;color:var(--text-muted)">
                        <?= htmlspecialchars($sub['ends_at'] ?? $sub['cancelled_at'] ?? '—') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:var(--text-muted);font-size:14px">No subscribers yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>
</div><!-- /container -->

<script>
function switchTab(name) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById('tab-' + name).classList.add('active');
}
</script>

</body>
</html>
