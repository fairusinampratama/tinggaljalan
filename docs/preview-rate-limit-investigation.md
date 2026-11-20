# Hosted preview HTTP 429 investigation

## Confirmed evidence

Read-only run [44686189159](https://github.com/fairusinampratama/tinggaljalan/actions/runs/44686189159), job 136120968613, reproduced the News startup failure on 20 November 2026 at 06:16:11 UTC / 13:16:11 WIB. Both API authentication modes and the browser's document request returned HTTP 200. Thirteen script responses and one stylesheet returned 200; one script returned **429**. The browser reported a module-loading failure, React did not start, and readable server content remained. Initial translations were present and network idle passed.

This establishes rate limiting of an application asset as the cause of that observed failure. It does not prove that every historic hosted failure shared the cause, or identify the exact emitting proxy/server. The deployed application defines a throttle only on the contact endpoint; no reviewed application rule rate-limits static JavaScript assets. Production was independently observed healthy at 1ad6e95.

Hostinger documents [server-level limits affecting automated cloud-network traffic](https://www.hostinger.com/support/429-errors-on-automated-integrations-and-link-previews/) and recommends backoff. Its [CDN 429 guide](https://www.hostinger.com/support/hostinger-cdn-429-too-many-requests-errors/) distinguishes server, application and CDN limits. These documents support a hosting-path explanation but do not identify the specific layer for this request. No hosting security setting, CDN setting or traffic limit has been changed.

## Controller mitigation

Only read-only preview GET navigation retries when its own same-origin document, script or stylesheet actually returns 429. Maximum: three fresh-document attempts, default delays 10 and 20 seconds. Retry-After can lengthen those waits up to 60 seconds; a larger requested cooldown fails closed rather than retrying early. Unrelated errors and third-party throttling do not authorize retries. The final successful document must complete the app handoff and every existing semantic/functional release assertion. Persistent throttling still fails and invokes existing rollback.

Failed throttled GET attempts are recorded as fixed attempt/status/delay evidence. Existing visual errors from earlier pages remain failures; only observations belonging to the discarded navigation attempt are removed before its fresh-document retry. Booking, payment, login and other submissions are never replayed. Production verification retains its existing behavior.

The Prepare workflow checks the **currently deployed** preview Routes and News hubs after interrupted-publication recovery and before the full candidate build. This is environment readiness, not candidate verification. Full candidate CI and post-publication desktop/mobile, media, crawler, booking/admin, integration, revision and production-health checks remain mandatory.

## Verification and limits

Node regressions cover one transient 429, persistent document throttling, Retry-After, long-cooldown refusal, foreign-origin rejection, listener cleanup and no retry for unrelated failures. An intercepted real-browser regression requires a module's first 429 to leave the server fallback present, then a new document with HTTP 200 and a successful handoff before acceptance. No real hosting request or application submission is made by that fixture.

The controller mitigation must pass CI before merge, then the protected read-only probe should be exercised on main. A passing live attempt without a 429 does not prove recovery occurred. A rate-limit retry plus a successful final handoff proves recovery for that occurrence, not removal of Hostinger's rate limit. Provider-side attribution requires response/request identifiers or hosting logs and remains separate follow-up if throttling persists.
