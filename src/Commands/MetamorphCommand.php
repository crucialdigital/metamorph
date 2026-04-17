<?php

namespace CrucialDigital\Metamorph\Commands;

use CrucialDigital\Metamorph\Config;
use CrucialDigital\Metamorph\Models\MetamorphForm;
use CrucialDigital\Metamorph\Models\MetamorphFormInput;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class MetamorphCommand extends Command
{
    public $signature = 'metamorph:models {--name=""}';

    public $description = 'Run or update all models';

    public function handle(): int
    {
        $name = $this->option('name');
        $model_dir = Config::dataModelBaseDir();
        $file_path = trim($model_dir . '/' . $name . '.json');

        $models = (isset($name) && $name != '' && file_exists($file_path))
            ? [$file_path]
            : iterator_to_array(
                Finder::create()->files()
                    ->ignoreDotFiles(true)
                    ->in($model_dir)
                    ->sortByName(), false);

        collect($models)->each(function (string $path) {
            $this->warn('Processing ' . $path);
            $content = file_get_contents($path);
            $model = json_decode($content, true);

            if (!isset($model['ref'])) {
                $this->error('Skipping ' . $path . ' — missing "ref" field');
                return;
            }

            // 1. Upsert the MetamorphForm document (without inputs)
            $form_fields = [...$model];
            unset($form_fields['inputs']);
            $form = MetamorphForm::updateOrCreate(['ref' => $model['ref']], $form_fields);

            $raw_inputs = $model['inputs'] ?? [];

            // 2. Sync the EMBEDDED inputs array on the form document (primary read path — zero extra query)
            //    Keep any user-created inputs (metamorph_input !== true) and replace the JSON-synced ones.
            $existing_embedded = collect($form->getAttribute('inputs') ?? []);
            $user_inputs  = $existing_embedded->filter(fn($i) => empty($i['metamorph_input']))->values()->toArray();
            $json_inputs  = collect($raw_inputs)
                ->filter(fn($i) => isset($i['field']))
                ->map(fn($i) => array_merge(['metamorph_input' => true], $i))
                ->values()
                ->toArray();

            // One single write to update the embedded array
            $form->setAttribute('inputs', array_merge($user_inputs, $json_inputs));
            $form->save();

            // 3. Keep the separate MetamorphFormInput collection in sync (used by the form-inputs CRUD API)
            $form->formInputs()->where('metamorph_input', '=', true)->delete();
            foreach ($raw_inputs as $input) {
                if (isset($input['field'])) {
                    MetamorphFormInput::updateOrCreate(
                        ['field' => $input['field'], 'form_id' => $form->id],
                        ['metamorph_input' => true, ...$input]
                    );
                }
            }

            $this->info('Done: ' . $model['ref']);
        });


        $this->alert('All done');

        return self::SUCCESS;
    }
}
