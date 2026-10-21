<?php
if ($action === "login") {
    if ($user) {
        redirect("?");
    }
    if ($post) {
        $error = $auth->login(
            (string) ($_POST["email"] ?? ""),
            (string) ($_POST["password"] ?? ""),
            $_SERVER["REMOTE_ADDR"] ?? "local",
        );
        if (!$error) {
            redirect("?");
        }
    }
    view("login", ["error" => $error ?? null, "title" => "Bienvenido a GED"]);
    exit();
}
if ($action === "logout" && $post) {
    $_SESSION = [];
    session_destroy();
    setcookie("ged_session", "", [
        "expires" => time() - 3600,
        "path" => url(),
        "httponly" => true,
        "samesite" => "Lax",
    ]);
    redirect("?action=login");
}
if ($action === "password") {
    if ($post) {
        $errors = $auth->password(
            $user,
            (string) ($_POST["current"] ?? ""),
            (string) ($_POST["password"] ?? ""),
            (string) ($_POST["confirm"] ?? ""),
        );
        if (!$errors) {
            $_SESSION["flash"] = "Contraseña actualizada.";
            redirect("?");
        }
    }
    view("password", ["errors" => $errors, "title" => "Cambiar contraseña"]);
    exit();
}
