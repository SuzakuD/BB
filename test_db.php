<?php require_once "config.php"; try { $pdo = getConnection(); echo "DB OK"; } catch(Exception $e) { echo "Error: " . $e->getMessage(); } ?>
