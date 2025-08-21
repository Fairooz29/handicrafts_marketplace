<?php
/**
 * Products API - Handle product-related operations
 */

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'search_helpers.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    sendJsonResponse(['error' => $e->getMessage()], 500);
}

function handleGetRequest($action) {
    global $db;
    
    switch ($action) {
        case 'all':
            getAllProducts();
            break;
        case 'single':
            getSingleProduct();
            break;
        case 'category':
            getProductsByCategory();
            break;
        case 'search':
            searchProducts();
            break;
        case 'filter':
            filterProducts();
            break;
        default:
            getAllProducts();
    }
}

function getAllProducts() {
    global $db;
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT 
                p.id, p.name, p.short_description, p.price, p.original_price, 
                p.discount_percentage, p.image, p.stock_quantity,
                c.name as category_name,
                a.name as artisan_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN artisans a ON p.artisan_id = a.id
            WHERE p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT $limit OFFSET $offset";
    
    $products = $db->fetchAll($sql);
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
    $totalResult = $db->fetch($countSql);
    $total = $totalResult['total'];
    
    sendJsonResponse([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'per_page' => $limit
        ]
    ]);
}

function getSingleProduct() {
    global $db;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        sendJsonResponse(['error' => 'Product ID is required'], 400);
    }
    
    $sql = "SELECT 
                p.*, 
                c.name as category_name,
                a.name as artisan_name, a.bio as artisan_bio, a.image as artisan_image,
                a.location as artisan_location, a.speciality as artisan_speciality
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN artisans a ON p.artisan_id = a.id
            WHERE p.id = ? AND p.status = 'active'";
    
    $product = $db->fetch($sql, [$id]);
    
    if (!$product) {
        sendJsonResponse(['error' => 'Product not found'], 404);
    }
    
    sendJsonResponse([
        'success' => true,
        'product' => $product
    ]);
}

function getProductsByCategory() {
    global $db;
    
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    
    if (!$categoryId) {
        sendJsonResponse(['error' => 'Category ID is required'], 400);
    }
    
    $sql = "SELECT 
                p.id, p.name, p.short_description, p.price, p.original_price, 
                p.discount_percentage, p.image, p.stock_quantity,
                c.name as category_name,
                a.name as artisan_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN artisans a ON p.artisan_id = a.id
            WHERE p.category_id = ? AND p.status = 'active'
            ORDER BY p.created_at DESC";
    
    $products = $db->fetchAll($sql, [$categoryId]);
    
    sendJsonResponse([
        'success' => true,
        'products' => $products
    ]);
}

function searchProducts() {
    global $db;
    
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (empty($query)) {
        getAllProducts();
        return;
    }
    
    // Build advanced search conditions with fuzzy matching
    $searchConditions = buildAdvancedSearchConditions($query);
    
    if (empty($searchConditions['conditions'])) {
        getAllProducts();
        return;
    }
    
    $whereClause = '(' . implode(' OR ', $searchConditions['conditions']) . ')';
    
    $sql = "SELECT DISTINCT
                p.id, p.name, p.short_description, p.price, p.original_price, 
                p.discount_percentage, p.image, p.stock_quantity,
                c.name as category_name,
                a.name as artisan_name,
                CASE 
                    WHEN LOWER(p.name) LIKE LOWER(?) THEN 100
                    WHEN LOWER(p.short_description) LIKE LOWER(?) THEN 80
                    WHEN LOWER(c.name) LIKE LOWER(?) THEN 70
                    WHEN LOWER(a.name) LIKE LOWER(?) THEN 60
                    ELSE 50
                END as relevance_score
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN artisans a ON p.artisan_id = a.id
            WHERE p.status = 'active' AND $whereClause
            ORDER BY relevance_score DESC, p.created_at DESC";
    
    // Add exact match parameters for relevance scoring
    $exactMatchTerm = "%$query%";
    $params = array_merge(
        [$exactMatchTerm, $exactMatchTerm, $exactMatchTerm, $exactMatchTerm],
        $searchConditions['params']
    );
    
    $products = $db->fetchAll($sql, $params);
    
    // Get search suggestions and synonyms
    $suggestions = getSynonymsAndMisspellings($query);
    
    sendJsonResponse([
        'success' => true,
        'products' => $products,
        'search_query' => $query,
        'total_results' => count($products),
        'search_suggestions' => $suggestions
    ]);
}

