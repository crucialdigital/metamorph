<?php

namespace CrucialDigital\Metamorph;

use CrucialDigital\Metamorph\Commands\InstallMetamorph;
use CrucialDigital\Metamorph\Commands\MakeInheritModel;
use CrucialDigital\Metamorph\Commands\MakeRepository;
use CrucialDigital\Metamorph\Commands\MetamorphCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->hasRoute('metamorph')
            ->hasCommands(
                [
                    MetamorphCommand::class,
                    MakeInheritModel::class,
                    MakeRepository::class,
                    InstallMetamorph::class,
                ]);
    }
}
