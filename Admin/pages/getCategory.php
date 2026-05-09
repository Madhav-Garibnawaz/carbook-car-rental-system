<?php

    include("connect.php");
    
    $brand_id=$_GET['brand_id'];

    echo "<option value='' >Select Category</option>";
    $q=mysqli_query($con,"select * from category_master where brand_id=$brand_id");
    while($row=mysqli_fetch_assoc($q))
    {
        echo "<option value=$row[category_id]>$row[category_name]</option>";
    }
?>