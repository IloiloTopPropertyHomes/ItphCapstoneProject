<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $conn = get_db_connection();

    // TEXT FIELDS
    $property_page = sanitize_input($_POST['property_page']);
    $title = sanitize_input($_POST['title']);
    $location = sanitize_input($_POST['location']);
    $description = sanitize_input($_POST['description']);

    // NUMERIC
    $price = floatval($_POST['price']);
    $bedrooms = intval($_POST['bedrooms']);
    $bathrooms = intval($_POST['bathrooms']);
    $available_units = intval($_POST['available_units']);

    // INSERT PROPERTY FIRST (without image)
 $stmt = $conn->prepare("INSERT INTO propertiies 
(title, property_page, available_units,price, location, bedrooms, bathrooms, description) 
VALUES (?, ?, ?, ?, ?, ?, ?,? )");

    $stmt->bind_param(
        "ssidsiis",
        $title,
         $property_page,
          $available_units,
        $price,
        
        $location,
       
        $bedrooms,
        $bathrooms,
        $description,
       
    );

    if (!$stmt->execute()) {
        die("Error: " . $stmt->error);
    }

    // GET INSERTED PROPERTY ID
    $property_id = $stmt->insert_id;

    // HANDLE MULTIPLE IMAGES

    $targetDir = __DIR__ . '/../photo/uploads/';
    $allowedTypes = ['jpg','jpeg','png','gif'];

    $mainImage = ''; // will store the first image

    foreach ($_FILES['images']['name'] as $key => $name) {

        if ($_FILES['images']['error'][$key] === 0) {

            $tmp = $_FILES['images']['tmp_name'][$key];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedTypes)) continue;

            $newName = time() . "_" . $name;
            $targetFile = $targetDir . $newName;

            if (move_uploaded_file($tmp, $targetFile)) {

                $encryptedImage = encrypt_data($newName);

                // Save in property_images table
                $imgStmt = $conn->prepare("INSERT INTO property_images (property_id, image) VALUES (?, ?)");
                $imgStmt->bind_param("is", $property_id, $encryptedImage);
                $imgStmt->execute();

                // Set the first uploaded image as main image
                if ($mainImage === '') {
                    $mainImage = $newName; // store plain filename, not encrypted
                }
            }
        }
    }

    // Update property with main image
    if ($mainImage !== '') {
        $updateStmt = $conn->prepare("UPDATE propertiies SET image=? WHERE id=?");
        $updateStmt->bind_param("si", $mainImage, $property_id);
        $updateStmt->execute();
    }

    header("Location: ../admin_side/index.php?success=property_added");
    exit();
    }?>