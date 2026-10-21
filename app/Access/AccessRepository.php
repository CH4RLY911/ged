<?php
final class AccessRepository
{
    public function __construct(private PDO $db) {}

    public function findById(int $id): ?array
    {
        $query = $this->db->prepare("SELECT * FROM users WHERE id=?");
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $query = $this->db->prepare("SELECT * FROM users WHERE email=?");
        $query->execute([$email]);
        return $query->fetch() ?: null;
    }

    public function reserveAttempt(string $key, int $now): bool
    {
        $this->db->beginTransaction();
        try {
            $this->db
                ->prepare("DELETE FROM login_attempts WHERE started<?")
                ->execute([$now - 900]);
            $query = $this->db->prepare(
                "SELECT * FROM login_attempts WHERE key_hash=?",
            );
            $query->execute([$key]);
            $attempt = $query->fetch();
            if ($attempt && $attempt["attempts"] >= 5) {
                $this->db->commit();
                return false;
            }
            if ($attempt) {
                $this->db
                    ->prepare(
                        "UPDATE login_attempts SET attempts=attempts+1 WHERE key_hash=?",
                    )
                    ->execute([$key]);
            } else {
                $this->db
                    ->prepare("INSERT INTO login_attempts VALUES (?,1,?)")
                    ->execute([$key, $now]);
            }
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function clearAttempts(string $key): void
    {
        $this->db
            ->prepare("DELETE FROM login_attempts WHERE key_hash=?")
            ->execute([$key]);
    }

    public function changePassword(int $id, string $hash): void
    {
        $this->db
            ->prepare(
                "UPDATE users SET password_hash=?, session_version=session_version+1 WHERE id=?",
            )
            ->execute([$hash, $id]);
    }
}
