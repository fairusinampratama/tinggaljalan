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
    <section id="faq" className="bg-white px-4 py-12 sm:px-8 lg:px-10">
      <div className="mx-auto max-w-4xl">
        <h2 className="mb-6 text-center font-display text-[1.625rem] font-bold leading-tight text-brandDark sm:text-[2rem]">
          {title}
        </h2>
        <div className="grid gap-2.5">
          {items.map((item, index) => {
            const isOpen = openIndex === index;
            const question = getLocalized(item.question, language);
            const answer = getLocalized(item.answer, language);

            return (
            <article key={question || index} className={`rounded-xl border border-brandLine bg-brandLight ${cardHoverClass}`}>
                <button
                  type="button"
                  className="flex w-full items-center justify-between gap-4 px-4 py-3 text-left text-sm font-bold text-brandDark transition hover:text-brandBlue focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brandBlue sm:text-base"
                  aria-expanded={isOpen}
                  onClick={() => setOpenIndex(isOpen ? -1 : index)}
                >
                  <span>{question}</span>
                  <ChevronDown className={`h-4 w-4 shrink-0 text-brandBlue transition ${isOpen ? 'rotate-180' : ''}`} />
                </button>
                {isOpen ? (
                  <p className="whitespace-pre-line px-4 pb-4 text-sm font-semibold leading-6 text-brandMuted">
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
