<?php
require_once __DIR__ . '/../../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/AuthController.php';

use App\Controllers\AuthController;

$database = new Database();
$db_connection = $database->connect();

$authController = new AuthController($db_connection);
$authController->forgotPasswordProcess();
?>