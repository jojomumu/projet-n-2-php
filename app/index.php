<?php

$configPath = 'config.ini';

$config = parse_ini_file($configPath);

$dbHost = $config['DB_HOST'];
$dbName = $config['DB_NAME'];
$dbUser = $config['DB_USER'];
$dbPass = $config['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$uri = $_SERVER["REQUEST_URI"];


if ($_SERVER["REQUEST_METHOD"] === "POST" && $uri === "/api/v1/addTech") {
    if (isset($_POST["techName"]) && isset($_FILES["logo"])) {
        $techName = $_POST["techName"];
        $logoFile = $_FILES["logo"];

        $logoPath = "logo/" . $logoFile["name"];
        move_uploaded_file($logoFile["tmp_name"], $logoPath);

        $insertTechQuery = "INSERT INTO technology (name, logoURL) VALUES (:techName, :logoPath)";
        $stmt = $pdo->prepare($insertTechQuery);
        $stmt->bindParam(':techName', $techName, PDO::PARAM_STR);
        $stmt->bindParam(':logoPath', $logoPath, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $techID = $pdo->lastInsertId();

            if (isset($_POST["categories"]) && is_array($_POST["categories"])) {
                foreach ($_POST["categories"] as $categoryID) {
                    $categoryID = intval($categoryID);
            
                    $insertCategoryQuery = "INSERT INTO technology_category (technologyID, categoryID) VALUES (:techID, :categoryID)";
                    $stmt = $pdo->prepare($insertCategoryQuery);
                    $stmt->bindParam(':techID', $techID, PDO::PARAM_INT);
                    $stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
            
                    if ($stmt->execute()) {
                        $confirmationMessages[] = "Technology associated with Category ID $categoryID";
                    } else {
                        $confirmationMessages[] = "Failed to associate with Category ID $categoryID";
                    }
                }
            }
            

            $response = [
                "message" => "Technology added successfully",
                "techID" => $techID,
                "confirmations" => $confirmationMessages
            ];

            header("Content-Type: application/json");
            echo json_encode($response);
        } else {
            $response = [
                "message" => "Failed to add technology"
            ];
            header("Content-Type: application/json");
            echo json_encode($response);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "GET" && $uri === '/api/v1/getTech') {
    $selectTechQuery = "SELECT t.*, GROUP_CONCAT(c.name) AS categories, GROUP_CONCAT(r.url) AS resource_urls
                        FROM technology t
                        LEFT JOIN technology_category tc ON t.ID = tc.technologyID
                        LEFT JOIN category c ON tc.categoryID = c.ID
                        LEFT JOIN technology_resource tr ON t.ID = tr.technologyID
                        LEFT JOIN resource r ON tr.resourceID = r.ID
                        GROUP BY t.ID";
    $stmt = $pdo->prepare($selectTechQuery);
    $stmt->execute();
    $technologies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($technologies) {
        $response = [
            "technologies" => $technologies
        ];

        header("Content-Type: application/json");
        echo json_encode($response);
    } else {
        $response = [
            "message" => "No technologies found"
        ];

        header("Content-Type: application/json");
        echo json_encode($response);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "DELETE" && isset($_GET["techID"])) {
    $techID = $_GET["techID"];

    $pdo->beginTransaction();

    try {
        $deleteResourceLinksQuery = "DELETE FROM technology_resource WHERE technologyID = :techID";
        $stmt = $pdo->prepare($deleteResourceLinksQuery);
        $stmt->bindParam(':techID', $techID, PDO::PARAM_INT);
        $stmt->execute();

        $deleteCategoryLinksQuery = "DELETE FROM technology_category WHERE technologyID = :techID";
        $stmt = $pdo->prepare($deleteCategoryLinksQuery);
        $stmt->bindParam(':techID', $techID, PDO::PARAM_INT);
        $stmt->execute();

        $deleteTechQuery = "DELETE FROM technology WHERE ID = :techID";
        $stmt = $pdo->prepare($deleteTechQuery);
        $stmt->bindParam(':techID', $techID, PDO::PARAM_INT);
        $stmt->execute();

        $pdo->commit();

        $response = [
            "message" => "Technology and associated links deleted successfully"
        ];

        header("Content-Type: application/json");
        echo json_encode($response);
    } catch (PDOException $e) {
        $pdo->rollBack();

        $response = [
            "message" => "Failed to delete technology and associated links: " . $e->getMessage()
        ];

        header("Content-Type: application/json");
        echo json_encode($response);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "PUT" && $uri === "/api/v1/modTech") {
    $requestData = json_decode(file_get_contents("php://input"), true);

    if (!isset($requestData["techID"])) {
        $response = [
            "message" => "TechID is required"
        ];

        header("Content-Type: application/json");
        echo json_encode($response);
        exit;
    }

    $techID = $requestData["techID"];
    $newTechName = isset($requestData["newTechName"]) ? $requestData["newTechName"] : null;
    $categoryIDs = isset($requestData["categories"]) ? $requestData["categories"] : [];

    $pdo->beginTransaction();

    try {
        if (!is_null($newTechName)) {
            $updateTechNameQuery = "UPDATE technology SET name = :newTechName WHERE ID = :techID";
            $stmt = $pdo->prepare($updateTechNameQuery);
            $stmt->bindParam(':newTechName', $newTechName, PDO::PARAM_STR);
            $stmt->bindParam(':techID', $techID, PDO::PARAM_INT);
            $stmt->execute();
        }

        $selectCurrentCategoriesQuery = "SELECT categoryID FROM technology_category WHERE technologyID = :techID";
        $stmt = $pdo->prepare($selectCurrentCategoriesQuery);
        $stmt->bindParam(':techID', $techID, PDO::PARAM_INT);
        $stmt->execute();
        $currentCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $categoriesToAdd = array_diff($categoryIDs, $currentCategories);
        $categoriesToRemove = array_diff($currentCategories, $categoryIDs);

        if (!empty($categoriesToAdd)) {
            foreach ($categoriesToAdd as $categoryID) {
                $insertCategoryQuery = "INSERT INTO technology_category (technologyID, categoryID) VALUES (:techID, :categoryID)";
                $stmt = $pdo->prepare($insertCategoryQuery);
                $stmt->bindParam(':techID', $techID, PDO::PARAM_INT);
                $stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        if (!empty($categoriesToRemove)) {
            foreach ($categoriesToRemove as $categoryID) {
                $deleteCategoryQuery = "DELETE FROM technology_category WHERE technologyID = :techID AND categoryID = :categoryID";
                $stmt = $pdo->prepare($deleteCategoryQuery);
                $stmt->bindParam(':techID', $techID, PDO::PARAM_INT);
                $stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        $pdo->commit();

        $response = [
            "message" => "Technology modified successfully"
        ];

        header("Content-Type: application/json");
        echo json_encode($response);
    } catch (PDOException $e) {
        $pdo->rollBack();

        $response = [
            "message" => "Failed to modify technology: " . $e->getMessage()
        ];

        header("Content-Type: application/json");
        echo json_encode($response);
    }


    echo json_encode($response);
}



if ($_SERVER["REQUEST_METHOD"] === "POST" && $uri === "/api/v1/addCategory") {
    if (isset($_POST["categoryName"])) {
        $categoryName = $_POST["categoryName"];

        $insertCategoryQuery = "INSERT INTO category (name) VALUES (:categoryName)";
        $stmt = $pdo->prepare($insertCategoryQuery);
        $stmt->bindParam(':categoryName', $categoryName, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $categoryID = $pdo->lastInsertId();
            
            $response = [
                "message" => "Category added successfully",
                "categoryID" => $categoryID
            ];
        } else {
            $response = [
                "message" => "Failed to add category"
            ];
        }

        header("Content-Type: application/json");
        echo json_encode($response);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "PUT" && $uri === "/api/v1/modCategory") {
    $putData = json_decode(file_get_contents("php://input"), true);

    if (isset($putData["categoryID"]) || isset($putData["technologyID"])) {
        $categoryID = isset($putData["categoryID"]) ? $putData["categoryID"] : null;
        $technologyID = isset($putData["technologyID"]) ? $putData["technologyID"] : null;
        $categoryName = isset($putData["categoryName"]) ? $putData["categoryName"] : null;

        if (!is_null($categoryName)) {
            $updateCategoryNameQuery = "UPDATE category SET name = :categoryName WHERE ID = :categoryID";
            $stmt = $pdo->prepare($updateCategoryNameQuery);
            $stmt->bindParam(':categoryName', $categoryName, PDO::PARAM_STR);
            $stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $confirmationMessages[] = "Category name updated successfully";
            } else {
                $confirmationMessages[] = "Failed to update category name";
            }
        }

        if (!is_null($technologyID)) {
            $updateLinkQuery = "UPDATE technology_category SET technologyID = :technologyID WHERE categoryID = :categoryID";
            $stmt = $pdo->prepare($updateLinkQuery);
            $stmt->bindParam(':technologyID', $technologyID, PDO::PARAM_INT);
            $stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $confirmationMessages[] = "Category linked to Technology ID $technologyID";
            } else {
                $confirmationMessages[] = "Failed to link category to Technology ID $technologyID";
            }
        }

        $response = [
            "message" => "Category updated successfully",
            "confirmations" => $confirmationMessages
        ];
    } else {
        $response = [
            "message" => "No categoryID or technologyID provided for modification"
        ];
    }

    header("Content-Type: application/json");
    echo json_encode($response);
}





if ($_SERVER["REQUEST_METHOD"] === "GET" && $uri === '/api/v1/getCategory') {
    $selectCategoriesQuery = "SELECT ID, name FROM category";
    $categories = $pdo->query($selectCategoriesQuery)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categories as &$category) {
        $categoryID = $category["ID"];
        $selectTechnologiesQuery = "SELECT t.* FROM technology t
                                   JOIN technology_category tc ON t.ID = tc.technologyID
                                   WHERE tc.categoryID = :categoryID";
        $stmt = $pdo->prepare($selectTechnologiesQuery);
        $stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
        $stmt->execute();
        $category["technologies"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    header("Content-Type: application/json");
    echo json_encode($categories);
}


if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["categoryID"])) {
    $categoryID = $_GET["categoryID"];
    
    $selectCategoryQuery = "SELECT * FROM category WHERE ID = :categoryID";
    $stmt = $pdo->prepare($selectCategoryQuery);
    $stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
    $stmt->execute();
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        
        $selectTechnologiesQuery = "SELECT t.* FROM technology t
                                   JOIN technology_category tc ON t.ID = tc.technologyID
                                   WHERE tc.categoryID = :categoryID";
        $stmt = $pdo->prepare($selectTechnologiesQuery);
        $stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
        $stmt->execute();
        $technologies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            "category" => $category,
            "technologies" => $technologies
        ];

    } else {
        $response = [
            "message" => "Category not found"
        ];

        header("Content-Type: application/json");
        echo json_encode($response);
    }


    header("Content-Type: application/json");
    echo json_encode($response);
}

if ($_SERVER["REQUEST_METHOD"] === "DELETE" && isset($_GET["categoryID"])) {
        $categoryID = $_GET["categoryID"];

        
        $pdo->beginTransaction();

        try {
            
            $deleteLinksQuery = "DELETE FROM technology_category WHERE categoryID = :categoryID";
            $stmt = $pdo->prepare($deleteLinksQuery);
            $stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
            $stmt->execute();

            
            $deleteCategoryQuery = "DELETE FROM category WHERE ID = :categoryID";
            $stmt = $pdo->prepare($deleteCategoryQuery);
            $stmt->bindParam(':categoryID', $categoryID, PDO::PARAM_INT);
            $stmt->execute();

            
            $pdo->commit();

            $response = [
                "message" => "Category and associated links deleted successfully"
            ];

            header("Content-Type: application/json");
            echo json_encode($response);
        } catch (PDOException $e) {
            
            $pdo->rollBack();

            $response = [
                "message" => "Failed to delete category and associated links: " . $e->getMessage()
            ];

            header("Content-Type: application/json");
            echo json_encode($response);
        }
        
    }


if ($_SERVER["REQUEST_METHOD"] === "POST" && $uri === "/api/v1/addResource") {
        if (isset($_POST["techID"], $_POST["resourceName"], $_POST["resourceURL"])) {
            $techID = $_POST["techID"];
            $resourceName = $_POST["resourceName"];
            $resourceURL = $_POST["resourceURL"];

            
            $insertResourceQuery = "INSERT INTO resource (description, url) VALUES ('$resourceName', '$resourceURL')";
            $pdo->query($insertResourceQuery);

            
            $resourceID = $pdo->lastInsertId();

            
            $insertLinkQuery = "INSERT INTO technology_resource (technologyID, resourceID) VALUES ('$techID', '$resourceID')";
            $pdo->query($insertLinkQuery);

            $response = [
                "message" => "Resource added and linked to technology successfully",
                "resourceID" => $resourceID
            ];

            header("Content-Type: application/json");
            echo json_encode($response);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "GET" && $uri === '/api/v1/getResource') {
    $selectResourceQuery = "SELECT id, description, url FROM resource";
    $stmt = $pdo->prepare($selectResourceQuery);
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($resources) {
        $response = [
            "resources" => $resources
        ];

        header("Content-Type: application/json");
        echo json_encode($response);
    } else {
        $response = [
            "message" => "No resources found"
        ];

        header("Content-Type: application/json");
        echo json_encode($response);
    }
}




?>