<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit39c8e93f5c28a88dbecb4bc30e8c2ef4
{
    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'FLL\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'FLL\\' => 
        array (
            0 => __DIR__ . '/../..' . '/FLL',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit39c8e93f5c28a88dbecb4bc30e8c2ef4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit39c8e93f5c28a88dbecb4bc30e8c2ef4::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}