<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit77e9731797705a0d6d471a9253cd82c6
{
    public static $prefixLengthsPsr4 = array (
        'T' => 
        array (
            'Tereta\\Docker\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Tereta\\Docker\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit77e9731797705a0d6d471a9253cd82c6::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit77e9731797705a0d6d471a9253cd82c6::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit77e9731797705a0d6d471a9253cd82c6::$classMap;

        }, null, ClassLoader::class);
    }
}
