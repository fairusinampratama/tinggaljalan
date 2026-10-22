export function articlePath(slug) {
  // Match PHP rawurlencode: the input is always the raw stored slug.
  return `/news/${encodeURIComponent(slug).replace(/[!'()*]/g, (character) => `%${character.charCodeAt(0).toString(16).toUpperCase()}`)}`;
}
