<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit601bf8d18c223b3751f0e7e81f7e56f1
{
    public static $files = array (
        'b45b351e6b6f7487d819961fef2fda77' => __DIR__ . '/..' . '/jakeasmith/http_build_url/src/http_build_url.php',
    );

    public static $classMap = array (
        'Contactform' => __DIR__ . '/../..' . '/contactform.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit601bf8d18c223b3751f0e7e81f7e56f1::$classMap;

        }, null, ClassLoader::class);
    }
}
