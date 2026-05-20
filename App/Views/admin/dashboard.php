<?php
// Nhúng AdminController chính chủ vào
require_once __DIR__ . '/../../Controllers/AdminController.php';

use App\Controllers\AdminController;

// Khởi tạo và ra lệnh cho Controller bốc dữ liệu lên ném ra View index.php
$adminController = new AdminController();
$adminController->index();
?>