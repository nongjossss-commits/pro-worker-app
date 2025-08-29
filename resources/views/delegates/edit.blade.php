@extends('layouts.app')

@section('title', 'Edit Delegate')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Edit Delegate</h1>
            <hr>
            <form action="{{ route('delegates.update', $delegate->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateNameTh">Name (TH)</label>
                            <input type="text" name="delegateNameTh" id="delegateNameTh" class="form-control" value="{{ $delegate->delegateNameTh }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateNameEn">Name (EN)</label>
                            <input type="text" name="delegateNameEn" id="delegateNameEn" class="form-control" value="{{ $delegate->delegateNameEn }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateId">National ID</label>
                            <input type="text" name="delegateId" id="delegateId" class="form-control" value="{{ $delegate->delegateId }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateEmployeeId">Employee ID</label>
                            <input type="text" name="delegateEmployeeId" id="delegateEmployeeId" class="form-control" value="{{ $delegate->delegateEmployeeId }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateIssueDate">Issue Date</label>
                            <input type="date" name="delegateIssueDate" id="delegateIssueDate" class="form-control" value="{{ $delegate->delegateIssueDate }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateExpiryDate">Expiry Date</label>
                            <input type="date" name="delegateExpiryDate" id="delegateExpiryDate" class="form-control" value="{{ $delegate->delegateExpiryDate }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegatePhone">Phone</label>
                            <input type="text" name="delegatePhone" id="delegatePhone" class="form-control" value="{{ $delegate->delegatePhone }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="delegateEmail">Email</label>
                            <input type="email" name="delegateEmail" id="delegateEmail" class="form-control" value="{{ $delegate->delegateEmail }}" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="delegatePhoto">Photo</label>
                    <input type="file" name="delegatePhoto" id="delegatePhoto" class="form-control">
                    @if ($delegate->delegatePhoto)
                        <img src="{{ $delegate->delegatePhoto }}" alt="{{ $delegate->delegateNameEn }}" width="100" class="mt-2">
                    @endif
                </div>
                <button type="submit" class="btn btn-primary">Update Delegate</button>
            </form>
        </div>
    </div>
</div>
@endsection
