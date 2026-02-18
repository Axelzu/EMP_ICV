<?php
/**
 * Envía un archivo local a un servidor remoto mediante SFTP
 */
function enviarServidorPrivado($rutaLocal, $nombreArchivo) {
    // === CONFIGURACIÓN DEL DESTINO ===
    $host = '192.168.98.XX';      // CAMBIA: IP de tu servidor privado
    $port = 22;                  // Puerto SSH
    $user = 'alex';        // CAMBIA: Usuario de la computadora destino
    $pass = 'alex3102255';       // CAMBIA: Contraseña de la computadora destino
    
    // Ruta donde quieres que caigan los archivos en la computadora destino
    // IMPORTANTE: El usuario debe tener permisos de escritura aquí.
    $rutaRemota = "\\192.168.100.98\webpage" . $nombreArchivo; 

    // 1. Conectar
    $connection = @ssh2_connect($host, $port);
    if (!$connection) {
        error_log("SFTP Error: No se pudo conectar a $host");
        return false;
    }

    // 2. Autenticar
    if (!@ssh2_auth_password($connection, $user, $pass)) {
        error_log("SFTP Error: Autenticación fallida para $user");
        return false;
    }

    // 3. Crear recurso SFTP e inicializar transferencia
    $sftp = ssh2_sftp($connection);
    
    // 4. Escribir el archivo en el destino
    $stream = @fopen("ssh2.sftp://" . intval($sftp) . $rutaRemota, 'w');
    
    if (!$stream) {
        error_log("SFTP Error: No se pudo abrir el archivo remoto en $rutaRemota");
        return false;
    }

    $data = file_get_contents($rutaLocal);
    fwrite($stream, $data);
    fclose($stream);

    return true;
}