<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Form Submission Debug</h2>";

echo "<h3>Current Request Info</h3>";
echo "<p><strong>Request Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p><strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h3>POST Data</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['contact_owner'])) {
        echo "<p style='color:green;'><strong>✓ contact_owner field found!</strong></p>";
    } else {
        echo "<p style='color:red;'><strong>✗ contact_owner field NOT found!</strong></p>";
    }
} else {
    echo "<p>No POST data (GET request)</p>";
}

echo "<h3>Session Info</h3>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Test Form</h3>";
echo '<form method="POST">';
echo '<textarea name="message" placeholder="Test message..." required></textarea><br><br>';
echo '<input type="hidden" name="csrf_token" value="test_token">';
echo '<button type="submit" name="contact_owner">Submit Test</button>';
echo '</form>';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_owner'])) {
    echo "<div style='background:lightgreen; padding:10px; margin:10px 0;'>";
    echo "<strong>SUCCESS: Form was submitted and contact_owner was found!</strong>";
    echo "</div>";
}
?>