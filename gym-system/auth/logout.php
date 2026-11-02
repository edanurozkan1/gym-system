<?php
require_once '../db/connection.php';
session_destroy();
redirect('../index.php');
?>
