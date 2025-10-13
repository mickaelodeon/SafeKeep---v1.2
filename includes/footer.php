        </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-shield-alt me-2"></i><?php echo Config::get('app.name'); ?></h5>
                    <p class="text-muted">School-based Lost & Found system helping students reconnect with their belongings safely and efficiently.</p>
                </div>
                <div class="col-md-4">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo Config::get('app.url'); ?>/posts/browse.php" class="text-light text-decoration-none">Browse Items</a></li>
                        <li><a href="<?php echo Config::get('app.url'); ?>/announcements.php" class="text-light text-decoration-none">Announcements</a></li>
                        <?php if (!$currentUser): ?>
                        <li><a href="<?php echo Config::get('app.url'); ?>/auth/register.php" class="text-light text-decoration-none">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6>Contact & Support</h6>
                    <ul class="list-unstyled text-muted">
                        <li><i class="fas fa-envelope me-2"></i><?php echo Config::get('mail.from_email'); ?></li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>Student Services Office</li>
                        <li><i class="fas fa-clock me-2"></i>Mon-Fri: 8AM-6PM</li>
                        <li><i class="fas fa-question-circle me-2"></i><a href="<?php echo Config::get('app.url'); ?>/help.php" class="text-light text-decoration-none">Help & FAQ</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo Config::get('app.name'); ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Version 1.0 | 
                        <a href="<?php echo Config::get('app.url'); ?>/privacy.php" class="text-light text-decoration-none">Privacy Policy</a> | 
                        <a href="<?php echo Config::get('app.url'); ?>/terms.php" class="text-light text-decoration-none">Terms of Use</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo Config::get('app.url'); ?>/assets/js/app.js"></script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($pageScript)): ?>
    <script src="<?php echo Config::get('app.url'); ?>/assets/js/<?php echo $pageScript; ?>.js"></script>
    <?php endif; ?>
    
    <!-- Additional JavaScript -->
    <?php if (isset($additionalJS)): echo $additionalJS; endif; ?>
</body>
</html>