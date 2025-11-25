<!-- Chat System Manager -->
<div x-data="chatManager()"
     x-init="initManager()"
     x-cloak
     class="chat-system-overlay"
     style="position: fixed; top: 0; left: 0; width: 0; height: 0; z-index: 1040;">

    <!-- Notification Sound -->
    <audio id="chatNotificationSound" preload="auto">
        <source src="data:audio/wav;base64,UklGRuQSAABXQVZFZm10IBAAAAABAAEAQB8AAIA+AAACABAAZGF0YcASAAAAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQAADtLu3m7eTtLAADFtEWGRYbFtAAAO0u7ebt5O0sAAMW0RYZFhsW0AAA7S7t5u3k7SwAAxbRFhkWGxbQ=" type="audio/wav">
    </audio>

    <!-- 1. Main Launcher Button (Floating & Draggable) -->
    <div x-show="!isContactListOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         class="position-fixed shadow-lg rounded-circle bg-primary text-white d-flex flex-column align-items-center justify-content-center cursor-pointer chat-launcher-btn"
         :style="`width: 60px; height: 60px; left: ${launcher.x}px; top: ${launcher.y}px; z-index: 2100; cursor: move;`"
         @mousedown="startDrag($event, 'launcher')"
         @touchstart.prevent="startDrag($event, 'launcher')"
         @touchend="if(!isDragging) toggleContactList()"
         @click="if(!isDragging) toggleContactList()"
         @dragover.prevent
         @drop.prevent="handleDrop($event, 'launcher')"
         title="{{ __('Open Chat') }}">

        <!-- Icon -->
        <i class="bi bi-chat-dots-fill fs-3"></i>

        <!-- Unread Badge -->
        <span x-show="totalUnread > 0"
              class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light"
              x-text="totalUnread"></span>
    </div>

    <!-- 2. Contact List (Now Floating Window) -->
    <div x-show="isContactListOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="chat-window shadow-lg rounded-3 flex-column border bg-white"
         :class="{ 'd-flex': isContactListOpen, 'd-none': !isContactListOpen, 'w-100 h-100 top-0 start-0': isMobile }"
         :style="isMobile ? 'position: fixed; z-index: 2100;' : `position: fixed; left: ${contactList.x}px; top: ${contactList.y}px; width: ${contactList.w}px; height: ${contactList.h}px; z-index: ${contactList.zIndex};`"
         @mousedown="bringToFront('contactList')">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center p-3 bg-primary text-white cursor-grab"
             @mousedown="!isMobile && startDrag($event, 'contactList')">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-people-fill"></i>
                <span class="fw-bold">{{ __('Contacts') }}</span>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-link text-white p-0" @click="showProfileModal = true" title="{{ __('My Profile') }}">
                    <i class="bi bi-person-circle fs-5"></i>
                </button>
                <button type="button" class="btn btn-sm btn-link text-white p-0 ms-2" @click="toggleContactList()" title="{{ __('Minimize') }}">
                    <i class="bi bi-dash-lg"></i>
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
                            @click="openChat(user)"
                            @dragover.prevent
                            @drop.prevent="handleDrop($event, 'contact', user)">
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
        </div>

        <!-- Resize Handles -->
        <template x-if="!isMobile">
            <div>
                <div class="resize-handle-r" @mousedown.stop.prevent="startResize($event, 'contactList', 'r')"></div>
                <div class="resize-handle-b" @mousedown.stop.prevent="startResize($event, 'contactList', 'b')"></div>
                <div class="resize-handle-rb" @mousedown.stop.prevent="startResize($event, 'contactList', 'rb')"></div>
            </div>
        </template>
    </div>

    <!-- 3. Individual Chat Windows (Always available, not dependent on Contact List) -->
    <template x-for="chat in openChats" :key="chat.id">
        <div x-show="!chat.minimized"
             class="chat-window shadow rounded-3 flex-column border bg-white"
             :class="{ 'd-flex': !chat.minimized, 'd-none': chat.minimized, 'w-100 h-100 top-0 start-0': isMobile }"
             :style="isMobile ? 'position: fixed; z-index: 2150;' : `position: fixed; left: ${chat.x}px; top: ${chat.y}px; width: ${chat.w}px; height: ${chat.h}px; z-index: ${chat.zIndex};`"
             @mousedown="bringToFront(chat.id)"
             @dragover.prevent
             @drop.prevent="handleDrop($event, 'chat_window', chat.id)">

            <!-- Header -->
            <div class="chat-header d-flex justify-content-between align-items-center p-2 bg-white border-bottom cursor-grab"
                 @mousedown="!isMobile && startDrag($event, chat.id)">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <!-- Mobile Back Button -->
                    <button x-show="isMobile" type="button" class="btn btn-sm btn-link text-dark p-0 me-1" @click="closeChat(chat.id)">
                        <i class="bi bi-arrow-left"></i>
                    </button>

                    <img :src="chat.user.avatar_url" class="rounded-circle object-fit-cover" width="32" height="32"
                         onerror="this.src='https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF'">
                    <div class="lh-1">
                        <div class="fw-bold text-truncate" style="max-width: 150px;" x-text="chat.user.name"></div>
                        <small class="text-success" style="font-size: 0.7rem;" x-show="chat.user.is_online">{{ __('Online') }}</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <!-- Minimize Button -->
                    <button type="button" class="btn btn-sm btn-link text-secondary p-0" @click.stop="chat.minimized = true; saveState()">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <!-- Close Button -->
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
                         <div class="p-2 rounded shadow-sm position-relative group-hover-actions"
                              :class="msg.sender_id == currentUserId ? 'bg-primary text-white' : 'bg-white text-dark'"
                              style="max-width: 85%; word-wrap: break-word;"
                              draggable="true"
                              @dragstart="startDragMessage($event, msg)">

                            <!-- Attachments/Context -->
                            <template x-if="msg.context_data">
                                <div class="mb-1 p-1 rounded bg-opacity-10" :class="msg.sender_id == currentUserId ? 'bg-black' : 'bg-secondary'">
                                    <!-- Link Type -->
                                    <template x-if="msg.context_data.type === 'link'">
                                        <a :href="msg.context_data.url" target="_blank" class="d-flex align-items-center text-decoration-none small" :class="msg.sender_id == currentUserId ? 'text-white' : 'text-primary'">
                                            <i class="bi bi-link-45deg me-1"></i><span x-text="msg.context_data.text || 'Link'"></span>
                                        </a>
                                    </template>

                                    <!-- Image Type -->
                                    <template x-if="msg.context_data.type === 'image'">
                                        <a :href="msg.context_data.url" target="_blank"><img :src="msg.context_data.url" class="img-fluid rounded" style="max-height: 120px;"></a>
                                    </template>

                                    <!-- File Type -->
                                    <template x-if="msg.context_data.type === 'file'">
                                        <a :href="msg.context_data.url" target="_blank" class="d-flex align-items-center text-decoration-none small" :class="msg.sender_id == currentUserId ? 'text-white' : 'text-primary'">
                                            <i class="bi bi-file-earmark-text me-1"></i><span x-text="msg.context_data.name || 'File'"></span>
                                        </a>
                                    </template>

                                    <!-- Ticket Type -->
                                    <template x-if="msg.context_data.type === 'ticket'">
                                        <div class="d-flex flex-column gap-1 p-1 border-start border-3 border-secondary bg-white bg-opacity-75 rounded-end text-dark">
                                             <div class="d-flex align-items-center justify-content-between">
                                                <a :href="msg.context_data.url" class="fw-bold text-decoration-none text-dark d-flex align-items-center gap-2 text-truncate" style="font-size: 0.9rem;">
                                                     <i class="bi bi-ticket-detailed-fill text-secondary"></i>
                                                     <span x-text="msg.context_data.name"></span>
                                                </a>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-1">
                                                <small class="text-muted text-truncate" style="max-width: 100px;" x-text="msg.context_data.subtitle"></small>
                                                <!-- Add Preview Button for Employees/Employers -->
                                                <template x-if="msg.context_data.employee_id">
                                                    <button type="button" class="btn btn-sm btn-outline-info btn-preview py-0 px-1 ms-2"
                                                            style="font-size: 0.7rem;"
                                                            :data-model-id="msg.context_data.employee_id"
                                                            data-model-type="employee"
                                                            title="{{ __('Preview') }}">
                                                        <i class="bi bi-eye"></i> {{ __('Preview') }}
                                                    </button>
                                                </template>
                                                <template x-if="!msg.context_data.employee_id && msg.context_data.employer_id">
                                                    <button type="button" class="btn btn-sm btn-outline-info btn-preview py-0 px-1 ms-2"
                                                            style="font-size: 0.7rem;"
                                                            :data-model-id="msg.context_data.employer_id"
                                                            data-model-type="employer"
                                                            title="{{ __('Preview') }}">
                                                        <i class="bi bi-eye"></i> {{ __('Preview') }}
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Notification Type -->
                                    <template x-if="msg.context_data.type === 'notification'">
                                        <div class="d-flex flex-column gap-1 p-1 border-start border-3 border-danger bg-white bg-opacity-75 rounded-end text-dark">
                                             <div class="d-flex align-items-center justify-content-between">
                                                <a :href="msg.context_data.url" class="fw-bold text-decoration-none text-dark d-flex align-items-center gap-2 text-truncate" style="font-size: 0.9rem;">
                                                     <i class="bi bi-bell-fill text-danger"></i>
                                                     <span x-text="msg.context_data.name"></span>
                                                </a>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-1">
                                                <small class="text-muted text-truncate" style="max-width: 100px;" x-text="msg.context_data.subtitle"></small>
                                                <!-- V2.5-S18-FIX: Add Preview Button for Notifications with Employees -->
                                                <template x-if="msg.context_data.employee_id">
                                                    <button type="button" class="btn btn-sm btn-outline-info btn-preview py-0 px-1 ms-2"
                                                            style="font-size: 0.7rem;"
                                                            :data-model-id="msg.context_data.employee_id"
                                                            data-model-type="employee"
                                                            title="{{ __('Preview') }}">
                                                        <i class="bi bi-eye"></i> {{ __('Preview') }}
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- New Employee Draft Type -->
                                    <template x-if="msg.context_data.type === 'new_employee_draft'">
                                        <div class="d-flex flex-column gap-1 p-1 border-start border-3 border-success bg-white bg-opacity-75 rounded-end text-dark">
                                             <div class="d-flex align-items-center justify-content-between">
                                                <div class="fw-bold text-dark d-flex align-items-center gap-2 text-truncate" style="font-size: 0.9rem;">
                                                     <i class="bi bi-person-plus-fill text-success"></i>
                                                     <span x-text="msg.context_data.name"></span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-1">
                                                <small class="text-muted text-truncate" style="max-width: 100px;" x-text="msg.context_data.subtitle"></small>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Employee Type -->
                                    <template x-if="msg.context_data.type === 'employee'">
                                        <div class="d-flex flex-column gap-1 p-1 border-start border-3 border-warning bg-white bg-opacity-75 rounded-end text-dark">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <a :href="'/employees/' + msg.context_data.id + '/locate'" class="fw-bold text-decoration-none text-dark d-flex align-items-center gap-2 text-truncate" style="font-size: 0.9rem;">
                                                     <i class="bi bi-person-badge-fill text-warning"></i>
                                                     <span x-text="msg.context_data.name"></span>
                                                </a>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-1">
                                                <small class="text-muted text-truncate" style="max-width: 100px;" x-text="msg.context_data.subtitle"></small>
                                                <button type="button" class="btn btn-sm btn-outline-info btn-preview py-0 px-1 ms-2"
                                                        style="font-size: 0.7rem;"
                                                        :data-model-id="msg.context_data.id"
                                                        data-model-type="employee"
                                                        title="{{ __('Preview') }}">
                                                    <i class="bi bi-eye"></i> {{ __('Preview') }}
                                                </button>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Employer Type -->
                                    <template x-if="msg.context_data.type === 'employer'">
                                        <div class="d-flex flex-column gap-1 p-1 border-start border-3 border-info bg-white bg-opacity-75 rounded-end text-dark">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <a :href="msg.context_data.url" class="fw-bold text-decoration-none text-dark d-flex align-items-center gap-2 text-truncate" style="font-size: 0.9rem;">
                                                     <i class="bi bi-building-fill text-info"></i>
                                                     <span x-text="msg.context_data.name"></span>
                                                </a>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-1">
                                                <small class="text-muted text-truncate" style="max-width: 100px;" x-text="msg.context_data.subtitle"></small>
                                                <button type="button" class="btn btn-sm btn-outline-info btn-preview py-0 px-1 ms-2"
                                                        style="font-size: 0.7rem;"
                                                        :data-model-id="msg.context_data.id"
                                                        data-model-type="employer"
                                                        title="{{ __('Preview') }}">
                                                    <i class="bi bi-eye"></i> {{ __('Preview') }}
                                                </button>
                                            </div>
                                        </div>
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
            <template x-if="!isMobile">
                <div>
                    <div class="resize-handle-r" @mousedown.stop.prevent="startResize($event, chat.id, 'r')"></div>
                    <div class="resize-handle-b" @mousedown.stop.prevent="startResize($event, chat.id, 'b')"></div>
                    <div class="resize-handle-rb" @mousedown.stop.prevent="startResize($event, chat.id, 'rb')"></div>
                </div>
            </template>
        </div>
    </template>

    <!-- 4. Minimized Chat Stack (Top Right, Vertical Stack) -->
    <!-- Modified to show only round bubbles and fix badge clipping -->
    <div class="position-fixed top-0 end-0 mt-5 pt-5 me-2 d-flex flex-column gap-2" style="z-index: 2060; pointer-events: none;">
        <template x-for="chat in openChats" :key="chat.id">
            <div x-show="chat.minimized"
                 class="position-relative shadow rounded-circle bg-white border d-flex align-items-center justify-content-center cursor-pointer slide-in-right"
                 style="pointer-events: auto; width: 50px; height: 50px; background-color: rgba(255,255,255,0.95) !important;"
                 @click="chat.minimized = false; saveState(); bringToFront(chat.id)"
                 @dragover.prevent
                 @drop.prevent="handleDrop($event, 'chat_window', chat.id)"
                 :title="chat.user.name">
                <img :src="chat.user.avatar_url" class="w-100 h-100 object-fit-cover rounded-circle"
                     onerror="this.src='https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF'">
                <span x-show="chat.unreadCount > 0"
                      class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-danger border border-white"
                      style="font-size: 0.7rem; padding: 0.25em 0.5em !important; transform: translate(25%, -25%) !important;"
                      x-text="chat.unreadCount">
                </span>
            </div>
        </template>
    </div>
    <!-- END CHAT SYSTEM CONTAINER -->

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
                <div class="d-flex justify-content-between gap-2 mt-3">
                    <button type="button" @click="showSoundSettingsModal = true; showProfileModal = false" class="btn btn-sm btn-outline-secondary"><i class="bi bi-bell"></i> {{ __('Sounds') }}</button>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" @click="showProfileModal = false" class="btn btn-sm btn-secondary">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sound Settings Modal -->
    <div x-show="showSoundSettingsModal"
         x-cloak
         class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center bg-black bg-opacity-50"
         :class="{ 'd-flex': showSoundSettingsModal }"
         style="z-index: 2101;"
         x-transition.opacity
         @click.self="showSoundSettingsModal = false">
        <div class="bg-white rounded shadow p-3" style="width: 400px; max-height: 80vh; display: flex; flex-direction: column;">
             <h6 class="border-bottom pb-2 mb-3 d-flex justify-content-between">
                {{ __('Notification Sounds') }}
                <button type="button" class="btn-close btn-close-sm" @click="showSoundSettingsModal = false"></button>
            </h6>
            <div class="flex-grow-1 overflow-auto">
                <ul class="list-group list-group-flush">
                    <template x-for="sound in availableSounds" :key="sound.file">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="soundOption" :id="'sound_'+sound.file" :value="sound.file" x-model="selectedSound">
                                <label class="form-check-label" :for="'sound_'+sound.file" x-text="sound.name"></label>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" @click="playSound(sound.file)"><i class="bi bi-play-fill"></i></button>
                        </li>
                    </template>
                </ul>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3 border-top pt-3">
                <button type="button" @click="showSoundSettingsModal = false; showProfileModal = true" class="btn btn-sm btn-secondary">{{ __('Back') }}</button>
                <button type="button" @click="saveSoundSettings()" class="btn btn-sm btn-primary">{{ __('Save & Close') }}</button>
            </div>
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
            // Contact List Window State
            contactList: { x: window.innerWidth - 350, y: 80, w: 320, h: 600, zIndex: 2050 },

            // Main launcher position
            launcher: { x: window.innerWidth - 80, y: window.innerHeight - 80 },
            activeZIndex: 2050,
            dragData: null,
            isDragging: false,
            pollingInterval: null,
            showProfileModal: false,
            showSoundSettingsModal: false,
            availableSounds: [],
            selectedSound: 'access_granted.mp3', // A default sound
            profileForm: {
                name: @json(auth()->user()->name),
                position_title: @json(auth()->user()->position_title ?? ''),
                bio: @json(auth()->user()->bio ?? ''),
                avatar: null,
                original_avatar_url: @json(auth()->user()->avatar_url)
            },
            profilePreviewUrl: null,
            isMobile: window.innerWidth < 768,
            lastNotifiedMessageId: 0,

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
                this.fetchSounds();
                this.pollingInterval = setInterval(() => this.checkNewMessages(), 10000);
                this.requestNotificationPermission();

                window.addEventListener('mousemove', (e) => this.onMouseMove(e));
                window.addEventListener('touchmove', (e) => this.onMouseMove(e));
                window.addEventListener('mouseup', () => this.onMouseUp());
                window.addEventListener('touchend', () => this.onMouseUp());
                window.addEventListener('resize', () => { this.isMobile = window.innerWidth < 768; });
            },

            requestNotificationPermission() {
                if (!("Notification" in window)) return;
                if (Notification.permission !== "granted" && Notification.permission !== "denied") {
                    Notification.requestPermission();
                }
            },

            // --- Actions ---
            toggleContactList() {
                this.isContactListOpen = !this.isContactListOpen;
                if(this.isContactListOpen) {
                    this.bringToFront('contactList');
                    this.fetchContacts();
                }
                this.saveState();
            },

            openChat(user) {
                let chat = this.openChats.find(c => c.id === user.id);
                if (chat) {
                    chat.minimized = false;
                    chat.unreadCount = 0;
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
                if (targetId === 'contactList') {
                    this.contactList.zIndex = this.activeZIndex;
                } else {
                    const chat = this.openChats.find(c => c.id === targetId);
                    if (chat) chat.zIndex = this.activeZIndex;
                }
            },

            // --- Drag & Drop for Data Sharing ---
            handleDrop(e, targetType, targetId) {
                e.preventDefault();
                // Try to get data from application/json, fallback to text/plain
                let rawData = e.dataTransfer.getData('application/json');
                if (!rawData) {
                    rawData = e.dataTransfer.getData('text/plain');
                }

                if (!rawData) return;

                let data;
                try {
                    data = JSON.parse(rawData);
                } catch (err) { console.error('Invalid drop data', err); return; }

                if (targetType === 'launcher') {
                    if (!this.isContactListOpen) this.toggleContactList();
                    return;
                }

                let chat = null;
                if (targetType === 'contact') {
                    // targetId is the user object in the loop
                    this.openChat(targetId);
                    chat = this.openChats.find(c => c.id === targetId.id);
                } else if (targetType === 'chat_window') {
                    chat = this.openChats.find(c => c.id === targetId);
                }

                if (chat) {
                    let attachmentName = data.title;
                    let attachmentText = `[${data.type.toUpperCase()}] ${data.title}`;
                    let contextType = 'link'; // Default type
                    let contextUrl = data.url;

                    if (data.type === 'employees_bulk') {
                         attachmentName = `${data.count} Employees`;
                         attachmentText = `[BULK] ${data.count} Employees Selected`;
                    }
                    else if (data.type === 'employee') {
                        contextType = 'employee';
                        contextUrl = `/employees/${data.id}/locate`;
                    }
                    else if (data.type === 'employer') {
                        contextType = 'employer';
                    }
                    else if (data.type === 'ticket') {
                        contextType = 'ticket';
                        attachmentName = `Ticket #${data.id}`;
                        attachmentText = `[TICKET] ${data.title}`;
                    }
                    // First, set up the basic context object
                    chat.contextToAttach = {
                        type: 'link', // Default
                        id: data.id,
                        url: data.url,
                        text: `[${data.type.toUpperCase()}] ${data.title}`,
                        name: data.title,
                        subtitle: data.subtitle || data.code || '',
                        employee_id: data.employee_id || null,
                        employer_id: data.employer_id || null
                    };

                    // Then, customize it based on the type
                    if (data.type === 'employees_bulk') {
                         chat.contextToAttach.name = `${data.count} Employees`;
                         chat.contextToAttach.text = `[BULK] ${data.count} Employees Selected`;
                    }
                    else if (data.type === 'employee') {
                        chat.contextToAttach.type = 'employee';
                        chat.contextToAttach.url = `/employees/${data.id}/locate`;
                    }
                    else if (data.type === 'employer') {
                        chat.contextToAttach.type = 'employer';
                    }
                    else if (data.type === 'ticket') {
                        chat.contextToAttach.type = 'ticket';
                        chat.contextToAttach.name = `Ticket #${data.id}`;
                    }
                    else if (data.type === 'notification') {
                        chat.contextToAttach.type = 'notification';
                        chat.contextToAttach.name = `Notification: ${data.title}`;
                    }
                    else if (data.type === 'new_employee_draft') {
                        contextType = 'new_employee_draft';
                        attachmentName = `${data.title}`;
                        attachmentText = `[NEW EMPLOYEE] ${data.title}`;
                        // No URL for drafts usually
                    }
                     else if (data.type === 'file') {
                        contextType = 'file';
                        attachmentName = data.title; // File name
                        attachmentText = `[FILE] ${data.title}`;
                        // URL should already be in data.url
                    }

                    chat.contextToAttach = {
                        type: contextType,
                        id: data.id, // Important for preview triggers
                        url: contextUrl,
                        text: attachmentText,
                        name: attachmentName,
                        subtitle: data.subtitle || data.code || '',
                        employee_id: data.employee_id || null,
                        employer_id: data.employer_id || null
                    };

                    this.bringToFront(chat.id);
                    // Attempt to focus input
                    this.$nextTick(() => {
                        const input = document.querySelector(`#msg-container-${chat.id} + div textarea`);
                        if(input) input.focus();
                    });
                }
            },

            // --- Drag Message to Forward ---
            startDragMessage(e, msg) {
                // Allows dragging a message bubble to another chat to forward it
                let payload = {
                    type: 'message',
                    title: msg.message || 'Forwarded Message',
                    url: window.location.href // Fallback
                };

                // If message has context, forward that context
                if (msg.context_data) {
                     payload = {
                        ...msg.context_data,
                        title: msg.context_data.name || msg.context_data.text
                     };
                } else {
                    // Text only message
                    payload.subtitle = msg.message;
                }

                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('application/json', JSON.stringify(payload));
            },


            // --- Drag & Resize Logic ---
            startDrag(e, targetId) {
                if (e.target.closest('button') || e.target.closest('input')) return;
                const clientX = (e.touches && e.touches.length > 0) ? e.touches[0].clientX : e.clientX;
                const clientY = (e.touches && e.touches.length > 0) ? e.touches[0].clientY : e.clientY;

                let targetObj;
                if (targetId === 'launcher') targetObj = this.launcher;
                else if (targetId === 'contactList') targetObj = this.contactList;
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
                const clientX = (e.touches && e.touches.length > 0) ? e.touches[0].clientX : e.clientX;
                const clientY = (e.touches && e.touches.length > 0) ? e.touches[0].clientY : e.clientY;

                let targetObj;
                if (targetId === 'contactList') targetObj = this.contactList;
                else targetObj = this.openChats.find(c => c.id === targetId);

                if (!targetObj) return;

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

                const clientX = (e.touches && e.touches.length > 0) ? e.touches[0].clientX : e.clientX;
                const clientY = (e.touches && e.touches.length > 0) ? e.touches[0].clientY : e.clientY;
                const deltaX = clientX - this.dragData.startX;
                const deltaY = clientY - this.dragData.startY;

                // Add drag threshold to prevent accidental drags when clicking
                if (Math.abs(deltaX) < 5 && Math.abs(deltaY) < 5) return;

                this.isDragging = true;

                let targetObj;
                let width = 0, height = 0;

                if (this.dragData.targetId === 'launcher') {
                    targetObj = this.launcher;
                    width = 60;
                    height = 60;
                } else if (this.dragData.targetId === 'contactList') {
                    targetObj = this.contactList;
                    width = targetObj.w;
                    height = targetObj.h;
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
            fetchSounds() {
                fetch('/sounds/sounds.json')
                    .then(res => res.json())
                    .then(data => {
                        this.availableSounds = data;
                    })
                    .catch(e => console.error('Could not load sound list:', e));
            },

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
                        // Refresh contacts to update unread counts (Backend marks as read)
                        this.fetchContacts();
                    });
            },

            checkNewMessages() {
                fetch('{{ route('chat.check_new') }}')
                    .then(res => res.json())
                    .then(data => {
                        if (data.messages && data.messages.length > 0) {
                            let hasUpdatesToContacts = false; // Flag to check if we need to refresh the contact list
                            let shouldPlaySound = false; // Flag to play sound only once per batch
                            let latestMessageForNotification = null;

                            // Find the newest message ID in this batch
                            const latestMessageIdInBatch = Math.max(...data.messages.map(m => m.id));

                            // Determine if we should play a sound
                            if (latestMessageIdInBatch > this.lastNotifiedMessageId) {
                                shouldPlaySound = true;
                                this.lastNotifiedMessageId = latestMessageIdInBatch;
                                // Find the specific message to show in the desktop notification
                                latestMessageForNotification = data.messages.find(m => m.id === latestMessageIdInBatch && m.sender_id !== this.currentUserId);
                            }

                            data.messages.forEach(msg => {
                                const chatPartnerId = (msg.sender_id === this.currentUserId) ? msg.receiver_id : msg.sender_id;
                                const chat = this.openChats.find(c => c.id === chatPartnerId);

                                if (chat) {
                                    // Chat window is open for this message
                                    if (chat.minimized) {
                                        // If minimized, just increment unread count and flag for contact list update
                                        if (!chat.messages.find(m => m.id === msg.id)) {
                                            chat.messages.push(msg); // Add message so it's there when un-minimized
                                            chat.unreadCount = (chat.unreadCount || 0) + 1;
                                        }
                                        hasUpdatesToContacts = true;
                                    } else {
                                        // THE FIX: If chat is open and active, fetch all messages.
                                        // This marks them as read on the backend and syncs everything.
                                        this.fetchMessages(chat.id); // This will also call fetchContacts() internally
                                        // We don't need to do anything else for this message.
                                        // fetchMessages will handle appending, scrolling, and syncing the contact list.
                                    }
                                } else {
                                    // Chat window is not open at all, so we need to refresh contacts for the badge to appear.
                                    hasUpdatesToContacts = true;
                                }
                            });

                            // Play sound and show notification if determined earlier
                            if (shouldPlaySound && latestMessageForNotification) {
                                this.playNotificationSound();
                                this.showDesktopNotification(latestMessageForNotification);
                            }

                            // If any updates require a contact list refresh, do it now.
                            // This is for chats that are minimized or not open.
                            if (hasUpdatesToContacts) {
                                this.fetchContacts();
                            }
                            this.saveState();
                        }
                    });
            },

            playNotificationSound() {
                const audio = document.getElementById('chatNotificationSound');
                if (audio) {
                    const soundFile = this.selectedSound || 'access_granted.mp3';
                    audio.src = `/sounds/${soundFile}`;
                    audio.play().catch(e => console.log('Audio play failed:', e));
                }
            },

            // Method to preview a sound from the settings modal
            playSound(soundFile) {
                const audio = new Audio(`/sounds/${soundFile}`);
                audio.play().catch(e => console.log('Preview audio play failed:', e));
            },

            showDesktopNotification(msg) {
                if (!("Notification" in window)) return;

                if (Notification.permission === "granted") {
                    const n = new Notification(msg.sender.name || 'New Message', {
                        body: msg.message || 'You have a new message attachment.',
                        icon: msg.sender.avatar_path ? `/storage/${msg.sender.avatar_path}` : '/images/logo.jpg'
                    });
                    n.onclick = () => {
                        window.focus();
                        this.openChat({ id: msg.sender_id, name: msg.sender.name, avatar_url: msg.sender.avatar_path ? `/storage/${msg.sender.avatar_path}` : null });
                    };
                }
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
                    launcher: this.launcher,
                    contactList: {
                        x: this.contactList.x,
                        y: this.contactList.y,
                        w: this.contactList.w,
                        h: this.contactList.h
                    },
                    openChats: this.openChats.map(c => ({
                        id: c.id,
                        user: c.user,
                        x: c.x, y: c.y, w: c.w, h: c.h,
                        minimized: c.minimized,
                        zIndex: c.zIndex,
                        unreadCount: c.unreadCount
                    })),
                    selectedSound: this.selectedSound
                };
                localStorage.setItem('chatState_' + this.currentUserId, JSON.stringify(state));
            },

            saveSoundSettings() {
                this.saveState();
                this.showSoundSettingsModal = false;
            },

            loadState() {
                const saved = localStorage.getItem('chatState_' + this.currentUserId);
                if (saved) {
                    try {
                        const parsed = JSON.parse(saved);
                        this.isContactListOpen = parsed.isContactListOpen;
                        if(parsed.launcher) {
                            this.launcher = {...this.launcher, ...parsed.launcher};
                            // Safety Check on Load
                            this.launcher.x = Math.max(0, Math.min(window.innerWidth - 60, this.launcher.x));
                            this.launcher.y = Math.max(0, Math.min(window.innerHeight - 60, this.launcher.y));
                        }
                        if(parsed.contactList) {
                            this.contactList = {...this.contactList, ...parsed.contactList};
                            // Basic bounds check for contact list
                            this.contactList.x = Math.max(0, Math.min(window.innerWidth - 100, this.contactList.x));
                            this.contactList.y = Math.max(0, Math.min(window.innerHeight - 100, this.contactList.y));
                        }
                        if(parsed.openChats) {
                            this.openChats = parsed.openChats.map(c => ({
                                ...c,
                                messages: [],
                                newMessage: '',
                                isUploading: false,
                                contextToAttach: null,
                                unreadCount: c.unreadCount || 0
                            }));
                            this.openChats.forEach(c => this.fetchMessages(c.id));
                        }
                        if (parsed.selectedSound) {
                            this.selectedSound = parsed.selectedSound;
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
                if (type === 'employee') return 'bi-person-badge-fill';
                if (type === 'employer') return 'bi-building-fill';
                if (type === 'ticket') return 'bi-ticket-detailed-fill';
                if (type === 'notification') return 'bi-bell-fill';
                if (type === 'new_employee_draft') return 'bi-person-plus-fill';
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
