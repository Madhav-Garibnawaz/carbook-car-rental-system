<!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<?php
include("connect.php");
$x = $_GET['id'];
echo "id is : $x";
$q = mysqli_query($con,"delete from category_master where category_id=$x");
if($q) {
    echo "<div class='alert alert-success'>Deleting Category...</div>";
    header("location:view_categories.php");
} else {
    echo "<div class='alert alert-danger'>Database error</div>";
}
?>