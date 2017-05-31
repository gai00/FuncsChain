<?php
{
    // for autoloading easily.
    spl_autoload_register(function($class) {
        $path = __DIR__ . "/libs/" . str_replace('\\', '/', $class) . '.php';
        if(file_exists($path)) {
            include_once($path);
        }
    });
}