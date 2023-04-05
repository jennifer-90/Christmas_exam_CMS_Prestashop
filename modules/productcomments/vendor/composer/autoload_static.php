<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita054bffe72b0a2294f96917e79f9106d
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PrestaShop\\Module\\ProductComment\\' => 33,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PrestaShop\\Module\\ProductComment\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'PrestaShop\\Module\\ProductComment\\Entity\\ProductComment' => __DIR__ . '/../..' . '/src/Entity/ProductComment.php',
        'PrestaShop\\Module\\ProductComment\\Entity\\ProductCommentCriterion' => __DIR__ . '/../..' . '/src/Entity/ProductCommentCriterion.php',
        'PrestaShop\\Module\\ProductComment\\Entity\\ProductCommentGrade' => __DIR__ . '/../..' . '/src/Entity/ProductCommentGrade.php',
        'PrestaShop\\Module\\ProductComment\\Entity\\ProductCommentReport' => __DIR__ . '/../..' . '/src/Entity/ProductCommentReport.php',
        'PrestaShop\\Module\\ProductComment\\Entity\\ProductCommentUsefulness' => __DIR__ . '/../..' . '/src/Entity/ProductCommentUsefulness.php',
        'PrestaShop\\Module\\ProductComment\\Repository\\ProductCommentCriterionRepository' => __DIR__ . '/../..' . '/src/Repository/ProductCommentCriterionRepository.php',
        'PrestaShop\\Module\\ProductComment\\Repository\\ProductCommentRepository' => __DIR__ . '/../..' . '/src/Repository/ProductCommentRepository.php',
        'ProductComments' => __DIR__ . '/../..' . '/productcomments.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita054bffe72b0a2294f96917e79f9106d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita054bffe72b0a2294f96917e79f9106d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita054bffe72b0a2294f96917e79f9106d::$classMap;

        }, null, ClassLoader::class);
    }
}
