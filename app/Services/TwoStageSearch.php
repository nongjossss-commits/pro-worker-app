<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\JobOwner;
use Illuminate\Http\Request;

/**
 * Shared rules for the two-stage search boxes (Employees, Notified-Out,
 * Incomplete Data, Notifications, Pre-Production, Workflow, Registration /
 * Renewal Resolution, Finance):
 *
 *  - Box 1 (employer_filter_name) takes anything — an employee (name,
 *    passport, IDs…), an employer, or a job owner. Picking a suggestion locks
 *    it exactly: employer → employer_filter_id, employee → employee_pick_id,
 *    job owner → job_owner_pick_id. Typed text without a pick is a free-text
 *    term like box 2.
 *  - Box 2 (search) narrows what box 1 found, with the same kinds of text.
 *  - Every free-text term must match (AND). Where the list is grouped by
 *    employer (Workflow, Registration…), a term that matches the employer /
 *    job owner keeps all of that employer's employees; otherwise only the
 *    employees matching the term are shown.
 */
class TwoStageSearch
{
    /** Hidden "exact pick" fields filled by a picked suggestion (public/js/live-search-filter.js). */
    public const PICK_FIELDS = 'employee_pick_id,job_owner_pick_id';

    /** Free-text terms in order: box 1 (unless it locked onto a pick) then box 2. */
    public static function terms(Request $request): array
    {
        $terms = [];
        $box1 = trim((string) $request->input('employer_filter_name'));
        if ($box1 !== '' && ! $request->filled('employer_filter_id') && ! self::hasPick($request)) {
            $terms[] = $box1;
        }
        $box2 = trim((string) $request->input('search'));
        if ($box2 !== '') {
            $terms[] = $box2;
        }

        return array_values(array_unique($terms));
    }

    public static function hasPick(Request $request): bool
    {
        return $request->filled('employee_pick_id') || $request->filled('job_owner_pick_id');
    }

    /**
     * For a live-search preview request: the terms of the OTHER box that
     * should scope these suggestions — box 2's suggestions stay inside box 1's
     * result; box 1's suggestions are never narrowed by box 2.
     * (live-search-filter.js sends `_box` = the asking input's name.)
     */
    public static function previewScopeTerms(Request $request): array
    {
        if ($request->input('_box') !== 'search') {
            return [];
        }
        $box1 = trim((string) $request->input('employer_filter_name'));

        return ($box1 !== '' && ! $request->filled('employer_filter_id') && ! self::hasPick($request)) ? [$box1] : [];
    }

    /** True when this preview request comes from box 1 (or a page with a single box). */
    public static function isBoxOne(Request $request): bool
    {
        return $request->input('_box') !== 'search';
    }

    /** Employee columns that free text matches (names also ignoring spaces). */
    public static function whereEmployee($q, string $term): void
    {
        $like = "%{$term}%";
        $clean = '%' . str_replace(' ', '', $term) . '%';
        $q->where(function ($e) use ($like, $clean) {
            $e->where('employeeNameTh', 'like', $like)
              ->orWhere('employeeNameEn', 'like', $like)
              ->orWhere('name_suffix', 'like', $like)
              ->orWhere('employeePassport', 'like', $like)
              ->orWhere('employeeWorkPermit', 'like', $like)
              ->orWhere('employee_id_number', 'like', $like)
              ->orWhere('name_list_number', 'like', $like)
              ->orWhere('pinkCardNo', 'like', $like)
              ->orWhere('request_number', 'like', $like)
              ->orWhere('registration_request_number', 'like', $like)
              ->orWhere('renewal_request_number', 'like', $like)
              ->orWhere('employer_employee_id', 'like', $like)
              // Request numbers printed on the work cards live outside the
              // employee row: Workflow / Pre-Production cards on the item,
              // Registration / Renewal resolution cards per resolution tab.
              ->orWhereHas('productionItems', fn ($i) => $i->where('request_number', 'like', $like))
              ->orWhereHas('requestNumbers', fn ($r) => $r->where('request_number', 'like', $like))
              ->orWhereRaw("REPLACE(employeeNameTh, ' ', '') LIKE ?", [$clean])
              ->orWhereRaw("REPLACE(employeeNameEn, ' ', '') LIKE ?", [$clean]);
        });
    }

