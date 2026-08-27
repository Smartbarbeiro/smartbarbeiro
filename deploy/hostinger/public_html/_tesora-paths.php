<?php

declare(strict_types=1);

function tesora_public_path(): string
{
    $dir = realpath(__DIR__) ?: __DIR__;

    return str_replace('\\', '/', $dir);
}

function tesora_laravel_root(): string
{
    $root = dirname(__DIR__).'/laravel';

    if (! is_file($root.'/vendor/autoload.php') && is_file($root.'/laravel/vendor/autoload.php')) {
        return $root.'/laravel';
    }

    return $root;
}

function tesora_env_path(): string
{
    return tesora_laravel_root().'/.env';
}
