<?php
/**
 * Admin Categories Management
 * Manage item categories
 */

declare(strict_types=1);

// Include necessary dependencies without header
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

Session::init();

// Require admin login
Session::requireAdmin();

$pageTitle = 'Manage Categories';
$showAdminSidebar = true;

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $adminId = Session::getUser()['id'];

    try {
        switch ($action) {
            case 'create':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $icon = trim($_POST['icon'] ?? 'fas fa-tag');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);

                if (empty($name)) {
                    $error = 'Category name is required.';
                } else {
                    $categoryId = Database::insert('categories', [
                        'name' => $name,
                        'description' => $description,
                        'icon' => $icon,
                        'sort_order' => $sortOrder
                    ]);
                    
                    Utils::logAuditAction($adminId, 'CREATE_CATEGORY', 'categories', $categoryId);
                    Utils::redirect($_SERVER['PHP_SELF'], 'Category created successfully.', 'success');
                }
                break;

            case 'update':
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $icon = trim($_POST['icon'] ?? 'fas fa-tag');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);

                if (empty($name)) {
                    $error = 'Category name is required.';
                } elseif (Database::update('categories', [
                    'name' => $name,
                    'description' => $description,
                    'icon' => $icon,
                    'sort_order' => $sortOrder
                ], 'id = ?', [$categoryId])) {
                    Utils::logAuditAction($adminId, 'UPDATE_CATEGORY', 'categories', $categoryId);
                    Utils::redirect($_SERVER['PHP_SELF'], 'Category updated successfully.', 'success');
                } else {
                    $error = 'Failed to update category.';
                }
                break;

            case 'toggle':
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $category = Database::selectOne("SELECT * FROM categories WHERE id = ?", [$categoryId]);
                
                if ($category) {
                    $newStatus = $category['is_active'] ? 0 : 1;
                    Database::update('categories', ['is_active' => $newStatus], 'id = ?', [$categoryId]);
                    
                    $actionType = $newStatus ? 'ACTIVATE_CATEGORY' : 'DEACTIVATE_CATEGORY';
                    Utils::logAuditAction($adminId, $actionType, 'categories', $categoryId);
                    
                    $message = $newStatus ? 'Category activated successfully.' : 'Category deactivated successfully.';
                    Utils::redirect($_SERVER['PHP_SELF'], $message, 'success');
                } else {
                    $error = 'Category not found.';
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Error processing request: ' . $e->getMessage();
    }
}

// Get categories
try {
    $categories = Database::select("SELECT * FROM categories ORDER BY sort_order, name");
} catch (Exception $e) {
    $error = 'Error loading categories: ' . $e->getMessage();
    $categories = [];
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Categories</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Categories Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <p class="text-muted text-center py-4">No categories found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Icon</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['sort_order']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                                    </td>
                                    <td>
                                        <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $category['is_active'] ? 'success' : 'warning'; ?>">
                                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?php echo $category['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-<?php echo $category['is_active'] ? 'warning' : 'success'; ?>" 
                                                        onclick="return confirm('<?php echo $category['is_active'] ? 'Deactivate' : 'Activate'; ?> this category?')">
                                                    <i class="fas fa-<?php echo $category['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?php echo $category['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Category</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="name<?php echo $category['id']; ?>" class="form-label">Category Name</label>
                                                        <input type="text" class="form-control" id="name<?php echo $category['id']; ?>" 
                                                               name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="description<?php echo $category['id']; ?>" class="form-label">Description</label>
                                                        <textarea class="form-control" id="description<?php echo $category['id']; ?>" 
                                                                  name="description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="icon<?php echo $category['id']; ?>" class="form-label">Icon Class</label>
                                                        <input type="text" class="form-control" id="icon<?php echo $category['id']; ?>" 
                                                               name="icon" value="<?php echo htmlspecialchars($category['icon']); ?>" 
                                                               placeholder="e.g., fas fa-mobile-alt">
                                                        <small class="form-text text-muted">FontAwesome icon class</small>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="sort_order<?php echo $category['id']; ?>" class="form-label">Sort Order</label>
                                                        <input type="number" class="form-control" id="sort_order<?php echo $category['id']; ?>" 
                                                               name="sort_order" value="<?php echo $category['sort_order']; ?>" min="0">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update Category</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="new_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="new_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_description" class="form-label">Description</label>
                        <textarea class="form-control" id="new_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_icon" class="form-label">Icon Class</label>
                        <input type="text" class="form-control" id="new_icon" name="icon" 
                               placeholder="e.g., fas fa-mobile-alt" value="fas fa-tag">
                        <small class="form-text text-muted">FontAwesome icon class</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="new_sort_order" name="sort_order" 
                               value="<?php echo count($categories) + 1; ?>" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>