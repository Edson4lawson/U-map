/**
 * XSS Prevention Utilities
 * Sanitizes user input to prevent XSS attacks
 */

/**
 * Sanitize HTML content to prevent XSS
 */
export function sanitizeHTML(html: string): string {
  const tempDiv = document.createElement('div');
  tempDiv.textContent = html;
  return tempDiv.innerHTML;
}

/**
 * Sanitize text content (remove all HTML tags)
 */
export function sanitizeText(text: string): string {
  const tempDiv = document.createElement('div');
  tempDiv.textContent = text;
  return tempDiv.textContent || '';
}

/**
 * Escape special characters to prevent XSS
 */
export function escapeHtml(unsafe: string): string {
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

/**
 * Validate and sanitize URL
 */
export function sanitizeUrl(url: string): string {
  try {
    const parsed = new URL(url);
    // Only allow http and https protocols
    if (!['http:', 'https:'].includes(parsed.protocol)) {
      return '';
    }
    return parsed.toString();
  } catch {
    return '';
  }
}

/**
 * Check if string contains potentially dangerous content
 */
export function containsDangerousContent(text: string): boolean {
  const dangerousPatterns = [
    /<script/i,
    /javascript:/i,
    /on\w+\s*=/i, // onclick, onerror, etc.
    /<iframe/i,
    /<object/i,
    /<embed/i,
    /data:/i,
    /vbscript:/i,
  ];

  return dangerousPatterns.some(pattern => pattern.test(text));
}

/**
 * Sanitize message content for display
 */
export function sanitizeMessage(content: string): string {
  // Remove any HTML tags
  let sanitized = sanitizeText(content);
  
  // Remove potentially dangerous patterns
  sanitized = sanitized.replace(/javascript:/gi, '');
  sanitized = sanitized.replace(/on\w+\s*=/gi, '');
  
  return sanitized;
}

/**
 * Safe innerHTML setter
 */
export function setSafeHTML(element: HTMLElement, html: string): void {
  element.textContent = html;
}
