<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Support\Filament\CloneAction;
use Commero\Support\Locales;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;

abstract class AdminResource extends Resource
{
    /**
     * Search relationship Select options by the active localized translation.
     *
     * Filament defaults relationship searches to the configured title
     * attribute. Localized entities use `id` as that attribute because their
     * visible label is produced by getOptionLabelFromRecordUsing(), so an
     * unconfigured searchable Select searches IDs instead of translated names.
     */
    protected static function getLocalizedRelationshipSearchResults(Select $component, ?string $search): array
    {
        $relationship = $component->getRelationship();

        if (! $relationship) {
            return [];
        }

        $locale = Locales::resolve(app()->getLocale());
        $likeSearch = '%'.mb_strtolower(trim((string) $search)).'%';
        $relatedModel = $relationship->getRelated();
        $translationRelation = 'translations';
        $translationModel = $relatedModel->{$translationRelation}()->getRelated();
        $translationNameColumn = $translationModel->qualifyColumn('name');

        $query = $relatedModel->newQuery()
            ->with([
                $translationRelation => fn (Relation $translations): Relation => $translations
                    ->whereIn('locale', Locales::preferred($locale)),
            ])
            ->when(trim((string) $search) !== '', function (Builder $query) use ($likeSearch, $translationRelation, $translationNameColumn, $locale): void {
                $query->whereHas($translationRelation, function (Builder $translations) use ($likeSearch, $translationNameColumn, $locale): void {
                    $translations
                        ->whereIn('locale', Locales::preferred($locale))
                        ->whereRaw("LOWER({$translationNameColumn}) LIKE ?", [$likeSearch]);
                });
            })
            ->limit($component->getOptionsLimit());

        return $query->get()
            ->mapWithKeys(fn (Model $record): array => [
                (string) $record->getKey() => $component->hasOptionLabelFromRecordUsingCallback()
                    ? $component->getOptionLabelFromRecord($record)
                    : (string) data_get($record, $component->getRelationshipTitleAttribute() ?? $record->getKeyName()),
            ])
            ->all();
    }

    /**
     * Build localized hierarchical options for translation-backed entities.
     *
     * @param  class-string<Model>  $modelClass
     * @return array<string, string>
     */
    protected static function getLocalizedHierarchySelectOptions(string $modelClass): array
    {
        return $modelClass::query()
            ->withTranslationsFor(app()->getLocale())
            ->orderBy('path')
            ->get()
            ->mapWithKeys(fn (Model $record): array => [
                (string) $record->getKey() => static::formatLocalizedHierarchySelectLabel($record),
            ])
            ->all();
    }

    protected static function formatLocalizedHierarchySelectLabel(Model $record): string
    {
        $name = method_exists($record, 'translation')
            ? $record->translation(app()->getLocale())?->name
            : null;
        $name ??= (string) ($record->getAttribute('path') ?? $record->getKey());
        $indent = str_repeat('— ', max(0, (int) ($record->getAttribute('depth') ?? 0)));

        return $indent.$name;
    }

    public static function getCloneAction(): CloneAction
    {
        $resource = static::class;

        return CloneAction::make()
            ->iconButton()
            ->successRedirectUrl(fn (?Model $replica): ?string => $replica
                ? $resource::getUrl('edit', ['record' => $replica])
                : null);
    }

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

        $policyBaseName = class_basename($modelClass).'Policy';
        $policyClass = is_file(app_path('Policies/'.$policyBaseName.'.php')) && class_exists('App\\Policies\\'.$policyBaseName)
            ? 'App\\Policies\\'.$policyBaseName
            : 'Commero\\Policies\\'.$policyBaseName;

        if (! class_exists($policyClass)) {
            return;
        }

        Gate::policy($modelClass, $policyClass);
    }
}
