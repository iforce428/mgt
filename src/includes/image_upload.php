<?php
function handle_image_upload($file, $target_dir = null) {
    // Set default upload directory if not specified
    if ($target_dir === null) {
        $target_dir = dirname(dirname(__DIR__)) . '/uploads/';
    }

    // Create uploads directory if it doesn't exist
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
        // Create .htaccess to protect uploads directory
        $htaccess_content = "Options -Indexes\n";
        $htaccess_content .= "<FilesMatch '\.(php|php3|php4|php5|php7|phtml|phar)$'>\n";
        $htaccess_content .= "Order Deny,Allow\n";
        $htaccess_content .= "Deny from all\n";
        $htaccess_content .= "</FilesMatch>\n";
        file_put_contents($target_dir . '.htaccess', $htaccess_content);
    }

    // Validate file
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file parameters');
    }

    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception('File too large');
        case UPLOAD_ERR_PARTIAL:
            throw new Exception('File upload incomplete');
        case UPLOAD_ERR_NO_FILE:
            throw new Exception('No file uploaded');
        case UPLOAD_ERR_NO_TMP_DIR:
            throw new Exception('Missing temporary folder');
        case UPLOAD_ERR_CANT_WRITE:
            throw new Exception('Failed to write file');
        case UPLOAD_ERR_EXTENSION:
            throw new Exception('File upload stopped by extension');
        default:
            throw new Exception('Unknown upload error');
    }

    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File too large (max 5MB)');
    }

    // Check file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];

    if (!array_key_exists($mime_type, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG and GIF allowed.');
    }

    // Generate unique filename
    $extension = $allowed_types[$mime_type];
    $filename = uniqid() . '.' . $extension;
    $target_path = $target_dir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Get the base URL from server variables
    $base_url = 'http://localhost/mgt/';

    // Return the full URL path for the uploaded file
    return $base_url . 'uploads/' . $filename;
}
?> 