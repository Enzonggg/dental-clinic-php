<?php
include_once('../includes/header.php');

// Check if user is logged in and is a doctor
if (!is_logged_in() || $_SESSION['role'] !== 'doctor') {
    header("Location: ../auth/login.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle adding new treatment plan template
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_template'])) {
    $title = sanitize_input($_POST['title']);
    $category = sanitize_input($_POST['category']);
    $description = sanitize_input($_POST['description']);
    $procedure_steps = sanitize_input($_POST['procedure_steps']);
    $estimated_cost = floatval($_POST['estimated_cost']);
    
    // Insert new template
    $insert_sql = "INSERT INTO treatment_templates (doctor_id, title, category, description, procedure_steps, estimated_cost) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("issssd", $doctor_id, $title, $category, $description, $procedure_steps, $estimated_cost);
    
    if ($insert_stmt->execute()) {
        $success_message = "Treatment plan template added successfully.";
    } else {
        // Check if the table doesn't exist
        if (strpos($conn->error, "doesn't exist") !== false) {
            // Create the treatment_templates table if it doesn't exist
            $create_table_sql = "CREATE TABLE treatment_templates (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                doctor_id INT(11) NOT NULL,
                title VARCHAR(255) NOT NULL,
                category VARCHAR(100) NOT NULL,
                description TEXT NOT NULL,
                procedure_steps TEXT NOT NULL,
                estimated_cost DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (doctor_id) REFERENCES users(id)
            )";
            
            if ($conn->query($create_table_sql)) {
                // Try inserting again
                if ($insert_stmt->execute()) {
                    $success_message = "Treatment plan template added successfully.";
                } else {
                    $error_message = "Failed to add template: " . $conn->error;
                }
            } else {
                $error_message = "Failed to create template table: " . $conn->error;
            }
        } else {
            $error_message = "Failed to add template: " . $conn->error;
        }
    }
}

// Handle deleting a template
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $template_id = $_GET['delete'];
    
    // Check if the template belongs to the doctor
    $check_sql = "SELECT * FROM treatment_templates WHERE id = ? AND doctor_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $template_id, $doctor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 1) {
        $delete_sql = "DELETE FROM treatment_templates WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $template_id);
        
        if ($delete_stmt->execute()) {
            $success_message = "Template deleted successfully.";
        } else {
            $error_message = "Failed to delete template: " . $conn->error;
        }
    } else {
        $error_message = "Invalid template or you don't have permission to delete it.";
    }
}

// Get all treatment templates for this doctor
try {
    $templates_sql = "SELECT * FROM treatment_templates WHERE doctor_id = ? ORDER BY category, title";
    $templates_stmt = $conn->prepare($templates_sql);
    $templates_stmt->bind_param("i", $doctor_id);
    $templates_stmt->execute();
    $templates = $templates_stmt->get_result();
    $templates_exist = true;
} catch (Exception $e) {
    // Table likely doesn't exist yet
    $templates_exist = false;
}

