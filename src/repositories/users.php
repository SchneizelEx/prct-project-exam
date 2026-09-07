<?php
declare(strict_types=1);

function users_find_by_username(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function users_find(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function users_all(PDO $pdo, ?string $role = null): array
{
    if ($role !== null) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE role = ? ORDER BY full_name');
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
    $stmt = $pdo->query('SELECT * FROM users ORDER BY role, full_name');
    return $stmt->fetchAll();
}

function users_create(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO users (role, username, password_hash, full_name, email, code, is_active)
         VALUES (:role, :username, :password_hash, :full_name, :email, :code, 1)'
    );
    $stmt->execute([
        'role' => $data['role'],
        'username' => $data['username'],
        'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
        'full_name' => $data['full_name'],
        'email' => $data['email'] !== '' ? $data['email'] : null,
        'code' => $data['code'] !== '' ? $data['code'] : null,
    ]);
    return (int)$pdo->lastInsertId();
}

function users_set_active(PDO $pdo, int $id, bool $active): void
{
    $stmt = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?');
    $stmt->execute([$active ? 1 : 0, $id]);
}

function users_reset_password(PDO $pdo, int $id, string $newPassword): void
{
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id]);
}
