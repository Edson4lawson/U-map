/**
 * Secure Message Composable
 * Handles message display with XSS prevention
 */

import { sanitizeMessage, containsDangerousContent } from '@/utils/xss';

export function useSecureMessage() {
  /**
   * Display message safely
   */
  const displayMessage = (content: string): string => {
    // Check for dangerous content
    if (containsDangerousContent(content)) {
      console.warn('Dangerous content detected in message');
      return '[Content blocked for security]';
    }

    return sanitizeMessage(content);
  };

  /**
   * Validate message before sending
   */
  const validateMessage = (content: string): { valid: boolean; error?: string } => {
    if (!content || content.trim().length === 0) {
      return { valid: false, error: 'Message cannot be empty' };
    }

    if (content.length > 1000) {
      return { valid: false, error: 'Message cannot exceed 1000 characters' };
    }

    if (containsDangerousContent(content)) {
      return { valid: false, error: 'Message contains invalid content' };
    }

    return { valid: true };
  };

  /**
   * Sanitize message before sending
   */
  const prepareMessage = (content: string): string => {
    // Remove any HTML tags
    let sanitized = content.replace(/<[^>]*>/g, '');
    
    // Remove dangerous patterns
    sanitized = sanitized.replace(/javascript:/gi, '');
    sanitized = sanitized.replace(/on\w+\s*=/gi, '');
    
    return sanitized.trim();
  };

  return {
    displayMessage,
    validateMessage,
    prepareMessage,
  };
}
