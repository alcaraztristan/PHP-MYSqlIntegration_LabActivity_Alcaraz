<?php
require_once 'config.php';

try {
    // Running a simple query directly since no user input is involved
    $result = $conn->query("SELECT id, name, email, created_at FROM users ORDER BY id DESC");
    $users = $result->fetch_all(MYSQLI_ASSOC); // Fetch rows directly as an associative array
    $result->free();
} catch (mysqli_sql_exception $e) {
    die("Could not retrieve users from the database.");
}
?>

<h2>Registered Users</h2>
<table border="1" cellpadding="10" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($users)): ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                    <td>
                        <a href="update.php?id=<?= $user['id'] ?>">Edit</a> | 
                        <a href="delete.php?id=<?= $user['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No users found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>