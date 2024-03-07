<?php

namespace Brace\Dbg;

use Brace\Core\EnvironmentType;

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



        set_exception_handler($handler = function (\Exception|\Error $ex, string $file=null, int $line=null) use ($verbose) {
            $msg = "";

            if ($verbose === true) {
                $previous = $ex;
                $msg .=  "\n\nUncaught " . get_class($ex) . ": " . $ex->getMessage();


                $msg .=  " in '" . ($file ?? $ex->getFile()) . "' on line '" . ($line ?? $previous->getLine()) ."' ";


                while ($previous !== null) {
                    $msg .=  "\n\n---";
                    $msg .=  "\n" . get_class($previous) . " Msg: '{$previous->getMessage()}' Code: {$previous->getCode()}\n";

                    $msg .=  "\nin file: " . ($file ?? $previous->getFile()) . "(". ($line ?? $previous->getLine()).")";
                    $msg .=  "\n\n";
                    $msg .=  $previous->getTraceAsString();
                    $msg .=  "\n===";
                    $previous = $previous->getPrevious();
                }
                $msg .=  "\n\nGenerated by brace/dbg environment - in developer mode";
            } else {
                $msg = "";
            }
            //echo $msg;

            if(php_sapi_name() !== "cli") {
                if (!headers_sent()) {
                    header("Content-Type: text/plain");
                    header("HTTP/1.1 500 Internal Server Error");
                }
                echo("HTTP/1.1 500 Internal Server Error" . $msg);
                ini_set("display_errors", 0);
            } else {
                ini_set("display_errors", 0);
            }

            trigger_error($msg, E_USER_ERROR);
            exit(5);
        });

        set_error_handler(function ($errNo, $errStr, $errFile, $errLine) use ($handler) {
            throw new \Error($errStr . " in $errFile line $errLine", $errNo);   // Throw error to be catched by exception handler (makes Exceptions handlebar)
            
        }, E_NOTICE | E_WARNING | E_ERROR | E_RECOVERABLE_ERROR | E_COMPILE_ERROR | E_COMPILE_WARNING);

        // error_handler won't process compile errors. Handle it here.
        register_shutdown_function(function () use ($handler) {
            $last = error_get_last();
            if ($last === null)
                return;
            if ($last["type"] === E_COMPILE_ERROR)
                $handler(new \Error($last["message"], $last["type"]), $last["file"], $last["line"]);
        });


    }

    private static function detectEnvironment(array $allowHosts) : EnvironmentType {
        if (php_sapi_name() === "cli")
            return EnvironmentType::DEVELOPMENT;
        if ( ! isset ($_SERVER["HTTP_HOST"]))
            return EnvironmentType::PRODUCTION;
        if (array_key_exists($_SERVER["HTTP_HOST"], $allowHosts))
            return $allowHosts[$_SERVER["HTTP_HOST"]];
        if (in_array($_SERVER["HTTP_HOST"], $allowHosts))
            return EnvironmentType::DEVELOPMENT;

        return EnvironmentType::PRODUCTION;
    }


    private static function registerDefaultLogger(string $log_file="php://stderr")
    {
        self::$logger = function (...$params) {
            $ret=[];
            foreach ($params as $param) {
                $ret[] = self::GetType($param);
            }
            file_put_contents("php://stderr", str_replace("\n", "\r\n", "out(" . implode(", ", $ret) . ");\n"));
        };
    }


    public static bool $developmentMode = false;

    public static EnvironmentType $environmentType;

    public static \Closure|bool $logger = false;

    public static function SetupEnvironment(
        bool $autodetect_developement_mode = true,
        array $development_mode_hosts = ["localhost"],
        callable $logger = null,
        string $log_file = "php://stderr"
    ) {

        if ($autodetect_developement_mode) {
            self::$environmentType = self::detectEnvironment($development_mode_hosts);
            self::$developmentMode = self::$environmentType === EnvironmentType::DEVELOPMENT;
        } else {
            self::$environmentType = EnvironmentType::PRODUCTION;
            self::$developmentMode = false;
        }



        if (self::$environmentType !== EnvironmentType::PRODUCTION) {
            self::_setupErrorHandler(true);
            self::registerDefaultLogger($log_file);
        } else {
            self::_setupErrorHandler(false); // Be silent on Production
        }
    }

}
