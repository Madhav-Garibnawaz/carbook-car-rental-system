<?php 
    include('connect.php');
    session_name('admin_session');
    session_start();
    include('../components/navbar.php'); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Categories</title>

    <style>
        /* ---- YOUR EXISTING CSS (UNCHANGED) ---- */

        html, body {
            height: 100%;
            overflow: hidden;
        }

        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0b5ed7;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --border-color: #dee2e6;
            --focus-color: rgba(13, 110, 253, 0.25);
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .content {
                min-height: calc(100vh - 70px); /* adjust if navbar height differs */
                display: flex;
                align-items: center;
                justify-content: center; /* horizontal center */
        }

        .main-container {
                width: 100%;
        }

        .form-card {
            max-width: 600px;
            margin: auto;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .card-body {
            padding: 2.5rem;
        }

        .form-footer {
            background: #f8f9fa;
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }
        
    </style>
</head>

<body>

<!-- ===== MAIN PANEL CONTENT ===== -->
<div class="main-panel1">
    <div class="content">
        <div class="main-container mt-5">

            <?php
                if (isset($_POST['insbrand'])) {

                    $brand_id = $_POST['ddlbrand'];
                    $category_name = $_POST['ct_name'];
                    $category_image = $_FILES['ct_image']['name'];
                    $tmp        = $_FILES['ct_image']['tmp_name'];
                    $dst        = "./images/category_images/" . $category_image;

                    if (!preg_match("/^[A-Za-z ]+$/", $category_name)) {
                        echo "Only alphabets allowed";
                        exit;
                    }
                    if (!empty($category_name) && !empty($category_image)) {
                        $result = mysqli_query($con,"SELECT * FROM category_master WHERE category_name='$category_name' AND brand_id='$brand_id'");
                        if(mysqli_num_rows($result) > 0) {
                            echo "
                            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                            <script>
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Already Exists!',
                                    text: 'This Category name already exists!..',
                                    confirmButtonColor: '#d33'
                                });
                            </script>;";
                        } else {
                            if (move_uploaded_file($tmp, $dst)) {

                            $q = "insert into category_master values ('', $brand_id, '$category_name', '$category_image', 1)";

                            if (mysqli_query($con, $q)) {
                                echo "<div class='alert alert-success alert-dismissible fade show w-75 mx-auto text-center' role='alert'>
                                        Your Brand Added Successfully!.. <a href='view_categories.php'>View</a>
                                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                                </div>";
                            } else {
                                echo "<div class='alert alert-danger'>Database error</div>";
                            }

                        } else {
                            echo "<div class='alert alert-warning'>Image upload failed</div>";
                        }
                        }

                    } else {
                        echo "<div class='alert alert-warning'>All fields are required</div>";
                    }
                }
                ?>

            <div class="card form-card">
                <div class="card-body">
                    <h4><i class="bi bi-plus-circle"></i> Insert Categories</h4>
                    <small>Add a categories to your system</small>
                </div>

                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Brand *</label>
                            <select name="ddlbrand" class="form-select" id="txtbrand" required>
                                <option value="">Select Brand</option>
                                <?php
                                    $q = mysqli_query($con, "SELECT * FROM brand_master WHERE is_active=1");
                                    while ($row = mysqli_fetch_assoc($q)) {
                                        echo "<option value='" . $row['brand_id'] . "'>" . $row['brand_name'] . "</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category Name *</label>
                            <input type="text"
                            name="ct_name"
                            class="form-control"
                            required
                            pattern="[A-Za-z ]+"
                            title="Only alphabet characters are allowed">

                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Category Image *</label>
                            <input type="file"
                                   name="ct_image"
                                   class="form-control"
                                   accept="image/*"
                                   required>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit"
                                    name="insbrand"
                                    class="btn btn-primary px-4">
                                <i class="bi bi-check-circle"></i> Save Category
                            </button>
                            <button type="reset"
                                    class="btn btn-secondary px-4">
                                Reset
                            </button>
                        </div>

                    </form>
                </div>

                <div class="form-footer">
                    All fields marked * are mandatory
                </div>
            </div>

        </div>
    </div>
</div>
<script>
    function validateForm() {
        let brand = document.getElementById("txtbrand").value;

        if (brand === "") {
            Swal.fire({
                icon: "warning",
                title: "Brand Required",
                text: "Please select a brand",
            });
            return false;
        }
        return true;
    }
</script>

<?php include('../components/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
