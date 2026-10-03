import Dexie from 'dexie';

class ChatDatabase extends Dexie {
    constructor() {
        super('UmapChatDatabase');

        this.version(1).stores({
            conversations: 'id, last_message_at, updated_at',
            messages: 'id, partner_id, sender_id, receiver_id, created_at, is_read, [sender_id+receiver_id]',
            meta: 'key',
        });

        this.conversations = this.table('conversations');
        this.messages = this.table('messages');
        this.meta = this.table('meta');
    }

    /**
     * Verify and enforce account isolation.
     * If the logged in user ID changes, purge the database immediately to prevent data leakage.
     * @param {number|string|null} currentUserId
     */
    async ensureUserIsolation(currentUserId) {
        if (!currentUserId) return;
        const currentIdStr = String(currentUserId);

        try {
            const record = await this.meta.get('current_user_id');
            if (record && record.value && String(record.value) !== currentIdStr) {
                console.warn('[IndexedDB] Different user account detected. Purging local chat cache.');
                await this.clearAllChatData();
            }
            await this.meta.put({ key: 'current_user_id', value: currentIdStr });
        } catch (e) {
            console.error('[IndexedDB] Error checking user isolation:', e);
        }
    }

    /**
     * Retrieve cached conversations sorted by last_message_at descending.
     */
    async getCachedConversations() {
        try {
            const list = await this.conversations.toArray();
            return list.sort((a, b) => {
                const dateA = new Date(a.last_message_at || a.last_message?.created_at || a.updated_at || 0).getTime();
                const dateB = new Date(b.last_message_at || b.last_message?.created_at || b.updated_at || 0).getTime();
                return dateB - dateA;
            });
        } catch (e) {
            console.warn('[IndexedDB] Failed to read conversations from cache:', e);
            return [];
        }
    }

    /**
     * Upsert conversations list (merge, no destructive overwrite).
     */
    async saveConversations(conversations) {
        if (!Array.isArray(conversations) || conversations.length === 0) return;

        try {
            // Clean objects for structured cloning in IndexedDB
            const cleanList = conversations.map(c => ({
                id: Number(c.id),
                name: c.name || '',
                avatar: c.avatar || null,
                study_status: c.study_status || null,
                study_location: c.study_location || null,
                last_message: c.last_message || null,
                last_message_at: c.last_message_at || (c.last_message ? c.last_message.created_at : null),
                unread_count: Number(c.unread_count || 0),
                updated_at: c.updated_at || new Date().toISOString(),
            }));

            await this.conversations.bulkPut(cleanList);
        } catch (e) {
            console.warn('[IndexedDB] Failed to save conversations to cache:', e);
        }
    }

    /**
     * Retrieve cached messages for a conversation partner (up to limit, latest first, ordered chronologically).
     */
    async getCachedMessages(partnerId, limit = 100) {
        if (!partnerId) return [];
        const pid = Number(partnerId);

        try {
            // Query by partner_id or sender/receiver
            const msgs = await this.messages
                .where('partner_id')
                .equals(pid)
                .sortBy('created_at');

            if (msgs.length > limit) {
                return msgs.slice(-limit);
            }
            return msgs;
        } catch (e) {
            console.warn(`[IndexedDB] Failed to read messages for partner ${partnerId}:`, e);
            return [];
        }
    }

    /**
     * Upsert a batch of messages (merge by id) and prune old messages beyond the limit.
     */
    async saveMessages(partnerId, messages, limit = 100) {
        if (!partnerId || !Array.isArray(messages) || messages.length === 0) return;
        const pid = Number(partnerId);

        try {
            // Prepare clean objects with partner_id assigned
            const cleanMsgs = messages
                .filter(m => m && (m.id || m.id === 0) && !String(m.id).startsWith('temp-'))
                .map(m => ({
                    id: Number(m.id),
                    partner_id: pid,
                    sender_id: Number(m.sender_id),
                    receiver_id: Number(m.receiver_id),
                    content: m.content || '',
                    created_at: m.created_at || new Date().toISOString(),
                    is_read: Boolean(m.is_read),
                    sender: m.sender ? { id: m.sender.id, name: m.sender.name } : null,
                    receiver: m.receiver ? { id: m.receiver.id, name: m.receiver.name } : null,
                }));

            if (cleanMsgs.length > 0) {
                await this.messages.bulkPut(cleanMsgs);
            }

            // Pruning: keep only the latest `limit` messages for this partner
            const allForPartner = await this.messages
                .where('partner_id')
                .equals(pid)
                .sortBy('created_at');

            if (allForPartner.length > limit) {
                const toDelete = allForPartner.slice(0, allForPartner.length - limit);
                const idsToDelete = toDelete.map(m => m.id);
                await this.messages.bulkDelete(idsToDelete);
            }
        } catch (e) {
            console.warn(`[IndexedDB] Failed to save messages for partner ${partnerId}:`, e);
        }
    }

    /**
     * Upsert a single real-time message received via WebSocket / Reverb.
     * Prevents disappearing message bug on chat close/reopen.
     */
    async saveSingleMessage(partnerId, message, limit = 100) {
        if (!partnerId || !message || !message.id || String(message.id).startsWith('temp-')) return;
        const pid = Number(partnerId);

        try {
            const cleanMsg = {
                id: Number(message.id),
                partner_id: pid,
                sender_id: Number(message.sender_id),
                receiver_id: Number(message.receiver_id),
                content: message.content || '',
                created_at: message.created_at || new Date().toISOString(),
                is_read: Boolean(message.is_read),
                sender: message.sender ? { id: message.sender.id, name: message.sender.name } : null,
                receiver: message.receiver ? { id: message.receiver.id, name: message.receiver.name } : null,
            };

            await this.messages.put(cleanMsg);

            // Also update conversation's last message in Dexie
            const conv = await this.conversations.get(pid);
            if (conv) {
                conv.last_message = {
                    id: cleanMsg.id,
                    content: cleanMsg.content,
                    created_at: cleanMsg.created_at,
                    sender_id: cleanMsg.sender_id,
                    is_read: cleanMsg.is_read,
                };
                conv.last_message_at = cleanMsg.created_at;
                await this.conversations.put(conv);
            }

            // Prune if necessary
            const count = await this.messages.where('partner_id').equals(pid).count();
            if (count > limit) {
                const oldest = await this.messages
                    .where('partner_id')
                    .equals(pid)
                    .limit(count - limit)
                    .primaryKeys();
                await this.messages.bulkDelete(oldest);
            }
        } catch (e) {
            console.warn(`[IndexedDB] Failed to save single message for partner ${partnerId}:`, e);
        }
    }

    /**
     * Purge all cached chat data on logout or account switch.
     */
    async clearAllChatData() {
        try {
            await this.conversations.clear();
            await this.messages.clear();
            await this.meta.clear();
            console.log('[IndexedDB] Local chat database successfully cleared.');
        } catch (e) {
            console.error('[IndexedDB] Failed to clear chat database:', e);
        }
    }
}

export const chatDatabase = new ChatDatabase();
