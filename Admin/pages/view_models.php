<title>View Models</title>
<?php
include('connect.php');
session_name('admin_session');
session_start();
include('../components/navbar.php');
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<style>
    .page-content {
        min-height: calc(100vh - 70px);
        padding: 2rem 1rem;
    }

    .card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 1.5rem;
    }

    .card-header h4 {
        color: white;
        margin: 0;
    }

    tr:has(.btn-success) {
        opacity: 0.5;
        filter: grayscale(80%);
        transition: opacity 0.3s ease, filter 0.3s ease;
    }

    tr:has(.btn-success):hover {
        opacity: 0.7;
    }

    .btn-sm {
        padding: 0.4rem 0.8rem;
    }

    .dt-buttons {
        margin-bottom: 1rem;
    }

    .dt-button {
        background: #667eea !important;
        border: none !important;
        color: white !important;
        border-radius: 6px !important;
        padding: 0.5rem 1rem !important;
        margin-right: 0.5rem !important;
    }

    .dt-button:hover {
        background: #764ba2 !important;
    }

    tfoot select {
        width: 100%;
        padding: 4px;
    }

    .filter-row th {
        padding: 6px;
    }

    .filter-row select {
        width: 100%;
    }

    .model-img {
        width: 140px;
        height: 90px;          /* Rectangle shape */
        object-fit: cover;     /* Crop nicely, no stretch */
        border-radius: 8px;
        border: 1px solid #ddd;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        cursor: pointer;
    }

    .model-img:hover {
        transform: scale(1.08);
        box-shadow: 0 6px 16px rgba(0,0,0,0.25);
    }

</style>

<div class="container">
    <div class="page-content">
        <div class="card w-100">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-list me-2"></i>Models Management</h4>
            </div>

            <div class="card-body">
                <table id="modelsTable" class="table table-bordered table-striped table-hover">
                    <thead>
                        <!-- Filter row -->
                        <tr class="filter-row">
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                        <tr>
                            <th>ID</th>
                            <th>Brand Name</th>
                            <th>Category Name</th>
                            <th>Model Name</th>
                            <th>Image</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                    <?php
                    $q = mysqli_query($con, "SELECT m.model_id, m.model_name, m.model_image, m.model_description, m.is_active, c.category_name,b.brand_name FROM model_master m INNER JOIN category_master c ON m.category_id = c.category_id INNER JOIN brand_master b ON c.brand_id = b.brand_id");
                    while ($row = mysqli_fetch_assoc($q)) {
                    ?>
                        <tr>
                            <td><?= $row['model_id']; ?></td>
                            <td><?= $row['brand_name']; ?></td>
                            <td><?= $row['category_name']; ?></td>
                            <td><?= $row['model_name']; ?></td>
                            <td>
                                <img src="./images/model_images/<?= $row['model_image']; ?>"
                                    class="model-img"
                                    alt="<?= $row['model_name']; ?>">
                            </td>
                            <td class="text-wrap" style="max-width: 220px;"><?= $row['model_description']; ?></td>
                            <td>
                                <?php if($row['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Disable</span>
                                <?php endif; ?>
                            </td>
                            <td class="d-flex flex-column gap-3">
                                <a href="edit_model.php?id=<?= $row['model_id']; ?>" 
                                   class="btn btn-sm btn-primary"
                                   title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <a href="delete_model.php?id=<?= $row['model_id']; ?>"
                                   class="btn btn-sm btn-danger"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this model?')">
                                    <i class="fas fa-trash"></i>
                                </a>

                                <?php if ($row['is_active']): ?>
                                    <a href="toggle_model.php?id=<?= $row['model_id']; ?>&status=0"
                                       class="btn btn-sm btn-warning"
                                       title="Disable"
                                       onclick="return confirm('Are you sure you want to disable this model?')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="toggle_model.php?id=<?= $row['model_id']; ?>&status=1"
                                       class="btn btn-sm btn-success"
                                       title="Activate"
                                       onclick="return confirm('Are you sure you want to activate this model?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('../components/footer.php'); ?>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>

<!-- Export dependencies -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<!-- Export buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function () {
    var table = $('#modelsTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                text: '<i class="fas fa-copy"></i> Copy'
            },
            {
                extend: 'csv',
                text: '<i class="fas fa-file-csv"></i> CSV'
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF'
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print'
            }
        ],
        columnDefs: [
            { orderable: false, targets: [4, 7] }
        ],
        language: {
            searchPlaceholder: "Search models..."
        },

        initComplete: function () {
            var api = this.api();

            api.columns([1, 2, 6]).every(function () {
                var column = this;
                var th = $('.filter-row th').eq(column.index());

                var select = $('<select class="form-select form-select-sm"><option value="">All</option></select>')
                    .appendTo(th.empty())
                    .on('change', function () {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });

                column.data().unique().sort().each(function (d) {
                    select.append('<option value="' + d + '">' + d + '</option>');
                });
            });
        }
    });
});
</script>