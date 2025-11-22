<?php
require "config/Database.php";

$db = new Database();
$conn = $db->connect();

echo "CONNECTED TO SUPABASE!";
?>
