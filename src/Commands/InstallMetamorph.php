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
        Artisan::call('metamorph:models');
        return self::SUCCESS;
    }
}
