<?php
session_name('driver_session');
session_start();
session_unset();
session_destroy();
header("Location: register.php");
exit;
?>