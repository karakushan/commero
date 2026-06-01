<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\OrderResource\Pages;
use Commero\Models\Currency;
use Commero\Models\Order;
use Commero\Models\OrderItem;
use Commero\Models\OrderStatus;
use Commero\Models\PaymentMethod;
use Commero\Models\Product;
use Commero\Models\ShippingMethod;
use Commero\Models\User;
use Commero\Support\Phone;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class OrderResource extends AdminResource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.order.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.orders');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.order.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.order.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $defaultStatusCode = OrderStatus::query()
            ->where('is_default_for_new_order', true)
            ->value('code');

        if (! $defaultStatusCode) {
            return null;
        }

        $newOrdersCount = Order::query()
            ->where('status', $defaultStatusCode)
            ->count();

        return $newOrdersCount > 0 ? (string) $newOrdersCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('commero::admin.order.order_section'))
                ->schema([
                    TextInput::make('number')->label(__('commero::admin.order.number'))->required()->unique(ignoreRecord: true),
                    Select::make('status')->label(__('commero::admin.common.status'))
                        ->options(fn (): array => static::getOrderStatusOptions())
                        ->default(fn (): string => OrderStatus::query()->where('is_default_for_new_order', true)->value('code') ?? 'new')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Placeholder::make('source')
                        ->label(__('commero::admin.order.source'))
                        ->content(fn (?Order $record): string => static::getOrderSourceLabel((bool) ($record?->is_quick_order ?? false))),
                    TextInput::make('total_amount')->label(__('commero::admin.order.total_amount'))->numeric()->inputMode('decimal')->default(0)->required(),
                    Textarea::make('comment')->label(__('commero::admin.order.comment'))->rows(4)->columnSpanFull(),
                ])
                ->columns(2),
            Section::make(__('commero::admin.order.customer_section'))
                ->schema([
                    TextInput::make('customer_name')->label(__('commero::admin.order.customer_name'))->required(),
                    TextInput::make('customer_phone')
                        ->label(__('commero::admin.order.customer_phone'))
                        ->required()
                        ->dehydrateStateUsing(fn (?string $state): ?string => Phone::normalize($state)),
                    TextInput::make('customer_email')->label(__('commero::admin.order.customer_email'))->email(),
                ])
                ->columns(2),
            Section::make(__('commero::admin.order.user_section'))
                ->schema([
                    Select::make('user_id')
                        ->label(__('commero::admin.order.user'))
                        ->relationship('user', 'email')
                        ->getOptionLabelFromRecordUsing(fn (User $record): string => trim($record->name.' <'.$record->email.'>'))
                        ->searchable(['name', 'email', 'first_name', 'last_name', 'phone'])
                        ->preload(),
                    Placeholder::make('user_profile_link')
                        ->label(__('commero::admin.order.user_profile'))
                        ->content(fn (?Order $record): HtmlString => static::getUserProfileLink($record)),
                ])
                ->columns(2),
            Section::make(__('commero::admin.order.other_recipient_section'))
                ->schema([
                    Toggle::make('has_other_recipient')
                        ->label(__('commero::admin.order.has_other_recipient'))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set): void {
                            if ($state) {
                                return;
                            }

                            $set('recipient_first_name', null);
                            $set('recipient_last_name', null);
                            $set('recipient_phone', null);
                            $set('recipient_email', null);
                        }),
                    TextInput::make('recipient_first_name')
                        ->label(__('commero::admin.order.recipient_first_name'))
                        ->visible(fn (callable $get): bool => (bool) $get('has_other_recipient')),
                    TextInput::make('recipient_last_name')
                        ->label(__('commero::admin.order.recipient_last_name'))
                        ->visible(fn (callable $get): bool => (bool) $get('has_other_recipient')),
                    TextInput::make('recipient_phone')
                        ->label(__('commero::admin.order.recipient_phone'))
                        ->dehydrateStateUsing(fn (?string $state): ?string => Phone::normalize($state))
                        ->visible(fn (callable $get): bool => (bool) $get('has_other_recipient')),
                    TextInput::make('recipient_email')
                        ->label(__('commero::admin.order.recipient_email'))
                        ->email()
                        ->visible(fn (callable $get): bool => (bool) $get('has_other_recipient')),
                ])
                ->columns(2),
            Section::make(__('commero::admin.order.delivery_payment_section'))
                ->schema([
                    Select::make('payment_method_code')
                        ->label(__('commero::admin.order.payment_method_name'))
                        ->options(fn (): array => static::getPaymentMethodOptions())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateHydrated(fn (Select $component, ?string $state, callable $set) => $set('payment_method_name', $state ? static::getPaymentMethodLabel($state) : null))
                        ->afterStateUpdated(fn (?string $state, callable $set) => $set('payment_method_name', $state ? static::getPaymentMethodLabel($state) : null)),
                    Hidden::make('payment_method_name'),
                    Select::make('shipping_method_code')
                        ->label(__('commero::admin.order.shipping_method_name'))
                        ->options(fn (): array => static::getShippingMethodOptions())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateHydrated(fn (Select $component, ?string $state, callable $set) => $set('shipping_method_name', $state ? static::getShippingMethodLabel($state) : null))
                        ->afterStateUpdated(fn (?string $state, callable $set) => $set('shipping_method_name', $state ? static::getShippingMethodLabel($state) : null)),
                    Hidden::make('shipping_method_name'),
                    TextInput::make('delivery_city_name')
                        ->label(__('commero::admin.order.delivery_city_name')),
                    TextInput::make('delivery_city_ref')
                        ->label(__('commero::admin.order.delivery_city_ref')),
                    TextInput::make('delivery_warehouse_name')
                        ->label(__('commero::admin.order.delivery_warehouse_name')),
                    TextInput::make('delivery_warehouse_ref')
                        ->label(__('commero::admin.order.delivery_warehouse_ref')),
                    TextInput::make('delivery_street')
                        ->label(__('commero::admin.order.delivery_street')),
                    TextInput::make('delivery_house')
                        ->label(__('commero::admin.order.delivery_house')),
                    TextInput::make('delivery_apartment')
                        ->label(__('commero::admin.order.delivery_apartment')),
                ])
                ->columns(2),
            Section::make(__('commero::admin.order.items'))
                ->schema([
                    Repeater::make('items')
                        ->label(__('commero::admin.order.items'))
                        ->relationship()
                        ->schema([
                            Placeholder::make('product_thumbnail')
                                ->label(__('commero::admin.order.thumbnail'))
                                ->columnSpan(1)
                                ->content(function (?OrderItem $record): HtmlString {
                                    $path = $record?->product?->primaryImage?->path;

                                    if (! $path) {
                                        return new HtmlString('—');
                                    }

                                    $url = asset('storage/'.$path);

                                    return new HtmlString('<img src="'.$url.'" alt="" style="width: 64px; height: 64px; object-fit: cover; border-radius: 0.5rem;">');
                                }),
                            Placeholder::make('product_sku')
                                ->label(__('commero::admin.common.sku'))
                                ->columnSpan(2)
                                ->content(fn (?OrderItem $record): string => $record?->product_sku ?: ($record?->product?->sku ?? '—')),
                            Placeholder::make('variant_name')
                                ->label(__('commero::admin.order.variant'))
                                ->columnSpan(3)
                                ->content(fn (?OrderItem $record): string => $record?->variant_name ?: '—'),
                            Placeholder::make('product_price')
                                ->label(__('commero::admin.common.price'))
                                ->columnSpan(2)
                                ->content(function (?OrderItem $record): string {
                                    $price = $record?->unit_price ?? $record?->product?->variants->first()?->price;

                                    return $price !== null ? number_format((float) $price, 2, '.', ' ') : '—';
                                }),
                            Placeholder::make('variant_attributes')
                                ->label(__('commero::admin.order.variant_attributes'))
                                ->columnSpan(12)
                                ->content(fn (?OrderItem $record): string => collect($record?->variant_attributes ?? [])
                                    ->pluck('label')
                                    ->filter()
                                    ->implode(', ') ?: '—'),
                            Select::make('product_id')
                                ->label(__('commero::admin.order.product'))
                                ->columnSpan(5)
                                ->relationship(
                                    name: 'product',
                                    titleAttribute: 'id',
                                    modifyQueryUsing: fn (Builder $query): Builder => $query->withTranslationsFor(app()->getLocale()),
                                )
                                ->getOptionLabelFromRecordUsing(
                                    fn (Product $record): string => $record->translation(app()->getLocale())?->name
                                        ?? $record->sku
                                        ?? (string) $record->id,
                                )
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('quantity')
                                ->label(__('commero::admin.order.quantity'))
                                ->columnSpan(2)
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required(),
                        ])
                        ->columns(12)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label(__('commero::admin.order.number'))->searchable(),
                TextColumn::make('customer_name')->label(__('commero::admin.order.customer_name'))->searchable(),
                TextColumn::make('customer_phone')->label(__('commero::admin.order.customer_phone'))->searchable(),
                TextColumn::make('status')->label(__('commero::admin.common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => static::getOrderStatusLabel($state))
                    ->color(fn (string $state): string => static::getOrderStatusBadgeColor($state)),
                TextColumn::make('is_quick_order')
                    ->label(__('commero::admin.order.source'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => static::getOrderSourceLabel($state))
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                TextColumn::make('total_amount')->label(__('commero::admin.order.total_amount'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' '.Currency::getBaseSymbol()),
                TextColumn::make('updated_at')->label(__('commero::admin.common.updated_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('commero::admin.common.status'))
                    ->options(fn (): array => static::getOrderStatusOptions()),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                DeleteBulkAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('number')->label(__('commero::admin.order.number')),
            TextEntry::make('status')->label(__('commero::admin.common.status'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => static::getOrderStatusLabel($state))
                ->color(fn (string $state): string => static::getOrderStatusBadgeColor($state)),
            TextEntry::make('is_quick_order')
                ->label(__('commero::admin.order.source'))
                ->badge()
                ->formatStateUsing(fn (bool $state): string => static::getOrderSourceLabel($state))
                ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
            TextEntry::make('customer_name')->label(__('commero::admin.order.customer_name')),
            TextEntry::make('customer_phone')->label(__('commero::admin.order.customer_phone')),
            TextEntry::make('customer_email')->label(__('commero::admin.order.customer_email')),
            TextEntry::make('user.email')
                ->label(__('commero::admin.order.user'))
                ->default('—')
                ->url(fn (Order $record): ?string => $record->user ? UserResource::getUrl('edit', ['record' => $record->user]) : null),
            TextEntry::make('total_amount')->label(__('commero::admin.order.total_amount'))->numeric(decimalPlaces: 2),
            TextEntry::make('payment_method_name')->label(__('commero::admin.order.payment_method_name')),
            TextEntry::make('payment_method_code')->label(__('commero::admin.order.payment_method_code')),
            TextEntry::make('shipping_method_name')->label(__('commero::admin.order.shipping_method_name')),
            TextEntry::make('shipping_method_code')->label(__('commero::admin.order.shipping_method_code')),
            TextEntry::make('delivery_city_name')->label(__('commero::admin.order.delivery_city_name'))->default('—'),
            TextEntry::make('delivery_city_ref')->label(__('commero::admin.order.delivery_city_ref'))->default('—'),
            TextEntry::make('delivery_warehouse_name')->label(__('commero::admin.order.delivery_warehouse_name'))->default('—'),
            TextEntry::make('delivery_warehouse_ref')->label(__('commero::admin.order.delivery_warehouse_ref'))->default('—'),
            TextEntry::make('delivery_street')->label(__('commero::admin.order.delivery_street'))->default('—'),
            TextEntry::make('delivery_house')->label(__('commero::admin.order.delivery_house'))->default('—'),
            TextEntry::make('delivery_apartment')->label(__('commero::admin.order.delivery_apartment'))->default('—'),
            Section::make(__('commero::admin.order.other_recipient_section'))
                ->schema([
                    TextEntry::make('recipient_first_name')->label(__('commero::admin.order.recipient_first_name'))->default('—'),
                    TextEntry::make('recipient_last_name')->label(__('commero::admin.order.recipient_last_name'))->default('—'),
                    TextEntry::make('recipient_phone')->label(__('commero::admin.order.recipient_phone'))->default('—'),
                    TextEntry::make('recipient_email')->label(__('commero::admin.order.recipient_email'))->default('—'),
                ])
                ->columns(2)
                ->visible(fn (Order $record): bool => $record->has_other_recipient),
            TextEntry::make('comment')->label(__('commero::admin.order.comment'))->columnSpanFull(),
            RepeatableEntry::make('items')
                ->label(__('commero::admin.order.items'))
                ->schema([
                    ImageEntry::make('product.primaryImage.path')
                        ->label(__('commero::admin.order.thumbnail'))
                        ->disk('public')
                        ->square(),
                    TextEntry::make('product.sku')
                        ->label(__('commero::admin.common.sku'))
                        ->state(fn (OrderItem $record): string => $record->product_sku ?: ($record->product?->sku ?? '—')),
                    TextEntry::make('variant_name')
                        ->label(__('commero::admin.order.variant'))
                        ->default('—'),
                    TextEntry::make('product_price')
                        ->label(__('commero::admin.common.price'))
                        ->state(function (OrderItem $record): string {
                            $price = $record->unit_price ?? $record->product?->variants->first()?->price;

                            return $price !== null ? number_format((float) $price, 2, '.', ' ') : '—';
                        }),
                    TextEntry::make('product_id')
                        ->label(__('commero::admin.order.product'))
                        ->state(fn (OrderItem $record): string => $record->product_name
                            ?: $record->product?->translation(app()->getLocale())?->name
                            ?? $record->product?->sku
                            ?? (string) $record->product_id),
                    TextEntry::make('variant_attributes')
                        ->label(__('commero::admin.order.variant_attributes'))
                        ->state(fn (OrderItem $record): string => collect($record->variant_attributes ?? [])
                            ->pluck('label')
                            ->filter()
                            ->implode(', ') ?: '—'),
                    TextEntry::make('quantity')->label(__('commero::admin.order.quantity')),
                ])
                ->columns(6)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'user',
            'items.product' => fn ($query) => $query
                ->withTranslationsFor(app()->getLocale())
                ->with(['primaryImage', 'variants']),
            'items.variant',
        ]);
    }

    protected static function getUserProfileLink(?Order $record): HtmlString
    {
        if (! $record?->user) {
            return new HtmlString('—');
        }

        $url = UserResource::getUrl('edit', ['record' => $record->user]);
        $label = e(trim($record->user->name.' <'.$record->user->email.'>'));

        return new HtmlString('<a href="'.$url.'" class="text-primary-600 underline">'.$label.'</a>');
    }

    protected static function getOrderStatusLabel(string $state): string
    {
        return OrderStatus::query()
            ->withTranslationsFor(app()->getLocale())
            ->where('code', $state)
            ->first()
            ?->name
            ?? $state;
    }

    protected static function getOrderStatusOptions(): array
    {
        return OrderStatus::query()
            ->withTranslationsFor(app()->getLocale())
            ->where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->mapWithKeys(fn (OrderStatus $status): array => [
                $status->code => $status->name,
            ])
            ->all();
    }

    protected static function getPaymentMethodOptions(): array
    {
        return PaymentMethod::query()
            ->withTranslationsFor(app()->getLocale())
            ->where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->mapWithKeys(fn (PaymentMethod $paymentMethod): array => [
                $paymentMethod->code => $paymentMethod->name,
            ])
            ->all();
    }

    protected static function getPaymentMethodLabel(string $code): ?string
    {
        return PaymentMethod::query()
            ->withTranslationsFor(app()->getLocale())
            ->where('code', $code)
            ->first()
            ?->name;
    }

    protected static function getShippingMethodOptions(): array
    {
        return ShippingMethod::query()
            ->withTranslationsFor(app()->getLocale())
            ->where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->mapWithKeys(fn (ShippingMethod $shippingMethod): array => [
                $shippingMethod->code => $shippingMethod->name,
            ])
            ->all();
    }

    protected static function getShippingMethodLabel(string $code): ?string
    {
        return ShippingMethod::query()
            ->withTranslationsFor(app()->getLocale())
            ->where('code', $code)
            ->first()
            ?->name;
    }

    protected static function getOrderStatusBadgeColor(string $state): string
    {
        return match ($state) {
            'new', 'processing', 'awaiting_payment', 'returned' => 'warning',
            'paid', 'shipped' => 'info',
            'delivered', 'completed' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    protected static function getOrderSourceLabel(bool $isQuickOrder): string
    {
        return $isQuickOrder
            ? __('commero::admin.order.source_quick_order')
            : __('commero::admin.order.source_checkout');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
