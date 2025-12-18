<?php
require '../config/db.php';

// 1. SECURITY CHECK
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Only allow logged-in vendors to process this form
if (($_SESSION['role'] ?? '') !== 'vendor') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_product'])) {
    
    // 2. DATA SANITIZATION
    $vendor_id   = $_SESSION['user_id'];
    $category_id = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);
    $name        = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $mrp         = filter_var($_POST['mrp'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $price       = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $stock       = filter_var($_POST['stock'], FILTER_SANITIZE_NUMBER_INT);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);

    try {
        $pdo->beginTransaction();

        // 3. INSERT PRODUCT DETAILS
        $sql = "INSERT INTO products (vendor_id, category_id, name, description, mrp, price, stock, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$vendor_id, $category_id, $name, $description, $mrp, $price, $stock]);
        
        $product_id = $pdo->lastInsertId();

        // 4. HANDLE IMAGE UPLOADS
        if (!empty($_FILES['product_images']['name'][0])) {
            $target_dir = "../assets/products/";
            
            // Ensure directory exists
            if (!is_dir($target_dir)) { 
                mkdir($target_dir, 0777, true); 
            }

            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['product_images']['name'][$key];
                $file_ext  = pathinfo($file_name, PATHINFO_EXTENSION);
                
                // Create a unique clean filename
                $clean_name = "prod_" . $product_id . "_" . time() . "_" . $key . "." . $file_ext;
                
                // First image uploaded in the array becomes the Cover Image
                $is_cover = ($key === 0) ? 1 : 0;

                if (move_uploaded_file($tmp_name, $target_dir . $clean_name)) {
                    $img_sql = "INSERT INTO product_images (product_id, image_path, is_cover) VALUES (?, ?, ?)";
                    $pdo->prepare($img_sql)->execute([$product_id, $clean_name, $is_cover]);
                }
            }
        }

        $pdo->commit();
        header("Location: manage_products.php?success=added");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        // Log the error for debugging
        error_log("Product Add Error: " . $e->getMessage());
        header("Location: add_product.php?error=database_fail");
        exit();
    }

} else {
    // Redirect if someone tries to access this file directly
    header("Location: add_product.php");
    exit();
}