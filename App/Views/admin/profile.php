<?php
require_once __DIR__ . '/../../Controllers/AdminController.php';

use App\Controllers\AdminController;

$adminController = new AdminController();
$adminController->profile();
?>
