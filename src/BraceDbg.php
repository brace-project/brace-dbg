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


    private static function _setupErrorHandler(bool $verbose = false)
    {
        set_error_handler(function ($errNo, $errStr, $errFile, $errLine) {
            $msg = "$errStr in $errFile on line $errLine";
            if ($errNo == E_NOTICE || $errNo == E_WARNING) {
                throw new \ErrorException($msg, $errNo);
            } else {
            }
        });

        set_exception_handler(function (\Exception|\Error $ex) use ($verbose) {
            header("Content-Type: text/plain");
            header("HTTP/1.1 500 Internal Server Error");
            echo "HTTP/1.1 500 Internal Server Error";

            if ($verbose === true) {
                $previous = $ex;
                echo "\n\nUncaught " . get_class($ex) . ": " . $ex->getMessage();

                while ($previous !== null) {
                    echo "\n\n---";
                    echo "\n" . get_class($previous) . " Msg: '{$previous->getMessage()}' Code: {$previous->getCode()}\n";

                    echo "\nin file: " . $previous->getFile() . "(". $previous->getLine().")";
                    echo "\n\n";
                    echo $previous->getTraceAsString();
                    echo "\n===";
                    $previous = $previous->getPrevious();
                }
            }
            throw $ex;
        });
    }

    private static function isDevModeAutodetect(array $allowHosts) : bool
    {
        if ( ! isset ($_SERVER["HTTP_HOST"]))
            return false;
        return in_array($_SERVER["HTTP_HOST"], $allowHosts);
    }

    private static function registerDefaultLogger(string $log_file="php://stderr")
    {
        self::$logger = function (...$params) {
            $ret=[];
            foreach ($params as $param) {
                $ret[] = self::GetType($param);
            }
            file_put_contents("php://stderr", "out(" . implode(", ", $ret) . ");\n");
        };
    }


    public static bool $developmentMode = false;

    public static \Closure|bool $logger = false;

    public static function SetupEnvironment(
        bool $autodetect_developement_mode = true,
        array $development_mode_hosts = ["localhost"],
        callable $logger = null,
        string $log_file = "php://stderr"
    ) {

        if ($autodetect_developement_mode) {
            self::$developmentMode = self::isDevModeAutodetect($development_mode_hosts);
        }

        if ($logger === null && self::$developmentMode) {
            self::registerDefaultLogger($log_file);
        }
        out(self::$developmentMode);
        self::_setupErrorHandler(self::$developmentMode);
    }

}
