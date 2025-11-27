@extends('layouts.app')

@section('title', 'Chat')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite('resources/css/chat.css')
@endpush

@section('content')
<div id="chat-app-container" class="d-flex" x-data="chatApp()">
    <!-- Sidebar -->
    <aside id="chat-sidebar" :class="{'open': sidebarOpen, 'closed': !sidebarOpen}" class="d-flex flex-column">
        <!-- Sidebar Header -->
        <header class="chat-sidebar-header d-flex align-items-center justify-content-between p-3 border-bottom">
            <h5 class="mb-0">Chat</h5>
            <div class="d-flex align-items-center">
                <button @click="showCreateRoomModal = true" class="btn btn-sm btn-icon" title="Create Group">
                    <i class="bi bi-people-fill"></i>
                </button>
                <button @click="showUserProfileModal = true" class="btn btn-sm btn-icon ms-2" title="My Profile">
                    <img :src="currentUser.avatar_url" class="rounded-circle" width="32" height="32" alt="My Avatar">
                </button>
                 <button @click="sidebarOpen = false" class="btn btn-sm btn-icon d-lg-none ms-2">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </header>

        <!-- Search Bar -->
        <div class="p-3 border-bottom">
            <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                <input type="text" x-model="searchTerm" @input.debounce.300ms="filterContacts" class="form-control bg-light border-0" placeholder="Search contacts...">
            </div>
        </div>

        <!-- Contact List -->
        <div class="chat-contact-list flex-grow-1" style="overflow-y: auto;">
             <template x-if="loadingContacts">
                <div class="p-4 text-center text-muted">Loading contacts...</div>
            </template>
            <template x-if="!loadingContacts">
                <ul class="list-unstyled mb-0">
                    <template x-for="contact in filteredContacts" :key="contact.id">
                        <li @click="selectContact(contact)" :class="{ 'active': activeContact && activeContact.id === contact.id }">
                            <a href="#" class="d-flex align-items-center text-decoration-none text-dark p-3">
                                <div class="position-relative me-3">
                                    <img :src="contact.avatar_url" width="50" height="50" class="rounded-circle" alt="avatar">
                                    <span x-show="contact.is_online" class="badge bg-success rounded-pill position-absolute bottom-0 end-0 border border-white" style="width: 12px; height: 12px; padding: 0;"></span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1" x-text="contact.name"></h6>
                                        <small class="text-muted" x-text="contact.last_message_time"></small>
                                    </div>
                                    <p class="mb-0 text-muted small" x-text="contact.position_title"></p>
                                </div>
                                 <span x-show="contact.unread_count > 0" class="badge bg-primary rounded-pill ms-2" x-text="contact.unread_count"></span>
                            </a>
                        </li>
                    </template>
                     <template x-if="filteredContacts.length === 0">
                        <div class="p-4 text-center text-muted">No contacts found.</div>
                    </template>
                </ul>
            </template>
        </div>
    </aside>

    <!-- Main Chat Area -->
    <main id="chat-main" class="flex-grow-1 d-flex flex-column">
        <!-- Chat Header -->
        <header class="chat-main-header d-flex align-items-center justify-content-between p-3 border-bottom bg-light">
            <div class="d-flex align-items-center" x-show="activeContact">
                <button @click="sidebarOpen = true" class="btn btn-sm btn-icon d-lg-none me-2">
                    <i class="bi bi-list"></i>
                </button>
                <div class="position-relative me-3">
                    <img :src="activeContact?.avatar_url" width="45" height="45" class="rounded-circle" alt="avatar">
                     <span x-show="activeContact?.is_online && !activeContact?.is_room" class="badge bg-success rounded-pill position-absolute bottom-0 end-0 border border-white" style="width: 12px; height: 12px; padding: 0;"></span>
                </div>
                <div>
                    <h6 class="mb-0" x-text="activeContact?.name"></h6>
                    <small class="text-muted" x-text="activeContact?.position_title"></small>
                </div>
            </div>
             <div x-show="!activeContact" class="d-flex align-items-center text-muted">
                 <button @click="sidebarOpen = true" class="btn btn-sm btn-icon d-lg-none me-2">
                    <i class="bi bi-list"></i>
                </button>
                Select a contact to start chatting
            </div>
            <div class="d-flex align-items-center" x-show="activeContact">
                <button @click="showChatSettingsModal = true" class="btn btn-sm btn-icon" title="Chat Settings">
                    <i class="bi bi-gear"></i>
                </button>
            </div>
        </header>

        <!-- Message Area -->
        <div id="message-area" class="flex-grow-1 p-4" style="overflow-y: auto;">
             <template x-if="loadingMessages">
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </template>
            <template x-if="!activeContact && !loadingMessages">
                 <div class="d-flex justify-content-center align-items-center h-100 flex-column text-center">
                    <i class="bi bi-chat-dots" style="font-size: 4rem; color: #e0e0e0;"></i>
                    <h4 class="mt-3">Welcome to SmartChat</h4>
                    <p class="text-muted">Select a conversation from the sidebar to begin.</p>
                </div>
            </template>

            <template x-for="message in messages" :key="message.id">
                <div class="message-item d-flex mb-3" :class="message.sender_id == {{ auth()->id() }} ? 'justify-content-end' : 'justify-content-start'">
                    <div class="d-flex">
                        <div class="flex-shrink-0" x-show="message.sender_id != {{ auth()->id() }}">
                            <img :src="message.sender.avatar_url" width="40" height="40" class="rounded-circle me-3" alt="sender avatar">
                        </div>
                        <div class="message-bubble" :class="message.sender_id == {{ auth()->id() }} ? 'sent' : 'received'">
                            <div class="message-sender" x-show="activeContact?.is_room && message.sender_id != {{ auth()->id() }}" x-text="message.sender.name"></div>
                            <div class="message-content" x-html="renderMessage(message.message)"></div>

                            <!-- Context Data (Attachments) Renderer -->
                            <template x-if="message.context_data">
                                <div class="message-attachment mt-2">
                                     <template x-if="message.context_data.type === 'image'">
                                        <a :href="message.context_data.url" data-lightbox="chat-images">
                                            <img :src="message.context_data.url" class="img-fluid rounded" style="max-height: 150px;" alt="attachment">
                                        </a>
                                    </template>
                                     <template x-if="message.context_data.type === 'gif'">
                                        <img :src="message.context_data.url" class="img-fluid rounded" style="max-height: 150px;" alt="gif">
                                    </template>
                                    <template x-if="message.context_data.type === 'file'">
                                        <div class="d-flex align-items-center p-2 border rounded bg-light">
                                            <i class="bi bi-file-earmark-text h3 me-2"></i>
                                            <div class="flex-grow-1">
                                                <a :href="message.context_data.url" target="_blank" class="text-dark text-decoration-none" x-text="message.context_data.name"></a>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <small class="message-timestamp" x-text="formatTimestamp(message.created_at)"></small>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Typing Indicator -->
        <div id="typing-indicator" class="p-2 ps-4 text-muted small" x-show="isTyping" x-text="`${typingUser}... is typing`"></div>

        <!-- Message Input -->
        <footer class="chat-main-footer p-3 border-top bg-light" x-show="activeContact">
            <div class="input-group">
                <!-- Attachment Button -->
                <button class="btn btn-outline-secondary" type="button" @click="$refs.fileInput.click()">
                    <i class="bi bi-paperclip"></i>
                </button>
                <input type="file" x-ref="fileInput" @change="handleFileUpload" class="d-none">


                <!-- Emoji and GIF buttons -->
                <div class="btn-group">
                    <button class="btn btn-outline-secondary" type="button" @click.stop="toggleEmojiPicker">
                        <i class="bi bi-emoji-smile"></i>
                    </button>
                    <button class="btn btn-outline-secondary" type="button" @click.stop="toggleGifPicker">
                        <i class="bi bi-file-image"></i>
                    </button>
                </div>

                <!-- Emoji Picker -->
                <div x-show="showEmojiPicker" @click.outside="showEmojiPicker = false" class="emoji-picker-container shadow-sm">
                    <emoji-picker class="light"></emoji-picker>
                </div>

                 <!-- GIF Picker -->
                <div x-show="showGifPicker" @click.outside="showGifPicker = false" class="gif-picker-container shadow-sm p-2">
                    <input type="text" x-model="gifSearchTerm" @input.debounce.500ms="searchGifs" class="form-control form-control-sm mb-2" placeholder="Search GIPHY...">
                    <div class="gif-grid">
                        <template x-if="loadingGifs">
                            <div class="d-flex justify-content-center w-100"><div class="spinner-border spinner-border-sm"></div></div>
                        </template>
                        <template x-for="gif in gifs" :key="gif.id">
                            <img :src="gif.images.fixed_width_small.url" @click="sendGif(gif)" class="gif-item" alt="gif">
                        </template>
                    </div>
                </div>

                <!-- Text Input -->
                <textarea x-ref="messageInput" x-model="newMessage" @keydown.enter.prevent="sendMessage" @keydown="isTyping = true"
                    class="form-control" placeholder="Type a message..." rows="1" style="resize: none;"></textarea>

                <!-- Send Button -->
                <button class="btn btn-primary" type="button" @click="sendMessage">
                    Send <i class="bi bi-send-fill ms-1"></i>
                </button>
            </div>
        </footer>
    </main>

    <!-- Modals -->
    <!-- User Profile Modal -->
    <div x-show="showUserProfileModal" class="chat-modal" @keydown.escape.window="showUserProfileModal = false">
        <div class="chat-modal-content" @click.outside="showUserProfileModal = false">
            <div class="modal-header">
                <h5>My Profile</h5>
                <button type="button" class="btn-close" @click="showUserProfileModal = false"></button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="updateUserProfile">
                    <div class="text-center mb-3">
                        <label for="avatarUpload" class="cursor-pointer">
                            <img :src="profileForm.avatarPreview || currentUser.avatar_url" width="100" height="100" class="rounded-circle mb-2" alt="Avatar Preview">
                            <div>Click to change</div>
                        </label>
                        <input type="file" id="avatarUpload" @change="previewAvatar" class="d-none">
                    </div>
                    <div class="mb-3">
                        <label for="profileName" class="form-label">Name</label>
                        <input type="text" id="profileName" class="form-control" x-model="profileForm.name">
                    </div>
                     <div class="mb-3">
                        <label for="profileTitle" class="form-label">Position / Title</label>
                        <input type="text" id="profileTitle" class="form-control" x-model="profileForm.position_title">
                    </div>
                    <div class="mb-3">
                        <label for="profileBio" class="form-label">Bio</label>
                        <textarea id="profileBio" class="form-control" rows="3" x-model="profileForm.bio"></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" @click="showUserProfileModal = false">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="profileForm.loading">
                            <span x-show="profileForm.loading" class="spinner-border spinner-border-sm"></span>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Chat Settings Modal -->
    <div x-show="showChatSettingsModal" class="chat-modal" @keydown.escape.window="showChatSettingsModal = false">
        <div class="chat-modal-content" @click.outside="showChatSettingsModal = false">
             <div class="modal-header">
                <h5>Chat Settings</h5>
                <button type="button" class="btn-close" @click="showChatSettingsModal = false"></button>
            </div>
            <div class="modal-body">
                <h6>Chat Background</h6>
                 <div class="background-grid">
                    <template x-for="(bg, index) in chatBackgrounds" :key="index">
                        <div class="background-item"
                             :class="{'selected': selectedBackground === bg}"
                             @click="selectBackground(bg)">
                             <img :src="bg" class="img-fluid rounded">
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Room Modal -->
    <div x-show="showCreateRoomModal" class="chat-modal" @keydown.escape.window="showCreateRoomModal = false">
        <div class="chat-modal-content" @click.outside="showCreateRoomModal = false">
             <div class="modal-header">
                <h5>Create New Group</h5>
                <button type="button" class="btn-close" @click="showCreateRoomModal = false"></button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="createRoom">
                    <div class="mb-3">
                        <label for="roomName" class="form-label">Group Name</label>
                        <input type="text" id="roomName" class="form-control" x-model="createRoomForm.name" required>
                    </div>
                     <div class="mb-3">
                        <label class="form-label">Select Members</label>
                        <div class="user-select-list border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                            <template x-for="contact in allContacts.filter(c => !c.is_room)" :key="contact.id">
                                 <div class="form-check">
                                    <input class="form-check-input" type="checkbox" :value="contact.id" :id="'user_'+contact.id" x-model="createRoomForm.users">
                                    <label class="form-check-label" :for="'user_'+contact.id" x-text="contact.name"></label>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" @click="showCreateRoomModal = false">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="createRoomForm.loading">
                             <span x-show="createRoomForm.loading" class="spinner-border spinner-border-sm"></span>
                            Create Group
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div> <!-- End x-data="chatApp()" -->
@endsection

@push('scripts')
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    @vite('resources/js/chat.js')
@endpush
