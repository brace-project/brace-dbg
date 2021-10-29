<?php


function out(...$data) : void
{
    static $logger = null;
    if ($logger === null)
        $logger = \Brace\Dbg\BraceDbg::$logger;
    if ($logger === false)
        return;
    $logger(...$data);
}


