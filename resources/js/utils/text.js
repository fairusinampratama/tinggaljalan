// CMS textarea text is plain text. Blank lines separate paragraphs; single
// newlines remain line breaks. Never interpret markup as HTML.
export function textParagraphs(text = '') {
  return String(text).replace(/\r\n?/g, '\n').trim().split(/\n[\t ]*\n+/).filter(Boolean);
}

export function articleSectionId(heading = '') {
  return String(heading).toLowerCase().replace(/[^a-z0-9]+/g, '-');
}
