<?php
session_start();
require_once 'includes/auth.php';

require_login();

header('Location: principal.php');
exit;
