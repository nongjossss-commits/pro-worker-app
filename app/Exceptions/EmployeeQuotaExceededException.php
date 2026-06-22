<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown by EmployeeQuotaService::ensureCanCreate() when a new employee
 * would push the active count past the Super-Admin-configured cap.
 *
 * The render() method intentionally handles both AJAX (JSON 422) and
 * traditional form submits (redirect back with a quota error bag) so
 * the eight-or-so insert paths don't each need their own try/catch —
 * Laravel's exception handler picks the right response shape per request.
 */
class EmployeeQuotaExceededException extends Exception
{
    public function __construct(
        public readonly int $currentCount,
        public readonly int $maxAllowed,
        public readonly int $attempted = 1,
    ) {
        parent::__construct($this->buildMessage());
    }

    protected function buildMessage(): string
    {
        return __('Employee quota reached (:current / :max). Cannot save :n more employee(s) until an admin raises the cap or an existing employee is removed.', [
            'current' => $this->currentCount,
            'max' => $this->maxAllowed,
            'n' => $this->attempted,
        ]);
    }

    /**
     * Render the exception. JSON responses get a structured payload so
     * the front-end can show a Swal popup; regular form posts get a
     * flash-and-redirect so the receiving page can highlight the error.
     */
    public function render(Request $request)
    {
        // `error` carries the human-friendly message because that's the
        // field every existing AJAX call site (Trash page, import flow,
        // workflow add-employee modal) already surfaces in its Swal.
        // `error_code` lets newer code branch on the machine identifier
        // if it wants to.
        $payload = [
            'error' => $this->getMessage(),
            'error_code' => 'employee_quota_exceeded',
            'message' => $this->getMessage(),
            'current' => $this->currentCount,
            'max' => $this->maxAllowed,
            'attempted' => $this->attempted,
        ];

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json($payload, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return back()
            ->withInput()
            ->with('quota_exceeded', $payload)
            ->withErrors(['quota' => $this->getMessage()]);
    }
}
