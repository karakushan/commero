<?php

namespace Commero\Interfaces\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

abstract class AdminResource extends Resource
{
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): \Illuminate\Auth\Access\Response
    {
        static::registerPolicyIfNeeded($record ?? static::getModel());

        return parent::getAuthorizationResponse($action, $record);
    }

    /**
     * @param  Model|class-string<Model>  $subject
     */
    protected static function registerPolicyIfNeeded(Model|string $subject): void
    {
        $modelClass = is_string($subject) ? $subject : $subject::class;

        if (Gate::getPolicyFor($subject)) {
            return;
        }

        $policyClass = 'App\\Policies\\'.class_basename($modelClass).'Policy';

        if (! class_exists($policyClass)) {
            return;
        }

        Gate::policy($modelClass, $policyClass);
    }
}
