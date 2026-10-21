<?php
final class BookRepository
{
    public function __construct(private PDO $db) {}
    public function find(int $id): ?array
    {
        $q = $this->db->prepare("SELECT * FROM books WHERE id=?");
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }
    public function search(
        string $search,
        string $status,
        string $sort,
        int $page,
    ): array {
        $where = [];
        $args = [];
        if ($status !== "all") {
            $where[] = "active=?";
            $args[] = $status === "inactive" ? 0 : 1;
        }
        if ($search !== "") {
            $where[] =
                "(code LIKE ? ESCAPE '!' OR title LIKE ? ESCAPE '!' OR author LIKE ? ESCAPE '!' OR isbn LIKE ? ESCAPE '!')";
            $term =
                "%" .
                str_replace(["!", "%", "_"], ["!!", "!%", "!_"], $search) .
                "%";
            array_push($args, $term, $term, $term, $term);
        }
        $clause = $where ? " WHERE " . implode(" AND ", $where) : "";
        $q = $this->db->prepare("SELECT COUNT(*) FROM books" . $clause);
        $q->execute($args);
        $count = (int) $q->fetchColumn();
        $order =
            [
                "code" => "code ASC",
                "title" => "title ASC",
                "pvp" => "pvp IS NULL, pvp ASC",
                "pvp_desc" => "pvp IS NULL, pvp DESC",
            ][$sort] ?? "title ASC";
        $page = max(1, min($page, max(1, (int) ceil($count / 20))));
        $q = $this->db->prepare(
            "SELECT * FROM books" .
                $clause .
                " ORDER BY " .
                $order .
                ", id ASC LIMIT 20 OFFSET " .
                ($page - 1) * 20,
        );
        $q->execute($args);
        return [$q->fetchAll(), $count, $page];
    }
    public function save(array $d, ?int $id, ?string $cover): int
    {
        $values = [];
        foreach (Catalog::FIELDS as $f) {
            $values[$f] = $d[$f] === "" ? null : $d[$f];
        }
        $values["cover"] = $cover;
        $values["updated_at"] = gmdate("Y-m-d H:i:s");
        if ($id) {
            $sql =
                "UPDATE books SET " .
                implode(
                    ",",
                    array_map(fn($f) => $f . "=?", array_keys($values)),
                ) .
                " WHERE id=?";
            $values["id"] = $id;
        } else {
            $values["created_at"] = $values["updated_at"];
            $sql =
                "INSERT INTO books (" .
                implode(",", array_keys($values)) .
                ") VALUES (" .
                implode(",", array_fill(0, count($values), "?")) .
                ")";
        }
        $this->db->prepare($sql)->execute(array_values($values));
        return $id ?? (int) $this->db->lastInsertId();
    }
    public function toggle(int $id): void
    {
        $this->db
            ->prepare(
                "UPDATE books SET active=CASE WHEN active=1 THEN 0 ELSE 1 END, updated_at=? WHERE id=?",
            )
            ->execute([gmdate("Y-m-d H:i:s"), $id]);
    }
    public function duplicate(string $field, string $value, ?int $id): bool
    {
        if (!in_array($field, ["code", "isbn"], true)) {
            throw new InvalidArgumentException("Campo no admitido.");
        }
        $q = $this->db->prepare(
            "SELECT id FROM books WHERE " . $field . "=? AND id<>?",
        );
        $q->execute([$value, $id ?? 0]);
        return (bool) $q->fetch();
    }
}
