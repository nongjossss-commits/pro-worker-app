const fs = require('fs');
const file = 'resources/views/production/documents/receipt.blade.php';
let content = fs.readFileSync(file, 'utf8');

// 1. Add $vatEnabled
content = content.replace(
    /\$vatIncluded = \$financial\['vat_included'\] \?\? false;/,
    `$vatEnabled = $financial['vat_enabled'] ?? true;
            $vatIncluded = $financial['vat_included'] ?? false;`
);

// 2. Update logic
const oldLogic = `            // NetBase + VAT = GrandTotal
            $netBase = $grandTotal / (1 + ($vatRate / 100));
            $vatAmount = $grandTotal - $netBase;

            // WHT Note`;

const newLogic = `            // NetBase + VAT = GrandTotal
            if (!$vatEnabled) {
                $netBase = $grandTotal;
                $vatAmount = 0;
            } else {
                $netBase = $grandTotal / (1 + ($vatRate / 100));
                $vatAmount = $grandTotal - $netBase;
            }

            // WHT Note`;
content = content.replace(oldLogic, newLogic);

const oldFactor = `            if ($whtEnabled) {
                // GrossUp Factor = 1 + VAT - WHT
                $factor = 1 + ($vatRate/100) - ($whtRate/100);
                $realBase = $grandTotal / $factor;

                $vatAmount = $realBase * ($vatRate/100);
                $whtAmount = $realBase * ($whtRate/100);
                $fullInvoiceValue = $realBase + $vatAmount;
            } else {
                $realBase = $netBase; // Calculated earlier
                $whtAmount = 0;
                $fullInvoiceValue = $grandTotal;
            }`;

const newFactor = `            if ($whtEnabled) {
                // GrossUp Factor = 1 + VAT - WHT (if VAT is enabled)
                if (!$vatEnabled) {
                    $factor = 1 - ($whtRate/100);
                    $realBase = $grandTotal / $factor;
                    $vatAmount = 0;
                    $whtAmount = $realBase * ($whtRate/100);
                    $fullInvoiceValue = $realBase;
                } else {
                    $factor = 1 + ($vatRate/100) - ($whtRate/100);
                    $realBase = $grandTotal / $factor;
                    $vatAmount = $realBase * ($vatRate/100);
                    $whtAmount = $realBase * ($whtRate/100);
                    $fullInvoiceValue = $realBase + $vatAmount;
                }
            } else {
                $realBase = $netBase; // Calculated earlier
                $whtAmount = 0;
                $fullInvoiceValue = $grandTotal;
            }`;
content = content.replace(oldFactor, newFactor);

const oldView = `        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">Base Amount</td>
            <td class="amount">{{ number_format($realBase, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">VAT {{ $vatRate }}%</td>
            <td class="amount">{{ number_format($vatAmount, 2) }}</td>
        </tr>`;

const newView = `        @if($vatEnabled)
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">Base Amount</td>
            <td class="amount">{{ number_format($realBase, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">VAT {{ $vatRate }}%</td>
            <td class="amount">{{ number_format($vatAmount, 2) }}</td>
        </tr>
        @else
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">Base Amount</td>
            <td class="amount">{{ number_format($realBase, 2) }}</td>
        </tr>
        @endif`;
content = content.replace(oldView, newView);

fs.writeFileSync(file, content);
