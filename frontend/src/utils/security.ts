/**
 * Security Utilities
 * Token handling and secure storage management
 */

const TOKEN_KEY = 'auth_token';
const REFRESH_TOKEN_KEY = 'refresh_token';

/**
 * Store authentication token securely
 * Uses sessionStorage instead of localStorage for better security
 */
export function setAuthToken(token: string): void {
  sessionStorage.setItem(TOKEN_KEY, token);
}

/**
 * Get authentication token
 */
export function getAuthToken(): string | null {
  return sessionStorage.getItem(TOKEN_KEY);
}

/**
 * Remove authentication token
 */
export function removeAuthToken(): void {
  sessionStorage.removeItem(TOKEN_KEY);
  sessionStorage.removeItem(REFRESH_TOKEN_KEY);
}

/**
 * Store refresh token
 */
export function setRefreshToken(token: string): void {
  sessionStorage.setItem(REFRESH_TOKEN_KEY, token);
}

/**
 * Get refresh token
 */
export function getRefreshToken(): string | null {
  return sessionStorage.getItem(REFRESH_TOKEN_KEY);
}

/**
 * Check if user is authenticated
 */
export function isAuthenticated(): boolean {
  return !!getAuthToken();
}

/**
 * Clear all authentication data
 */
export function clearAuth(): void {
  removeAuthToken();
  // Clear any other sensitive data
  sessionStorage.removeItem('user_data');
}

/**
 * Secure API request wrapper
 */
export async function secureFetch(url: string, options: RequestInit = {}): Promise<Response> {
  const token = getAuthToken();
  
  const secureOptions: RequestInit = {
    ...options,
    headers: {
      ...options.headers,
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    },
    credentials: 'omit', // Never send cookies
  };

  const response = await fetch(url, secureOptions);

  // Handle 401 Unauthorized
  if (response.status === 401) {
    clearAuth();
    window.location.href = '/login';
    throw new Error('Unauthorized');
  }

  return response;
}

/**
 * Generate CSRF token (if needed)
 */
export function generateCSRFToken(): string {
  const array = new Uint8Array(32);
  crypto.getRandomValues(array);
  return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
}

/**
 * Validate token format
 */
export function isValidToken(token: string): boolean {
  // Basic validation - adjust based on your token format
  return token.length > 20 && /^[a-zA-Z0-9._-]+$/.test(token);
}

/**
 * Sanitize user input before sending to API
 */
export function sanitizeInput(input: string): string {
  return input.trim().slice(0, 10000); // Limit length
}

/**
 * Check for content security violations
 */
export function checkCSPViolation(): void {
  if (window.addEventListener) {
    window.addEventListener('securitypolicyviolation', (event) => {
      console.error('CSP Violation:', event);
      // Send to logging service
    });
  }
}
