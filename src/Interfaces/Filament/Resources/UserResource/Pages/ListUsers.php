<?php

namespace Commero\Interfaces\Filament\Resources\UserResource\Pages;

use Commero\Interfaces\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
