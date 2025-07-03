<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Check if color column exists
    $stmt = $pdo->query("DESCRIBE companies");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasColorColumn = in_array('color', $columns);
    
    if ($hasColorColumn) {
        // Get all companies with their colors
        $companies = $pdo->query("SELECT id, name, color, created_at FROM companies ORDER BY name")->fetchAll();
        echo "<h2>✅ Color column exists!</h2>";
        echo "<h3>Companies and their colors:</h3>";
        echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Color</th><th>Preview</th><th>Created</th></tr>";
        
        foreach ($companies as $company) {
            echo "<tr>";
            echo "<td>" . $company['id'] . "</td>";
            echo "<td>" . htmlspecialchars($company['name']) . "</td>";
            echo "<td>" . $company['color'] . "</td>";
            echo "<td style='background-color: " . $company['color'] . "; width: 50px; height: 30px;'></td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($company['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<br><p><strong>Status:</strong> Your database is ready! You can now use the color picker in the company management form.</p>";
        
    } else {
        echo "<h2>❌ Color column does not exist</h2>";
        echo "<p>You need to run the migration script. Execute this SQL in phpMyAdmin:</p>";
        echo "<pre>";
        echo "USE straordinari;\n";
        echo "ALTER TABLE companies ADD COLUMN color VARCHAR(7) NOT NULL DEFAULT '#6c757d';\n";
        echo "UPDATE companies SET color = '#1e3a8a' WHERE name = 'Defenda';\n";
        echo "UPDATE companies SET color = '#3b82f6' WHERE name = 'Euroansa';\n";
        echo "UPDATE companies SET color = '#f59e0b' WHERE name = 'Italian Luxury Villas';";
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<br><br>
<a href="manage_companies.php">→ Go to Company Management</a> | 
<a href="index.php">→ Go to Main Page</a> 