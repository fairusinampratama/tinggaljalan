import { useEffect, useMemo } from 'react';
import { Link as InertiaLink, router, usePage } from '@inertiajs/react';

function normalizeHref(value = '/') {
  if (typeof value === 'string') {
    return value;
  }

  return value?.pathname ?? '/';
}

export function Link({ to, href, children, ...props }) {
  return (
    <InertiaLink href={normalizeHref(href ?? to)} {...props}>
      {children}
    </InertiaLink>
  );
}

export function NavLink({ to, href, className, children, ...props }) {
  const { url } = usePage();
  const target = normalizeHref(href ?? to);
  const currentPath = url.split('?')[0].split('#')[0] || '/';
  const targetPath = target.split('?')[0].split('#')[0] || '/';
  const isActive = targetPath === '/' ? currentPath === '/' : currentPath.startsWith(targetPath);
  const resolvedClassName = typeof className === 'function' ? className({ isActive }) : className;

  return (
    <InertiaLink href={target} className={resolvedClassName} {...props}>
      {children}
    </InertiaLink>
  );
}

export function useNavigate() {
  return (to, options = {}) => {
    router.visit(normalizeHref(to), {
      replace: Boolean(options.replace),
      preserveScroll: Boolean(options.preserveScroll),
      preserveState: Boolean(options.preserveState),
    });
  };
}

export function useLocation() {
  const { url } = usePage();

  return useMemo(() => {
    const parsed = new URL(url, window.location.origin);

    return {
      pathname: parsed.pathname,
      search: parsed.search,
      hash: parsed.hash,
    };
  }, [url]);
}

export function useParams() {
  const { props, url } = usePage();
  const segments = url.split('?')[0].split('/').filter(Boolean);

  return {
    routeId: props.route?.id ?? props.route?.slug ?? segments.at(-1),
    articleSlug: props.article?.slug ?? segments.at(-1),
    slug: segments.at(-1),
  };
}

export function useSearchParams() {
  const { url } = usePage();
  const location = useLocation();
  const params = useMemo(() => new URLSearchParams(location.search), [location.search]);

  function setSearchParams(nextParams, options = {}) {
    const normalized = nextParams instanceof URLSearchParams ? nextParams : new URLSearchParams(nextParams);
    const query = normalized.toString();

    router.get(`${location.pathname}${query ? `?${query}` : ''}`, {}, {
      preserveScroll: true,
      preserveState: true,
      replace: options.replace ?? true,
    });
  }

  return [params, setSearchParams];
}

export function Navigate({ to, replace = false }) {
  useEffect(() => {
    router.visit(normalizeHref(to), { replace });
  }, [replace, to]);

  return null;
}

export function BrowserRouter({ children }) {
  return children;
}

export function Routes({ children }) {
  return children;
}

export function Route() {
  return null;
}

export function Outlet() {
  return null;
}
