const fs = require('fs');
const file = 'resources/views/production/documents/invoice.blade.php';
let content = fs.readFileSync(file, 'utf8');

// 1. Add $vatEnabled
content = content.replace(
    /\$vatIncluded = \$financial\['vat_included'\] \?\? false;/,
    `$vatEnabled = $financial['vat_enabled'] ?? true;
            $vatIncluded = $financial['vat_included'] ?? false;`
);

// 2. Update logic
const oldLogic = `            // 1. Calculate VAT & Net Base
            if ($vatIncluded) {
                // GrandTotal is Inc VAT
                $netBase = $grandTotal / (1 + ($vatRate / 100));
                $vatAmount = $grandTotal - $netBase;
            } else {
                // GrandTotal is Gross (Net + VAT) if entered that way?
                // Wait, if !vatIncluded, usually "Fixed Total" input is Ex-VAT.
                // But "Transaction Amount" input by user - is it Inc or Ex?
                // The JS logic: "Base (Ex) -> +VAT -> Total (Inc)".
                // And Transaction Amount is usually derived from Total (Inc).
                // So Transaction Amount is ALWAYS Inclusive of VAT in the DB logic we wrote.
                // JS: \`totalAmount\` (Inc VAT) -> scheduledAmount (Inc VAT).
                // So here, regardless of \`vatIncluded\` setting, \`$grandTotal\` IS INCLUSIVE OF VAT.

                // So logic is SAME:
                $netBase = $grandTotal / (1 + ($vatRate / 100));
                $vatAmount = $grandTotal - $netBase;
            }`;

const newLogic = `            // 1. Calculate VAT & Net Base
            if (!$vatEnabled) {
                $netBase = $grandTotal;
                $vatAmount = 0;
            } else {
                if ($vatIncluded) {
                    // GrandTotal is Inc VAT
                    $netBase = $grandTotal / (1 + ($vatRate / 100));
                    $vatAmount = $grandTotal - $netBase;
                } else {
                    // JS logic ensures total amount is always inclusive of VAT during entry
                    $netBase = $grandTotal / (1 + ($vatRate / 100));
                    $vatAmount = $grandTotal - $netBase;
                }
            }`;

content = content.replace(oldLogic, newLogic);

// 3. Update view
const oldSubtotal = `        <!-- Net Before VAT -->
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">Net Amount</td>
            <td class="amount">{{ number_format($netBase, 2) }}</td>
        </tr>

        <!-- VAT -->
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">VAT {{ $vatRate }}%</td>
            <td class="amount">{{ number_format($vatAmount, 2) }}</td>
        </tr>`;

const newSubtotal = `        <!-- Net Before VAT -->
        @if($vatEnabled)
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">Net Amount</td>
            <td class="amount">{{ number_format($netBase, 2) }}</td>
        </tr>

        <!-- VAT -->
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">VAT {{ $vatRate }}%</td>
            <td class="amount">{{ number_format($vatAmount, 2) }}</td>
        </tr>
        @endif`;

content = content.replace(oldSubtotal, newSubtotal);

fs.writeFileSync(file, content);
