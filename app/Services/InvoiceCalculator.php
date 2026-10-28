<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceCalculator
{
    public function total(Invoice $invoice): int
    {
        $subtotal = 0;
        foreach ($invoice->items ?? [] as $item) {
            $subtotal += (int) $item['quantity'] * (int) $item['unit_price_minor'];
        }

        return $subtotal + (int) round($subtotal * $invoice->tax_percent / 100, 0, PHP_ROUND_HALF_UP);
    }
}
