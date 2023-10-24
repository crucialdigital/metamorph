<?php

namespace CrucialDigital\Metamorph\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeDataModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metamorph:make-data-model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create data model metamorph model';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->argument('name') == "") {
            $this->error('Name is required');
            return self::FAILURE;
        }
        $dir = config('metamorph.data_model_base_dir', database_path('/models')) . "/";
        if (file_exists($dir . Str::slug($this->argument('name')). '.json')) {
            $this->error('The data model ' . $dir . Str::ucfirst($this->argument('name')) . ' already exists !');
            return self::FAILURE;
        }

        $replaces = [
            "{{ model_name }}" => Str::ucfirst($this->argument('name')),
            "{{ model_name_lower }}" => Str::slug($this->argument('name')),
        ];
        $content = file_get_contents(__DIR__ . '/stubs/data_model.stub');

        foreach ($replaces as $search => $replace) {
            $content = Str::replace($search, $replace, $content);
        }
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        file_put_contents($dir . Str::slug($this->argument('name')) . '.json', $content);
        $this->info('The repository ' . $dir . Str::ucfirst($this->argument('name')) . ' create successfully !');
        return self::SUCCESS;
    }
}
