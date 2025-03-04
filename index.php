<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include necessary files
include("./resources/includes/bootstrap.php");
include("./resources/includes/functions.inc.php");

// Load database configuration
try {
    $dbConfig = getDatabaseConfigFromEnv(__DIR__ . '/.env');
} catch (Exception $e) {
    echo 'Error de conexión a la base de datos: ' . $e->getMessage();
    exit;
}

// Initialize the game board if not already done
if (!isset($_SESSION['board'])) {
    $_SESSION['board'] = initBoard();
}

// Initialize marks array if not already done
if (!isset($_SESSION['marks'])) {
    $_SESSION['marks'] = array_fill(0, 10, array_fill(0, 10, null));
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark':
            // Get row and column from form
            $row = intval($_POST['fila']) - 1; // Convert from 1-based to 0-based indexing
            $col = intval($_POST['columna']) - 1;
            
            // Validate row and column
            if ($row >= 0 && $row < 10 && $col >= 0 && $col < 10) {
                $result = markCell($row, $col);
                $_SESSION['message'] = $result;
            } else {
                $_SESSION['message'] = [
                    'success' => false, 
                    'message' => 'Fila o columna inválida. Por favor ingrese valores entre 1 y 7.'
                ];
            }
            break;
            
        case 'clear':
            $result = clearBoard();
            $_SESSION['message'] = $result;
            break;
            
        case 'save':
            $result = saveToDatabase($pdo);
            $_SESSION['message'] = $result;
            break;
    }
    
    // Redirect to prevent form resubmission
    header('Location: index.php');
    exit;
}

// Generate the HTML markup for the game board
$bodyContent = getBodyMarkup();

// Include the template file to render the page
include("./resources/templates/index.tpl.php");
?>