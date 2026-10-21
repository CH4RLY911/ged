<?php
final class Catalog
{
    public const FIELDS = [
        "code",
        "title",
        "subtitle",
        "author",
        "isbn",
        "publisher",
        "pvp",
        "edition",
        "year",
        "format",
        "width",
        "height",
        "pages",
        "synopsis",
        "notes",
    ];
    public function __construct(private BookRepository $repository) {}

    public function find(int $id): ?array
    {
        return $this->repository->find($id);
    }
    public function search(
        string $q,
        string $status,
        string $sort,
        int $page,
    ): array {
        return $this->repository->search($q, $status, $sort, $page);
    }
    public function save(array $d, ?int $id, ?string $cover): int
    {
        return $this->repository->save($d, $id, $cover);
    }
    public function toggle(int $id): void
    {
        $this->repository->toggle($id);
    }
    public function validate(array $input, ?int $id): array
    {
        $d = [];
        $errors = [];
        foreach (self::FIELDS as $field) {
            $d[$field] = trim((string) ($input[$field] ?? ""));
        }
        $d["code"] = strtoupper($d["code"]);
        if (!preg_match('/^[A-Z0-9_-]{1,40}$/D', $d["code"])) {
            $errors["code"] =
                "Ingresá un código de hasta 40 letras, números, guiones o guiones bajos.";
        }
        if ($d["title"] === "" || mb_strlen($d["title"]) > 500) {
            $errors["title"] = "Ingresá un título de hasta 500 caracteres.";
        }
        $d["isbn"] = str_replace(["-", " "], "", strtoupper($d["isbn"]));
        if ($d["isbn"] !== "" && !$this->validIsbn($d["isbn"])) {
            $errors["isbn"] =
                "Ingresá un ISBN-10 o ISBN-13 válido, con su dígito de control.";
        }
        foreach (["code", "isbn"] as $field) {
            if ($d[$field] !== "") {
                if ($this->repository->duplicate($field, $d[$field], $id)) {
                    $errors[$field] =
                        "Ya existe un título con este " .
                        ($field === "code" ? "código." : "ISBN.");
                }
            }
        }
        foreach (
            [
                "pvp" => [1, 999999999999],
                "pages" => [1, 100000],
                "year" => [1000, 2100],
            ]
            as $f => $range
        ) {
            if (
                $d[$f] !== "" &&
                (!ctype_digit($d[$f]) ||
                    strlen($d[$f]) > 12 ||
                    (int) $d[$f] < $range[0] ||
                    (int) $d[$f] > $range[1])
            ) {
                $errors[$f] =
                    "Ingresá un entero entre " .
                    $range[0] .
                    " y " .
                    $range[1] .
                    ".";
            }
        }
        foreach (["width", "height"] as $f) {
            $d[$f] = str_replace(",", ".", $d[$f]);
            if (
                $d[$f] !== "" &&
                (!preg_match('/^\d{1,3}(\.\d{1,2})?$/D', $d[$f]) ||
                    (float) $d[$f] <= 0 ||
                    (float) $d[$f] > 300)
            ) {
                $errors[$f] =
                    "Ingresá una medida mayor que 0 y hasta 300 cm (hasta dos decimales).";
            }
        }
        foreach ($d as $f => $v) {
            if (strlen($v) > 50000) {
                $errors[$f] = "El contenido es demasiado largo.";
            }
        }
        return [$d, $errors];
    }
    private function validIsbn(string $s): bool
    {
        if (preg_match('/^\d{9}[\dX]$/D', $s)) {
            $sum = 0;
            for ($i = 0; $i < 10; $i++) {
                $sum += (10 - $i) * ($s[$i] === "X" ? 10 : (int) $s[$i]);
            }
            return $sum % 11 === 0;
        }
        if (preg_match('/^97[89]\d{10}$/D', $s)) {
            $sum = 0;
            for ($i = 0; $i < 13; $i++) {
                $sum += (int) $s[$i] * ($i % 2 ? 3 : 1);
            }
            return $sum % 10 === 0;
        }
        return false;
    }
}
