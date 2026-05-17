<?php

declare(strict_types=1);

if (!function_exists('toast_message')) {
    function toast_message(?string $text, string $icon = 'info'): ?array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        return [
            'icon' => $icon,
            'text' => $text,
        ];
    }
}

if (!function_exists('render_sweetalert_assets')) {
    function render_sweetalert_assets(): void
    {
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>' . PHP_EOL;
    }
}

if (!function_exists('render_toast_script')) {
    function render_toast_script(?array $toast): void
    {
        $payload = json_encode($toast, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo <<<HTML
<script>
const firmapeToast = {$payload};
if (firmapeToast) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: firmapeToast.icon,
        title: firmapeToast.text,
        showConfirmButton: false,
        timer: 3200,
        timerProgressBar: true,
        width: '360px'
    });
}
</script>
HTML;
    }
}
