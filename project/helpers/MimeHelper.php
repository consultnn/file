<?php

namespace helpers;


class MimeHelper
{
    private static $list;
    private const MIME_KEEP_EXTENSION = [
        'application/zip', 'application/gzip', 'application/xml', 'text/plain'
    ];

    public static function extension(string $mime, string $path): string
    {
        $sourceExtension = pathinfo($path, PATHINFO_EXTENSION);

        if (substr_count($mime, '/') === 2) {
            // баг в PHP 7.3.2 - 7.4.5+
            $mime = substr($mime, 0, strlen($mime) / 2);
        }

        if (in_array($mime, self::MIME_KEEP_EXTENSION, true)) {
            return pathinfo($path, PATHINFO_EXTENSION);
        }

        $list = self::list();
        $validExtensions = $list[$mime] ?? [];
        if (!in_array($sourceExtension, $validExtensions, true)) {
            return current($validExtensions) ?: 'bin';
        }

        return $sourceExtension;
    }

    private static function list(): array
    {
        if (is_array(self::$list)) {
            return self::$list;
        }

        $source = dirname(__DIR__)  . '/settings/mime-types.php';
        $cache = RUNTIME_DIR  . '/mime-types.cache.php';

        if (!file_exists($cache)
            || (filemtime($cache) < filemtime($source))
        ) {
            self::build($source, $cache);
        }

        self::$list = require $cache;
        return self::$list;
    }

    private static function build(string $source, string $cache)
    {
        $list = require $source;
        $optimized = [];
        foreach ($list as $extension => $mime) {
            $optimized[$mime][] = $extension;
        }
        file_put_contents($cache, '<?php return ' . var_export($optimized, true) . ';');
    }

}