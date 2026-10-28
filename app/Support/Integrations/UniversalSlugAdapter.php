<?php

namespace App\Support\Integrations;

use App\Contracts\Slugger;
use ImranDev\UniversalSlug\UniversalSlug;

class UniversalSlugAdapter implements Slugger
{
    public function slug(string $text): string
    {
        return UniversalSlug::of($text)->onlySlug()->toString();
    }
}
