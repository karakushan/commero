<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\MarketingLeadResource\Pages;
use Commero\Models\MarketingLead;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MarketingLeadResource extends Resource
{
    protected static ?string $model = MarketingLead::class;

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.marketing_lead.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.navigation.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.marketing_lead.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.marketing_lead.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MarketingLead::query()->where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')
                ->label(__('admin.common.status'))
                ->options(static::getStatusOptions())
                ->required(),
            DateTimePicker::make('processed_at')
                ->label(__('admin.marketing_lead.processed_at'))
                ->seconds(false),
            Textarea::make('internal_note')
                ->label(__('admin.marketing_lead.internal_note'))
                ->rows(6)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('product.translations'))
            ->columns([
                TextColumn::make('id')->label(__('admin.common.id'))->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.marketing_lead.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('admin.marketing_lead.types.'.$state)),
                TextColumn::make('subject')
                    ->label(__('admin.marketing_lead.subject'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('name')
                    ->label(__('admin.marketing_lead.name'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('admin.common.phone'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('admin.common.email'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('admin.marketing_lead.statuses.'.$state)),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.marketing_lead.type'))
                    ->options(static::getTypeOptions()),
                SelectFilter::make('status')
                    ->label(__('admin.common.status'))
                    ->options(static::getStatusOptions()),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('type')
                ->label(__('admin.marketing_lead.type'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => __('admin.marketing_lead.types.'.$state)),
            TextEntry::make('status')
                ->label(__('admin.common.status'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => __('admin.marketing_lead.statuses.'.$state)),
            TextEntry::make('subject')->label(__('admin.marketing_lead.subject'))->default('—'),
            TextEntry::make('name')->label(__('admin.marketing_lead.name'))->default('—'),
            TextEntry::make('phone')->label(__('admin.common.phone'))->default('—'),
            TextEntry::make('email')->label(__('admin.common.email'))->default('—'),
            TextEntry::make('locale')->label(__('admin.common.locale'))->default('—'),
            TextEntry::make('source_url')
                ->label(__('admin.marketing_lead.source_url'))
                ->default('—')
                ->url(fn (MarketingLead $record): ?string => $record->source_url),
            TextEntry::make('product_name')
                ->label(__('admin.order.product'))
                ->state(fn (MarketingLead $record): string => $record->product?->translation(app()->getLocale())?->name
                    ?? $record->product?->sku
                    ?? '—'),
            TextEntry::make('message')
                ->label(__('admin.common.message'))
                ->default('—')
                ->columnSpanFull(),
            Section::make(__('admin.marketing_lead.form_data'))
                ->schema([
                    TextEntry::make('form_data')
                        ->hiddenLabel()
                        ->state(fn (MarketingLead $record): string => static::formatJson($record->form_data))
                        ->html()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make(__('admin.marketing_lead.client_meta'))
                ->schema([
                    TextEntry::make('client_meta')
                        ->hiddenLabel()
                        ->state(fn (MarketingLead $record): string => static::formatJson($record->client_meta))
                        ->html()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
            TextEntry::make('internal_note')
                ->label(__('admin.marketing_lead.internal_note'))
                ->default('—')
                ->columnSpanFull(),
            TextEntry::make('processed_at')
                ->label(__('admin.marketing_lead.processed_at'))
                ->dateTime(),
            TextEntry::make('created_at')
                ->label(__('admin.common.created_at'))
                ->dateTime(),
            TextEntry::make('updated_at')
                ->label(__('admin.common.updated_at'))
                ->dateTime(),
        ])->columns(2);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketingLeads::route('/'),
            'view' => Pages\ViewMarketingLead::route('/{record}'),
            'edit' => Pages\EditMarketingLead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('product.translations');
    }

    /**
     * @return array<string, string>
     */
    protected static function getTypeOptions(): array
    {
        return [
            'callback' => __('admin.marketing_lead.types.callback'),
            'contact_form' => __('admin.marketing_lead.types.contact_form'),
            'product_waitlist' => __('admin.marketing_lead.types.product_waitlist'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function getStatusOptions(): array
    {
        return [
            'new' => __('admin.marketing_lead.statuses.new'),
            'processed' => __('admin.marketing_lead.statuses.processed'),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    protected static function formatJson(?array $data): string
    {
        if ($data === null || $data === []) {
            return '—';
        }

        $json = (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return '<pre style="white-space: pre-wrap; font-size: 12px;">'.e($json).'</pre>';
    }
}
