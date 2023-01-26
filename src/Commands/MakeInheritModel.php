<?php

namespace CrucialDigital\Metamorph\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MakeInheritModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "metamorph:make-model {name} {--S|search=['name']}, {--L|label='name'}, {--R|repository}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model inherit from base model of metamorph';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $dir = config('metamorph.model_dir', app_path('/Models/')) . "/";
        if (file_exists( $dir . Str::ucfirst($this->argument('name')))) {
            $this->error('The model ' . Str::ucfirst($this->argument('name')) . ' already exists !');
            return self::FAILURE;
        }

        $replaces = [
            "{{ class_name }}" => Str::ucfirst($this->argument('name')),
            "{{ search_fields }}" => $this->option('search'),
            "{{ display_label }}" => $this->option('label'),
        ];
        $content = file_get_contents(__DIR__ . '/stubs/model.stub');

        foreach ($replaces as $search => $replace) {
            $content = Str::replace($search, $replace, $content);
        }
        file_put_contents($dir . Str::ucfirst($this->argument('name')) . '.php', $content);
        if($this->option('repository')){
            $repository_name = Str::ucfirst($this->argument('name')) . 'Repository';
            Artisan::call('metamorph:make-repository ' . $repository_name . ' --model='. $this->argument('name'));
        }
        $this->info('The model ' . $dir . Str::ucfirst($this->argument('name')) . ' create successfully !');
        return self::SUCCESS;
    }
}
