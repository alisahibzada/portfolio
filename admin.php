<?php
session_start();

// ==========================================
// 1. CONFIGURATION
// ==========================================
$admin_password = "AliShah@2026"; // Change this to your preferred password

$host     = 'localhost';
$dbname   = 'ali_portfolio_db';
$username = 'root';
$password = ''; // Default XAMPP password is empty

// Generate CSRF token for secure delete/update actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================================
// 2. AUTHENTICATION (LOGIN / LOGOUT)
// ==========================================
$login_error = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $entered_password = $_POST['admin_password'] ?? '';
    if (hash_equals($admin_password, $entered_password)) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $login_error = "Incorrect password. Please try again.";
    }
}

$is_logged_in = $_SESSION['admin_logged_in'] ?? false;

// ==========================================
// 3. DATABASE ACTIONS (IF LOGGED IN)
// ==========================================
$messages = [];
$action_alert = '';

if ($is_logged_in) {
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Handle Delete or Mark as Read via POST + CSRF check
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                die("Invalid CSRF token.");
            }

            $msg_id = filter_input(INPUT_POST, 'msg_id', FILTER_VALIDATE_INT);

            if ($msg_id) {
                if ($_POST['action'] === 'delete') {
                    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = :id");
                    $stmt->execute([':id' => $msg_id]);
                    $action_alert = '<div class="alert alert-warning alert-dismissible fade show">Message deleted successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                } elseif ($_POST['action'] === 'toggle_read') {
                    $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = NOT is_read WHERE id = :id");
                    $stmt->execute([':id' => $msg_id]);
                }
            }
        }

        // Fetch all messages ordered by newest first
        $stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY submitted_at DESC");
        $messages = $stmt->fetchAll();

    } catch (PDOException $e) {
        $action_alert = '<div class="alert alert-danger">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Sahibzada Ali Shah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #0b1120;
            color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .custom-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
        }
        .custom-input {
            background-color: #0f172a;
            border: 1px solid #334155;
            color: #f8fafc;
        }
        .custom-input:focus {
            background-color: #0f172a;
            border-color: #0dcaf0;
            color: #f8fafc;
            box-shadow: 0 0 0 0.2rem rgba(13, 202, 240, 0.25);
        }
        .table-dark-custom {
            --bs-table-bg: #1e293b;
            --bs-table-striped-bg: #172033;
            --bs-table-hover-bg: #263449;
            --bs-table-border-color: #334155;
            color: #f8fafc;
        }
        .unread-row {
            border-left: 4px solid #0dcaf0;
            font-weight: 500;
        }
    </style>
</head>
<body>

