
<!-- Floating Chat Widget -->
<div x-data="chatWidget()"
     x-init="initChat()"
     @keydown.escape.window="isExpanded = false"
     class="chat-wrapper"
     :class="{'chat-hidden': isHidden}"
     style="z-index: 9999;">

    <!-- Hidden Tab (Top Right) -->
    <div x-show="isHidden"
         class="position-fixed top-0 end-0 mt-2 me-2 p-2 bg-primary text-white rounded-start shadow cursor-pointer d-flex align-items-center justify-content-center"
         style="width: 40px; height: 40px; z-index: 10000; transition: all 0.3s ease;"
         @click="toggleHide()"
         title="{{ __('Show Chat') }}">
         <i class="bi bi-chat-dots-fill fs-5"></i>
         <span x-show="unreadCount > 0" class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger" x-text="unreadCount"></span>
    </div>

    <!-- Floating Draggable Button / Expanded Chat Window -->
    <div x-show="!isHidden"
         x-ref="chatWindow"
         class="position-fixed shadow-lg bg-white"
         :class="isExpanded ? 'rounded-3' : 'rounded-circle chat-floating-btn'"
         :style="`top: ${y}px; left: ${x}px; width: ${isExpanded ? '350px' : '60px'}; height: ${isExpanded ? 'auto' : '60px'}; transition: width 0.2s, height 0.2s;`"
         @mousedown="startDrag"
         @touchstart="startDrag">

        <!-- Floating Button Content (Minimized) -->
        <div x-show="!isExpanded"
             class="w-100 h-100 d-flex align-items-center justify-content-center bg-primary text-white rounded-circle position-relative cursor-pointer"
             @click="if(!isDragging) toggleChat()"
             title="{{ __('Open Chat') }}">
            <i class="bi bi-chat-dots-fill fs-4"></i>
            <span x-show="unreadCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" x-text="unreadCount"></span>
        </div>

        <!-- Expanded Chat Window Content -->
        <div x-show="isExpanded" class="d-flex flex-column h-100" style="max-height: 500px;">
            <!-- Header -->
            <div class="chat-header d-flex justify-content-between align-items-center p-2 bg-primary text-white rounded-top-3 cursor-grab"
                 @mousedown="startDrag"
                 @touchstart="startDrag">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span class="fw-bold">{{ __('Team Chat') }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Hide to Top Right Button -->
                    <button type="button" class="btn btn-sm btn-link text-white p-0" @click.stop="toggleHide()" title="{{ __('Minimize to Tab') }}">
                        <i class="bi bi-box-arrow-in-up-right"></i>
                    </button>
                    <!-- Minimize to Floating Button -->
                    <button type="button" class="btn btn-sm btn-link text-white p-0" @click.stop="toggleChat()" title="{{ __('Close') }}">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="chat-body bg-white d-flex flex-column border-start border-end border-bottom rounded-bottom-3" style="height: 400px;">

                <!-- View: Contacts List -->
                <div x-show="view === 'contacts'" class="flex-grow-1 overflow-auto p-0 d-flex flex-column">
                    <div class="p-2 border-bottom bg-light d-flex justify-content-between align-items-center">
                        <small class="text-muted">{{ __('Contacts') }}</small>
                        <button @click="showProfileModal = true" class="btn btn-sm btn-link text-decoration-none p-0">
                            <i class="bi bi-person-circle me-1"></i>{{ __('My Profile') }}
                        </button>
                    </div>

                    <div class="flex-grow-1 overflow-auto">
                        <template x-if="contacts.length === 0">
                            <div class="text-center p-4 text-muted">
                                <small>{{ __('No active users found.') }}</small>
                            </div>
                        </template>

                        <ul class="list-group list-group-flush">
                            <template x-for="user in contacts" :key="user.id">
                                <li class="list-group-item list-group-item-action d-flex align-items-center gap-2 cursor-pointer" @click="selectUser(user)">
                                    <div class="position-relative">
                                        <img :src="user.avatar_url" class="rounded-circle object-fit-cover" width="32" height="32" onerror="this.src='https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF'">
                                        <span x-show="user.is_online" class="position-absolute bottom-0 end-0 p-1 bg-success border border-light rounded-circle"></span>
                                    </div>
                                    <div class="flex-grow-1 lh-sm">
                                        <div class="d-flex justify-content-between">
                                            <strong x-text="user.name" class="fs-6"></strong>
                                            <span x-show="user.unread_count > 0" class="badge bg-danger rounded-pill" x-text="user.unread_count"></span>
                                        </div>
                                        <small class="text-muted d-block text-truncate" style="max-width: 180px;" x-text="user.position_title || '{{ __('No Position') }}'"></small>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <!-- View: Conversation -->
                <div x-show="view === 'conversation'" class="flex-grow-1 d-flex flex-column h-100">
                    <div class="p-2 border-bottom bg-light d-flex align-items-center">
                        <button @click="view = 'contacts'" class="btn btn-sm btn-link text-dark me-2"><i class="bi bi-arrow-left"></i></button>
                        <div class="d-flex align-items-center gap-2" x-show="activeUser">
                            <img :src="activeUser?.avatar_url" class="rounded-circle object-fit-cover" width="24" height="24" onerror="this.src='https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF'">
                            <span class="fw-bold small" x-text="activeUser?.name"></span>
                        </div>
                    </div>

                    <div class="flex-grow-1 overflow-auto p-3 bg-light" id="chatMessagesContainer">
                        <template x-for="msg in messages" :key="msg.id">
                            <div class="mb-2 d-flex" :class="msg.sender_id == currentUserId ? 'justify-content-end' : 'justify-content-start'">
                                <div class="p-2 rounded shadow-sm"
                                     :class="msg.sender_id == currentUserId ? 'bg-primary text-white' : 'bg-white text-dark'"
                                     style="max-width: 80%; word-wrap: break-word;">

                                    <!-- Context / Attachment -->
                                    <template x-if="msg.context_data">
                                        <div class="mb-1 p-1 rounded bg-opacity-10" :class="msg.sender_id == currentUserId ? 'bg-black' : 'bg-secondary'">
                                            <!-- Link Type -->
                                            <template x-if="msg.context_data.type === 'link'">
                                                <a :href="msg.context_data.url" target="_blank" class="d-flex align-items-center text-decoration-none small" :class="msg.sender_id == currentUserId ? 'text-white' : 'text-primary'">
                                                    <i class="bi bi-link-45deg me-1"></i>
                                                    <span x-text="msg.context_data.text || 'Linked Data'"></span>
                                                </a>
                                            </template>
                                            <!-- Image Type -->
                                            <template x-if="msg.context_data.type === 'image'">
                                                <div>
                                                    <a :href="msg.context_data.url" target="_blank">
                                                        <img :src="msg.context_data.url" class="img-fluid rounded mb-1" style="max-height: 150px;">
                                                    </a>
                                                    <div x-text="msg.context_data.name" class="small opacity-75 text-truncate"></div>
                                                </div>
                                            </template>
                                            <!-- File Type -->
                                            <template x-if="msg.context_data.type === 'file'">
                                                <a :href="msg.context_data.url" target="_blank" class="d-flex align-items-center text-decoration-none small" :class="msg.sender_id == currentUserId ? 'text-white' : 'text-primary'">
                                                    <i class="bi bi-file-earmark-text me-1"></i>
                                                    <span x-text="msg.context_data.name || 'File'"></span>
                                                </a>
                                            </template>
                                        </div>
                                    </template>

                                    <div x-text="msg.message"></div>
                                    <small class="d-block text-end opacity-50" style="font-size: 0.65rem;" x-text="formatTime(msg.created_at)"></small>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Input Area -->
                    <div class="p-2 border-top bg-white">
                        <!-- Attachment Preview -->
                        <div x-show="contextToAttach" class="mb-2 p-1 border rounded bg-light d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center small text-primary text-truncate" style="max-width: 90%;">
                                <i class="bi" :class="getAttachmentIcon(contextToAttach.type)"></i>
                                <span class="ms-1 text-truncate" x-text="contextToAttach.name || contextToAttach.text"></span>
                            </div>
                            <button type="button" class="btn-close btn-close-sm" @click="contextToAttach = null"></button>
                        </div>

                        <!-- Progress Bar -->
                        <div x-show="isUploading" class="progress mb-2" style="height: 3px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                        </div>

                        <form @submit.prevent="sendMessage" class="d-flex gap-2 align-items-center">
                             <!-- Dropup for Attachments -->
                            <div class="dropup">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-paperclip"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item small" href="#" @click.prevent="attachContext()"><i class="bi bi-link-45deg me-2"></i>{{ __('Link Current Page') }}</a></li>
                                    <li><a class="dropdown-item small" href="#" @click.prevent="$refs.chatFileInput.click()"><i class="bi bi-file-earmark me-2"></i>{{ __('Upload File/Image') }}</a></li>
                                </ul>
                            </div>
                            <input type="file" x-ref="chatFileInput" class="d-none" @change="handleChatFileUpload">

                            <input type="text" x-model="newMessage" class="form-control form-control-sm" placeholder="{{ __('Type a message...') }}">
                            <button type="submit" class="btn btn-sm btn-primary" :disabled="isUploading || (!newMessage && !contextToAttach)"><i class="bi bi-send-fill"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal (Overlay) -->
    <div x-show="showProfileModal"
         class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black bg-opacity-50"
         style="z-index: 10001;"
         x-transition.opacity
         @click.self="showProfileModal = false">
        <div class="bg-white rounded shadow p-3" style="width: 350px;">
            <h6 class="border-bottom pb-2 mb-3 d-flex justify-content-between">
                {{ __('Edit My Chat Profile') }}
                <button type="button" class="btn-close btn-close-sm" @click="showProfileModal = false"></button>
            </h6>
            <form @submit.prevent="updateProfile">
                <div class="text-center mb-3">
                     <div class="position-relative d-inline-block">
                        <img :src="profilePreviewUrl || profileForm.original_avatar_url" class="rounded-circle object-fit-cover border" width="80" height="80">
                        <label class="position-absolute bottom-0 end-0 bg-light rounded-circle border p-1 cursor-pointer shadow-sm" title="{{ __('Change Avatar') }}">
                            <i class="bi bi-camera-fill text-primary"></i>
                            <input type="file" @change="handleAvatarUpload" class="d-none" accept="image/*">
                        </label>
                     </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small">{{ __('Display Name') }} <span class="text-danger">*</span></label>
                    <input type="text" x-model="profileForm.name" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">{{ __('Position / Title') }}</label>
                    <input type="text" x-model="profileForm.position_title" class="form-control form-control-sm">
                </div>
                <div class="mb-2">
                    <label class="form-label small">{{ __('Bio / Status') }}</label>
                    <textarea x-model="profileForm.bio" class="form-control form-control-sm" rows="2"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" @click="showProfileModal = false" class="btn btn-sm btn-secondary">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
    .chat-floating-btn:hover {
        transform: scale(1.05);
        transition: transform 0.2s;
    }
    .cursor-pointer { cursor: pointer; }
    .cursor-grab { cursor: grab; }
    .cursor-grab:active { cursor: grabbing; }
    .no-caret::after { display: none; }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('chatWidget', () => ({
            isExpanded: false,
            isHidden: false,
            isDragging: false,
            isUploading: false,
            x: window.innerWidth - 80, // Initial X
            y: window.innerHeight - 100, // Initial Y
            offsetX: 0,
            offsetY: 0,

            view: 'contacts',
            contacts: [],
            messages: [],
            currentUserId: {{ auth()->id() }},
            activeUser: null,
            newMessage: '',
            unreadCount: 0,
            pollingInterval: null,
            showProfileModal: false,
            contextToAttach: null,
            lastCheckTime: null,

            profileForm: {
                name: @json(auth()->user()->name),
                position_title: @json(auth()->user()->position_title ?? ''),
                bio: @json(auth()->user()->bio ?? ''),
                avatar: null,
                original_avatar_url: @json(auth()->user()->avatar_url)
            },
            profilePreviewUrl: null,

            initChat() {
                this.fetchContacts();
                // Restore position if saved (Optional feature, omitted for now to keep simple)
                this.pollingInterval = setInterval(() => {
                    this.checkNewMessages();
                }, 5000);

                // Add global mouseup listener to stop dragging
                window.addEventListener('mouseup', () => this.stopDrag());
                window.addEventListener('touchend', () => this.stopDrag());
                window.addEventListener('mousemove', (e) => this.onDrag(e));
                window.addEventListener('touchmove', (e) => this.onDrag(e));
            },

            // --- Drag Logic ---
            startDrag(e) {
                // Only allow dragging from specific areas (button or header)
                // Prevent dragging if clicking buttons inside
                if (e.target.closest('button') || e.target.closest('.btn') || e.target.closest('input')) return;

                this.isDragging = true;
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                this.offsetX = clientX - this.x;
                this.offsetY = clientY - this.y;
            },
            onDrag(e) {
                if (!this.isDragging) return;
                e.preventDefault(); // Prevent scrolling on touch
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;

                this.x = clientX - this.offsetX;
                this.y = clientY - this.offsetY;

                // Boundary checks (keep on screen)
                if (this.x < 0) this.x = 0;
                if (this.y < 0) this.y = 0;
                if (this.x > window.innerWidth - 60) this.x = window.innerWidth - 60;
                if (this.y > window.innerHeight - 60) this.y = window.innerHeight - 60;
            },
            stopDrag() {
                this.isDragging = false;
            },

            // --- Visibility Logic ---
            toggleChat() {
                if (this.isDragging) return; // Prevent toggle after drag
                this.isExpanded = !this.isExpanded;

                // Adjust position if going offscreen when expanding
                if (this.isExpanded) {
                    if (this.x + 350 > window.innerWidth) {
                        this.x = window.innerWidth - 370;
                    }
                    if (this.y + 500 > window.innerHeight) {
                        this.y = window.innerHeight - 520;
                    }
                    this.fetchContacts();
                }

                if (this.isExpanded && this.view === 'conversation' && this.activeUser) {
                     this.$nextTick(() => this.scrollToBottom());
                }
            },
            toggleHide() {
                this.isHidden = !this.isHidden;
                if (this.isHidden) {
                    this.isExpanded = false;
                }
            },

            // --- Data Fetching ---
            fetchContacts() {
                fetch('{{ route('chat.contacts') }}')
                    .then(res => res.json())
                    .then(data => {
                        this.contacts = data;
                        this.unreadCount = data.reduce((acc, user) => acc + user.unread_count, 0);
                    });
            },

            selectUser(user) {
                this.activeUser = user;
                this.view = 'conversation';
                this.messages = [];
                this.fetchMessages(user.id);
            },

            fetchMessages(userId) {
                fetch(`/chat/messages/${userId}`)
                    .then(res => res.json())
                    .then(data => {
                        this.messages = data;
                        this.$nextTick(() => this.scrollToBottom());

                        const contact = this.contacts.find(c => c.id === userId);
                        if (contact) {
                            this.unreadCount -= contact.unread_count;
                            contact.unread_count = 0;
                        }
                    });
            },

            // --- Messaging & Attachments ---
            sendMessage() {
                if ((!this.newMessage.trim() && !this.contextToAttach) || this.isUploading) return;

                const payload = {
                    receiver_id: this.activeUser.id,
                    message: this.newMessage,
                    context_data: this.contextToAttach,
                };

                fetch('{{ route('chat.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(msg => {
                    this.messages.push(msg);
                    this.newMessage = '';
                    this.contextToAttach = null;
                    this.$nextTick(() => this.scrollToBottom());
                });
            },

            handleChatFileUpload(e) {
                const file = e.target.files[0];
                if (!file) return;

                this.isUploading = true;
                const formData = new FormData();
                formData.append('file', file);

                fetch('{{ route('chat.upload') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                })
                .then(res => {
                    if(!res.ok) throw new Error('Upload failed');
                    return res.json();
                })
                .then(data => {
                    this.contextToAttach = {
                        type: data.type, // 'image' or 'file'
                        url: data.url,
                        name: data.name,
                        mime: data.mime
                    };
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Upload failed') }}',
                        text: '{{ __('Could not upload the file.') }}',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    console.error(err);
                })
                .finally(() => {
                    this.isUploading = false;
                    e.target.value = ''; // Reset input
                });
            },

            checkNewMessages() {
                let url = '{{ route('chat.check_new') }}';
                if (this.lastCheckTime) {
                    url += `?last_check=${this.lastCheckTime}`;
                }

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        this.lastCheckTime = data.timestamp;

                        if (data.messages && data.messages.length > 0) {
                            data.messages.forEach(msg => {
                                if (this.activeUser && this.view === 'conversation' && msg.sender_id === this.activeUser.id) {
                                    if (!this.messages.find(m => m.id === msg.id)) {
                                        this.messages.push(msg);
                                        this.$nextTick(() => this.scrollToBottom());
                                    }
                                }
                            });
                            this.fetchContacts();
                        }
                    });
            },

            // --- Profile Management ---
            handleAvatarUpload(e) {
                const file = e.target.files[0];
                if (file) {
                    this.profileForm.avatar = file;
                    this.profilePreviewUrl = URL.createObjectURL(file);
                }
            },

            updateProfile() {
                const formData = new FormData();
                formData.append('name', this.profileForm.name);
                formData.append('position_title', this.profileForm.position_title);
                formData.append('bio', this.profileForm.bio);
                if (this.profileForm.avatar) {
                    formData.append('avatar', this.profileForm.avatar);
                }

                fetch('{{ route('chat.profile.update_info') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.showProfileModal = false;
                        this.profileForm.original_avatar_url = data.user.avatar_url; // Update original
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __('Profile Updated') }}',
                            text: '{{ __('Your chat profile has been updated successfully.') }}',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Update failed') }}',
                        text: '{{ __('An error occurred while updating your profile.') }}',
                    });
                });
            },

            // --- Utilities ---
            formatTime(dateString) {
                const date = new Date(dateString);
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },

            scrollToBottom() {
                const container = document.getElementById('chatMessagesContainer');
                if (container) container.scrollTop = container.scrollHeight;
            },

            attachContext() {
                this.contextToAttach = {
                    url: window.location.href,
                    text: document.title,
                    type: 'link'
                };
            },

            getAttachmentIcon(type) {
                if (type === 'link') return 'bi-link-45deg';
                if (type === 'image') return 'bi-file-image';
                return 'bi-file-earmark';
            }
        }));
    });
</script>
