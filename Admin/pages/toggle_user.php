<?php
require("connect.php");
session_name('admin_session');
session_start();

if(isset($_GET['id']) && isset($_GET['status'])){
    $uid    = intval($_GET['id']);
    $status = intval($_GET['status']); // 1 = activate, 0 = deactivate

    $result = mysqli_query($con, "UPDATE users_master SET status=$status WHERE ui=$uid");

    if($result){
        if($status === 2){
            $_SESSION['admin_toast'] = [
                'type' => 'success',
                'msg'  => 'User #'.str_pad($uid,6,'0',STR_PAD_LEFT).' has been activated successfully.'
            ];
        } else {
            $_SESSION['admin_toast'] = [
                'type' => 'warning',
                'msg'  => 'User #'.str_pad($uid,6,'0',STR_PAD_LEFT).' has been deactivated.'
            ];
        }
    }
}

header("location: user_master.php");
exit;
?>