<?php 
    include('connect.php');
    session_name('admin_session');
    session_start();
    include('../components/navbar.php'); 

    // Handle form submission
    $x = $_GET['id'] ?? 0;
    $message = '';
    $messageType = '';

    if (isset($_POST['editbrand'])) {
        $brand_name = mysqli_real_escape_string($con, $_POST['pname']);
        $brand_logo = $_FILES['txtpic']['name'];
        $tmp = $_FILES['txtpic']['tmp_name'];
        
        if (!empty($brand_name)) {
            // If new image is uploaded
            if (!empty($brand_logo)) {
                $ext = strtolower(pathinfo($brand_logo, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($ext, $allowed)) {
                    $message = "Only image files are allowed (jpg, jpeg, png, gif, webp)";
                    $messageType = "warning";
                } else {
                    // Create unique filename
                    $new_filename = "brand_" . time() . "_" . uniqid() . "." . $ext;
                    $dst = "./images/brand_images/" . $new_filename;
                    
                    if (move_uploaded_file($tmp, $dst)) {
                        // Delete old image
                        $old_query = mysqli_query($con, "SELECT brand_logo FROM brand_master WHERE brand_id=$x");
                        $old_row = mysqli_fetch_assoc($old_query);
                        if ($old_row && file_exists("./images/brand_images/" . $old_row['brand_logo'])) {
                            unlink("./images/brand_images/" . $old_row['brand_logo']);
                        }
                        
                        $q = "UPDATE brand_master SET brand_name='$brand_name', brand_logo='$new_filename' WHERE brand_id=$x";
                        
                        if (mysqli_query($con, $q)) {
                            $message = "Brand updated successfully!";
                            $messageType = "success";
                            echo "<script>
                                setTimeout(() => {
                                    window.location.href = 'view_brands.php';
                                }, 1500);
                            </script>";
                        } else {
                            $message = "Database error: " . mysqli_error($con);
                            $messageType = "danger";
                        }
                    } else {
                        $message = "Image upload failed";
                        $messageType = "warning";
                    }
                }
            } else {
                // Update only name, keep existing image
                $q = "UPDATE brand_master SET brand_name='$brand_name' WHERE brand_id=$x";
                
                if (mysqli_query($con, $q)) {
                    $message = "Brand name updated successfully!";
                    $messageType = "success";
                    echo "<script>
                        setTimeout(() => {
                            window.location.href = 'view_brands.php';
                        }, 1500);
                    </script>";
                } else {
                    $message = "Database error: " . mysqli_error($con);
                    $messageType = "danger";
                }
            }
        } else {
            $message = "Brand name is required";
            $messageType = "warning";
        }
    }

    // Fetch brand data
    $qry = mysqli_query($con, "SELECT * FROM brand_master WHERE brand_id=$x");
    $brand = mysqli_fetch_array($qry);

    if (!$brand) {
        echo "<script>alert('Brand not found'); window.location.href='view_brands.php';</script>";
        exit();
    }
?>

    <div class="container">
        <div class="page-inner">
            <div class="page-header">
                <h3 class="fw-bold mb-3">Edit Brand</h3>
                <ul class="breadcrumbs mb-3">
                    <li class="nav-home">
                        <a href="index.php"><i class="icon-home"></i></a>
                    </li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="view_brands.php">Brands</a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item">Edit Brand</li>
                </ul>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <strong><?= $messageType === 'success' ? 'Success!' : 'Error!' ?></strong> <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-5 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-edit me-2"></i>Update Brand Information
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="editBrandForm">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="pname" class="form-label">
                                                Brand Name <span class="text-danger">*</span>
                                            </label>
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                id="pname" 
                                                name="pname" 
                                                value="<?= htmlspecialchars($brand['brand_name']) ?>"
                                                placeholder="Enter brand name"
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="txtpic" class="form-label">
                                                Brand Logo (Leave empty to keep current logo)
                                            </label>
                                            <input 
                                                type="file" 
                                                class="form-control" 
                                                id="txtpic" 
                                                name="txtpic" 
                                                accept="image/*"
                                                onchange="previewImage(this)"
                                            />
                                            <small class="form-text text-muted">
                                                Accepted formats: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label">Current Logo</label>
                                            <div class="d-flex align-items-center gap-3">
                                                <img 
                                                    src="./images/brand_images/<?= $brand['brand_logo'] ?>" 
                                                    alt="Current Logo" 
                                                    id="currentLogo"
                                                    class="img-thumbnail"
                                                    style="max-width: 150px; max-height: 150px; object-fit: contain;"
                                                />
                                                <div id="newLogoPreview" style="display: none;">
                                                    <p class="mb-1 text-muted small">New Logo Preview:</p>
                                                    <img 
                                                        id="previewImg" 
                                                        class="img-thumbnail"
                                                        style="max-width: 150px; max-height: 150px; object-fit: contain;"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-action">
                                    <button type="submit" name="editbrand" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Update Brand
                                    </button>
                                    <a href="view_brands.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('newLogoPreview');
            const previewImg = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Check file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        // Form validation
        document.getElementById('editBrandForm').addEventListener('submit', function(e) {
            const brandName = document.getElementById('pname').value.trim();
            
            if (brandName === '') {
                e.preventDefault();
                alert('Please enter a brand name');
                return false;
            }
        });
    </script>

<?php include('../components/footer.php'); ?>