    /** Employer columns + job owner name + address that free text matches. */
    public static function whereEmployer($q, string $term): void
    {
        $like = "%{$term}%";
        $clean = '%' . str_replace(' ', '', $term) . '%';
        $q->where(function ($er) use ($like, $clean, $term) {
            $er->where('employerNameTh', 'like', $like)
               ->orWhere('employerNameEn', 'like', $like)
               ->orWhere('name_suffix', 'like', $like)
               ->orWhere('employerId', 'like', $like)
               ->orWhere('employerTaxId', 'like', $like)
               ->orWhere('outsource_re_code', 'like', $like)
               ->orWhereRaw("REPLACE(employerNameTh, ' ', '') LIKE ?", [$clean])
               ->orWhereRaw("REPLACE(employerNameEn, ' ', '') LIKE ?", [$clean])
               ->orWhereHas('jobOwner', function ($o) use ($like, $clean) {
                   $o->where('name', 'like', $like)->orWhereRaw("REPLACE(name, ' ', '') LIKE ?", [$clean]);
               })
               ->orWhere(fn ($a) => $a->filterByAddress($term));
        });
    }

    /** Employee query: the employee OR their employer / job owner matches. */
    public static function whereEmployeeOrEmployer($q, string $term, bool $includeEmployer = true): void
    {
        $q->where(function ($w) use ($term, $includeEmployer) {
            $w->where(fn ($e) => self::whereEmployee($e, $term));
            if ($includeEmployer) {
                $w->orWhereHas('employer', fn ($er) => self::whereEmployer($er, $term));
            }
        });
    }

    /** Employee query: apply the exact picks (employee / job owner). */
    public static function applyEmployeePicks($q, Request $request): void
    {
        if ($request->filled('employee_pick_id')) {
            $q->where('id', (int) $request->input('employee_pick_id'));
        }
        if ($request->filled('job_owner_pick_id')) {
            $ownerId = (int) $request->input('job_owner_pick_id');
            $q->whereHas('employer', fn ($er) => $er->where('job_owner_id', $ownerId));
        }
    }

    /** Employer query: apply the exact picks (employer has that employee / that job owner). */
    public static function applyEmployerPicks($q, Request $request, ?\Closure $employeeScope = null): void
    {
        if ($request->filled('employee_pick_id')) {
            $id = (int) $request->input('employee_pick_id');
            $q->whereHas('employees', function ($e) use ($id, $employeeScope) {
                $e->where('id', $id);
            });
        }
        if ($request->filled('job_owner_pick_id')) {
            $q->where('job_owner_id', (int) $request->input('job_owner_pick_id'));
        }
    }

    /** Does this employer itself (or its job owner / address) match the term? Cached per request. */
    public static function employerMatches(?int $employerId, string $term): bool
    {
        static $cache = [];
        if (! $employerId) {
            return false;
        }
        $key = $employerId . '|' . $term;
        if (! array_key_exists($key, $cache)) {
            $q = Employer::withTrashed()->whereKey($employerId);
            self::whereEmployer($q, $term);
            $cache[$key] = $q->exists();
        }

        return $cache[$key];
    }

    /**
     * Employees shown inside one employer's card: for each term, keep all of
     * them when the employer matches it, otherwise only matching employees;
     * plus the exact employee pick. $employeeQuery is an Employee query, or a
     * query whose employee lives behind $relation (e.g. ProductionItem 'employee').
     */
    public static function applyInsideEmployer($query, ?int $employerId, Request $request, ?string $relation = null, ?\Closure $extraMatch = null): void
    {
        foreach (self::terms($request) as $term) {
            if (self::employerMatches($employerId, $term)) {
                continue;
            }
            $query->where(function ($w) use ($term, $relation, $extraMatch) {
                if ($relation) {
                    $w->whereHas($relation, fn ($e) => self::whereEmployee($e, $term));
                } else {
                    self::whereEmployee($w, $term);
                }
                if ($extraMatch) {
                    $w->orWhere(fn ($x) => $extraMatch($x, $term));
                }
            });
        }
        if ($request->filled('employee_pick_id')) {
            $query->where($relation ? 'employee_id' : 'id', (int) $request->input('employee_pick_id'));
        }
    }

