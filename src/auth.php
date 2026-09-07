<?php
declare(strict_types=1);

function auth_attempt_login(PDO $pdo, string $username, string $password): bool
{
    $user = users_find_by_username($pdo, $username);
    if (!$user || !$user['is_active']) {
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];

    return true;
}

function auth_logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function current_user(): ?array
{
    return isset($_SESSION['user_id']) ? [
        'id' => (int)$_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name'],
    ] : null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect(base_url('login.php'));
    }
    return $user;
}

function require_role(string|array $roles): array
{
    $user = require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
    return $user;
}

function role_home_path(string $role): string
{
    return match ($role) {
        'admin' => base_url('admin/dashboard.php'),
        'teacher' => base_url('teacher/dashboard.php'),
        'student' => base_url('student/dashboard.php'),
        default => base_url('login.php'),
    };
}
