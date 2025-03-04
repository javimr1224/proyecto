<?php

/**
 * Lee un archivo .env y devuelve los datos de conexión a la base de datos.
 *
 * @param string $envPath Ruta al archivo .env.
 * @return array Datos de conexión a la base de datos.
 * @throws Exception Si el archivo .env no existe o no se puede leer.
 */
function getDatabaseConfigFromEnv($envPath)
{
    if (!file_exists($envPath)) {
        throw new Exception("El archivo .env no existe en la ruta especificada.");
    }

    $envContent = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($envContent === false) {
        throw new Exception("No se pudo leer el archivo .env.");
    }

    $dbConfig = [];
    foreach ($envContent as $line) {
        // Ignorar comentarios y líneas vacías
        if (str_starts_with(trim($line), '#') || empty(trim($line))) {
            continue;
        }

        // Separar clave y valor
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Almacenar valores relevantes para la base de datos
        switch ($key) {
            case 'DB_CONNECTION':
                $dbConfig['connection'] = $value;
                break;
            case 'DB_HOST':
                $dbConfig['host'] = $value;
                break;
            case 'DB_PORT':
                $dbConfig['port'] = $value;
                break;
            case 'DB_DATABASE':
                $dbConfig['database'] = $value;
                break;
            case 'DB_USERNAME':
                $dbConfig['username'] = $value;
                break;
            case 'DB_PASSWORD':
                $dbConfig['password'] = $value;
                break;
        }
    }

    return $dbConfig;
}