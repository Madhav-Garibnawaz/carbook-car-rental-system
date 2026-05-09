<?php

    include("connect.php");
    
    $brand_id=$_GET['brand_id'];

    echo "<option value='' >Select Model</option>";
    $q=mysqli_query($con,"select * from model_master where brand_id=$brand_id");
    while($row=mysqli_fetch_assoc($q))
    {
        echo "<option value=$row[model_id]>$row[model_name]</option>";
    }
?>