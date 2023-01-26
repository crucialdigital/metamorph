<?php

namespace CrucialDigital\Metamorph\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metamorph:make-repository {name} {--model=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create repository form metamorph model';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->option('model') == "") {
            $this->error('Model is required');
            return self::FAILURE;
        }
        $dir = config('metamorph.repository_dir', app_path('/Repositories')) . "/";
        if (file_exists($dir . Str::ucfirst($this->argument('name')))) {
            $this->error('The repository ' . $dir . Str::ucfirst($this->argument('name')) . ' already exists !');
            return self::FAILURE;
        }

        $replaces = [
            "{{ class_name }}" => Str::ucfirst($this->argument('name')),
            "{{ model }}" => $this->option('model'),
        ];
        $content = file_get_contents(__DIR__ . '/stubs/repository.stub');

        foreach ($replaces as $search => $replace) {
            $content = Str::replace($search, $replace, $content);
        }
        file_put_contents($dir . Str::ucfirst($this->argument('name')) . '.php', $content);
        $this->info('The repository ' . $dir . Str::ucfirst($this->argument('name')) . ' create successfully !');
        return self::SUCCESS;
    }
}
