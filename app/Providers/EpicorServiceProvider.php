<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Epicor\Estu\EpicorFormatter;
use App\Services\Epicor\Estu\HeaderLine;
use App\Services\Epicor\Estu\DetailLine;
use App\Services\Epicor\Estu\EstuBuilder;
use App\Services\Epicor\Estu\EstuParser;

class EpicorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Stateless services as singletons
        $this->app->singleton(EpicorFormatter::class);
        $this->app->singleton(HeaderLine::class);
        $this->app->singleton(DetailLine::class);

        // Builder/Parser as transient (avoid cross-request state)
        $this->app->bind(EstuBuilder::class);
        $this->app->bind(EstuParser::class);
    }
}
