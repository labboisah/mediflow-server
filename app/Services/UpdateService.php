<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Throwable;

class UpdateService
{
    protected $repo = 'labboisah/fyh';
    protected $branch = 'main';

    public function getRemoteCommit()
    {
        $url = "https://api.github.com/repos/{$this->repo}/commits/{$this->branch}";

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Accept' => 'application/vnd.github+json',
                ])
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        if ($response->successful()) {
            return $response->json()['sha'];
        }

        return null;
    }

    
    public function getLocalCommit()
    {
        $process = new Process([$this->gitExecutable(), 'rev-parse', 'HEAD'], base_path());
        $process->setTimeout(30);
        $process->run();

        return $process->isSuccessful() ? trim($process->getOutput()) : null;

    }

    public function hasUpdate()
    {
        $remote = $this->getRemoteCommit();
        $local = $this->getLocalCommit();

        return filled($remote) && filled($local) && $remote !== $local;
    }

    public function update(?callable $executor = null): array
    {
        $steps = $this->updateSteps();
        $results = [];

        foreach ($steps as $index => $step) {
            $execution = $executor
                ? $executor($step)
                : $this->executeStep($step['command']);

            $results[] = [
                'step' => $index + 1,
                'title' => $step['title'],
                'output' => $execution['output'],
                'return_code' => $execution['return_code'],
                'progress' => intval((($index + 1) / count($steps)) * 100),
            ];

            if ($execution['return_code'] !== 0) {
                return [
                    'success' => false,
                    'results' => $results,
                ];
            }
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    public function updateSteps(): array
    {
        return [
            [
                'title' => 'Pulling latest update...',
                'command' => [$this->gitExecutable(), 'pull', 'origin', $this->branch],
            ],
            [
                'title' => 'Installing composer packages...',
                'command' => [$this->composerExecutable(), 'install', '--no-interaction'],
            ],
            [
                'title' => 'Running database migrations...',
                'command' => [$this->phpExecutable(), 'artisan', 'migrate', '--force'],
            ],
            [
                'title' => 'Clearing optimization cache...',
                'command' => [$this->phpExecutable(), 'artisan', 'optimize:clear'],
            ],
        ];
    }

    private function executeStep(array $command): array
    {
        $process = new Process($command, base_path());
        $process->setTimeout(300);
        $process->run();

        $output = trim($process->getOutput() . PHP_EOL . $process->getErrorOutput());

        return [
            'output' => $output === '' ? [] : preg_split('/\R/', $output),
            'return_code' => $process->getExitCode(),
        ];
    }

    private function gitExecutable(): string
    {
        return $this->firstExistingExecutable([
            'C:\\laragon\\bin\\git\\cmd\\git.exe',
            'C:\\Program Files\\Git\\cmd\\git.exe',
        ], 'git');
    }

    private function composerExecutable(): string
    {
        return $this->firstExistingExecutable([
            'C:\\laragon\\bin\\composer\\composer.bat',
            'C:\\laragon\\bin\\composer\\composer',
        ], PHP_OS_FAMILY === 'Windows' ? 'composer.bat' : 'composer');
    }

    private function phpExecutable(): string
    {
        $php = PHP_BINARY ?: 'php';

        if (PHP_OS_FAMILY === 'Windows' && in_array(strtolower(basename($php)), ['php-cgi.exe', 'php-win.exe'], true)) {
            $candidate = dirname($php) . DIRECTORY_SEPARATOR . 'php.exe';

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $php;
    }

    private function firstExistingExecutable(array $candidates, string $fallback): string
    {
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $fallback;
    }
}
