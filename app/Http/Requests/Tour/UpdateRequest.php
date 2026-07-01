<?php

namespace App\Http\Requests\Tour;

/**
 * Same shape as StoreRequest — kept as its own class so we can add
 * update-only rules later without touching create.
 */
class UpdateRequest extends StoreRequest
{
}
