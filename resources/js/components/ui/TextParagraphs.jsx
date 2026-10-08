import { Fragment } from 'react';
import { textParagraphs } from '../../utils/text';

export function TextParagraphs({ text, className }) {
  return textParagraphs(text).map((paragraph, index) => (
    <p key={index} className={className}>
      {paragraph.split('\n').map((line, lineIndex) => (
        <Fragment key={lineIndex}>{lineIndex > 0 ? <br /> : null}{line}</Fragment>
      ))}
    </p>
  ));
}
