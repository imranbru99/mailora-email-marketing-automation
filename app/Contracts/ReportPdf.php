<?php

namespace App\Contracts;

use Symfony\Component\HttpFoundation\Response;

interface ReportPdf
{
    /**
     * @param  array<string,mixed>  $data
     */
    public function download(string $view, array $data, string $filename): Response;
}
