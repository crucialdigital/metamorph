<?php

namespace CrucialDigital\Metamorph\Commands;

use CrucialDigital\Metamorph\Models\MetamorphForm;
use CrucialDigital\Metamorph\Models\MetamorphFormInput;
use Illuminate\Console\Command;

/**
 * One-time migration: copies all MetamorphFormInput documents from the
 * separate collection into the embedded inputs array on their parent MetamorphForm.
 *
 * Run this ONCE after upgrading to the version that introduces embedded inputs.
 *
 * Usage:
 *   php artisan metamorph:migrate-inputs
 *   php artisan metamorph:migrate-inputs --dry-run
 */
class MigrateFormInputsCommand extends Command
{
    protected $signature = 'metamorph:migrate-inputs {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Migrate MetamorphFormInput documents into the embedded inputs array on MetamorphForm (run once after upgrade).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY-RUN] No data will be written.');
        }

        $forms = MetamorphForm::all();

        if ($forms->isEmpty()) {
            $this->info('No MetamorphForm documents found. Nothing to migrate.');
            return self::SUCCESS;
        }

        $this->info("Found {$forms->count()} form(s) to process.");
        $bar = $this->output->createProgressBar($forms->count());
        $bar->start();

        $migrated = 0;
        $skipped  = 0;

        foreach ($forms as $form) {
            $inputs = MetamorphFormInput::where('form_id', $form->id)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($i) => $i->toArray())
                ->toArray();

            if (empty($inputs)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (!$dryRun) {
                $form->setAttribute('inputs', $inputs);
                $form->save();
            } else {
                $this->newLine();
                $this->line("  [DRY-RUN] Would embed " . count($inputs) . " input(s) into form [{$form->id}] (ref: {$form->ref})");
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration complete.");
        $this->table(
            ['', 'Count'],
            [
                ['Forms with inputs migrated', $migrated],
                ['Forms skipped (no inputs)', $skipped],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry-run. Re-run without --dry-run to apply changes.');
        } else {
            $this->info('You can now safely run php artisan metamorph:models to re-sync all JSON data models.');
        }

        return self::SUCCESS;
    }
}
