{{-- 1. ระบุให้ View นี้ใช้ Layout หลักจาก 'layouts.app' --}}
@extends('layouts.app')

{{-- 2. กำหนดว่าเนื้อหาทั้งหมดต่อไปนี้ จะถูกนำไปใส่ในช่อง @yield('content') ของ Layout --}}
@section('content')

{{-- 
    เราใช้ Class ของ Bootstrap เพื่อสร้าง Layout ที่สวยงาม
    และเพื่อให้ Test สามารถหาข้อความเจอได้ง่าย 
--}}
<div class="content-section">
    <div class="container-fluid">
        
        {{-- Header Section --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-shield-lock-fill me-2"></i>
                Manage Roles and Permissions
            </h2>
        </div>

        {{-- Roles Section --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Roles</h5>
            </div>
            <div class="card-body">
                @foreach ($roles as $role)
                    <div class="mb-3 pb-3 border-bottom">
                        <h6 class="fw-bold text-primary">{{ $role->name }}</h6>
                        <div class="ps-3">
                            @forelse ($role->permissions as $permission)
                                <span class="badge bg-secondary fw-normal me-1">{{ $permission->name }}</span>
                            @empty
                                <p class="text-muted small mb-0">No permissions assigned.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- All Permissions Section --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Available Permissions</h5>
            </div>
            <div class="card-body">
                @foreach ($permissions as $permission)
                     <span class="badge bg-info text-dark fw-normal me-1 mb-1">{{ $permission->name }}</span>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection