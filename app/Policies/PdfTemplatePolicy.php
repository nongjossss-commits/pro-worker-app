<?php

namespace App\Policies;

use App\Models\PdfTemplate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PdfTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-pdf-templates');
    }

    public function view(User $user, PdfTemplate $pdfTemplate): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('staff')) {
            return true;
        }

        if ($pdfTemplate->type === 'global') {
            return true;
        }

        if ($user->hasRole('employer') && $pdfTemplate->employer_id === $user->employer->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-pdf-templates');
    }

    public function update(User $user, PdfTemplate $pdfTemplate): bool
    {
        if ($user->hasPermissionTo('edit-pdf-templates')) {
             if ($user->hasRole('admin') || $user->hasRole('staff')) {
                 return true;
             }
             if ($user->hasRole('employer') && $pdfTemplate->employer_id === $user->employer->id) {
                 return true;
             }
        }
        return false;
    }

    public function delete(User $user, PdfTemplate $pdfTemplate): bool
    {
        if ($user->hasPermissionTo('delete-pdf-templates')) {
             if ($user->hasRole('admin') || $user->hasRole('staff')) {
                 return true;
             }
             if ($user->hasRole('employer') && $pdfTemplate->employer_id === $user->employer->id) {
                 return true;
             }
        }
        return false;
    }
}
