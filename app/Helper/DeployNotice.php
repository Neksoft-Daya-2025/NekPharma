<?php

namespace App\Helper;

class DeployNotice
{
    private const NOTICE_FILE = 'app/deploy_notice.json';

    public static function path(): string
    {
        return storage_path(self::NOTICE_FILE);
    }

    /**
     * @return array{id: string, message: string, deployed_at: string, commit: string|null}|null
     */
    public static function current(): ?array
    {
        $path = self::path();

        if (!is_readable($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (!is_array($data) || empty($data['id']) || empty($data['message'])) {
            return null;
        }

        return $data;
    }

    public static function publish(string $message, ?string $commit = null): void
    {
        if (!$commit) {
            $commit = trim((string) (@shell_exec('git rev-parse --short HEAD') ?: ''));
        }

        $payload = [
            'id' => $commit ?: ('deploy-' . now()->format('YmdHis')),
            'message' => $message,
            'deployed_at' => now()->toIso8601String(),
            'commit' => $commit ?: null,
        ];

        $dir = dirname(self::path());
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(self::path(), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
