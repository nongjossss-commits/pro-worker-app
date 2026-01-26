{{-- resources/views/workflow/partials/order_items.blade.php --}}

@php
    $steps = $order->workType->steps ?? collect();
@endphp

@if($groupedItems->isEmpty())
    <div class="text-center py-4 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        {{ __('No employees in this job yet.') }}
    </div>
@else
    @foreach($groupedItems as $groupName => $items)
        @if($groupName)
            <div class="d-flex align-items-center mb-2 mt-4 px-2">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                    <i class="bi bi-collection-fill" style="font-size: 0.75rem;"></i>
                </div>
                <h6 class="fw-bold text-dark mb-0">{{ $groupName }}</h6>
                <span class="badge bg-secondary bg-opacity-10 text-secondary ms-2 rounded-pill">{{ $items->count() }}</span>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="table-responsive rounded">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th style="width: 60px;">{{ __('Photo') }}</th>
                            <th style="min-width: 200px;">{{ __('Employee') }}</th>
                            <th style="min-width: 150px;">{{ __('Passport / Doc') }}</th>

                            {{-- Dynamic Steps Headers --}}
                            @foreach($steps as $step)
                                <th class="text-center" style="min-width: 100px;">
                                    <span class="d-block small fw-bold text-muted">{{ $step->name }}</span>
                                </th>
                            @endforeach

                            <th class="text-end pe-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $index => $item)
                            <tr>
                                <td class="ps-3 text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    @if($item->employee && $item->employee->employeePhoto)
                                        <img src="{{ asset('storage/'.$item->employee->employeePhoto) }}" class="rounded-circle border" width="40" height="40" style="object-fit: cover;">
                                    @else
                                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($item->employee)
                                        <div class="fw-bold text-dark">{{ $item->employee->employeeNameEn ?? '-' }}</div>
                                        <div class="small text-muted">{{ $item->employee->employeeNameTh ?? '-' }}</div>
                                    @elseif($item->new_employee_data)
                                        <div class="fw-bold text-info">
                                            <i class="bi bi-asterisk me-1"></i>
                                            {{ $item->new_employee_data['name_en'] ?? $item->new_employee_data['name_th'] ?? 'New Employee' }}
                                        </div>
                                        <div class="small text-muted">{{ __('Imported / Manual') }}</div>
                                    @else
                                        <span class="text-danger">Unknown</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->employee)
                                        <div class="small"><i class="bi bi-book me-1"></i>{{ $item->employee->employeePassport ?? '-' }}</div>
                                        <div class="small text-muted">{{ $item->employee->employeeNationality ?? '-' }}</div>
                                    @endif
                                </td>

                                {{-- Steps Checkboxes --}}
                                @foreach($steps as $step)
                                    @php
                                        // Check if completed
                                        // $item->completedWorkTypeSteps is a Collection loaded via relation
                                        $pivot = $item->completedWorkTypeSteps->find($step->id);
                                        $isCompleted = !is_null($pivot);
                                    @endphp
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input cursor-pointer" type="checkbox"
                                                style="transform: scale(1.2);"
                                                @checked($isCompleted)
                                                onchange="toggleWorkStep(this, {{ $item->id }}, {{ $step->id }})">
                                        </div>
                                        @if($isCompleted)
                                            <div class="text-success" style="font-size: 0.65rem;">
                                                {{ \Carbon\Carbon::parse($pivot->pivot->completed_at)->format('d/m') }}
                                            </div>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="text-end pe-3">
                                    <button class="btn btn-sm btn-link text-muted" title="Manage Group" onclick="editItemGroup({{ $item->id }}, '{{ $item->group_name }}')">
                                        <i class="bi bi-tag"></i>
                                    </button>
                                    <button class="btn btn-sm btn-link text-success" title="Complete & Transfer" onclick="finalizeItem({{ $item->id }})">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    @if($item->employee)
                                    <a href="{{ route('employees.show', $item->employee->id) }}" class="btn btn-sm btn-link text-primary" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endif
