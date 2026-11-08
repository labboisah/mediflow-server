<?php

namespace Tests\Unit;

use App\Services\UpdateService;
use Tests\TestCase;

class UpdateServiceTest extends TestCase
{
    public function test_update_steps_use_existing_executables_and_artisan_commands(): void
    {
        $steps = app(UpdateService::class)->updateSteps();

        $this->assertCount(4, $steps);
        $this->assertSame(['pull', 'origin', 'main'], array_slice($steps[0]['command'], 1));
        $this->assertStringNotContainsString('C:\\var\\bin\\git', $steps[0]['command'][0]);
        $this->assertSame(['install', '--no-interaction'], array_slice($steps[1]['command'], 1));
        $this->assertSame(['artisan', 'migrate', '--force'], array_slice($steps[2]['command'], 1));
        $this->assertSame(['artisan', 'optimize:clear'], array_slice($steps[3]['command'], 1));
    }

    public function test_update_stops_after_first_failed_step(): void
    {
        $service = app(UpdateService::class);
        $executed = [];

        $result = $service->update(function (array $step) use (&$executed) {
            $executed[] = $step['title'];

            return [
                'output' => ['failed'],
                'return_code' => count($executed) === 2 ? 1 : 0,
            ];
        });

        $this->assertFalse($result['success']);
        $this->assertCount(2, $result['results']);
        $this->assertSame(50, $result['results'][1]['progress']);
        $this->assertSame(['Pulling latest update...', 'Installing composer packages...'], $executed);
    }
}
