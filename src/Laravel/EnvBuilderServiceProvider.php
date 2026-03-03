<?php

declare(strict_types=1);

namespace EnvBuilder\Laravel;

use EnvBuilder\Laravel\Commands\BuildEnvCommand;
use Illuminate\Support\ServiceProvider;

final class EnvBuilderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            BuildEnvCommand::class,
        ]);
    }
}

