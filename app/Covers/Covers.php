<?php
final class Covers
{
    public static function directory(): string
    {
        global $config;
        return $config["covers"] ?? ROOT . "/storage/covers";
    }
    public static function upload(array $file): string
    {
        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(
                "No se pudo subir la tapa. El máximo permitido es 5 MB.",
            );
        }
        if ($file["size"] > 5 * 1024 * 1024 || $file["size"] === 0) {
            throw new InvalidArgumentException(
                "La tapa debe pesar entre 1 byte y 5 MB.",
            );
        }
        $info = @getimagesize($file["tmp_name"]);
        $mime = new finfo(FILEINFO_MIME_TYPE)->file($file["tmp_name"]);
        $types = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp",
        ];
        if (
            !$info ||
            !isset($types[$mime]) ||
            $info["mime"] !== $mime ||
            $info[0] > 10000 ||
            $info[1] > 10000 ||
            $info[0] * $info[1] > 40000000
        ) {
            throw new InvalidArgumentException(
                "Usá una imagen JPEG, PNG o WebP válida, de hasta 10.000 px por lado y 40 megapíxeles.",
            );
        }
        if (!is_dir(self::directory())) {
            mkdir(self::directory(), 0700, true);
        }
        $name = bin2hex(random_bytes(20)) . "." . $types[$mime];
        if (
            !move_uploaded_file(
                $file["tmp_name"],
                self::directory() . "/" . $name,
            )
        ) {
            throw new RuntimeException("No se pudo guardar la tapa.");
        }
        chmod(self::directory() . "/" . $name, 0600);
        return $name;
    }
    public static function remove(?string $name): void
    {
        if (
            $name &&
            preg_match('/^[a-f0-9]{40}\.(jpg|png|webp)$/D', $name) &&
            is_file(self::directory() . "/" . $name)
        ) {
            unlink(self::directory() . "/" . $name);
        }
    }
}
