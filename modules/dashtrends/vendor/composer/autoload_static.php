<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf6ee855ea218dd5b6625a60d4fa14373
{
    public static $classMap = array (
        'dashtrends' => __DIR__ . '/../..' . '/dashtrends.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInitf6ee855ea218dd5b6625a60d4fa14373::$classMap;

        }, null, ClassLoader::class);
    }
}
