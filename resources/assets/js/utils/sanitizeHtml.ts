import DOMPurify from 'dompurify'

/**
 * Sanitizes HTML content to prevent XSS attacks.
 * Allows safe HTML tags and attributes while removing dangerous content.
 *
 * @param html - The HTML string to sanitize
 * @returns The sanitized HTML string
 */
export const sanitizeHtml = (html: string): string => {
  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS: ['a', 'p', 'br', 'strong', 'em', 'u', 'span', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li'],
    ALLOWED_ATTR: ['href', 'target', 'rel', 'class'],
    ALLOW_DATA_ATTR: false,
  })
}

/**
 * Sanitizes a URL to ensure it's safe for use in href attributes.
 * Prevents javascript: and data: URLs that could execute code.
 *
 * @param url - The URL to sanitize
 * @returns The sanitized URL or '#' if unsafe
 */
export const sanitizeUrl = (url: string): string => {
  const trimmed = url.trim().toLowerCase()

  // Block dangerous URL schemes
  if (trimmed.startsWith('javascript:') || trimmed.startsWith('data:') || trimmed.startsWith('vbscript:')) {
    return '#'
  }

  // Allow http, https, mailto, tel, and relative URLs
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('mailto:') || trimmed.startsWith('tel:') || trimmed.startsWith('/') || trimmed.startsWith('#')) {
    return url.trim()
  }

  // Default to # for unknown schemes
  return '#'
}