    /** Box-1 suggestions for job owners (picking one shows every employer under them). */
    public static function jobOwnerSuggestions(string $q, int $limit = 5): array
    {
        $like = "%{$q}%";
        $clean = '%' . str_replace(' ', '', $q) . '%';

        return JobOwner::where(fn ($o) => $o->where('name', 'like', $like)->orWhereRaw("REPLACE(name, ' ', '') LIKE ?", [$clean]))
            ->withCount('employers')
            ->limit($limit)
            ->get()
            ->map(fn ($o) => [
                'label' => $o->name,
                'value' => $o->name,
                'sub_label' => __(':n employers', ['n' => $o->employers_count]),
                'group' => __('Job Owner'),
                'pick' => ['job_owner_pick_id' => $o->id],
            ])->all();
    }

    /**
     * Suggestions for either box of a module.
     *  - $employeeBase(): the module's Employee query (its own scope — tab, status…)
     *  - $employerBase(): the module's Employer query for box 1 (null = all employers)
     * Box 1 gets employers + job owners + employees; box 2 gets employees inside
     * box 1's result (locked employer, picks, or box 1's typed text).
     */
    public static function preview(Request $request, string $q, \Closure $employeeBase, ?\Closure $employerBase = null, int $limit = 12): array
    {
        $results = [];
        $employerFilterId = $request->input('employer_filter_id');
        $boxOne = self::isBoxOne($request);

        if ($boxOne && ! $employerFilterId) {
            $employers = $employerBase ? $employerBase() : Employer::query();
            self::whereEmployer($employers, $q);
            foreach ($employers->limit(5)->get(['id', 'employerNameTh', 'employerNameEn']) as $er) {
                $results[] = [
                    'id' => $er->id,
                    'label' => $er->employerNameTh ?: $er->employerNameEn ?: '-',
                    'sub_label' => ($er->employerNameEn && $er->employerNameEn !== $er->employerNameTh) ? $er->employerNameEn : '',
                    'group' => __('นายจ้าง'),
                ];
            }
            array_push($results, ...self::jobOwnerSuggestions($q, 3));
        }

        $employees = $employeeBase();
        if ($employerFilterId) {
            $employees->where('employer_id', $employerFilterId);
        }
        if (! $boxOne) {
            foreach (self::previewScopeTerms($request) as $term) {
                self::whereEmployeeOrEmployer($employees, $term);
            }
            self::applyEmployeePicks($employees, $request);
        }
        // Employee rows match the employee's own fields; employer / job-owner
        // matches are offered as their own rows above (box 1).
        self::whereEmployee($employees, $q);
        $employees = $employees->with('employer:id,employerNameTh,employerNameEn')
            ->limit(8)
            ->get(['employees.id', 'employeeNameTh', 'employeeNameEn', 'employeeTitleTh', 'employeeTitleEn', 'employeePhoto', 'employeePassport', 'employeeWorkPermit', 'employees.employer_id']);
        foreach ($employees as $emp) {
            $results[] = self::employeeSuggestion($emp);
        }

        return array_slice($results, 0, $limit);
    }

    /** Standard suggestion row for an employee (with the exact pick). */
    public static function employeeSuggestion($emp, bool $withWorkPermit = true): array
    {
        return [
            'label' => trim(($emp->employeeTitleEn ? $emp->employeeTitleEn . ' ' : '') . ($emp->employeeNameEn ?: $emp->employeeNameTh ?: '-')),
            'value' => $emp->employeeNameEn ?: $emp->employeeNameTh ?: '-',
            'sub_label' => trim(implode(' | ', array_filter([
                $emp->employeeNameTh ? trim(($emp->employeeTitleTh ? $emp->employeeTitleTh . ' ' : '') . $emp->employeeNameTh) : null,
                $emp->employer->employerNameTh ?? $emp->employer->employerNameEn ?? null,
                $emp->employeePassport ? "PP: {$emp->employeePassport}" : null,
                ($withWorkPermit && $emp->employeeWorkPermit) ? "WP: {$emp->employeeWorkPermit}" : null,
            ]))),
            'photo' => $emp->photo_url,
            'group' => __('ลูกจ้าง'),
            'pick' => ['employee_pick_id' => $emp->id],
        ];
    }
}
