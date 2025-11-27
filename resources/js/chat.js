document.addEventListener('alpine:init', () => {
    Alpine.data('chatApp', () => ({
        // --- Core State ---
        sidebarOpen: true,
        contacts: [],
        allContacts: [],
        filteredContacts: [],
        activeContact: null,
        messages: [],
        currentUser: { id: AUTH_USER_ID, name: 'You', avatar_url: DEFAULT_AVATAR_URL },

        // --- UI/UX State ---
        loadingContacts: true,
        loadingMessages: false,
        searchTerm: '',
        newMessage: '',
        isTyping: false,
        typingUser: '',

        // --- Feature Toggles & Modals ---
        showEmojiPicker: false,
        showGifPicker: false,
        showUserProfileModal: false,
        showChatSettingsModal: false,
        showCreateRoomModal: false,

        // --- GIF State ---
        gifs: [],
        gifSearchTerm: '',
        loadingGifs: false,

        // --- Settings State ---
        chatBackgrounds: [],
        selectedBackground: localStorage.getItem('chat-background') || '',

        // --- Profile Edit State ---
        profileForm: {
            name: '',
            position_title: '',
            bio: '',
            avatar: null,
            avatarPreview: null,
            loading: false,
        },

        // --- Room Creation State ---
        createRoomForm: {
            name: '',
            users: [],
            loading: false,
        },

        // --- Polling State ---
        pollInterval: null,
        lastCheckTimestamp: null,
        lastRoomMessageId: 0,


        // --- Initialization ---
        init() {
            this.fetchContacts();
            this.fetchCurrentUser();
            this.applyBackground();

            this.$watch('newMessage', (value) => {
                if (value) {
                    this.isTyping = true;
                    // In a real app, you'd emit a typing event via WebSocket here
                } else {
                    this.isTyping = false;
                }
            });

            // Handle emoji selection
            const emojiPicker = this.$el.querySelector('emoji-picker');
            if(emojiPicker) {
                 emojiPicker.addEventListener('emoji-click', event => {
                    this.newMessage += event.detail.unicode;
                    this.$refs.messageInput.focus();
                });
            }

            // Adjust sidebar based on window size
            this.handleResize();
            window.addEventListener('resize', () => this.handleResize());

            // Start polling for new messages
            this.startPolling();
        },

        handleResize() {
            this.sidebarOpen = window.innerWidth >= 992; // Bootstrap's lg breakpoint
        },

        // --- Data Fetching ---
        async fetchContacts() {
            this.loadingContacts = true;
            try {
                const response = await fetch(API_ROUTES.contacts);
                if (!response.ok) throw new Error('Failed to fetch contacts');
                const data = await response.json();
                this.contacts = data;
                this.allContacts = data;
                this.filteredContacts = data;
            } catch (error) {
                console.error(error);
                showToast('Error loading contacts.', 'danger');
            } finally {
                this.loadingContacts = false;
            }
        },

        async fetchMessages(contactId) {
            this.loadingMessages = true;
            this.messages = [];
            try {
                const response = await fetch(API_ROUTES.messages.replace('{id}', contactId));
                 if (!response.ok) throw new Error('Failed to fetch messages');
                const data = await response.json();
                this.messages = this.hydrateMessages(data);
                this.scrollToBottom();
            } catch (error) {
                console.error(error);
                showToast('Error loading messages.', 'danger');
            } finally {
                this.loadingMessages = false;
            }
        },

        fetchCurrentUser() {
             // In a real app, you'd fetch this from an API endpoint
             // For now, we'll use placeholder data and update it via the profile form
            this.currentUser = {
                id: AUTH_USER_ID,
                name: AUTH_USER_NAME,
                avatar_url: AUTH_USER_AVATAR,
                position_title: 'Software Engineer',
                bio: 'Laravel and Alpine enthusiast.'
            };
            // Populate profile form
            this.profileForm.name = this.currentUser.name;
            this.profileForm.position_title = this.currentUser.position_title;
            this.profileForm.bio = this.currentUser.bio;
        },

        // --- Contact & Message Management ---
        selectContact(contact) {
            if(this.activeContact && this.activeContact.id === contact.id) return;

            this.activeContact = contact;
            this.messages = [];
            this.fetchMessages(contact.id);

            // Mark as read on the frontend immediately
            const contactInList = this.contacts.find(c => c.id === contact.id);
            if(contactInList) {
                contactInList.unread_count = 0;
            }

            // Close sidebar on mobile after selection
            if (window.innerWidth < 992) {
                this.sidebarOpen = false;
            }
        },

        filterContacts() {
            if (!this.searchTerm) {
                this.filteredContacts = this.allContacts;
                return;
            }
            const lowerCaseSearch = this.searchTerm.toLowerCase();
            this.filteredContacts = this.allContacts.filter(contact =>
                contact.name.toLowerCase().includes(lowerCaseSearch) ||
                (contact.position_title && contact.position_title.toLowerCase().includes(lowerCaseSearch))
            );
        },

        async sendMessage() {
            if (!this.newMessage.trim() && !this.attachmentData) return;
            if (!this.activeContact) return;

            const messagePayload = {
                receiver_id: this.activeContact.id,
                message: this.newMessage.trim(),
                context_data: this.attachmentData || null,
            };

            // Add placeholder message immediately for better UX
            const placeholderMessage = {
                id: Date.now(),
                sender_id: this.currentUser.id,
                message: this.newMessage,
                context_data: this.attachmentData,
                created_at: new Date().toISOString(),
                sender: { avatar_url: this.currentUser.avatar_url },
                sending: true
            };
            this.messages.push(placeholderMessage);
            this.scrollToBottom();

            this.newMessage = '';
            this.attachmentData = null; // Clear attachment after preparing payload

            try {
                const response = await fetch(API_ROUTES.send, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(messagePayload)
                });
                if (!response.ok) throw new Error('Failed to send message');
                const sentMessage = await response.json();

                // Replace placeholder with actual message from server
                const index = this.messages.findIndex(m => m.id === placeholderMessage.id);
                if(index > -1) {
                    this.messages.splice(index, 1, this.hydrateMessages([sentMessage])[0]);
                }

            } catch (error) {
                console.error(error);
                showToast('Failed to send message.', 'danger');
                 // Mark the placeholder as failed
                const index = this.messages.findIndex(m => m.id === placeholderMessage.id);
                if(index > -1) {
                    this.messages[index].sending = false;
                    this.messages[index].failed = true;
                }
            }
        },

        // --- Polling for New Messages ---
        startPolling() {
            this.pollInterval = setInterval(async () => {
                try {
                    const roomIds = this.contacts.filter(c => c.is_room).map(r => r.id.replace('room_', ''));
                    const response = await fetch(`${API_ROUTES.check_new}?last_check=${this.lastCheckTimestamp || ''}&last_room_message_id=${this.lastRoomMessageId}&rooms[]=${roomIds.join('&rooms[]=')}`);
                    if (!response.ok) return;

                    const data = await response.json();

                    // Handle Direct Messages
                    if (data.direct_messages && data.direct_messages.length > 0) {
                        data.direct_messages.forEach(msg => {
                            // If chat is active, append message
                            if (this.activeContact && this.activeContact.id == msg.sender_id) {
                                this.messages.push(this.hydrateMessages([msg])[0]);
                                this.scrollToBottom();
                                // We should also send a "read" receipt here in a real app
                            } else {
                                // Otherwise, increment unread count
                                const contact = this.contacts.find(c => c.id == msg.sender_id);
                                if (contact) {
                                    contact.unread_count = (contact.unread_count || 0) + 1;
                                }
                                showToast(`New message from ${msg.sender.name}`, 'success');
                            }
                        });
                    }

                    // Handle Room Messages
                    if (data.room_messages && data.room_messages.length > 0) {
                        data.room_messages.forEach(msg => {
                            if (this.activeContact && this.activeContact.id == `room_${msg.chat_room_id}`) {
                                this.messages.push(this.hydrateMessages([msg])[0]);
                                this.scrollToBottom();
                            } else {
                                const roomContact = this.contacts.find(c => c.id == `room_${msg.chat_room_id}`);
                                if (roomContact) {
                                    roomContact.unread_count = (roomContact.unread_count || 0) + 1;
                                }
                                showToast(`New message in ${roomContact?.name || 'group'}`, 'success');
                            }
                        });
                        // Update the last message ID to prevent refetching
                        this.lastRoomMessageId = data.room_messages[data.room_messages.length - 1].id;
                    }

                    this.lastCheckTimestamp = data.timestamp;

                } catch (error) {
                    console.error("Polling error:", error);
                }
            }, 5000); // Poll every 5 seconds
        },

        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
            }
        },


        // --- UI Toggles & Helpers ---
        toggleEmojiPicker() {
            this.showGifPicker = false;
            this.showEmojiPicker = !this.showEmojiPicker;
        },
        toggleGifPicker() {
            this.showEmojiPicker = false;
            this.showGifPicker = !this.showGifPicker;
            if (this.showGifPicker && this.gifs.length === 0) {
                this.searchGifs(); // Load trending GIFs on first open
            }
        },
        scrollToBottom() {
            this.$nextTick(() => {
                const messageArea = this.$el.querySelector('#message-area');
                if(messageArea) {
                    messageArea.scrollTop = messageArea.scrollHeight;
                }
            });
        },

        // --- File & GIF Handling ---
        async handleFileUpload() {
            const file = this.$refs.fileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch(API_ROUTES.upload, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: formData
                });
                if (!response.ok) throw new Error('File upload failed');
                const result = await response.json();

                // Set attachment data and send immediately
                this.attachmentData = {
                    type: result.type,
                    url: result.url,
                    name: result.name
                };
                this.sendMessage();

            } catch (error) {
                console.error(error);
                showToast('File upload failed.', 'danger');
            }
        },

        async searchGifs() {
            this.loadingGifs = true;
            try {
                const response = await fetch(`${API_ROUTES.giphy_proxy}?query=${this.gifSearchTerm}`);
                 if (!response.ok) throw new Error('Failed to fetch GIFs');
                const data = await response.json();
                this.gifs = data.data;
            } catch (error) {
                console.error(error);
                showToast('Could not load GIFs.', 'danger');
            } finally {
                this.loadingGifs = false;
            }
        },

        sendGif(gif) {
            this.attachmentData = {
                type: 'gif',
                url: gif.images.original.url,
                name: gif.title || 'GIF'
            };
            this.sendMessage();
            this.showGifPicker = false;
        },


        // --- Modals & Settings ---
        async updateUserProfile() {
            this.profileForm.loading = true;
            const formData = new FormData();
            formData.append('name', this.profileForm.name);
            formData.append('position_title', this.profileForm.position_title);
            formData.append('bio', this.profileForm.bio);
            if (this.profileForm.avatar) {
                formData.append('avatar', this.profileForm.avatar);
            }

            try {
                const response = await fetch(API_ROUTES.profile_update, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: formData
                });
                 if (!response.ok) throw new Error('Failed to update profile');
                const data = await response.json();

                // Update UI
                this.currentUser.name = data.user.name;
                this.currentUser.avatar_url = data.user.avatar_url;
                this.currentUser.position_title = data.user.position_title;
                this.currentUser.bio = data.user.bio;
                showToast('Profile updated successfully!', 'success');
                this.showUserProfileModal = false;
            } catch (error) {
                console.error(error);
                showToast('Error updating profile.', 'danger');
            } finally {
                this.profileForm.loading = false;
            }
        },
        previewAvatar(event) {
            const file = event.target.files[0];
            if (file) {
                this.profileForm.avatar = file;
                this.profileForm.avatarPreview = URL.createObjectURL(file);
            }
        },

        async fetchChatBackgrounds() {
            if(this.chatBackgrounds.length > 0) return;
            try {
                const response = await fetch(API_ROUTES.backgrounds);
                if (!response.ok) throw new Error('Failed to fetch backgrounds');
                this.chatBackgrounds = await response.json();
            } catch (error) {
                console.error(error);
            }
        },
        selectBackground(bgUrl) {
            this.selectedBackground = bgUrl;
            localStorage.setItem('chat-background', bgUrl);
            this.applyBackground();
        },
        applyBackground() {
            const messageArea = document.getElementById('message-area');
            if (messageArea) {
                messageArea.style.backgroundImage = this.selectedBackground ? `url(${this.selectedBackground})` : 'none';
                messageArea.style.backgroundSize = 'cover';
                messageArea.style.backgroundPosition = 'center';
            }
        },

        async createRoom() {
            if(!this.createRoomForm.name || this.createRoomForm.users.length === 0) {
                showToast('Group name and at least one member are required.', 'danger');
                return;
            }
            this.createRoomForm.loading = true;
            try {
                const response = await fetch(API_ROUTES.create_room, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        name: this.createRoomForm.name,
                        users: this.createRoomForm.users
                    })
                });
                if (!response.ok) throw new Error('Failed to create room');
                const data = await response.json();

                // Add new room to the contact list and select it
                this.allContacts.unshift(data.room);
                this.filterContacts(); // Re-apply filter
                this.selectContact(data.room);

                showToast('Group created successfully!', 'success');
                this.showCreateRoomModal = false;
                this.createRoomForm.name = '';
                this.createRoomForm.users = [];

            } catch (error) {
                 console.error(error);
                 showToast('Error creating group.', 'danger');
            } finally {
                 this.createRoomForm.loading = false;
            }
        },


        // --- Formatting & Rendering ---
        hydrateMessages(messages) {
            return messages.map(msg => ({
                ...msg,
                sender: {
                    ...msg.sender,
                    avatar_url: msg.sender.avatar_path ? `${BASE_URL}/storage/${msg.sender.avatar_path}` : DEFAULT_AVATAR_URL,
                }
            }));
        },

        renderMessage(text) {
             if (!text) return '';
             // Simple link detection
            const urlRegex = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
            return text.replace(urlRegex, function(url) {
                return `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`;
            });
        },
        formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    }));
});

// --- Helper Functions ---
function showToast(message, type = 'success') {
    const toastEvent = new CustomEvent('show-toast', { detail: { message, type } });
    window.dispatchEvent(toastEvent);
}

// Assume these are passed from Blade
const API_ROUTES = {
    contacts: '/api/chat/contacts',
    messages: '/api/chat/messages/{id}',
    send: '/api/chat/send',
    upload: '/api/chat/upload',
    check_new: '/api/chat/check-new',
    profile_update: '/api/chat/profile/update',
    create_room: '/api/chat/rooms/create',
    backgrounds: '/api/chat/backgrounds',
    giphy_proxy: '/api/chat/giphy-proxy',
};
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
// These would be dynamically set by your Laravel view
const AUTH_USER_ID = document.body.dataset.userId || 1;
const AUTH_USER_NAME = document.body.dataset.userName || 'Current User';
const AUTH_USER_AVATAR = document.body.dataset.userAvatar || '/images/default-avatar.png';
const DEFAULT_AVATAR_URL = '/images/default-avatar.png';
const BASE_URL = window.location.origin;
