<div x-data="searchableSelect({
    name: '{{ $name }}',
    placeholder: '{{ $placeholder ?? 'Type to search...' }}',
    apiUrl: '{{ $apiUrl }}',
    initialValue: '{{ $initialValue ?? '' }}',
    initialText: '{{ $initialText ?? '' }}',
    required: {{ $required ?? 'false' }}
})" class="position-relative search-select-container">
    <input type="hidden" :name="name" :value="value" x-ref="hiddenInput" @change="dispatchChange">

    <div class="input-group">
        <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text"
               class="form-control border-start-0"
               :placeholder="placeholder"
               x-model="searchText"
               @input.debounce.300ms="search()"
               @focus="open = true"
               @click.away="open = false"
               @keydown.escape="open = false"
               :required="required && !value"
               autocomplete="off">
        <button class="btn btn-outline-secondary" type="button" x-show="value" @click="clear()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="dropdown-menu w-100 show" x-show="open && (results.length > 0 || isLoading)" style="position: absolute; top: 100%; left: 0; z-index: 1050; max-height: 250px; overflow-y: auto;">
        <div x-show="isLoading" class="dropdown-item text-muted disabled">Loading...</div>
        <template x-for="item in results" :key="item.id">
            <a href="#" class="dropdown-item" @click.prevent="select(item)">
                <div class="fw-bold" x-text="item.employerNameTh || item.name || '-'"></div>
                <div class="small text-muted">
                    <span x-text="item.employerNameEn || ''"></span>
                    <span x-show="item.employerNameEn"> - </span>
                    <span x-text="item.employerId || item.id"></span>
                </div>
            </a>
        </template>
    </div>
</div>

@once
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('searchableSelect', (config) => ({
            name: config.name,
            value: config.initialValue,
            searchText: config.initialText,
            placeholder: config.placeholder,
            apiUrl: config.apiUrl,
            required: config.required,

            open: false,
            isLoading: false,
            results: [],

            init() {
                // If we have an initial value but no text, try to fetch it?
                // For now assuming controller passes both if needed.
            },

            search() {
                if (!this.searchText || this.searchText.length < 2) {
                    this.results = [];
                    return;
                }

                this.isLoading = true;
                this.open = true;

                fetch(`${this.apiUrl}?query=${encodeURIComponent(this.searchText)}`)
                    .then(response => response.json())
                    .then(data => {
                        this.results = data; // Assuming API returns array of objects
                        this.isLoading = false;
                    })
                    .catch(() => {
                        this.results = [];
                        this.isLoading = false;
                    });
            },

            select(item) {
                this.value = item.id;
                this.searchText = item.employerNameTh || item.name || item.employerNameEn; // Prioritize TH name
                this.open = false;
                this.results = [];
                this.dispatchChange();

                // Dispatch specific event for external listeners (e.g., loading full details)
                this.$dispatch('item-selected', { item: item, name: this.name });
            },

            clear() {
                this.value = '';
                this.searchText = '';
                this.results = [];
                this.open = false;
                this.dispatchChange();
                this.$dispatch('item-cleared', { name: this.name });
            },

            dispatchChange() {
                // Manually trigger change event on the hidden input for native form validation/listeners
                this.$nextTick(() => {
                    const event = new Event('change', { bubbles: true });
                    this.$refs.hiddenInput.dispatchEvent(event);
                });
            }
        }));
    });
</script>
@endonce
