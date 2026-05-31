<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\UserResource\Pages;
use Commero\Models\User;
use Commero\Support\Phone;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.user.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.access');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.user.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.user.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('first_name')->label(__('commero::admin.resources.user.first_name'))->required()->maxLength(255),
            TextInput::make('last_name')->label(__('commero::admin.resources.user.last_name'))->maxLength(255),
            TextInput::make('phone')
                ->label(__('commero::admin.resources.user.phone'))
                ->maxLength(255)
                ->dehydrateStateUsing(fn (?string $state): ?string => Phone::normalize($state)),
            TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')->label(__('commero::admin.resources.user.password'))
                ->password()
                ->required(fn (string $context): bool => $context === 'create')
                ->dehydrated(fn ($state) => filled($state))
                ->maxLength(255),
            Select::make('roles')
                ->label(__('commero::admin.resources.user.roles'))
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('commero::admin.common.id'))->sortable(),
                TextColumn::make('name')->label(__('commero::admin.common.name'))->searchable(),
                TextColumn::make('phone')->label(__('commero::admin.resources.user.phone'))->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('roles.name')
                    ->label(__('commero::admin.resources.user.roles'))
                    ->badge(),
                TextColumn::make('email_verified_at')->label(__('commero::admin.resources.user.email_verified_at'))->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
