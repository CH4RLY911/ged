<?php
$id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT) ?: 0;
$book = $id ? $catalog->find($id) : null;
if (in_array($action, ["detail", "edit", "toggle", "cover"], true) && !$book) {
    http_response_code(404);
    view("message", [
        "title" => "Título no encontrado",
        "message" => "El título solicitado no existe.",
    ]);
    exit();
}
if ($action === "cover") {
    if (
        !$book["cover"] ||
        !is_file(Covers::directory() . "/" . $book["cover"])
    ) {
        http_response_code(404);
        exit();
    }
    header(
        "Content-Type: " .
            new finfo(FILEINFO_MIME_TYPE)->file(
                Covers::directory() . "/" . $book["cover"],
            ),
    );
    readfile(Covers::directory() . "/" . $book["cover"]);
    exit();
}
if ($action === "toggle" && $post) {
    $catalog->toggle($id);
    $_SESSION["flash"] = $book["active"]
        ? "Título desactivado."
        : "Título reactivado.";
    redirect("?action=detail&id=" . $id);
}
if ($action === "new" || $action === "edit") {
    $d = $book ?? array_fill_keys(Catalog::FIELDS, "");
    if ($post) {
        $token = (string) ($_POST["submission"] ?? "");
        if (!isset($_SESSION["forms"][$token])) {
            http_response_code(409);
            view("message", [
                "title" => "Formulario ya utilizado",
                "message" =>
                    "Este formulario ya fue guardado o venció. Volvé a la biblioteca para consultar el título.",
            ]);
            exit();
        }
        [$d, $errors] = $catalog->validate($_POST, $book ? $id : null);
        $cover = $book["cover"] ?? null;
        $uploaded = null;
        if (
            !$errors &&
            isset($_FILES["cover"]) &&
            $_FILES["cover"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            try {
                $uploaded = Covers::upload($_FILES["cover"]);
                $cover = $uploaded;
            } catch (InvalidArgumentException $e) {
                $errors["cover"] = $e->getMessage();
            }
        } elseif (!$errors && !empty($_POST["remove_cover"])) {
            $cover = null;
        }
        if (!$errors) {
            try {
                $saved = $catalog->save($d, $book ? $id : null, $cover);
            } catch (PDOException $e) {
                if ($uploaded) {
                    Covers::remove($uploaded);
                }
                if ($e->getCode() === "23000") {
                    $errors["code"] =
                        "El código o ISBN ya está registrado. Revisá ambos campos.";
                } else {
                    throw $e;
                }
            }
            if (!$errors) {
                if (($book["cover"] ?? null) !== $cover) {
                    Covers::remove($book["cover"] ?? null);
                }
                unset($_SESSION["forms"][$token]);
                $_SESSION["flash"] = "Título guardado.";
                redirect(
                    ($_POST["after"] ?? "") === "another"
                        ? "?action=new"
                        : "?action=detail&id=" . $saved,
                );
            }
        }
    }
    $_SESSION["forms"] ??= [];
    if (count($_SESSION["forms"]) > 30) {
        array_shift($_SESSION["forms"]);
    }
    $token = bin2hex(random_bytes(24));
    $_SESSION["forms"][$token] = true;
    view("form", [
        "title" => $book ? "Editar título" : "Nuevo título",
        "d" => $d,
        "book" => $book,
        "errors" => $errors,
        "token" => $token,
    ]);
    exit();
}
if ($action === "detail") {
    view("detail", ["title" => $book["title"], "book" => $book]);
    exit();
}
if ($action !== "library") {
    http_response_code(405);
    view("message", [
        "title" => "Acción no disponible",
        "message" => "Volvé a la biblioteca para continuar.",
    ]);
    exit();
}
$search = trim((string) ($_GET["q"] ?? ""));
$status = $_GET["status"] ?? "active";
if (!in_array($status, ["active", "inactive", "all"], true)) {
    $status = "active";
}
$sort = $_GET["sort"] ?? "title";
[$books, $count, $page] = $catalog->search(
    $search,
    $status,
    $sort,
    max(1, (int) ($_GET["page"] ?? 1)),
);
view(
    "library",
    compact("books", "count", "page", "search", "status", "sort") + [
        "title" => "Biblioteca de títulos",
    ],
);
