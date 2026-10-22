<?php

namespace App\Observers;

use App\Services\RevalidateContent;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

class ContentObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Model $model): void
    {
        app(RevalidateContent::class)->send();
    }

    public function deleted(Model $model): void
    {
        app(RevalidateContent::class)->send();
    }
}
