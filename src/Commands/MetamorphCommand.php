<?php

namespace CrucialDigital\Metamorph\Commands;

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
        $model_dir = config('metamorph.data_model_base_dir');
        $file_path = trim($model_dir . '/' . $name . '.json');

        $models = (isset($name) && $name != '' && file_exists($file_path))
            ? [$file_path]
            : iterator_to_array(
                Finder::create()->files()
                    ->ignoreDotFiles(true)
                    ->in($model_dir)
                    ->sortByName(), false);

        collect($models)->each(function (string $path) {
            $this->warn('Preforming ' . $path);
            $content = file_get_contents($path);
            $model = json_decode($content, true);
            if (isset($model['ref'])) {
                $form = MetamorphForm::updateOrCreate(['ref' => $model['ref']], $model);
                $form->inputs()->where('metamorph_input', true)->delete();
                collect($model['inputs'] ?? [])->each(function ($input) use ($form) {
                    if (isset($input['field'])) {
                        MetamorphFormInput::updateOrCreate([
                            'field' => $input['field'],
                            'form_id' => $form->_id
                        ],[
                            'metamorph_input' => true,
                            ...$input
                        ]);
                    }
                });
            }
            $this->info('Preformed ' . $model['ref']);
        });


        $this->alert('All done');

        return self::SUCCESS;
    }
}
