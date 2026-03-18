<?php

declare(strict_types=1);

namespace Modules\Foundation\Policies;

use Modules\Foundation\Models\Property;
use Modules\Foundation\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('properties.view');
    }

    public function view(User $user, Property $property): bool
    {
        return $user->can('properties.view');
    }

    public function create(User $user): bool
    {
        return $user->can('properties.create');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->can('properties.edit');
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->can('properties.delete');
    }
}
