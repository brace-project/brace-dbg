<?php

namespace Brace\Dbg;

class BraceDbg
{


    public static function GetType($input) {
        if (is_string($input)) return "'" . $input ."'";
        if ($input === true) return "true";
        if ($input === false) return "false";
        if ($input === null) return "NULL";
        if (is_int($input)) return (string)$input;
        if (is_float($input)) return (string)$input;
        if (is_array($input) || is_object($input)) {
            $ret = print_r ($input, true);
            if (strlen($ret) < 255) {
                return preg_replace("/\n[ ]*/m", " ", $ret);
            } else {
                return "\n" . print_r ($input, true). "";
            }
        }
        return get_debug_type($input);
    }


    public static \Closure|bool $logger = false;

    public static function SetupEnvironment(
        callable $logger = null,
    ) {
        if ($logger === null) {
            $logger = fn(...$out) => file_put_contents("php://stderr", "out(" . implode(", ", array_filter($out, fn ($in) => "'" . print_r($in, true) . "'")) . ")\n");
            $logger = function (...$params) {
                $ret=[];
                foreach ($params as $param) {
                    $ret[] = self::GetType($param);
                }
                file_put_contents("php://stderr", "out(" . implode(", ", $ret) . ");\n");
            };
        }
        self::$logger = $logger;
    }

}
