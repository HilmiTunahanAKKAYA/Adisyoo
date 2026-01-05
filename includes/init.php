<?php
// initialization include: start session and make DB helper available
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// db.php provides getPDO()
require_once __DIR__ . '/../db.php';
