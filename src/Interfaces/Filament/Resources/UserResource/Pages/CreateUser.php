<?php

namespace Commero\Interfaces\Filament\Resources\UserResource\Pages;

use Commero\Interfaces\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
