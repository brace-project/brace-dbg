<?php


function out(...$data) : void
{
    static $logger = null;
    if ($logger === false)
        return;
    if ($logger === null)
        $logger = \Brace\Dbg\BraceDbg::$logger;
    $logger(...$data);
}


