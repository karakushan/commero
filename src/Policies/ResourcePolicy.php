<?php

declare(strict_types=1);

namespace Commero\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

abstract class ResourcePolicy
{
    use HandlesAuthorization;

    abstract protected function resource(): string;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:'.$this->resource());
    }

    public function view(AuthUser $authUser, mixed $record = null): bool
    {
        return $authUser->can('View:'.$this->resource());
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:'.$this->resource());
    }

    public function update(AuthUser $authUser, mixed $record = null): bool
    {
        return $authUser->can('Update:'.$this->resource());
    }

    public function delete(AuthUser $authUser, mixed $record = null): bool
    {
        return $authUser->can('Delete:'.$this->resource());
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:'.$this->resource());
    }

    public function restore(AuthUser $authUser, mixed $record = null): bool
    {
        return $authUser->can('Restore:'.$this->resource());
    }

    public function forceDelete(AuthUser $authUser, mixed $record = null): bool
    {
        return $authUser->can('ForceDelete:'.$this->resource());
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:'.$this->resource());
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:'.$this->resource());
    }

    public function replicate(AuthUser $authUser, mixed $record = null): bool
    {
        return $authUser->can('Replicate:'.$this->resource());
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:'.$this->resource());
    }
}
