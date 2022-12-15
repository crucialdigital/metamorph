<?php

namespace CrucialDigital\Metamorph\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallMetamorph extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metamorph:install {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install metamorph in your application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $route_dir = base_path('/routes/');
        if (!file_exists($route_dir . 'metamorph.php') || $this->option('force')) {
            $content = file_get_contents(__DIR__ . '/stubs/routes.stub');
            file_put_contents($route_dir . 'metamorph.php', $content);
            $this->alert('Route file copied !');
        }
        return self::SUCCESS;
    }
}
