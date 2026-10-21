<?php
declare(strict_types=1);
define("ROOT", dirname(__DIR__));
$configPath =
    getenv("GED_CONFIG") ?:
    ROOT .
        "/config/" .
        (is_file(ROOT . "/config/local.php") ? "local.php" : "example.php");
$config = require $configPath;
require ROOT . "/app/Database.php";
require ROOT . "/app/Library/BookRepository.php";
require ROOT . "/app/Library/Catalog.php";
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}
function redirect(string $path): never
{
    header("Location: " . $path, true, 303);
    exit();
}
function pvp($value): string
{
    return $value === null || $value === ""
        ? "Pendiente"
        : '$ ' . preg_replace("/\B(?=(\d{3})+(?!\d))/", ".", (string) $value);
}
function csrf(): string
{
    return '<input type="hidden" name="csrf" value="' .
        e($_SESSION["csrf"]) .
        '">';
}
function view(string $name, array $data = []): void
{
    global $config;
    extract($data);
    require ROOT . "/views/layout.php";
}

function localDate(string $value): string
{
    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'))
        ->format('d/m/Y · H:i');
}

/** Relative installation directory, inferred from the web entry point unless configured. */
function basePath(): string
{
    global $config;
    $path = $config['base_path'] ?? dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    return $path === '/' || $path === '.' ? '' : '/' . trim($path, '/');
}
function url(string $path = '/'): string
{
    return basePath() . '/' . ltrim($path, '/');
}
