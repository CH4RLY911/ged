<?php
final class Auth
{
    public function __construct(private AccessRepository $repository) {}

    public function user(): ?array
    {
        if (empty($_SESSION["uid"])) {
            return null;
        }
        $user = $this->repository->findById($_SESSION["uid"]);
        return $user &&
            $user["session_version"] === ($_SESSION["version"] ?? null)
            ? $user
            : null;
    }

    public function login(string $email, string $password, string $ip): ?string
    {
        $key = hash("sha256", $ip);
        if (!$this->repository->reserveAttempt($key, time())) {
            return "Demasiados intentos. Esperá 15 minutos antes de volver a intentar.";
        }
        $user = $this->repository->findByEmail(strtolower(trim($email)));
        $hash = $user
            ? $user["password_hash"]
            : password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        if (!password_verify($password, $hash) || !$user) {
            return "Correo o contraseña incorrectos.";
        }
        $this->repository->clearAttempts($key);
        session_regenerate_id(true);
        $_SESSION["uid"] = $user["id"];
        $_SESSION["version"] = $user["session_version"];
        $_SESSION["last"] = time();
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
        return null;
    }

    public function password(
        array $user,
        string $current,
        string $new,
        string $confirm,
    ): array {
        $errors = [];
        if (!password_verify($current, $user["password_hash"])) {
            $errors["current"] = "La contraseña actual no es correcta.";
        }
        if (strlen($new) < 8 || strlen($new) > 72) {
            $errors["password"] = "Usá entre 8 y 72 caracteres.";
        }
        if ($new !== $confirm) {
            $errors["confirm"] = "Las contraseñas no coinciden.";
        }
        if (!$errors) {
            $this->repository->changePassword(
                $user["id"],
                password_hash($new, PASSWORD_DEFAULT),
            );
            $_SESSION["version"] = $user["session_version"] + 1;
            session_regenerate_id(true);
            $_SESSION["csrf"] = bin2hex(random_bytes(32));
        }
        return $errors;
    }
}