<?php if (!$is_logged_in): ?>
    <!-- LOGIN SCREEN -->
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card custom-card p-4 shadow-lg" style="max-width: 420px; width: 100%;">
            <div class="text-center mb-4">
                <div class="fs-1 text-info mb-2"><i class="fas fa-user-shield"></i></div>
                <h3 class="fw-bold text-white">Admin Login</h3>
                <p class="text-secondary small">Sahibzada Ali Shah — Portfolio Inbox</p>
            </div>

            <?php if ($login_error): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>

            <form method="POST" action="admin.php">
                <div class="mb-3">
                    <label class="form-label text-secondary small">Admin Password</label>
                    <input type="password" name="admin_password" class="form-control custom-input" placeholder="Enter admin password" required autofocus>
                </div>
                <button type="submit" name="login_submit" class="btn btn-info w-100 fw-semibold text-dark">
                    <i class="fas fa-sign-in-alt me-2"></i>Unlock Dashboard
                </button>
            </form>
            <div class="text-center mt-3">
                <a href="index.html" class="text-secondary text-decoration-none small">&larr; Back to Portfolio</a>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ADMIN DASHBOARD -->
    <nav class="navbar navbar-dark bg-dark border-bottom border-secondary border-opacity-25 py-3">
        <div class="container">
            <span class="navbar-brand fw-bold">
                <i class="fas fa-inbox text-info me-2"></i>Ali<span class="text-info">Shah</span> Inbox
            </span>
            <div class="d-flex gap-2">
                <a href="index.html" class="btn btn-outline-light btn-sm">View Site</a>
                <a href="admin.php?logout=1" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <?= $action_alert ?>

        <!-- Stats Summary -->
        <?php
            $total_count = count($messages);
            $unread_count = count(array_filter($messages, fn($m) => $m['is_read'] == 0));
        ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card custom-card p-3">
                    <small class="text-secondary">Total Messages</small>
                    <h3 class="text-white fw-bold mb-0"><?= $total_count ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card p-3">
                    <small class="text-secondary">Unread Messages</small>
                    <h3 class="text-info fw-bold mb-0"><?= $unread_count ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card p-3">
                    <small class="text-secondary">Read Messages</small>
                    <h3 class="text-success fw-bold mb-0"><?= $total_count - $unread_count ?></h3>
                </div>
            </div>
        </div>

        <!-- Messages Table -->
        <div class="card custom-card p-4">
            <h4 class="text-white mb-4">Contact Form Submissions</h4>

            <?php if (empty($messages)): ?>
                <div class="text-center py-5 text-secondary">
                    <i class="fas fa-folder-open fs-1 mb-3"></i>
                    <p class="mb-0">No messages received yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark-custom table-hover align-middle">
                        <thead>
                            <tr class="text-info small text-uppercase">
                                <th>Status</th>
                                <th>Sender</th>
                                <th>Subject & Message</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                                <tr class="<?= $msg['is_read'] ? '' : 'unread-row' ?>">
                                    <td>
                                        <?php if ($msg['is_read']): ?>
                                            <span class="badge bg-secondary bg-opacity-25 text-secondary">Read</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">New</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-white"><?= htmlspecialchars($msg['sender_name']) ?></div>
                                        <a href="mailto:<?= htmlspecialchars($msg['sender_email']) ?>" class="text-info small text-decoration-none">
                                            <?= htmlspecialchars($msg['sender_email']) ?>
                                        </a>
                                    </td>
                                    <td style="max-width: 380px;">
                                        <div class="fw-semibold text-white small"><?= htmlspecialchars($msg['subject']) ?></div>
                                        <div class="text-secondary small text-truncate"><?= htmlspecialchars($msg['message']) ?></div>
                                    </td>
                                    <td class="small text-secondary">
                                        <?= date("M d, Y h:i A", strtotime($msg['submitted_at'])) ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <!-- View Modal Trigger -->
                                        <button type="button" class="btn btn-sm btn-outline-info me-1" data-bs-toggle="modal" data-bs-target="#viewModal<?= $msg['id'] ?>" title="Read Full Message">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <!-- Toggle Read/Unread -->
                                        <form method="POST" action="admin.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
                                            <input type="hidden" name="action" value="toggle_read">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary me-1" title="Toggle Read Status">
                                                <i class="fas <?= $msg['is_read'] ? 'fa-envelope' : 'fa-envelope-open' ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Delete Form -->
                                        <form method="POST" action="admin.php" class="d-inline" onsubmit="return confirm('Delete this message permanently?');">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Message">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Bootstrap Modal to Read Full Message -->
                                <div class="modal fade" id="viewModal<?= $msg['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content custom-card text-white">
                                            <div class="modal-header border-secondary border-opacity-25">
                                                <h5 class="modal-title text-info"><?= htmlspecialchars($msg['subject']) ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="small text-secondary mb-1">
                                                    <strong>From:</strong> <?= htmlspecialchars($msg['sender_name']) ?> (<?= htmlspecialchars($msg['sender_email']) ?>)
                                                </p>
                                                <p class="small text-secondary mb-3">
                                                    <strong>Received:</strong> <?= date("F j, Y \a\t g:i A", strtotime($msg['submitted_at'])) ?>
                                                </p>
                                                <div class="p-3 rounded" style="background-color: #0f172a; white-space: pre-wrap;"><?= htmlspecialchars($msg['message']) ?></div>
                                            </div>
                                            <div class="modal-footer border-secondary border-opacity-25">
                                                <a href="mailto:<?= htmlspecialchars($msg['sender_email']) ?>?subject=Re: <?= rawurlencode($msg['subject']) ?>" class="btn btn-info text-dark fw-semibold btn-sm">
                                                    <i class="fas fa-reply me-1"></i>Reply via Email
                                                </a>
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>