// Get distinct categories for filtering
$categories = [];
if ($templates_exist) {
    $categories_sql = "SELECT DISTINCT category FROM treatment_templates WHERE doctor_id = ? ORDER BY category";
    $categories_stmt = $conn->prepare($categories_sql);
    $categories_stmt->bind_param("i", $doctor_id);
    $categories_stmt->execute();
    $categories_result = $categories_stmt->get_result();
    
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Treatment Plan Templates</h2>
        <p>Create and manage standardized treatment plans that you can quickly apply to patients.</p>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Your Treatment Plan Templates</h5>
                <div>
                    <?php if(!empty($categories)): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                Filter by Category
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="treatment_plans.php">All Categories</a></li>
                                <?php foreach($categories as $category): ?>
                                    <li><a class="dropdown-item" href="treatment_plans.php?category=<?php echo urlencode($category); ?>"><?php echo htmlspecialchars($category); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                        Add New Template
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if($templates_exist && $templates->num_rows > 0): ?>
                    <div class="accordion" id="templateAccordion">
                        <?php 
                            $current_category = '';
                            $category_filter = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
                            
                            // Reset the result set pointer
                            $templates->data_seek(0);
                            
                            while($template = $templates->fetch_assoc()): 
                                // Skip if category filter is set and doesn't match
                                if(!empty($category_filter) && $template['category'] != $category_filter) {
                                    continue;
                                }
                                
                                // Create category headers
                                if($current_category != $template['category']):
                                    if(!empty($current_category)): 
                                        echo '</div>'; // Close previous category
                                    endif;
                                    
                                    $current_category = $template['category'];
                                    echo '<h5 class="mt-3 mb-2 bg-light p-2 rounded">' . htmlspecialchars($current_category) . '</h5>';
                                    echo '<div class="category-group mb-3">';
                                endif;
                        ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?php echo $template['id']; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $template['id']; ?>">
                                        <div class="d-flex justify-content-between w-100 me-3">
                                            <span><?php echo htmlspecialchars($template['title']); ?></span>
                                            <span class="text-muted">$<?php echo number_format($template['estimated_cost'], 2); ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $template['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#templateAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h6>Description:</h6>
                                                <p><?php echo htmlspecialchars($template['description']); ?></p>
                                                
                                                <h6>Procedure Steps:</h6>
                                                <p class="mb-3"><?php echo nl2br(htmlspecialchars($template['procedure_steps'])); ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6>Details:</h6>
                                                        <p>Category: <?php echo htmlspecialchars($template['category']); ?></p>
                                                        <p>Estimated Cost: $<?php echo number_format($template['estimated_cost'], 2); ?></p>
                                                        <p>Created: <?php echo format_date($template['created_at']); ?></p>
                                                        
                                                        <div class="d-flex justify-content-between mt-3">
                                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $template['id']; ?>">
                                                                Edit
                                                            </button>
                                                            <a href="treatment_plans.php?delete=<?php echo $template['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this template?')">
                                                                Delete
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Edit Modal for this template -->
                            <div class="modal fade" id="editModal<?php echo $template['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Treatment Plan Template</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post" action="">
                                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                                <input type="hidden" name="action" value="edit_template">
                                                
                                                <div class="mb-3">
                                                    <label for="edit_title<?php echo $template['id']; ?>" class="form-label">Title</label>
                                                    <input type="text" class="form-control" id="edit_title<?php echo $template['id']; ?>" name="title" value="<?php echo htmlspecialchars($template['title']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="edit_category<?php echo $template['id']; ?>" class="form-label">Category</label>
                                                    <input type="text" class="form-control" id="edit_category<?php echo $template['id']; ?>" name="category" value="<?php echo htmlspecialchars($template['category']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="edit_description<?php echo $template['id']; ?>" class="form-label">Description</label>
                                                    <textarea class="form-control" id="edit_description<?php echo $template['id']; ?>" name="description" rows="3" required><?php echo htmlspecialchars($template['description']); ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="edit_procedure_steps<?php echo $template['id']; ?>" class="form-label">Procedure Steps</label>
                                                    <textarea class="form-control" id="edit_procedure_steps<?php echo $template['id']; ?>" name="procedure_steps" rows="5" required><?php echo htmlspecialchars($template['procedure_steps']); ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="edit_estimated_cost<?php echo $template['id']; ?>" class="form-label">Estimated Cost ($)</label>
                                                    <input type="number" step="0.01" class="form-control" id="edit_estimated_cost<?php echo $template['id']; ?>" name="estimated_cost" value="<?php echo $template['estimated_cost']; ?>" required>
                                                </div>
                                                
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        
                        <?php if(!empty($current_category)): ?>
                            </div><!-- Close last category -->
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p>You haven't created any treatment plan templates yet. Use the "Add New Template" button to create your first template.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Quick Tips</h5>
            </div>
            <div class="card-body">
                <ul>
                    <li>Create templates for common treatment procedures.</li>
                    <li>Organize templates by categories for easier management.</li>
                    <li>Include detailed steps for consistent patient care.</li>
                    <li>Add reasonable cost estimates to help patients plan.</li>
                    <li>Use templates when creating new patient treatment plans.</li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Popular Categories</h5>
            </div>
            <div class="card-body">
                <p>Consider organizing your templates into these common categories:</p>
                <ul>
                    <li>Diagnostic Procedures</li>
                    <li>Preventive Care</li>
                    <li>Restorative Treatments</li>
                    <li>Cosmetic Dentistry</li>
                    <li>Orthodontics</li>
                    <li>Periodontal Treatments</li>
                    <li>Endodontic Procedures</li>
                    <li>Oral Surgery</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Treatment Plan Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Root Canal Treatment">
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category" required placeholder="e.g., Endodontic Procedures">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required placeholder="Brief description of the treatment"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="procedure_steps" class="form-label">Procedure Steps</label>
                        <textarea class="form-control" id="procedure_steps" name="procedure_steps" rows="5" required placeholder="Step-by-step procedure instructions"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="estimated_cost" class="form-label">Estimated Cost ($)</label>
                        <input type="number" step="0.01" class="form-control" id="estimated_cost" name="estimated_cost" required>
                    </div>
                    
                    <button type="submit" name="add_template" class="btn btn-primary">Add Template</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
