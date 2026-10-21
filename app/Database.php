<?php
final class Database
{
    public static function connect(array $config): PDO
    {
        $db = new PDO($config["dsn"], $config["user"], $config["password"], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === "sqlite") {
            $db->exec("PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000");
        }
        return $db;
    }
    public static function migrate(PDO $db): void
    {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) !== "sqlite") {
            throw new RuntimeException(
                "Este instalador requiere SQLite; consulte la documentación de migración.",
            );
        }
        $db->exec(
            "CREATE TABLE IF NOT EXISTS schema_migrations (version INTEGER PRIMARY KEY)",
        );
        foreach (glob(ROOT . "/migrations/*.sql") as $file) {
            $version = (int) basename($file);
            $q = $db->prepare(
                "SELECT version FROM schema_migrations WHERE version=?",
            );
            $q->execute([$version]);
            if (!$q->fetch()) {
                $db->beginTransaction();
                try {
                    $db->exec(file_get_contents($file));
                    $db->prepare(
                        "INSERT INTO schema_migrations VALUES (?)",
                    )->execute([$version]);
                    $db->commit();
                } catch (Throwable $e) {
                    $db->rollBack();
                    throw $e;
                }
            }
        }
    }
}
