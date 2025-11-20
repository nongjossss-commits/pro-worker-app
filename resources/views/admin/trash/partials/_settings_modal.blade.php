<!-- Modal for Trash Retention Settings -->
<div class="modal fade" id="trashSettingsModal" tabindex="-1" aria-labelledby="trashSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trashSettingsModalLabel">Trash Retention Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.trash.updateSettings') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info text-sm">
                        Specify how many days soft-deleted items should be kept before being permanently deleted. Leave blank or set to 'forever' to keep items indefinitely.
                    </div>

                    @foreach(['employees', 'employers', 'agents', 'importers', 'delegates', 'addresses'] as $model)
                        <div class="mb-3 row align-items-center">
                            <label for="retention_{{ $model }}" class="col-sm-4 col-form-label text-capitalize">
                                {{ Str::plural(ucfirst($model)) }}
                            </label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control"
                                           id="retention_{{ $model }}"
                                           name="retention_days[{{ $model }}]"
                                           value="{{ $retentionSettings[$model] === 'forever' ? '' : $retentionSettings[$model] }}"
                                           min="1"
                                           placeholder="Forever">
                                    <span class="input-group-text">days</span>
                                </div>
                                <div class="form-text">
                                    Current: <span class="fw-bold">{{ $retentionSettings[$model] === 'forever' || $retentionSettings[$model] === null ? 'Forever' : $retentionSettings[$model] . ' days' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
