<?php

namespace App\Models;

/**
 * A non-persisted stand-in for Importer, backed by an Employer record's
 * data. Lets Auto PDF generation use a second Employer as the importer-slot
 * data source instead of a real Importer record.
 *
 * Extends Importer (rather than being a plain adapter/wrapper class) purely
 * so it satisfies the `?\App\Models\Importer $targetImporter` type-hints in
 * PdfGeneratorService::resolveValue()/resolveOfficeValue() and the
 * signatureGroup-keyed signature/stamp resolution — none of that resolution
 * code needs to change, since from PHP's perspective this IS an Importer.
 *
 * Fields with no Employer equivalent (importerLicenseNo/IssueDate/ExpiryDate)
 * are intentionally left unset and render blank on the PDF.
 */
class EmployerBackedImporter extends Importer
{
    protected ?Employer $sourceEmployer = null;

    public static function fromEmployer(Employer $employer): self
    {
        $instance = new self();
        $instance->sourceEmployer = $employer;

        // Raw original strips the name_suffix accessor appends — same
        // technique PdfGeneratorService::resolveValue() already uses for
        // 'employer.employerNameTh'.
        $instance->importerNameTh = $employer->getRawOriginal('employerNameTh');
        $instance->importerNameEn = $employer->employerNameEn;
        $instance->importerId = $employer->employerId;
        $instance->importerSignerTh = $employer->signerNameTh;
        $instance->importerSignerEn = $employer->signerNameEn;
        $instance->signer_2_name_th = $employer->signer_2_name_th;
        $instance->signer_2_name_en = $employer->signer_2_name_en;
        $instance->signature_1_path = $employer->signature_1_path;
        $instance->signature_2_path = $employer->signature_2_path;
        $instance->importer_stamp_path = $employer->employer_stamp_path;
        $instance->importer_stamp_width_mm = $employer->employer_stamp_width_mm;
        $instance->importer_stamp_height_mm = $employer->employer_stamp_height_mm;

        foreach ([1, 2, 3] as $n) {
            $instance->{"importer_doc_other_{$n}"} = $employer->{"employer_doc_other_{$n}"};
            $instance->{"importer_doc_other_{$n}_desc"} = $employer->{"employer_doc_other_{$n}_desc"};
        }

        return $instance;
    }

    public function addresses()
    {
        // This instance is never saved (no id of its own) — route address
        // lookups to the real, persisted Employer record instead.
        return $this->sourceEmployer ? $this->sourceEmployer->addresses() : parent::addresses();
    }

    /**
     * Single resolution point used by all 3 Auto PDF generation paths
     * (PdfGenerationController::quickPrint(), ::processSynchronously()'s
     * download branch, and PdfGeneratorService::generateForEmployees()'s
     * save_to_slot branch) so the "use another Employer as the importer
     * slot" behavior is defined exactly once.
     */
    public static function resolveTarget(?string $source, $importerId, $employerId): ?Importer
    {
        if ($source === 'employer' && $employerId) {
            $employer = Employer::find($employerId);
            return $employer ? self::fromEmployer($employer) : null;
        }

        return $importerId ? Importer::find($importerId) : null;
    }
}
