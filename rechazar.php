<?php
session_start();
require_once 'includes/auth.php';
require_module('FIRMAR');

header("Location: gestion.php?error=documentos_backend");
exit;
