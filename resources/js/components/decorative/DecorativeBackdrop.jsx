import landscape from '../../../images/decorative/home-landscape.svg';
import botanical from '../../../images/decorative/home-botanical.svg';

/** A white, non-interactive canvas. Only the home composition exists for now. */
export function DecorativeBackdrop({ variant = 'home', children }) {
  return (
    <div className="decorative-canvas" data-backdrop={variant}>
      {variant === 'home' ? (
        <div className="decorative-backdrop" aria-hidden="true">
          <span className="decorative-backdrop__landscape" style={{ '--motif': `url("${landscape}")` }} />
          <span className="decorative-backdrop__botanical" style={{ '--motif': `url("${botanical}")` }} />
        </div>
      ) : null}
      <div className="decorative-canvas__content">{children}</div>
    </div>
  );
}
