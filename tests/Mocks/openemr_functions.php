<?php

declare(strict_types=1);

// Stubs for OpenEMR global functions used by library classes (xlt, attr, etc.).
// Loaded by the test bootstrap before any test runs.

if (!function_exists('xlt')) {
    function xlt(string $text): string
    {
        return $text;
    }
}

if (!function_exists('attr')) {
    function attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
