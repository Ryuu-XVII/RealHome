<?php
$dir = 'uploads';

// Check if the directory exists
if (!file_exists($dir)) {
    // Try to create the directory
    if (mkdir($dir, 0755, true)) {
        echo "Directory '$dir' created successfully.";
    } else {
        echo "Failed to create directory '$dir'.";
    }
} else {
    echo "Directory '$dir' already exists.";
}
?>
