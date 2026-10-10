<?php
// mongo_db.php

$uri = "mongodb://localhost:27017"; // Or your specific connection URI

try {
    $conn = new MongoDB\Driver\Manager($uri);
} catch (Exception $e) {
    die("Error connecting to MongoDB: " . $e->getMessage());
}
?>