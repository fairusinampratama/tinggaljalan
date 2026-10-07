import kawung from '../../../images/decorative/kawung.svg';
import mangosteen from '../../../images/decorative/tampuk-manggis.svg';
import woven from '../../../images/decorative/sumba-woven.svg';
import orangutan from '../../../images/decorative/orangutan-foliage.webp';

/** Regional interpretations, confined to the edges of a white public-page canvas. */
export function DecorativeBackdrop({ variant = 'home', children }) {
  const quiet = variant === 'transaction';
  return (
    <div className="decorative-canvas" data-backdrop={variant}>
      <div className="decorative-backdrop" aria-hidden="true">
        {!quiet ? <img className="decorative-backdrop__orangutan" src={orangutan} alt="" loading="lazy" decoding="async" width="640" height="640" /> : null}
        <span className="decorative-backdrop__kawung" style={{ '--motif': `url("${kawung}")` }} />
        {!quiet ? <span className="decorative-backdrop__mangosteen" style={{ '--motif': `url("${mangosteen}")` }} /> : null}
        <span className="decorative-backdrop__woven" style={{ '--motif': `url("${woven}")` }} />
      </div>
      <div className="decorative-canvas__content">{children}</div>
    </div>
  );
}
