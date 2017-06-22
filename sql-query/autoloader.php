<?php

spl_autoload_register(function ($className) {
    if (stripos($className, 'MvcBox\\SqlQuery\\') === 0) {
        require_once __DIR__ . '/src/' . str_replace('\\', '/', $className) . '.php';
    }
});
