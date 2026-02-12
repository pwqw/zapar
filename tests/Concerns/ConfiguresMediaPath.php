<?php

namespace Tests\Concerns;

use App\Models\Setting;

trait ConfiguresMediaPath
{
    protected function setUpMediaPath(): void
    {
        Setting::set('media_path', '/var/media');
    }
}
