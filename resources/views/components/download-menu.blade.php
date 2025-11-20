<!-- resources/views/components/download-menu.blade.php -->
<div class="dropdown ms-3" x-data="downloadMenu()">
    <button class="btn btn-light position-relative" type="button" id="downloadMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-download"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" x-show="activeCount > 0" x-text="activeCount" style="display: none;">
        </span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="downloadMenuButton" style="width: 320px;">
        <li><h6 class="dropdown-header">รายการดาวน์โหลด (Downloads)</h6></li>
        <li><hr class="dropdown-divider"></li>

        <template x-for="job in jobs" :key="job.id">
            <li class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="d-block fw-bold text-truncate" style="max-width: 200px;" x-text="jobName(job)"></span>
                        <small class="text-muted" x-text="job.type === 'zip' ? 'แยกไฟล์ (ZIP)' : 'รวมไฟล์ (PDF)'"></small>
                    </div>

                    <template x-if="job.status === 'pending' || job.status === 'processing'">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </template>

                    <template x-if="job.status === 'completed'">
                        <a :href="'/downloads/' + job.id + '/file'" class="btn btn-sm btn-success" target="_blank">
                            <i class="bi bi-download"></i>
                        </a>
                    </template>

                    <template x-if="job.status === 'failed'">
                        <i class="bi bi-exclamation-circle text-danger" title="Failed"></i>
                    </template>
                </div>
                <div class="progress mt-1" style="height: 3px;" x-show="job.status === 'pending' || job.status === 'processing'">
                     <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                </div>
            </li>
        </template>

        <template x-if="jobs.length === 0">
            <li class="text-center text-muted">ไม่มีรายการดาวน์โหลด</li>
        </template>
    </ul>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('downloadMenu', () => ({
            jobs: [],

            get activeCount() {
                return this.jobs.filter(j => j.status === 'pending' || j.status === 'processing').length;
            },

            init() {
                this.fetchJobs();
                setInterval(() => {
                    if (this.activeCount > 0) {
                        this.fetchJobs();
                    }
                }, 5000); // Poll every 5s if there are active jobs

                // Also poll occasionally even if nothing is active, just in case a new one starts elsewhere
                setInterval(() => {
                     if (this.activeCount === 0) {
                        this.fetchJobs();
                    }
                }, 30000);

                // Listen for global event to refresh immediately
                window.addEventListener('download-started', () => {
                    this.fetchJobs();
                });
            },

            fetchJobs() {
                fetch('{{ route("downloads.index") }}')
                    .then(response => response.json())
                    .then(data => {
                        this.jobs = data;
                    });
            },

            jobName(job) {
                // Format: Download #ID
                return `Download #${job.id} (${new Date(job.created_at).toLocaleTimeString()})`;
            }
        }));
    });
</script>
@endpush
