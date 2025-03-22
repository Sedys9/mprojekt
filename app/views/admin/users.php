<?php
require_once __DIR__ . '/../../models/User.php';

// Get list of all users
$users = User::getAllUsers();

// Get current action (default is list)
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Get user ID for edit/view actions
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$editUser = null;

// Load user details for edit form
if ($action === 'edit' && $userId) {
    $editUser = User::getUserById($userId);
    if (!$editUser) {
        echo '<div class="alert alert-danger">Uživatel nenalezen!</div>';
        $action = 'list';
    }
}
?>

<div class="user-management">
    <?php if ($action === 'list'): ?>
        <div class="user-actions">
            <h2>Správa uživatelů</h2>
            <a href="/mprojekt/public/admin?tab=users&action=create" class="btn btn-primary">Přidat nového uživatele</a>
        </div>
        
        <div class="table-responsive">
            <table class="admin-products">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Jméno</th>
                        <th>Příjmení</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registrován</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['first_name']) ?></td>
                            <td><?= htmlspecialchars($user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="role-badge role-<?= $user['role'] ?>">
                                    <?= $user['role'] == 'admin' ? 'Administrátor' : 'Uživatel' ?>
                                </span>
                            </td>
                            <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                            <td class="actions">
                                <a href="/mprojekt/public/admin?tab=users&action=edit&user_id=<?= $user['id'] ?>" class="btn btn-warning">Upravit</a>
                                <?php if ($user['id'] != $_SESSION['user_id']): // Prevent deleting yourself ?>
                                    <a href="/mprojekt/public/admin/user/delete?user_id=<?= $user['id'] ?>" class="btn btn-danger" onclick="return confirm('Opravdu chcete smazat tohoto uživatele?');">Smazat</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <?php if ($action === 'edit' && $editUser): ?>
        <h2>Úprava uživatele</h2>
        
        <form action="/mprojekt/public/admin/user/update" method="POST" class="user-form">
            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Jméno:</label>
                    <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($editUser['first_name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Příjmení:</label>
                    <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($editUser['last_name']) ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="user" <?= $editUser['role'] === 'user' ? 'selected' : '' ?>>Uživatel</option>
                    <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Administrátor</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Nové heslo (ponechte prázdné, pokud nechcete měnit):</label>
                <input type="password" id="password" name="password" minlength="6">
                <small>Minimálně 6 znaků</small>
            </div>
            
            <div class="form-actions">
                <a href="/mprojekt/public/admin?tab=users" class="btn btn-secondary">Zpět</a>
                <button type="submit" class="btn btn-primary">Uložit změny</button>
            </div>
        </form>
    <?php endif; ?>
    
    <?php if ($action === 'create'): ?>
        <h2>Přidat nového uživatele</h2>
        
        <form action="/mprojekt/public/admin/user/create" method="POST" class="user-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Jméno:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Příjmení:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Heslo:</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small>Minimálně 6 znaků</small>
            </div>
            
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="user" selected>Uživatel</option>
                    <option value="admin">Administrátor</option>
                </select>
            </div>
            
            <div class="form-actions">
                <a href="/mprojekt/public/admin?tab=users" class="btn btn-secondary">Zpět</a>
                <button type="submit" class="btn btn-primary">Vytvořit uživatele</button>
            </div>
        </form>
    <?php endif; ?>
</div>
