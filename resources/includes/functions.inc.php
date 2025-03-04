<?php
session_start();

// Function to initialize a new game board
function initBoard() {
    // Create a 10x10 board filled with zeros (empty water)
    $board = array_fill(0, 10, array_fill(0, 10, 0));
    
    // Define ships with their sizes
    $ships = [
        ['id' => '1', 'size' => 5],
        ['id' => '2', 'size' => 4],
        ['id' => '3', 'size' => 3],
        ['id' => '4', 'size' => 3],
        ['id' => '5', 'size' => 2],
        ['id' => '6', 'size' => 1],
        ['id' => '7', 'size' => 0]
    ];
    
    // Place each ship on the board
    foreach ($ships as $ship) {
        $board = placeShip($board, $ship['size']);
    }

    return $board;
}

// Function to place a ship of given size on the board
function placeShip($board, $size) {
    // Skip if size is 0
    if ($size == 0) {
        return $board;
    }
    
    $maxAttempts = 100;
    $attempts = 0;
    
    while ($attempts < $maxAttempts) {
        // Choose random direction: 0 = horizontal, 1 = vertical
        $direction = rand(0, 1);
        
        if ($direction == 0) { // Horizontal placement
            $row = rand(0, 9);
            $col = rand(0, 9 - $size);
            
            // Check if this position is valid (no other ships)
            $valid = true;
            for ($i = 0; $i < $size; $i++) {
                if ($board[$row][$col + $i] != 0) {
                    $valid = false;
                    break;
                }
            }
            
            // Place the ship if position is valid
            if ($valid) {
                for ($i = 0; $i < $size; $i++) {
                    $board[$row][$col + $i] = 1; // 1 indicates a ship
                }
                return $board;
            }
        } else { // Vertical placement
            $row = rand(0, 9 - $size);
            $col = rand(0, 9);
            
            // Check if this position is valid (no other ships)
            $valid = true;
            for ($i = 0; $i < $size; $i++) {
                if ($board[$row + $i][$col] != 0) {
                    $valid = false;
                    break;
                }
            }
            
            // Place the ship if position is valid
            if ($valid) {
                for ($i = 0; $i < $size; $i++) {
                    $board[$row + $i][$col] = 1; // 1 indicates a ship
                }
                return $board;
            }
        }
        
        $attempts++;
    }
    
    // Return the board even if we couldn't place a ship after max attempts
    return $board;
}

// Function to process a mark action (hit or miss)
function markCell($row, $col) {
    // Initialize board if not already done
    if (!isset($_SESSION['board'])) {
        $_SESSION['board'] = initBoard();
    }
    
    // Initialize marks array if not already done
    if (!isset($_SESSION['marks'])) {
        $_SESSION['marks'] = array_fill(0, 10, array_fill(0, 10, null));
    }
    
    // Check if there's a ship at this position (1 = ship)
    $hit = ($_SESSION['board'][$row][$col] == 1);
    
    // Mark the cell as hit or miss
    $_SESSION['marks'][$row][$col] = $hit ? 'hit' : 'miss';
    
    return [
        'success' => true,
        'hit' => $hit,
        'message' => $hit ? '¡Tocado!' : 'Agua'
    ];
}

// Function to clear all marks from the board
function clearBoard() {
    // Remove all marks but keep the ships
    $_SESSION['marks'] = array_fill(0, 10, array_fill(0, 10, null));
    
    return [
        'success' => true,
        'message' => 'Tablero limpiado correctamente'
    ];
}

// Function to save the current board state to the database
function saveToDatabase($pdo) {
    if (!isset($_SESSION['marks'])) {
        return [
            'success' => false, 
            'message' => 'No hay marcas para guardar en la base de datos'
        ];
    }
    
    try {
        // Convert marks array to JSON
        $marcasJson = json_encode($_SESSION['marks']);
        
        // Prepare SQL statement
        $stmt = $pdo->prepare("INSERT INTO tableros (marcas) VALUES (:marcas)");
        
        // Execute with parameters
        $stmt->execute(['marcas' => $marcasJson]);
        
        // Get the ID of the inserted record
        $id = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'message' => "Tablero guardado correctamente en la base de datos con ID: $id",
            'id' => $id
        ];
    } catch (PDOException $e) {
        return [
            'success' => false, 
            'message' => 'Error al guardar en la base de datos: ' . $e->getMessage()
        ];
    }
}

// Function to retrieve board IDs from the database
function getBoardIds($pdo) {
    try {
        $stmt = $pdo->query("SELECT id FROM tableros ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

// Function to generate HTML for the game interface
function getBodyMarkup() {
    // Get current marks if they exist
    $marks = $_SESSION['marks'] ?? array_fill(0, 10, array_fill(0, 10, null));
    
    // Start building HTML
    $html = '<div class="container-lg">';
    
    // Add a message display area for success/error messages
    if (isset($_SESSION['message'])) {
        $messageType = $_SESSION['message']['success'] ? 'success' : 'danger';
        $html .= '<div class="alert alert-' . $messageType . ' mt-3">' . $_SESSION['message']['message'] . '</div>';
        // Clear the message after displaying it
        unset($_SESSION['message']);
    }
    
    $html .= '<div class="row">
            <div class="form-container col-3">
                <form method="post" action="index.php">
                    <div class="row">
                        <div class="col-12">
                            <label for="columna" class="form-label">Columna (1-7)</label>
                            <input type="number" class="form-control" id="columna" name="columna" min="1" max="7" value="1" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <label for="fila" class="form-label">Fila (1-7)</label>
                            <input type="number" class="form-control" id="fila" name="fila" min="1" max="7" value="1" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" name="action" value="mark" class="btn btn-primary mt-3">Marcar</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" name="action" value="clear" class="btn btn-danger mt-3">Limpiar</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" name="action" value="save" class="btn btn-success mt-3">Guardar en B.D</button>
                        </div>
                    </div>
                </form>
            </div>
            <div id="board" class="col-9">
                <div id="messageArea"></div>
                <table>
                    <tr>
                        <th class="numbers"></th>
                        <th class="numbers">1</th>
                        <th class="numbers">2</th>
                        <th class="numbers">3</th>
                        <th class="numbers">4</th>
                        <th class="numbers">5</th>
                        <th class="numbers">6</th>
                        <th class="numbers">7</th>
                    </tr>';

    // Generate the board grid cells
    for ($i = 0; $i < 7; $i++) {
        $html .= '<tr>
                    <th class="letters">' . ($i + 1) . '</th>';
        
        for ($j = 0; $j < 7; $j++) {
            $cellClass = "cell";
            
            // Apply hit or miss styling if the cell is marked
            if (isset($marks[$i][$j])) {
                $cellClass .= " " . $marks[$i][$j];
            }
            
            $html .= '<td>
                        <div id="cell-' . ($i + 1) . '-' . ($j + 1) . '" class="' . $cellClass . '"></div>
                    </td>';
        }
        
        $html .= '</tr>';
    }

    $html .= '</table>
            </div>
        </div>
    </div>';
    
    return $html;
}

// Utility function for debugging
function dump($vars) {
    echo '<pre>' . print_r($vars, true) . '</pre>';
}
?>