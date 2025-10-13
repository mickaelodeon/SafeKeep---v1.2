<?php
/**
 * Create New Post
 * Form to create lost or found item posts
 */

declare(strict_types=1);

// Include necessary dependencies without header
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

Session::init();

Session::requireLogin();

$errors = [];
$formData = [];
$type = $_GET['type'] ?? 'lost';

// Get categories for dropdown
$categories = Database::select("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize input
        $formData = Security::sanitizeInput($_POST);
        
        // Validate required fields
        if (empty($formData['title'])) {
            $errors[] = 'Title is required.';
        }
        
        if (empty($formData['description'])) {
            $errors[] = 'Description is required.';
        }
        
        if (empty($formData['type']) || !in_array($formData['type'], ['lost', 'found'])) {
            $errors[] = 'Please select if this is a lost or found item.';
        }
        
        if (empty($formData['category'])) {
            $errors[] = 'Category is required.';
        }
        
        if (empty($formData['date_lost_found'])) {
            $errors[] = 'Date lost/found is required.';
        }
        
        if (empty($formData['location'])) {
            $errors[] = 'Location is required.';
        }
        
        // Validate date
        if (!empty($formData['date_lost_found'])) {
            $date = DateTime::createFromFormat('Y-m-d', $formData['date_lost_found']);
            if (!$date || $date > new DateTime()) {
                $errors[] = 'Please enter a valid date (not in the future).';
            }
        }
        
        $photoPath = null;
        
        // Handle file upload if provided
        if (!empty($_FILES['photo']['name'])) {
            $uploadResult = Security::handleFileUpload($_FILES['photo'], Config::get('uploads.upload_dir'));
            
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
            } else {
                $photoPath = $uploadResult['filename'];
            }
        }
        
        // Create post if no errors
        if (empty($errors)) {
            try {
                $postData = [
                    'user_id' => Session::getUser()['id'],
                    'title' => $formData['title'],
                    'description' => $formData['description'],
                    'type' => $formData['type'],
                    'category' => $formData['category'],
                    'date_lost_found' => $formData['date_lost_found'],
                    'location' => $formData['location'],
                    'photo_path' => $photoPath,
                    'status' => 'pending'
                ];
                
                $postId = Post::create($postData);
                Utils::redirect(Config::get('app.url') . '/posts/browse.php', 'Your post has been submitted for review. It will be visible once approved by an admin.', 'success');
                
            } catch (Exception $e) {
                $errors[] = 'Failed to create post. Please try again.';
                error_log('Post creation error: ' . $e->getMessage());
            }
        }
    }
}

// Include header after processing
$pageTitle = 'Post an Item';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Report a <?php echo $type === 'lost' ? 'Lost' : 'Found'; ?> Item
                </h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Type Selection -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="post_type" id="type_lost" value="lost" <?php echo $type === 'lost' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-warning" for="type_lost">
                                <i class="fas fa-search me-2"></i>I Lost Something
                            </label>
                            
                            <input type="radio" class="btn-check" name="post_type" id="type_found" value="found" <?php echo $type === 'found' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-success" for="type_found">
                                <i class="fas fa-check me-2"></i>I Found Something
                            </label>
                        </div>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    <input type="hidden" name="type" id="hidden_type" value="<?php echo $type; ?>">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="title" class="form-label">Item Title <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   value="<?php echo htmlspecialchars($formData['title'] ?? ''); ?>"
                                   placeholder="e.g., iPhone 13 Pro in blue case"
                                   maxlength="200"
                                   required>
                            <div class="invalid-feedback">Please provide a descriptive title.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name']); ?>" 
                                        <?php echo isset($formData['category']) && $formData['category'] === $category['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a category.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  rows="4" 
                                  placeholder="Provide a detailed description including color, size, distinguishing features, etc."
                                  maxlength="1000"
                                  data-auto-resize
                                  required><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                        <div class="form-text">
                            <span id="desc-counter">0</span>/1000 characters
                        </div>
                        <div class="invalid-feedback">Please provide a detailed description.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_lost_found" class="form-label">
                                Date <span id="date-label"><?php echo $type === 'lost' ? 'Lost' : 'Found'; ?></span> <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date_lost_found" 
                                   name="date_lost_found" 
                                   value="<?php echo htmlspecialchars($formData['date_lost_found'] ?? ''); ?>"
                                   max="<?php echo date('Y-m-d'); ?>"
                                   required>
                            <div class="invalid-feedback">Please enter the date.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="location" 
                                   name="location" 
                                   value="<?php echo htmlspecialchars($formData['location'] ?? ''); ?>"
                                   placeholder="e.g., Library 2nd floor, Building A Room 205"
                                   maxlength="200"
                                   required>
                            <div class="invalid-feedback">Please specify where it was lost/found.</div>
                        </div>
                    </div>

                    <!-- Photo Upload -->
                    <div class="mb-4">
                        <label class="form-label">Photo (Optional)</label>
                        <div class="file-upload-area border rounded p-4 text-center">
                            <input type="file" name="photo" id="photo" class="d-none" accept="image/*">
                            <div class="upload-content">
                                <i class="fas fa-camera fa-3x text-muted mb-3"></i>
                                <p class="mb-2">Click to upload or drag and drop</p>
                                <small class="text-muted">
                                    Max size: <?php echo Security::formatBytes(Config::get('uploads.max_file_size')); ?>
                                    • Formats: JPG, PNG, GIF, WebP
                                </small>
                            </div>
                            <div class="file-preview mt-3"></div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Your post will be reviewed by administrators before being published. 
                        You'll be notified once it's approved.
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit for Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeButtons = document.querySelectorAll('input[name="post_type"]');
    const hiddenType = document.getElementById('hidden_type');
    const dateLabel = document.getElementById('date-label');
    const description = document.getElementById('description');
    const descCounter = document.getElementById('desc-counter');

    // Handle type selection
    typeButtons.forEach(button => {
        button.addEventListener('change', function() {
            hiddenType.value = this.value;
            dateLabel.textContent = this.value === 'lost' ? 'Lost' : 'Found';
            
            // Update URL without refresh
            const url = new URL(window.location);
            url.searchParams.set('type', this.value);
            window.history.replaceState(null, '', url);
        });
    });

    // Character counter for description
    function updateCounter() {
        descCounter.textContent = description.value.length;
        
        if (description.value.length > 900) {
            descCounter.parentElement.classList.add('text-warning');
        } else {
            descCounter.parentElement.classList.remove('text-warning');
        }
    }

    description.addEventListener('input', updateCounter);
    updateCounter(); // Initial count

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // Auto-suggest locations (simple example)
    const locationInput = document.getElementById('location');
    const commonLocations = [
        'Main Library', 'Student Center', 'Cafeteria', 'Gym', 'Parking Lot A',
        'Building A', 'Building B', 'Building C', 'Auditorium', 'Computer Lab'
    ];

    locationInput.addEventListener('input', function() {
        // Simple autocomplete could be implemented here
        const value = this.value.toLowerCase();
        if (value.length > 2) {
            const matches = commonLocations.filter(loc => 
                loc.toLowerCase().includes(value)
            );
            // Display matches (implementation depends on UI preference)
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>