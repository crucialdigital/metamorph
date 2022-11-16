<?php

namespace CrucialDigital\Metamorph;

use CrucialDigital\Metamorph\Commands\MakeInheritModel;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CrucialDigital\Metamorph\Commands\MetamorphCommand;

class MetamorphServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('metamorph')
            ->hasConfigFile()
            ->hasRoute('api')
            ->hasCommands([MetamorphCommand::class, MakeInheritModel::class]);
    }
}
