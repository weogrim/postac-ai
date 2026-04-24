<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Intervention\Image\Image;
use Laravel\Cashier\Cashier;
use Plank\Mediable\Facades\ImageManipulator;
use Plank\Mediable\ImageManipulation;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Cashier::ignoreRoutes();
    }

    public function boot(): void
    {
        ImageManipulator::defineVariant(
            'square',
            ImageManipulation::make(function (Image $image): void {
                $image->cover(512, 512);
            })->outputWebpFormat()->setOutputQuality(85),
        );

        ImageManipulator::defineVariant(
            'thumb',
            ImageManipulation::make(function (Image $image): void {
                $image->cover(96, 96);
            })->outputWebpFormat()->setOutputQuality(80),
        );
    }
}
