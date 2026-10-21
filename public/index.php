<?php
declare(strict_types=1);
require dirname(__DIR__) . "/app/bootstrap.php";
require ROOT . "/app/Access/AccessRepository.php";
require ROOT . "/app/Access/Auth.php";
require ROOT . "/app/Covers/Covers.php";
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (basePath() !== '' && $requestPath === basePath()) {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . url() . ($query !== '' ? '?' . $query : ''), true, 308);
    exit;
}
ini_set("session.use_strict_mode", "1");
ini_set("session.use_only_cookies", "1");
session_name("ged_session");
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
    "path" => url(),
]);
session_start();
header(
    "Content-Security-Policy: default-src 'self'; img-src 'self' blob:; style-src 'self'; script-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'",
);
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: same-origin");
header("Cache-Control: no-store");
if (isset($_SESSION["last"]) && time() - $_SESSION["last"] > 1800) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION["csrf"] ??= bin2hex(random_bytes(32));
$_SESSION["last"] = time();
$action = $_GET["action"] ?? "library";
$post = $_SERVER["REQUEST_METHOD"] === "POST";
try {
    $db = Database::connect($config);
    $auth = new Auth(new AccessRepository($db));
    $catalog = new Catalog(new BookRepository($db));
    $user = $auth->user();
    if ($action !== "login" && !$user) {
        redirect("?action=login");
    }
    if (
        $post &&
        !hash_equals($_SESSION["csrf"], (string) ($_POST["csrf"] ?? ""))
    ) {
        http_response_code(403);
        view("message", [
            "title" => "Formulario vencido",
            "message" => "Volvé a abrir la pantalla e intentá nuevamente.",
        ]);
        exit();
    }
    $errors = [];
    require ROOT . "/app/Access/controller.php";
    require ROOT . "/app/Library/controller.php";
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    view("message", [
        "title" => "No pudimos completar la operación",
        "message" =>
            "Intentá nuevamente. Si el problema continúa, contactá al responsable de GED.",
    ]);
}
