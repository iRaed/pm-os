<?php

declare(strict_types=1);

namespace Modules\PropertyOnboarding\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Foundation\Models\Property;

class PropertyOnboarded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Property $property
    ) {}
}
