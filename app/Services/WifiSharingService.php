<?php

namespace App\Services;

use Illuminate\Http\Request;

class WifiSharingService
{
    private const HOST = '0.0.0.0';
    private const CHECK_HOST = '127.0.0.1';
    private const PORT = 8080;

    public function status(): array
    {
        $connected = $this->isListening();

        return [
            'connected' => $connected,
            'host' => self::HOST,
            'port' => self::PORT,
            'url' => 'http://' . $this->localIpAddress() . ':' . self::PORT,
        ];
    }

    public function isListening(): bool
    {
        $socket = @fsockopen(self::CHECK_HOST, self::PORT, $errno, $errstr, 0.25);

        if (! $socket) {
            return false;
        }

        fclose($socket);

        return true;
    }

    public function start(): bool
    {
        if ($this->isListening()) {
            return true;
        }

        $php = $this->phpExecutable();
        $artisan = base_path('artisan');

        if (PHP_OS_FAMILY === 'Windows') {
            $command = sprintf(
                'cmd /c start "" /B %s %s serve --port=%d --host=%s',
                $this->windowsQuote($php),
                $this->windowsQuote($artisan),
                self::PORT,
                self::HOST
            );

            pclose(popen($command, 'r'));
        } else {
            $command = sprintf(
                'nohup %s %s serve --port=%d --host=%s > /dev/null 2>&1 &',
                escapeshellarg($php),
                escapeshellarg($artisan),
                self::PORT,
                self::HOST
            );

            exec($command);
        }

        return $this->waitUntilListening();
    }

    public function canManage(Request $request): bool
    {
        return in_array($request->ip(), ['127.0.0.1', '::1', 'localhost'], true);
    }

    private function localIpAddress(): string
    {
        $hostIp = gethostbyname(gethostname());

        return filter_var($hostIp, FILTER_VALIDATE_IP) && ! str_starts_with($hostIp, '127.')
            ? $hostIp
            : '127.0.0.1';
    }

    private function waitUntilListening(): bool
    {
        $deadline = microtime(true) + 6;

        do {
            if ($this->isListening()) {
                return true;
            }

            usleep(250000);
        } while (microtime(true) < $deadline);

        return false;
    }

    private function windowsQuote(string $value): string
    {
        return '"' . str_replace('"', '\"', $value) . '"';
    }

    private function phpExecutable(): string
    {
        $php = PHP_BINARY ?: 'php';

        if (PHP_OS_FAMILY !== 'Windows') {
            return $php;
        }

        $binaryName = strtolower(basename($php));

        if (in_array($binaryName, ['php-cgi.exe', 'php-win.exe'], true)) {
            $candidate = dirname($php) . DIRECTORY_SEPARATOR . 'php.exe';

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $php;
    }
}
