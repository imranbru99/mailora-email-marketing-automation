<?php

namespace App\Support\Integrations;

use App\Contracts\ReportPdf;
use App\Services\ModuleRegistry;
use ImranDev\UnicodePdf\Facades\UnicodePdf;
use Symfony\Component\HttpFoundation\Response;

class UnicodePdfAdapter implements ReportPdf
{
    /**
     * @param  array<string,mixed>  $data
     */
    public function download(string $view, array $data, string $filename): Response
    {
        abort_unless(app(ModuleRegistry::class)->enabled('exports'), 403);

        return UnicodePdf::locale(app()->getLocale())->loadView($view, $data)->a4()->download($filename);
    }
}
