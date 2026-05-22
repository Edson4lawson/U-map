import { authService } from './authService';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class MessageService {
    async getMessages(receiverId) {
        const token = authService.getToken();
        const response = await fetch(`${API_URL}/messages/${receiverId}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        return await response.json();
    }

    async getConversations() {
        const token = authService.getToken();
        const response = await fetch(`${API_URL}/conversations`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        return await response.json();
    }

    async getUnreadCount() {
        const token = authService.getToken();
        if (!token) return { count: 0 };
        const response = await fetch(`${API_URL}/messages/unread-count`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        return await response.json();
    }

    async sendMessage(receiverId, content) {
        const token = authService.getToken();
        const response = await fetch(`${API_URL}/messages`, {
            method: 'POST',
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json' 
            },
            body: JSON.stringify({ receiver_id: receiverId, content })
        });
        return await response.json();
    }

    async getStudents() {
        const token = authService.getToken();
        const response = await fetch(`${API_URL}/students`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        return await response.json();
    }
}

export const messageService = new MessageService();
