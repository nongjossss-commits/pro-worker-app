
<!-- Floating Chat Widget -->
<div x-data="chatWidget()"
     x-init="initChat()"
     @click.away="isExpanded = false"
     :class="isExpanded ? 'chat-expanded' : 'chat-minimized'"
     class="chat-widget shadow-lg border rounded-3">

    <!-- Minimized / Header -->
    <div class="chat-header d-flex justify-content-between align-items-center p-2"
         :class="{'bg-primary text-white rounded-3': !isExpanded, 'bg-primary text-white rounded-top-3': isExpanded}"
         @click="toggleChat()">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-chat-dots-fill"></i>
            <span x-show="isExpanded || unreadCount > 0" class="fw-bold">
                {{ __('Team Chat') }}
                <span x-show="unreadCount > 0" class="badge bg-danger rounded-pill ms-1" x-text="unreadCount"></span>
            </span>
        </div>
        <div x-show="isExpanded">
            <i class="bi bi-chevron-down"></i>
        </div>
    </div>

    <!-- Expanded Body -->
    <div x-show="isExpanded" class="chat-body bg-white d-flex flex-column" style="height: 400px; width: 350px; display: none;">

        <!-- View: Contacts List -->
        <div x-show="view === 'contacts'" class="flex-grow-1 overflow-auto p-0">
            <div class="p-2 border-bottom bg-light d-flex justify-content-between align-items-center">
                <small class="text-muted">{{ __('Contacts') }}</small>
                <button @click="showProfileModal = true" class="btn btn-sm btn-link text-decoration-none p-0">
                    <i class="bi bi-person-circle me-1"></i>{{ __('My Profile') }}
                </button>
            </div>

            <template x-if="contacts.length === 0">
                <div class="text-center p-4 text-muted">
                    <small>{{ __('No active users found.') }}</small>
                </div>
            </template>

            <ul class="list-group list-group-flush">
                <template x-for="user in contacts" :key="user.id">
                    <li class="list-group-item list-group-item-action d-flex align-items-center gap-2 cursor-pointer" @click="selectUser(user)">
                        <div class="position-relative">
                            <img :src="user.avatar_url" class="rounded-circle" width="32" height="32">
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

        <!-- View: Conversation -->
        <div x-show="view === 'conversation'" class="flex-grow-1 d-flex flex-column h-100">
            <div class="p-2 border-bottom bg-light d-flex align-items-center">
                <button @click="view = 'contacts'" class="btn btn-sm btn-link text-dark me-2"><i class="bi bi-arrow-left"></i></button>
                <div class="d-flex align-items-center gap-2" x-show="activeUser">
                    <img :src="activeUser?.avatar_url" class="rounded-circle" width="24" height="24">
                    <span class="fw-bold small" x-text="activeUser?.name"></span>
                </div>
            </div>

            <div class="flex-grow-1 overflow-auto p-3 bg-light" id="chatMessagesContainer">
                <template x-for="msg in messages" :key="msg.id">
                    <div class="mb-2 d-flex" :class="msg.sender_id == currentUserId ? 'justify-content-end' : 'justify-content-start'">
                        <div class="p-2 rounded shadow-sm"
                             :class="msg.sender_id == currentUserId ? 'bg-primary text-white' : 'bg-white text-dark'"
                             style="max-width: 80%; word-wrap: break-word;">

                            <!-- Context / Tag -->
                            <template x-if="msg.context_data">
                                <div class="mb-1 p-1 rounded bg-opacity-10" :class="msg.sender_id == currentUserId ? 'bg-black' : 'bg-secondary'">
                                    <a :href="msg.context_data.url" class="d-flex align-items-center text-decoration-none small" :class="msg.sender_id == currentUserId ? 'text-white' : 'text-primary'">
                                        <i class="bi bi-link-45deg me-1"></i>
                                        <span x-text="msg.context_data.text || 'Linked Data'"></span>
                                    </a>
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
                <!-- Context Attachment Preview -->
                <div x-show="contextToAttach" class="mb-2 p-1 border rounded bg-light d-flex justify-content-between align-items-center">
                    <small class="text-primary"><i class="bi bi-link-45deg"></i> {{ __('Attaching current page') }}</small>
                    <button type="button" class="btn-close btn-close-sm" @click="contextToAttach = null"></button>
                </div>

                <form @submit.prevent="sendMessage" class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="attachContext()" title="{{ __('Attach current page') }}">
                        <i class="bi bi-paperclip"></i>
                    </button>
                    <input type="text" x-model="newMessage" class="form-control form-control-sm" placeholder="{{ __('Type a message...') }}">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-send-fill"></i></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Modal (Inside the widget scope) -->
    <div x-show="showProfileModal" class="position-absolute bottom-0 end-0 bg-white border rounded shadow p-3" style="width: 300px; z-index: 1050; margin-right: 360px; margin-bottom: 20px;"
         x-transition.enter="fade show" @click.away="showProfileModal = false">
        <h6 class="border-bottom pb-2 mb-3">{{ __('Edit My Chat Profile') }}</h6>
        <form @submit.prevent="updateProfile">
            <div class="mb-2">
                <label class="form-label small">{{ __('Position / Title') }}</label>
                <input type="text" x-model="profileForm.position_title" class="form-control form-control-sm">
            </div>
            <div class="mb-2">
                <label class="form-label small">{{ __('Bio / Status') }}</label>
                <textarea x-model="profileForm.bio" class="form-control form-control-sm" rows="2"></textarea>
            </div>
            <div class="mb-2">
                <label class="form-label small">{{ __('Avatar') }}</label>
                <input type="file" @change="handleAvatarUpload" class="form-control form-control-sm" accept="image/*">
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" @click="showProfileModal = false" class="btn btn-sm btn-secondary">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-sm btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>

