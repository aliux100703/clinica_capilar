// Only render the strict allowlisted HTML emitted by Laravel's SafeHtml service.
export function RichContent({ html }: { html?: string | null }) {
  if (!html) return null;
  return <div className="cms-rich-content" dangerouslySetInnerHTML={{ __html: html }} />;
}