function filterProducts() {
    global $db;
    
    // Get filter parameters
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $artisanId = isset($_GET['artisan_id']) ? (int)$_GET['artisan_id'] : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
    $offset = ($page - 1) * $limit;
    
    // Build the WHERE clause
    $whereConditions = ['p.status = ?'];
    $params = ['active'];
    
    // Add category filter
    if ($categoryId) {
        $whereConditions[] = 'p.category_id = ?';
        $params[] = $categoryId;
    }
    
    // Add artisan filter
    if ($artisanId) {
        $whereConditions[] = 'p.artisan_id = ?';
        $params[] = $artisanId;
    }
    
    // Add search filter
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $whereConditions[] = '(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ? OR c.name LIKE ? OR a.name LIKE ?)';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Build the ORDER BY clause
    $orderClause = 'ORDER BY ';
    switch ($sort) {
        case 'price_low':
            $orderClause .= 'p.price ASC';
            break;
        case 'price_high':
            $orderClause .= 'p.price DESC';
            break;
        case 'name':
            $orderClause .= 'p.name ASC';
            break;
        case 'newest':
            $orderClause .= 'p.created_at DESC';
            break;
        default:
            $orderClause .= 'p.created_at DESC'; // Default: newest first
    }
    
    // Main query to get products
    $sql = "SELECT 
                p.id, p.name, p.short_description, p.price, p.original_price, 
                p.discount_percentage, p.image, p.stock_quantity,
                c.name as category_name,
                a.name as artisan_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN artisans a ON p.artisan_id = a.id
            WHERE $whereClause
            $orderClause
            LIMIT $limit OFFSET $offset";
    
    $products = $db->fetchAll($sql, $params);
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total 
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN artisans a ON p.artisan_id = a.id
                 WHERE $whereClause";
    
    $totalResult = $db->fetch($countSql, $params);
    $total = $totalResult['total'];
    
    // Get filter counts for UI feedback
    $filterCounts = getFilterCounts($categoryId, $artisanId, $search);
    
    sendJsonResponse([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'per_page' => $limit
        ],
        'filters' => [
            'category_id' => $categoryId,
            'artisan_id' => $artisanId,
            'search' => $search,
            'sort' => $sort
        ],
        'filter_counts' => $filterCounts
    ]);
}

function getFilterCounts($categoryId, $artisanId, $search) {
    global $db;
    
    $counts = [];
    
    try {
        // Count by categories
        $categorySql = "SELECT c.id, c.name, COUNT(p.id) as count
                        FROM categories c
                        LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'";
        
        $whereConditions = [];
        $params = [];
        
        if ($artisanId) {
            $whereConditions[] = 'p.artisan_id = ?';
            $params[] = $artisanId;
        }
        
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $whereConditions[] = '(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($whereConditions)) {
            $categorySql .= ' WHERE ' . implode(' AND ', $whereConditions);
        }
        
        $categorySql .= ' GROUP BY c.id, c.name ORDER BY c.name';
        
        $counts['categories'] = $db->fetchAll($categorySql, $params);
        
        // Count by artisans
        $artisanSql = "SELECT a.id, a.name, COUNT(p.id) as count
                       FROM artisans a
                       LEFT JOIN products p ON a.id = p.artisan_id AND p.status = 'active'";
        
        $whereConditions = [];
        $params = [];
        
        if ($categoryId) {
            $whereConditions[] = 'p.category_id = ?';
            $params[] = $categoryId;
        }
        
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $whereConditions[] = '(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($whereConditions)) {
            $artisanSql .= ' WHERE ' . implode(' AND ', $whereConditions);
        }
        
        $artisanSql .= ' GROUP BY a.id, a.name ORDER BY a.name';
        
        $counts['artisans'] = $db->fetchAll($artisanSql, $params);
        
    } catch (Exception $e) {
        // If counts fail, continue without them
        $counts = ['error' => $e->getMessage()];
    }
    
    return $counts;
}

function handlePostRequest($action) {
    // Handle POST requests for products if needed
    sendJsonResponse(['error' => 'POST method not implemented for products'], 501);
}
?>
