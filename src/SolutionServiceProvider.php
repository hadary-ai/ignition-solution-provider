<?php

namespace Hadary\IgnitionSolutionProvider;

use Facade\IgnitionContracts\SolutionProviderRepository;
use Illuminate\Support\ServiceProvider;

class SolutionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->make(SolutionProviderRepository::class)->registerSolutionProvider(SolutionProvider::class);
    }
}