<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function (?User $user, string $ability, array $arguments = []): ?bool {
            if (! $user?->hasRole('admin')) {
                return null;
            }

            $target = $arguments[0] ?? null;

            if ($target instanceof User && in_array($ability, ['delete', 'forceDelete'], true)) {
                return null;
            }

            return true;
        });

        Model::preventLazyLoading(! app()->isProduction());
    }
}
