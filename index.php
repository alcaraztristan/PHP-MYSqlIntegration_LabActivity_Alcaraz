<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// DATABASE CONNECTION
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli('localhost', 'root', '', 'app_db');

if ($conn->connect_error) {
    die("<div style='color:red; padding:20px;'>Database connection failed: " . $conn->connect_error . "</div>");
}

// INITIALIZE VARIABLES
$message = '';
$messageType = 'success';
$search = $_GET['search'] ?? '';
$users = [];
$form = [
    'id' => 0,
    'name' => '',
    'email' => '',
];

// HANDLE POST REQUESTS (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF token verification
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Security token validation failed.");
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';

        if ($id > 0) {
            // Update existing user
            $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $email, $id);
            if ($stmt->execute()) {
                $message = "Record updated successfully!";
            }
            $stmt->close();
        } else {
            // Add new user
            $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $email);
            if ($stmt->execute()) {
                $message = "New record added successfully!";
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        // Delete user
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Record deleted successfully!";
        }
        $stmt->close();
    }
}

// HANDLE GET REQUESTS (Edit Form & Search)

// If edit fetch the specific users current data
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $form = $row;
    }
    $stmt->close();
}

// Fetch all users for the table (with optional search)
if ($search !== '') {
    $search_param = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY id DESC");
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM users ORDER BY id DESC");
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users | Campus Desk</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            background: #f7f8f7;
            color: #25352f;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .navbar {
            background: #fff;
            border-bottom: 1px solid #e5e9e6;
        }
        .brand {
            color: #216b53;
            font-weight: 700;
            text-decoration: none;
        }
        .page-title {
            font-size: 28px;
            font-weight: 700;
        }
        .content-card {
            background: #fff;
            border: 1px solid #e3e8e4;
            border-radius: 10px;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            color: #68756f;
            background: #fafbfa;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e9e6;
        }
        .table td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #edf0ee;
        }
        .table tbody tr:last-child td {
            border-bottom: 0;
        }
        .action-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dfe5e1;
            border-radius: 7px;
            background: #fff;
            color: #52615a;
            text-decoration: none;
        }
        .action-btn:hover {
            background: #f0f6f2;
            color: #216b53;
            border-color: #b9cec1;
        }
        .empty-state {
            padding: 50px 20px;
            text-align: center;
            color: #748079;
        }
        .modal-content {
            border: 0;
            border-radius: 10px;
        }
        .form-control {
            min-height: 42px;
        }
        .btn-success {
            --bs-btn-bg: #216b53;
            --bs-btn-border-color: #216b53;
            --bs-btn-hover-bg: #174d3d;
            --bs-btn-hover-border-color: #174d3d;
        }
        @media (max-width: 575px) {
            .table th, .table td { padding: 12px 10px; }
            .email-column, .date-column { display: none; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container py-3">
            <a class="brand" href="index.php">
                <i class="bi bi-mortarboard-fill me-1"></i>
                Campus Desk
            </a>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h1 class="page-title mb-1">Users</h1>
                <p class="text-secondary mb-0">Manage system records.</p>
            </div>

            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#userModal">
                <i class="bi bi-plus-lg me-1"></i> Add User
            </button>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= escape($messageType) ?> alert-dismissible fade show py-2" role="alert">
                <?= escape($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="content-card overflow-hidden">
            <div class="p-3 border-bottom">
                <form class="d-flex gap-2" method="get" action="index.php" role="search">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input class="form-control border-start-0" name="search" type="search" placeholder="Search by name or email" value="<?= escape($search) ?>">
                    </div>
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                    <?php if ($search !== ''): ?>
                        <a class="btn btn-light border" href="index.php" title="Clear search"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($users)): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="email-column">Email</th>
                                <th class="date-column">Added</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= escape($user['name']) ?></div>
                                        <small class="text-secondary">ID <?= (int) $user['id'] ?></small>
                                    </td>
                                    <td class="email-column"><?= escape($user['email']) ?></td>
                                    <td class="date-column text-secondary small text-nowrap">
                                        <?= escape(date('M j, Y', strtotime($user['created_at']))) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end gap-2">
                                            <a class="action-btn" href="index.php?edit=<?= (int) $user['id'] ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?>" title="Edit user">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="post" action="index.php<?= $search !== '' ? '?search=' . urlencode($search) : '' ?>" onsubmit="return confirm('Delete this record?')">
                                                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                                <button class="action-btn" type="submit" title="Delete user"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2 border-top text-secondary small">
                    Showing <?= count($users) ?> <?= count($users) === 1 ? 'record' : 'records' ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-person-lines-fill fs-2"></i>
                    <h2 class="h6 fw-bold mt-2 mb-1"><?= $search !== '' ? 'No matching users' : 'No users yet' ?></h2>
                    <p class="small mb-0">Click "Add User" to create the first record.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add/Edit User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-sm">
                <div class="modal-header">
                    <h2 class="modal-title fs-5"><?= $form['id'] ? 'Edit User' : 'Add User' ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="index.php<?= $search !== '' ? '?search=' . urlencode($search) : '' ?>">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?= escape($form['id']) ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input class="form-control" name="name" type="text" maxlength="100" placeholder="e.g. Juan Dela Cruz" value="<?= escape($form['name']) ?>" required>
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Email Address</label>
                            <input class="form-control" name="email" type="email" maxlength="100" placeholder="name@school.edu" value="<?= escape($form['email']) ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-success" type="submit">
                            <i class="bi bi-check-lg me-1"></i> <?= $form['id'] ? 'Save Changes' : 'Add User' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($form['id']): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = new bootstrap.Modal(document.getElementById('userModal'));
                modal.show();
            });
        </script>
    <?php endif; ?>
</body>
</html>