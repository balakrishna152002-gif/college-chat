<?php
/**
 * upload.php
 *
 * Handles file uploads securely.
 * Validates file type and size, generates a unique filename,
 * and moves the file to the 'uploads/' directory.
 */

include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if (isset($_FILES['file'])) {
    $uploadDir = 'uploads/';
    $file = $_FILES['file'];
    $originalFileName = basename($file['name']);
    $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
    $uniqueFileName = uniqid() . '.' . $fileExtension;
    $targetFilePath = $uploadDir . $uniqueFileName;

    // --- Security Checks ---
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Error: Invalid file type.']);
        exit;
    }

    $maxFileSize = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxFileSize) {
        echo json_encode(['success' => false, 'message' => 'Error: File size is larger than the allowed limit of 5MB.']);
        exit;
    }

    // --- Move the file ---
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        echo json_encode([
            'success' => true,
            'filePath' => $targetFilePath,
            'originalFileName' => $originalFileName
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: There was an error uploading your file.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file was uploaded.']);
}
?>
