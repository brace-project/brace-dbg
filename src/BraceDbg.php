<?php

namespace Brace\Dbg;

class BraceDbg
{


    public static \Closure|bool $logger = false;

    public static function SetupEnvironment(
        callable $logger = null,
    ) {
        if ($logger === null) {
            $logger = fn(...$out) => file_put_contents("php://stderr", "out(" . implode(", ", array_filter($out, fn ($in) => "'" . print_r($in, true) . "'")) . ")\n");
        }
        self::$logger = $logger;
    }

}