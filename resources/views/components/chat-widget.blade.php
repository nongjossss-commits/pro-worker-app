<!-- Chat System Manager -->
<div x-data="chatManager()"
     x-init="initManager()"
     x-cloak
     class="chat-system-overlay"
     style="position: fixed; top: 0; left: 0; width: 0; height: 0; z-index: 1040;">

    <!-- 1. Main Launcher Button (Floating & Draggable) -->
    <div x-show="!isLauncherDocked"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         class="position-fixed shadow-lg rounded-circle bg-primary text-white d-flex flex-column align-items-center justify-content-center cursor-pointer chat-launcher-btn"
         :style="`width: 60px; height: 60px; left: ${launcher.x}px; top: ${launcher.y}px; z-index: 2000; cursor: move;`"
         @mousedown="startDrag($event, 'launcher')"
         @click="if(!isDragging) toggleContactList()"
         title="{{ __('Open Chat') }}">

        <!-- Icon -->
        <i class="bi bi-chat-dots-fill fs-3"></i>

        <!-- Unread Badge -->
        <span x-show="totalUnread > 0"
              class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light"
              x-text="totalUnread"></span>

        <!-- Hide/Dock Button (Appears on Hover or Logic) -->
        <!-- BUTTON tag to prevent drag interference, positioned top-left -->
        <button type="button"
             class="position-absolute bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center border border-0 p-0"
             style="top: -8px; left: -8px; width: 28px; height: 28px; cursor: pointer; z-index: 2001;"
             @click.stop="dockLauncher(true)"
             title="{{ __('Hide to Side') }}">
            <i class="bi bi-chevron-right text-dark" style="font-size: 14px;"></i>
        </button>
    </div>

    <!-- 1.1 Docked Bracket (Side Toggle) -->
    <div x-show="isLauncherDocked"
         x-transition:enter="slide-in-right"
         class="position-fixed d-flex align-items-center justify-content-center shadow bg-primary text-white cursor-pointer"
         style="right: 0; top: 50%; transform: translateY(-50%); width: 20px; height: 80px; border-top-left-radius: 10px; border-bottom-left-radius: 10px; z-index: 2000;"
         @mouseenter="isContactListOpen = true"
         @click="dockLauncher(false)"
         title="{{ __('Show Chat') }}">
         <i class="bi bi-chevron-left"></i>
         <span x-show="totalUnread > 0"
              class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger border border-light"
              style="font-size: 0.6rem;"
              x-text="totalUnread"></span>
    </div>

    <!-- 2. Contact List (Right Sidebar) -->
    <!-- Changed from Draggable Window to Fixed Sidebar -->
    <div x-show="isContactListOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="position-fixed top-0 end-0 h-100 bg-white shadow-lg border-start d-flex flex-column"
         style="width: 320px; z-index: 2050;"
         @mousedown.outside="if(isLauncherDocked) isContactListOpen = false"
         @mouseleave="if(isLauncherDocked) isContactListOpen = false">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center p-3 bg-primary text-white">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-people-fill"></i>
                <span class="fw-bold">{{ __('Contacts') }}</span>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-link text-white p-0" @click="showProfileModal = true" title="{{ __('My Profile') }}">
                    <i class="bi bi-person-circle fs-5"></i>
                </button>
                <button type="button" class="btn btn-sm btn-link text-white p-0 ms-2" @click="isContactListOpen = false; if(!isLauncherDocked) dockLauncher(true)" title="{{ __('Close') }}">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="flex-grow-1 overflow-hidden d-flex flex-column bg-light">
            <!-- Search -->
            <div class="p-2 border-bottom bg-white d-flex gap-2">
                <div class="input-group input-group-sm flex-grow-1">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 bg-light" placeholder="{{ __('Search contacts...') }}" x-model="searchQuery">
                </div>
                 <button class="btn btn-sm btn-outline-secondary" @click="fetchContacts()" title="{{ __('Refresh') }}">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>

            <!-- List -->
            <div class="flex-grow-1 overflow-auto">
                <ul class="list-group list-group-flush">
                    <template x-for="user in filteredContacts" :key="user.id">
                        <li class="list-group-item list-group-item-action d-flex align-items-center gap-2 cursor-pointer p-2"
                            @click="openChat(user)">
                            <div class="position-relative">
                                <img :src="user.avatar_url" class="rounded-circle object-fit-cover border" width="40" height="40"
                                     onerror="this.src='https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF'">
                                <span x-show="user.is_online" class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle"></span>
                            </div>
                            <div class="flex-grow-1 lh-sm overflow-hidden">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong x-text="user.name" class="text-truncate" style="max-width: 140px;"></strong>
                                    <span x-show="user.unread_count > 0" class="badge bg-danger rounded-pill" x-text="user.unread_count"></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted text-truncate" style="max-width: 120px;" x-text="user.position_title || '{{ __('No Position') }}'"></small>
                                    <small class="text-muted" style="font-size: 0.7rem;" x-text="user.last_message_time ? formatTimeShort(user.last_message_time) : ''"></small>
                                </div>
                            </div>
                        </li>
                    </template>
                    <template x-if="filteredContacts.length === 0">
                        <div class="text-center p-4 text-muted">
                            <small>{{ __('No contacts found') }}</small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-link" @click="fetchContacts()">{{ __('Try Refreshing') }}</button>
                            </div>
                        </div>
                    </template>
                </ul>
            </div>

            <!-- Footer: Restore Floating Button -->
            <div class="p-2 border-top bg-white text-center" x-show="isLauncherDocked">
                <button class="btn btn-sm btn-outline-primary w-100" @click="dockLauncher(false)">
                    <i class="bi bi-box-arrow-in-left me-1"></i> {{ __('Restore Floating Button') }}
                </button>
            </div>
        </div>
    </div>

    <!-- 3. Individual Chat Windows -->
    <template x-for="chat in openChats" :key="chat.id">
        <div x-show="!chat.minimized"
             class="chat-window shadow rounded-3 flex-column border bg-white"
             :class="{ 'd-flex': !chat.minimized, 'd-none': chat.minimized }"
             :style="`position: fixed; left: ${chat.x}px; top: ${chat.y}px; width: ${chat.w}px; height: ${chat.h}px; z-index: ${chat.zIndex};`"
             @mousedown="bringToFront(chat.id)">

            <!-- Header -->
            <div class="chat-header d-flex justify-content-between align-items-center p-2 bg-white border-bottom cursor-grab"
                 @mousedown="startDrag($event, chat.id)">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <img :src="chat.user.avatar_url" class="rounded-circle object-fit-cover" width="32" height="32"
                         onerror="this.src='https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF'">
                    <div class="lh-1">
                        <div class="fw-bold text-truncate" style="max-width: 150px;" x-text="chat.user.name"></div>
                        <small class="text-success" style="font-size: 0.7rem;" x-show="chat.user.is_online">{{ __('Online') }}</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-link text-secondary p-0" @click.stop="chat.minimized = true; saveState()">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-link text-secondary p-0 ms-1" @click.stop="closeChat(chat.id)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="chat-messages flex-grow-1 overflow-auto p-3 bg-light d-flex flex-column" :id="'msg-container-'+chat.id">
                <template x-for="msg in chat.messages" :key="msg.id">
                    <div class="mb-2 d-flex" :class="msg.sender_id == currentUserId ? 'justify-content-end' : 'justify-content-start'">
                         <!-- Message Bubble -->
                         <div class="p-2 rounded shadow-sm"
                              :class="msg.sender_id == currentUserId ? 'bg-primary text-white' : 'bg-white text-dark'"
                              style="max-width: 85%; word-wrap: break-word;">

                            <!-- Attachments/Context -->
                            <template x-if="msg.context_data">
                                <div class="mb-1 p-1 rounded bg-opacity-10" :class="msg.sender_id == currentUserId ? 'bg-black' : 'bg-secondary'">
                                    <template x-if="msg.context_data.type === 'link'">
                                        <a :href="msg.context_data.url" target="_blank" class="d-flex align-items-center text-decoration-none small" :class="msg.sender_id == currentUserId ? 'text-white' : 'text-primary'">
                                            <i class="bi bi-link-45deg me-1"></i><span x-text="msg.context_data.text || 'Link'"></span>
                                        </a>
                                    </template>
                                    <template x-if="msg.context_data.type === 'image'">
                                        <a :href="msg.context_data.url" target="_blank"><img :src="msg.context_data.url" class="img-fluid rounded" style="max-height: 120px;"></a>
                                    </template>
                                    <template x-if="msg.context_data.type === 'file'">
                                        <a :href="msg.context_data.url" target="_blank" class="d-flex align-items-center text-decoration-none small" :class="msg.sender_id == currentUserId ? 'text-white' : 'text-primary'">
                                            <i class="bi bi-file-earmark-text me-1"></i><span x-text="msg.context_data.name || 'File'"></span>
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
                <template x-if="chat.contextToAttach">
                     <div class="mb-2 p-1 border rounded bg-light d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center small text-primary text-truncate" style="max-width: 90%;">
                            <i class="bi" :class="getAttachmentIcon(chat.contextToAttach.type)"></i>
                            <span class="ms-1 text-truncate" x-text="chat.contextToAttach.name || chat.contextToAttach.text"></span>
                        </div>
                        <button type="button" class="btn-close btn-close-sm" @click="chat.contextToAttach = null"></button>
                    </div>
                </template>

                <!-- Upload Progress -->
                <div x-show="chat.isUploading" class="progress mb-2" style="height: 3px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                </div>

                <form @submit.prevent="sendMessage(chat.id)" class="d-flex gap-2 align-items-end">
                    <div class="dropup">
                        <button class="btn btn-sm btn-light text-secondary" type="button" data-bs-toggle="dropdown"><i class="bi bi-paperclip"></i></button>
                        <ul class="dropdown-menu">
                             <li><a class="dropdown-item small" href="#" @click.prevent="attachContext(chat.id)"><i class="bi bi-link-45deg me-2"></i>{{ __('Link Page') }}</a></li>
                             <li><a class="dropdown-item small" href="#" @click.prevent="triggerFileUpload(chat.id)"><i class="bi bi-file-earmark me-2"></i>{{ __('Upload File') }}</a></li>
                        </ul>
                    </div>
                    <input type="file" :id="'file-input-'+chat.id" class="d-none" @change="handleFileUpload($event, chat.id)">

                    <textarea class="form-control form-control-sm" rows="1" style="resize: none;"
                              placeholder="{{ __('Type...') }}"
                              x-model="chat.newMessage"
                              @keydown.enter.prevent="if(!$event.shiftKey) sendMessage(chat.id)"></textarea>

                    <button type="submit" class="btn btn-sm btn-primary" :disabled="chat.isUploading || (!chat.newMessage && !chat.contextToAttach)">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
            </div>

            <!-- Resize Handles -->
            <div class="resize-handle-r" @mousedown.stop.prevent="startResize($event, chat.id, 'r')"></div>
            <div class="resize-handle-b" @mousedown.stop.prevent="startResize($event, chat.id, 'b')"></div>
            <div class="resize-handle-rb" @mousedown.stop.prevent="startResize($event, chat.id, 'rb')"></div>
        </div>
    </template>

    <!-- 4. Minimized Chat Stack (Top Right, Vertical Stack) -->
    <div class="position-fixed top-0 end-0 mt-5 pt-5 me-2 d-flex flex-column gap-2" style="z-index: 1990; pointer-events: none;">
        <template x-for="chat in openChats" :key="chat.id">
            <div x-show="chat.minimized"
                 class="position-relative shadow rounded-pill bg-white border d-flex align-items-center p-1 gap-2 cursor-pointer slide-in-right"
                 style="pointer-events: auto; max-width: 150px; background-color: rgba(255,255,255,0.95) !important;"
                 @click="chat.minimized = false; saveState(); bringToFront(chat.id)"
                 :title="chat.user.name">
                <img :src="chat.user.avatar_url" class="rounded-circle object-fit-cover" width="30" height="30"
                     onerror="this.src='https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF'">
                <span class="text-truncate small fw-bold" style="max-width: 80px;" x-text="chat.user.name"></span>
                <span x-show="chat.unreadCount > 0"
                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white p-1"
                      style="font-size: 0.6rem;"
                      x-text="chat.unreadCount"></span>
            </div>
        </template>
    </div>

     <!-- Profile Modal (Reused) -->
    <div x-show="showProfileModal"
         x-cloak
         class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center bg-black bg-opacity-50"
         :class="{ 'd-flex': showProfileModal }"
         style="z-index: 2100;"
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
                        <label class="position-absolute bottom-0 end-0 bg-light rounded-circle border p-1 cursor-pointer shadow-sm">
                            <i class="bi bi-camera-fill text-primary"></i>
                            <input type="file" @change="handleAvatarUpload" class="d-none" accept="image/*">
                        </label>
                     </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">{{ __('Display Name') }}</label>
                    <input type="text" x-model="profileForm.name" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">{{ __('Position') }}</label>
                    <input type="text" x-model="profileForm.position_title" class="form-control form-control-sm">
                </div>
                <div class="mb-2">
                    <label class="form-label small">{{ __('Bio') }}</label>
                    <textarea x-model="profileForm.bio" class="form-control form-control-sm" rows="2"></textarea>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" @click="showProfileModal = false" class="btn btn-sm btn-secondary">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
    [x-cloak] { display: none !important; }
    .chat-launcher-btn:hover { transform: scale(1.05); }
    .cursor-pointer { cursor: pointer; }
    .cursor-grab { cursor: grab; }
    .cursor-grab:active { cursor: grabbing; }

    /* Animations */
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    .slide-in-right { animation: slideInRight 0.3s ease-out; }

    /* Tailwind-like utilities if missing */
    .translate-x-full { transform: translateX(100%); }
    .translate-x-0 { transform: translateX(0); }

    /* Resize Handles */
    .resize-handle-r { position: absolute; top: 0; right: 0; width: 5px; height: 100%; cursor: e-resize; z-index: 10; }
    .resize-handle-b { position: absolute; bottom: 0; left: 0; width: 100%; height: 5px; cursor: s-resize; z-index: 10; }
    .resize-handle-rb { position: absolute; bottom: 0; right: 0; width: 10px; height: 10px; cursor: se-resize; z-index: 11; }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('chatManager', () => ({
            // --- State ---
            currentUserId: {{ auth()->id() }},
            contacts: [],
            searchQuery: '',
            openChats: [], // { id, user, messages, x, y, w, h, minimized, zIndex, ... }
            isContactListOpen: false,
            isLauncherDocked: false, // New Dock State
            // Main launcher position
            launcher: { x: window.innerWidth - 80, y: window.innerHeight - 80 },
            activeZIndex: 1050,
            dragData: null,
            isDragging: false,
            pollingInterval: null,
            showProfileModal: false,
            profileForm: {
                name: @json(auth()->user()->name),
                position_title: @json(auth()->user()->position_title ?? ''),
                bio: @json(auth()->user()->bio ?? ''),
                avatar: null,
                original_avatar_url: @json(auth()->user()->avatar_url)
            },
            profilePreviewUrl: null,

            // --- Computed ---
            get totalUnread() {
                return this.contacts.reduce((sum, c) => sum + c.unread_count, 0);
            },
            get filteredContacts() {
                if (!this.searchQuery) return this.contacts;
                const q = this.searchQuery.toLowerCase();
                return this.contacts.filter(c => c.name.toLowerCase().includes(q) || (c.position_title && c.position_title.toLowerCase().includes(q)));
            },

            // --- Init ---
            initManager() {
                this.loadState();
                this.fetchContacts();
                this.pollingInterval = setInterval(() => this.checkNewMessages(), 10000);

                window.addEventListener('mousemove', (e) => this.onMouseMove(e));
                window.addEventListener('touchmove', (e) => this.onMouseMove(e));
                window.addEventListener('mouseup', () => this.onMouseUp());
                window.addEventListener('touchend', () => this.onMouseUp());
            },

            // --- Actions ---
            toggleContactList() {
                this.isContactListOpen = !this.isContactListOpen;
                if(this.isContactListOpen) {
                    this.fetchContacts();
                }
                this.saveState();
            },

            dockLauncher(shouldDock) {
                this.isLauncherDocked = shouldDock;
                if(shouldDock) {
                    this.isContactListOpen = false; // Close list when hiding
                } else {
                    // When undocking, ensure launcher is visible within bounds
                    this.launcher.x = Math.max(0, Math.min(window.innerWidth - 60, this.launcher.x));
                    this.launcher.y = Math.max(0, Math.min(window.innerHeight - 60, this.launcher.y));
                }
                this.saveState();
            },

            openChat(user) {
                let chat = this.openChats.find(c => c.id === user.id);
                if (chat) {
                    chat.minimized = false;
                    this.bringToFront(chat.id);
                } else {
                    // Default Position (Center)
                    const offset = (this.openChats.length * 20) % 200;
                    chat = {
                        id: user.id,
                        user: user,
                        messages: [],
                        x: window.innerWidth / 2 - 175 + offset,
                        y: window.innerHeight / 2 - 250 + offset,
                        w: 350,
                        h: 450,
                        minimized: false,
                        zIndex: ++this.activeZIndex,
                        newMessage: '',
                        isUploading: false,
                        contextToAttach: null,
                        unreadCount: 0
                    };
                    this.openChats.push(chat);
                    this.fetchMessages(chat.id);
                }
                // Mark read locally
                const contact = this.contacts.find(c => c.id === user.id);
                if(contact) contact.unread_count = 0;

                this.saveState();
            },

            closeChat(id) {
                this.openChats = this.openChats.filter(c => c.id !== id);
                this.saveState();
            },

            bringToFront(targetId) {
                this.activeZIndex++;
                const chat = this.openChats.find(c => c.id === targetId);
                if (chat) chat.zIndex = this.activeZIndex;
            },

            // --- Drag & Resize Logic ---
            startDrag(e, targetId) {
                if (e.target.closest('button') || e.target.closest('input')) return;
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;

                let targetObj;
                if (targetId === 'launcher') targetObj = this.launcher;
                else targetObj = this.openChats.find(c => c.id === targetId);

                if (!targetObj) return;

                this.isDragging = false;
                this.dragData = {
                    type: 'move',
                    targetId: targetId,
                    startX: clientX,
                    startY: clientY,
                    initialX: targetObj.x,
                    initialY: targetObj.y
                };
                if(targetId !== 'launcher') this.bringToFront(targetId);
            },

            startResize(e, targetId, direction) {
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                let targetObj = this.openChats.find(c => c.id === targetId);

                this.dragData = {
                    type: 'resize',
                    targetId: targetId,
                    direction: direction,
                    startX: clientX,
                    startY: clientY,
                    initialW: targetObj.w,
                    initialH: targetObj.h
                };
                this.bringToFront(targetId);
            },

            onMouseMove(e) {
                if (!this.dragData) return;
                e.preventDefault();
                this.isDragging = true;

                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                const deltaX = clientX - this.dragData.startX;
                const deltaY = clientY - this.dragData.startY;

                let targetObj;
                let width = 0, height = 0;

                if (this.dragData.targetId === 'launcher') {
                    targetObj = this.launcher;
                    width = 60;
                    height = 60;
                } else {
                    targetObj = this.openChats.find(c => c.id === this.dragData.targetId);
                    if(targetObj) {
                        width = targetObj.w;
                        height = targetObj.h;
                    }
                }

                if (!targetObj) return;

                if (this.dragData.type === 'move') {
                    const newX = this.dragData.initialX + deltaX;
                    const newY = this.dragData.initialY + deltaY;

                    // BOUNDARY CHECKS
                    // Ensure element stays strictly within window bounds
                    const maxX = window.innerWidth - width;
                    const maxY = window.innerHeight - height;

                    targetObj.x = Math.max(0, Math.min(maxX, newX));
                    targetObj.y = Math.max(0, Math.min(maxY, newY));

                } else if (this.dragData.type === 'resize') {
                    if (this.dragData.direction.includes('r')) {
                        targetObj.w = Math.max(250, this.dragData.initialW + deltaX);
                    }
                    if (this.dragData.direction.includes('b')) {
                        targetObj.h = Math.max(300, this.dragData.initialH + deltaY);
                    }
                }
            },

            onMouseUp() {
                if (this.dragData) {
                    this.dragData = null;
                    setTimeout(() => this.isDragging = false, 100);
                    this.saveState();
                }
            },

            // --- API & Data ---
            fetchContacts() {
                fetch('{{ route('chat.contacts') }}')
                    .then(res => res.json())
                    .then(data => {
                        this.contacts = data;
                        this.contacts.sort((a, b) => {
                            if (a.unread_count !== b.unread_count) return b.unread_count - a.unread_count;
                            return new Date(b.last_message_time || 0) - new Date(a.last_message_time || 0);
                        });
                    });
            },

            fetchMessages(userId) {
                fetch(`/chat/messages/${userId}`)
                    .then(res => res.json())
                    .then(data => {
                        const chat = this.openChats.find(c => c.id === userId);
                        if (chat) {
                            chat.messages = data;
                            this.$nextTick(() => this.scrollToBottom(userId));
                        }
                    });
            },

            checkNewMessages() {
                fetch('{{ route('chat.check_new') }}')
                    .then(res => res.json())
                    .then(data => {
                         if (data.messages && data.messages.length > 0) {
                            let hasUpdates = false;
                            data.messages.forEach(msg => {
                                const chat = this.openChats.find(c => c.id === (msg.sender_id === this.currentUserId ? msg.receiver_id : msg.sender_id));
                                if (chat) {
                                    if (!chat.messages.find(m => m.id === msg.id)) {
                                        chat.messages.push(msg);
                                        this.$nextTick(() => this.scrollToBottom(chat.id));
                                    }
                                } else {
                                    hasUpdates = true;
                                }
                            });
                            if(hasUpdates) this.fetchContacts();
                        }
                    });
            },

            sendMessage(chatId) {
                const chat = this.openChats.find(c => c.id === chatId);
                if (!chat || (!chat.newMessage.trim() && !chat.contextToAttach)) return;

                const payload = {
                    receiver_id: chat.id,
                    message: chat.newMessage,
                    context_data: chat.contextToAttach,
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
                    chat.messages.push(msg);
                    chat.newMessage = '';
                    chat.contextToAttach = null;
                    this.$nextTick(() => this.scrollToBottom(chatId));
                    this.fetchContacts();
                });
            },

            // --- File Upload ---
            triggerFileUpload(chatId) {
                document.getElementById('file-input-'+chatId).click();
            },
            handleFileUpload(e, chatId) {
                const file = e.target.files[0];
                if (!file) return;
                const chat = this.openChats.find(c => c.id === chatId);
                if(!chat) return;

                chat.isUploading = true;
                const formData = new FormData();
                formData.append('file', file);

                fetch('{{ route('chat.upload') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    chat.contextToAttach = {
                        type: data.type,
                        url: data.url,
                        name: data.name,
                        mime: data.mime
                    };
                })
                .catch(err => console.error(err))
                .finally(() => {
                    chat.isUploading = false;
                    e.target.value = '';
                });
            },

            // --- Profile ---
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
                if (this.profileForm.avatar) formData.append('avatar', this.profileForm.avatar);

                fetch('{{ route('chat.profile.update_info') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.showProfileModal = false;
                        this.profileForm.original_avatar_url = data.user.avatar_url;
                        Swal.fire({ icon: 'success', title: '{{ __('Updated') }}', timer: 1500, showConfirmButton: false });
                    }
                });
            },

            // --- Helpers ---
            saveState() {
                const state = {
                    isContactListOpen: this.isContactListOpen,
                    isLauncherDocked: this.isLauncherDocked,
                    launcher: this.launcher,
                    // No need to save contactList position anymore as it is fixed
                    openChats: this.openChats.map(c => ({
                        id: c.id,
                        user: c.user,
                        x: c.x, y: c.y, w: c.w, h: c.h,
                        minimized: c.minimized,
                        zIndex: c.zIndex
                    }))
                };
                localStorage.setItem('chatState_' + this.currentUserId, JSON.stringify(state));
            },

            loadState() {
                const saved = localStorage.getItem('chatState_' + this.currentUserId);
                if (saved) {
                    try {
                        const parsed = JSON.parse(saved);
                        this.isContactListOpen = parsed.isContactListOpen;
                        if(parsed.isLauncherDocked !== undefined) this.isLauncherDocked = parsed.isLauncherDocked;
                        if(parsed.launcher) {
                            this.launcher = {...this.launcher, ...parsed.launcher};
                            // Safety Check on Load
                            this.launcher.x = Math.max(0, Math.min(window.innerWidth - 60, this.launcher.x));
                            this.launcher.y = Math.max(0, Math.min(window.innerHeight - 60, this.launcher.y));
                        }
                        if(parsed.openChats) {
                            this.openChats = parsed.openChats.map(c => ({
                                ...c,
                                messages: [],
                                newMessage: '',
                                isUploading: false,
                                contextToAttach: null
                            }));
                            this.openChats.forEach(c => this.fetchMessages(c.id));
                        }
                    } catch(e) { console.error(e); }
                }
            },

            scrollToBottom(chatId) {
                const container = document.getElementById('msg-container-'+chatId);
                if (container) container.scrollTop = container.scrollHeight;
            },
            formatTime(date) { return new Date(date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}); },
            formatTimeShort(date) { return new Date(date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}); },
            getAttachmentIcon(type) {
                if (type === 'link') return 'bi-link-45deg';
                if (type === 'image') return 'bi-file-image';
                return 'bi-file-earmark';
            },
            attachContext(chatId) {
                const chat = this.openChats.find(c => c.id === chatId);
                if(chat) {
                    chat.contextToAttach = {
                        url: window.location.href,
                        text: document.title,
                        type: 'link'
                    };
                }
            }
        }));
    });
</script>
