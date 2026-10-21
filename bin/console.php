<?php
require dirname(__DIR__) . "/app/bootstrap.php";
require ROOT . "/app/Covers/Covers.php";
if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit();
}
umask(0077);
function secret(string $prompt): string
{
    fwrite(STDOUT, $prompt);
    $tty = function_exists("stream_isatty") && stream_isatty(STDIN);
    if ($tty) {
        system("stty -echo");
    }
    try {
        $s = rtrim((string) fgets(STDIN), "\r\n");
    } finally {
        if ($tty) {
            system("stty echo");
            fwrite(STDOUT, "\n");
        }
    }
    return $s;
}
try {
    $cmd = $argv[1] ?? "help";
    if ($cmd === "check") {
        foreach (
            ["pdo_sqlite", "mbstring", "fileinfo", "session", "ctype"]
            as $ext
        ) {
            if (!extension_loaded($ext)) {
                throw new RuntimeException("Falta la extensión " . $ext);
            }
        }
        if (PHP_INT_SIZE < 8) {
            throw new RuntimeException("Se requiere PHP de 64 bits.");
        }
        if (PHP_VERSION_ID < 80200) {
            throw new RuntimeException("Se requiere PHP 8.2 o posterior.");
        }
        echo "Requisitos correctos: PHP " .
            PHP_VERSION .
            " y extensiones disponibles.\n";
        exit();
    }
    if (!is_dir(Covers::directory())) {
        mkdir(Covers::directory(), 0700, true);
    }
    $db = Database::connect($config);
    if ($cmd === "migrate") {
        Database::migrate($db);
        echo "Migraciones aplicadas.\n";
    } elseif (in_array($cmd, ["create-master", "reset-password"], true)) {
        $email = strtolower(trim($argv[2] ?? ""));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(
                "Indicá un correo válido como segundo argumento.",
            );
        }
        if (
            $cmd === "create-master" &&
            (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0
        ) {
            throw new RuntimeException("Ya existe el usuario maestro.");
        }
        $password = secret("Contraseña (8–72 caracteres): ");
        $confirm = secret("Repetir contraseña: ");
        if (
            strlen($password) < 8 ||
            strlen($password) > 72 ||
            $password !== $confirm
        ) {
            throw new RuntimeException(
                "Longitud inválida o contraseñas diferentes.",
            );
        }
        if ($cmd === "create-master") {
            $db->prepare(
                "INSERT INTO users(email,password_hash) VALUES (?,?)",
            )->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
        } else {
            $q = $db->prepare(
                "UPDATE users SET password_hash=?,session_version=session_version+1 WHERE email=?",
            );
            $q->execute([password_hash($password, PASSWORD_DEFAULT), $email]);
            if (!$q->rowCount()) {
                throw new RuntimeException("No existe ese usuario.");
            }
            $db->exec("DELETE FROM login_attempts");
        }
        echo "Usuario actualizado.\n";
    } elseif ($cmd === "backup") {
        $dest = $argv[2] ?? "";
        if (!$dest || file_exists($dest)) {
            throw new RuntimeException(
                "Indicá un directorio nuevo para el respaldo.",
            );
        }
        mkdir($dest, 0700, true);
        mkdir($dest . "/covers", 0700);
        $db->exec("VACUUM INTO " . $db->quote($dest . "/ged.sqlite"));
        foreach (glob(Covers::directory() . "/*") as $f) {
            if (is_file($f)) {
                copy($f, $dest . "/covers/" . basename($f));
            }
        }
        echo "Respaldo completo. Realizar con la aplicación detenida para sincronizar tapas.\n";
    } else {
        echo "Comandos: check | migrate | create-master correo | reset-password correo | backup /ruta/nueva\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
