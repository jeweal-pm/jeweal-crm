<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Enquirable
{
    public function activities(): MorphMany;

    public function assignTo(User $target, User $actor): void;

    public function changeStatus(string $status, User $actor): void;

    public function softDeleteBy(User $actor): void;

    public function restoreBy(User $actor): void;
}
