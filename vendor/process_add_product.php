<?php
// 1. DATABASE & SESSION SAFETY
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 2. SECURITY CHECK
if (($_SESSION['role'] ?? '') !== 'vendor') {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['submit_product'])) {
    try {
        // Start Transaction to ensure product and images are saved together
        $pdo->beginTransaction();

        $vendor_id   = $_SESSION['user_id'];
        $name        = trim($_POST['name']);
        $category_id = $_POST['category_id'];
        $mrp         = $_POST['mrp'];
        $price       = $_POST['price'];
        $stock       = $_POST['stock'];
        $description = $_POST['description'];
        
        // Get the cover index from the hidden input (defaults to 0)
        $cover_index = isset($_POST['cover_index']) ? (int)$_POST['cover_index'] : 0;

        // 3. INSERT MAIN PRODUCT DATA
        $stmt = $pdo->prepare("INSERT INTO products (vendor_id, category_id, name, mrp, price, stock, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$vendor_id, $category_id, $name, $mrp, $price, $stock, $description]);
        
        $product_id = $pdo->lastInsertId();

        // 4. HANDLE MULTI-IMAGE UPLOAD
        if (!empty($_FILES['product_images']['name'][0])) {
            
            $upload_dir = "../assets/products/";
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) { 
                mkdir($upload_dir, 0777, true); 
            }

            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['product_images']['name'][$key];
                    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Generate unique filename
                    $new_file_name = "v" . $vendor_id . "_p" . $product_id . "_" . time() . "_" . $key . "." . $file_ext;
                    $target_file   = $upload_dir . $new_file_name;

                    // Allowed extensions
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    if (in_array($file_ext, $allowed)) {
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            
                            // Determine if this specific image is the chosen cover
                            $is_cover = ($key === $cover_index) ? 1 : 0;

                            // Save image path and cover status to 'product_images' table
                            $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_cover) VALUES (?, ?, ?)");
                            $img_stmt->execute([$product_id, $new_file_name, $is_cover]);
                        }
                    }
                }
            }
        }

        // Commit all changes
        $pdo->commit();

        // 5. REDIRECT ON SUCCESS
        header("Location: manage_products.php?status=success");
        exit();

    } catch (Exception $e) {
        // Rollback database if any part of the process fails
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: upload_product.php?status=error&msg=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: upload_product.php");
    exit();
}