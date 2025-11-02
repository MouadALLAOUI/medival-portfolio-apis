<?php
require_once './config.php';
require_once INC_PATH . 'session_manager.php';

$sessionManager = SessionManager::getInstance();
$sessionManager->logout();

header('Location: ../index.php');
exit();
