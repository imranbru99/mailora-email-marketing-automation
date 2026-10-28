<?php

namespace App\Support\Integrations;

use App\Contracts\AdminTheme;
use ImranDevBD\LaravelAdminPanel\Themes\Palettes;

class AdminPanelAdapter implements AdminTheme
{
    public function primary(): string
    {
        return Palettes::PALETTES['indigo']['light']['primary'];
    }
}
