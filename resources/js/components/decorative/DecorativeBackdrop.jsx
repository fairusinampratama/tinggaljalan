import orangutan from '../../../images/decorative/orangutan-canopy.svg';
import foliage from '../../../images/decorative/foliage-sumatra-java.svg';
import flow from '../../../images/decorative/regional-flow.svg';
import band from '../../../images/decorative/regional-band.svg';
import mobile from '../../../images/decorative/regional-mobile.svg';

const motif = (asset) => ({ '--motif': `url("${asset}")` });

/** A bounded section composition: content can grow without scaling the artwork. */
export function DecorativeBackdrop({ variant = 'home', children }) {
  const quiet = variant === 'transaction';
  return (
    <div className="decorative-canvas" data-backdrop={variant}>
      <div className="decorative-backdrop" aria-hidden="true">
        {!quiet && variant !== 'continuation' ? <span className="decorative-backdrop__orangutan" style={motif(orangutan)} /> : null}
        {!quiet ? <span className="decorative-backdrop__foliage" style={motif(foliage)} /> : null}
        {!quiet ? <span className="decorative-backdrop__flow" style={motif(flow)} /> : null}
        <span className="decorative-backdrop__mobile" style={motif(mobile)} />
        {!quiet && variant !== 'detail' ? <span className="decorative-backdrop__band" style={motif(band)} /> : null}
      </div>
      <div className="decorative-canvas__content">{children}</div>
    </div>
  );
}

/** Follows the gallery in document flow, so variable titles/images move it naturally. */
export function DecorativeDivider() {
  return <div className="decorative-divider" aria-hidden="true" style={motif(band)} />;
}
