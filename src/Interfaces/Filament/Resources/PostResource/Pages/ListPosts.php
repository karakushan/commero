<?php

namespace Commero\Interfaces\Filament\Resources\PostResource\Pages;

use Commero\Interfaces\Filament\Resources\PostResource;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;
}
