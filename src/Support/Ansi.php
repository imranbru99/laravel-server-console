<?php

namespace Imran\WebShell\Support;

class Ansi
{
    public static function strip(string $value): string
    {
        $stripped = preg_replace('/\x1B(?:[@-Z\\-_]|\\[[0-?]*[ -\\/]*[@-~])/u', '', $value);

        return $stripped ?? $value;
    }
}
