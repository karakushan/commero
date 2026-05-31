<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\ProductReviewResource\Pages;
use Commero\Models\Product;
use Commero\Models\ProductReview;
use Commero\Support\Locales;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static ?int $navigationSort = 6;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.product_review.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.navigation.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.product_review.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.product_review.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingReviewsCount = ProductReview::query()
            ->roots()
            ->where('status', 'pending')
            ->count();

        return $pendingReviewsCount > 0 ? (string) $pendingReviewsCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->label(__('admin.order.product'))
                ->relationship('product', 'id')
                ->getOptionLabelFromRecordUsing(fn (Product $record): string => $record->translation(app()->getLocale())?->name ?? $record->sku)
                ->searchable()
                ->preload()
                ->required(),
            Placeholder::make('product_edit_link')
                ->label(__('admin.product_review.product_link'))
                ->content(fn (?ProductReview $record): HtmlString => static::getProductEditLink($record)),
            Select::make('user_id')
                ->label(__('admin.order.user'))
                ->relationship('user', 'email')
                ->searchable()
                ->preload(),
            Select::make('locale')
                ->label(__('admin.common.locale'))
                ->options(collect(Locales::supported())->mapWithKeys(fn (string $locale): array => [$locale => __('admin.locale_names.'.$locale)])->all())
                ->default(app()->getLocale())
                ->required(),
            Select::make('author_type')
                ->label(__('admin.product_review.author_type'))
                ->options([
                    'guest' => __('admin.product_review.author_types.guest'),
                    'user' => __('admin.product_review.author_types.user'),
                    'admin' => __('admin.product_review.author_types.admin'),
                ])
                ->default('guest')
                ->required(),
            TextInput::make('display_name')
                ->label(__('admin.product_review.display_name'))
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label(__('admin.order.customer_email'))
                ->email()
                ->maxLength(255),
            TextInput::make('rating')
                ->label(__('admin.product_review.rating'))
                ->numeric()
                ->minValue(1)
                ->maxValue(5)
                ->required(),
            TextInput::make('title')
                ->label(__('admin.common.title'))
                ->maxLength(255),
            Textarea::make('comment')
                ->label(__('admin.order.comment'))
                ->required()
                ->rows(6)
                ->columnSpanFull(),
            Select::make('status')
                ->label(__('admin.common.status'))
                ->options([
                    'pending' => __('admin.product_review.status.pending'),
                    'approved' => __('admin.product_review.status.approved'),
                    'rejected' => __('admin.product_review.status.rejected'),
                ])
                ->default('pending')
                ->required(),
            Repeater::make('images')
                ->label(__('admin.product_review.photos'))
                ->relationship()
                ->schema([
                    FileUpload::make('path')
                        ->label(__('admin.product_review.photo'))
                        ->disk('public')
                        ->directory('reviews/photos')
                        ->visibility('public')
                        ->image()
                        ->required(),
                    TextInput::make('alt')
                        ->label(__('admin.product_review.photo_alt'))
                        ->maxLength(255),
                    TextInput::make('sort')
                        ->label(__('admin.common.sort'))
                        ->numeric()
                        ->default(0)
                        ->required(),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query): Builder {
                $requestedProductId = request()->query('filters.product_id.value')
                    ?? request()->query('tableFilters.product_id.value');

                return $query
                    ->with(['product.translations', 'children', 'images'])
                    ->withCount(['children', 'images'])
                    ->when(filled($requestedProductId), fn (Builder $query): Builder => $query->where('product_id', $requestedProductId));
            })
            ->columns([
                TextColumn::make('id')->label(__('admin.common.id'))->sortable(),
                TextColumn::make('product_name')
                    ->label(__('admin.order.product'))
                    ->state(fn (ProductReview $record): string => $record->product?->translation(app()->getLocale())?->name ?? ($record->product?->sku ?? '-'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = trim($search);

                        return $query->whereHas('product', function (Builder $productQuery) use ($search): void {
                            $productQuery
                                ->where('products.sku', 'like', "%{$search}%")
                                ->orWhere('products.search_text', 'like', "%{$search}%")
                                ->orWhereHas('translations', function (Builder $translations) use ($search): void {
                                    $translations
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('slug', 'like', "%{$search}%");
                                });
                        });
                    }),
                TextColumn::make('display_name')->label(__('admin.product_review.display_name'))->searchable(),
                TextColumn::make('rating')->label(__('admin.product_review.rating'))->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('admin.product_review.status.'.$state)),
                TextColumn::make('created_at')->label(__('admin.product_review.created_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.common.status'))
                    ->options([
                        'pending' => __('admin.product_review.status.pending'),
                        'approved' => __('admin.product_review.status.approved'),
                        'rejected' => __('admin.product_review.status.rejected'),
                    ]),
                SelectFilter::make('product_id')
                    ->label(__('admin.order.product'))
                    ->options(fn (): array => Product::query()
                        ->withTranslationsFor(app()->getLocale())
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(fn (Product $record): array => [
                            (string) $record->id => $record->translation(app()->getLocale())?->name ?? $record->sku ?? (string) $record->id,
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $productId = $data['value'] ?? null;

                        if (blank($productId)) {
                            return $query;
                        }

                        return $query->where('product_id', $productId);
                    }),
                SelectFilter::make('locale')
                    ->label(__('admin.common.locale'))
                    ->options(collect(Locales::supported())->mapWithKeys(fn (string $locale): array => [$locale => __('admin.locale_names.'.$locale)])->all()),
                SelectFilter::make('rating')
                    ->label(__('admin.product_review.rating'))
                    ->options([
                        '5' => '5',
                        '4' => '4',
                        '3' => '3',
                        '2' => '2',
                        '1' => '1',
                    ]),
                SelectFilter::make('author_type')
                    ->label(__('admin.product_review.author_type'))
                    ->options([
                        'guest' => __('admin.product_review.author_types.guest'),
                        'user' => __('admin.product_review.author_types.user'),
                        'admin' => __('admin.product_review.author_types.admin'),
                    ]),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                Action::make('reply')
                    ->label(fn (ProductReview $record): string => $record->children()->exists()
                        ? __('admin.product_review.edit_reply')
                        : __('admin.product_review.add_reply'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->fillForm(function (ProductReview $record): array {
                        $reply = $record->children()->first();

                        return [
                            'display_name' => $reply?->display_name ?? (auth()->user()?->name ?? 'Admin'),
                            'comment' => $reply?->comment,
                            'status' => $reply?->status ?? 'approved',
                        ];
                    })
                    ->schema([
                        TextInput::make('display_name')
                            ->label(__('admin.product_review.display_name'))
                            ->required()
                            ->maxLength(255),
                        Textarea::make('comment')
                            ->label(__('admin.order.comment'))
                            ->required()
                            ->rows(5),
                        Select::make('status')
                            ->label(__('admin.common.status'))
                            ->options([
                                'pending' => __('admin.product_review.status.pending'),
                                'approved' => __('admin.product_review.status.approved'),
                                'rejected' => __('admin.product_review.status.rejected'),
                            ])
                            ->default('approved')
                            ->required(),
                    ])
                    ->action(function (array $data, ProductReview $record): void {
                        $reply = $record->children()->first() ?? new ProductReview;

                        $reply->fill([
                            'product_id' => $record->product_id,
                            'parent_id' => $record->id,
                            'user_id' => auth()->id(),
                            'locale' => $record->locale,
                            'display_name' => $data['display_name'],
                            'author_type' => 'admin',
                            'comment' => $data['comment'],
                            'status' => $data['status'],
                        ]);

                        $reply->save();

                        if ($reply->status === 'approved' && $record->status !== 'approved') {
                            $record->update(['status' => 'approved']);
                        }
                    }),
                Action::make('approve')
                    ->label(__('admin.product_review.actions.approve'))
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(fn (ProductReview $record) => $record->update(['status' => 'approved'])),
                Action::make('reject')
                    ->label(__('admin.product_review.actions.reject'))
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->action(fn (ProductReview $record) => $record->update(['status' => 'rejected'])),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('approveSelected')
                        ->label(__('admin.product_review.actions.approve_selected'))
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['status' => 'approved'])),
                    \Filament\Actions\BulkAction::make('rejectSelected')
                        ->label(__('admin.product_review.actions.reject_selected'))
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['status' => 'rejected'])),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->roots()
            ->with(['product.translations', 'images', 'children'])
            ->withCount(['children', 'images']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductReviews::route('/'),
            'create' => Pages\CreateProductReview::route('/create'),
            'edit' => Pages\EditProductReview::route('/{record}/edit'),
        ];
    }

    protected static function getProductEditLink(?ProductReview $record): HtmlString
    {
        if (! $record?->product) {
            return new HtmlString('&mdash;');
        }

        $label = e($record->product->translation(app()->getLocale())?->name ?? $record->product->sku);
        $url = ProductResource::getUrl('edit', ['record' => $record->product]);

        return new HtmlString(
            sprintf(
                '<a href="%s" class="text-primary-600 underline hover:no-underline">%s</a>',
                e($url),
                $label,
            ),
        );
    }
}
