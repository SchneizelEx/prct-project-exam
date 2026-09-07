<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = current_user();
redirect($user ? role_home_path($user['role']) : base_url('login.php'));
