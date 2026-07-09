import { useState } from 'react';
import { ChevronDown } from 'lucide-react';
import { cardHoverClass } from '../ui/styles';
import { getLocalized } from '../../utils/localization';

export function FaqSection({ title = 'FAQ General – Tinggal Jalan Tours', items, language = 'en' }) {
  const [openIndex, setOpenIndex] = useState(1);

  if (!items?.length) {
    return null;
  }

  return (
    <section id="faq" className="public-section bg-white">
      <div className="mx-auto max-w-4xl">
        <h2 className="public-heading-section mx-auto mb-7 max-w-3xl text-center text-ink">
          {title}
        </h2>
        <div className="grid gap-2.5">
          {items.map((item, index) => {
            const isOpen = openIndex === index;
            const question = getLocalized(item.question, language);
            const answer = getLocalized(item.answer, language);

            return (
            <article key={question || index} className={`rounded-xl border border-line bg-subtle/70 ${cardHoverClass}`}>
                <button
                  type="button"
                  className="flex w-full items-center justify-between gap-4 px-4 py-3 text-left text-sm font-bold text-ink transition hover:text-secondary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary sm:text-base"
                  aria-expanded={isOpen}
                  onClick={() => setOpenIndex(isOpen ? -1 : index)}
                >
                  <span>{question}</span>
                  <ChevronDown className={`h-4 w-4 shrink-0 text-secondary transition ${isOpen ? 'rotate-180' : ''}`} />
                </button>
                {isOpen ? (
                  <p className="whitespace-pre-line px-4 pb-4 text-sm font-semibold leading-6 text-muted">
                    {answer}
                  </p>
                ) : null}
              </article>
            );
          })}
        </div>
      </div>
    </section>
  );
}
