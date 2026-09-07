<?php
require_once __DIR__ . '/../src/bootstrap.php';

auth_logout();
redirect(base_url('login.php'));