<style>
    .chat-widget {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        transition: all 0.3s ease;
        max-width: 350px;
        width: 100%;
    }
    .chat-minimized {
        width: auto;
        cursor: pointer;
    }
    .chat-expanded {
        width: 350px;
    }
    .chat-body {
        height: 400px;
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('chatWidget', () => ({
            isExpanded: false,
            view: 'contacts', // contacts, conversation
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
                position_title: @json(auth()->user()->position_title ?? ''),
                bio: @json(auth()->user()->bio ?? ''),
                avatar: null
            },

            initChat() {
                this.fetchContacts();
                // Poll for new messages every 5 seconds using dedicated endpoint
                this.pollingInterval = setInterval(() => {
                    this.checkNewMessages();
                }, 5000);
            },

            toggleChat() {
                this.isExpanded = !this.isExpanded;
                if (this.isExpanded && this.view === 'conversation' && this.activeUser) {
                     this.scrollToBottom();
                }
                if (this.isExpanded) {
                    this.fetchContacts(); // Refresh contacts on open to sync state
                }
            },

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
                this.messages = []; // Clear temporarily
                this.fetchMessages(user.id);
            },

            fetchMessages(userId) {
                fetch(`/chat/messages/${userId}`)
                    .then(res => res.json())
                    .then(data => {
                        this.messages = data;
                        this.$nextTick(() => this.scrollToBottom());

                        // Reset local unread count for this user
                        const contact = this.contacts.find(c => c.id === userId);
                        if (contact) {
                            this.unreadCount -= contact.unread_count;
                            contact.unread_count = 0;
                        }
                    });
            },

            sendMessage() {
                if (!this.newMessage.trim() && !this.contextToAttach) return;

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

            checkNewMessages() {
                // Use lightweight endpoint
                let url = '{{ route('chat.check_new') }}';
                if (this.lastCheckTime) {
                    url += `?last_check=${this.lastCheckTime}`;
                }

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        this.lastCheckTime = data.timestamp;

                        if (data.messages && data.messages.length > 0) {
                            // If we are in conversation with the sender, append
                            data.messages.forEach(msg => {
                                if (this.activeUser && this.view === 'conversation' && msg.sender_id === this.activeUser.id) {
                                    // Avoid duplicates if simple append
                                    if (!this.messages.find(m => m.id === msg.id)) {
                                        this.messages.push(msg);
                                        this.$nextTick(() => this.scrollToBottom());
                                    }
                                }
                            });

                            // Refresh contacts/counts if new messages exist
                            // Ideally we'd update specific contact, but re-fetching contacts is safer to stay in sync
                            this.fetchContacts();
                        }
                    });
            },

            formatTime(dateString) {
                const date = new Date(dateString);
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },

            scrollToBottom() {
                const container = document.getElementById('chatMessagesContainer');
                if (container) container.scrollTop = container.scrollHeight;
            },

            attachContext() {
                // Context logic: grab current URL and Title
                this.contextToAttach = {
                    url: window.location.href,
                    text: document.title,
                    type: 'link'
                };
            },

            handleAvatarUpload(e) {
                this.profileForm.avatar = e.target.files[0];
            },

            updateProfile() {
                const formData = new FormData();
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
                        alert('{{ __('Profile Updated') }}');
                    }
                });
            }
        }));
    });
</script>
