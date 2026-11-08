<?php

namespace App\Console\Commands;

use App\Models\Admission;
use Illuminate\Console\Command;

class ReleaseDischargedAdmissionBeds extends Command
{
    protected $signature = 'beds:release-discharged {--dry-run : Show affected beds without updating them}';

    protected $description = 'Mark beds as vacant for discharged admissions when no other active admission uses the bed';

    public function handle(): int
    {
        $released = 0;
        $skipped = 0;
        $processed = 0;

        Admission::query()
            ->with(['bed', 'discharge'])
            ->whereHas('bed', fn ($query) => $query->where('status', 'occupied'))
            ->where(function ($query) {
                $query->whereRaw('LOWER(status) = ?', ['discharged'])
                    ->orWhereHas('discharge');
            })
            ->chunkById(100, function ($admissions) use (&$processed, &$released, &$skipped) {
                foreach ($admissions as $admission) {
                    $processed++;

                    if ($this->option('dry-run')) {
                        $hasOtherActiveAdmission = Admission::query()
                            ->where('bed_id', $admission->bed_id)
                            ->whereKeyNot($admission->id)
                            ->whereNotIn('status', [
                                'discharged',
                                'Discharged',
                                'closed',
                                'Closed',
                                'absconded',
                                'Absconded',
                                'sama',
                                'SAMA',
                            ])
                            ->exists();

                        if ($hasOtherActiveAdmission) {
                            $skipped++;
                            continue;
                        }

                        $released++;
                        $this->line("Would release bed {$admission->bed?->bed_no} for admission #{$admission->id}");
                        continue;
                    }

                    if ($admission->releaseBedIfNoActiveAdmission()) {
                        $released++;
                    } else {
                        $skipped++;
                    }
                }
            });

        $this->info('Discharged admission bed release scan complete.');
        $this->line("Processed: {$processed}");
        $this->line(($this->option('dry-run') ? 'Would release' : 'Released') . ": {$released}");
        $this->line("Skipped: {$skipped}");

        if ($this->option('dry-run')) {
            $this->info('Dry-run mode: no beds were updated.');
        }

        return Command::SUCCESS;
    }
}
