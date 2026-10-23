/**
 * Resolves avatar URL properly, supporting absolute URLs, leading slash paths, or storage paths.
 *
 * @param {string|null|undefined} avatar
 * @returns {string|null}
 */
export function getAvatarUrl(avatar) {
    if (!avatar) return null;
    if (
        avatar.startsWith("http://") ||
        avatar.startsWith("https://") ||
        avatar.startsWith("/") ||
        avatar.startsWith("data:") ||
        avatar.startsWith("blob:")
    ) {
        return avatar;
    }
    return `/storage/${avatar}`;
}
