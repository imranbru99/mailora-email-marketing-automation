<?php

namespace App\Contracts;

interface Slugger
{
    public function slug(string $text): string;
}
