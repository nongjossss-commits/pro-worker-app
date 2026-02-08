<div class="d-flex align-items-center gap-2">
    <label for="perPageSelect" class="small text-muted fw-bold text-nowrap">{{ __('Items per page:') }}</label>
    <select class="form-select form-select-sm shadow-sm border-secondary border-opacity-25" id="perPageSelect" style="width: auto; min-width: 80px;" onchange="window.changePerPage(this.value)">
        <option value="20" {{ (request('per_page') == 20 || !request('per_page')) ? 'selected' : '' }}>20</option>
        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
    </select>
</div>

<script>
    if (!window.changePerPage) {
        window.changePerPage = function(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', value);
            url.searchParams.delete('page'); // Reset to page 1
            window.location.href = url.toString();
        }
    }
</script>
