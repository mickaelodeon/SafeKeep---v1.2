<?php
$logFile = 'C:\xampp\apache\logs\error.log';

if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    
    // Get last 50 lines
    $recentLines = array_slice($lines, -50);
    
    echo "<h2>Recent Apache Error Logs</h2>";
    echo "<div style='background:#f8f9fa; padding:10px; font-family:monospace; font-size:12px; max-height:500px; overflow-y:scroll; border:1px solid #ccc;'>";
    
    foreach ($recentLines as $line) {
        if (strpos($line, 'SafeKeep DEBUG') !== false) {
            echo "<div style='color:blue; font-weight:bold;'>" . htmlspecialchars($line) . "</div>";
        } elseif (strpos($line, 'SafeKeep') !== false) {
            echo "<div style='color:red;'>" . htmlspecialchars($line) . "</div>";
        } elseif (!empty(trim($line))) {
            echo "<div>" . htmlspecialchars($line) . "</div>";
        }
    }
    
    echo "</div>";
} else {
    echo "<p>Log file not found at: $logFile</p>";
}
?>