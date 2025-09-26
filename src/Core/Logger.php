<?php

namespace Vleap\Relay\Core;

class Logger
{
    private static bool $enabled = true;

    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    public static function error(string $message, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';

        error_log("[{$timestamp}] ERROR: {$message}{$contextStr}");
    }

    public static function info(string $message, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';

        error_log("[{$timestamp}] INFO: {$message}{$contextStr}");
    }

    public static function debug(string $message, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';

        error_log("[{$timestamp}] DEBUG: {$message}{$contextStr}");
    }
}
