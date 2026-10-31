<!-- To be reviewed: template in the spirit of Québec's Law 25, to be adapted by EliteTap. -->
<section class="page container narrow">
  <h1>Privacy policy</h1>
  <p>Last updated: <?= date('Y-m-d', filemtime(__FILE__)) ?></p>

  <h2>Who is responsible</h2>
  <p>EliteTap, Laval (Québec). Person in charge of the protection of personal information: [NAME], reachable at <a href="mailto:info@elitetap.ca">info@elitetap.ca</a>.</p>

  <h2>Information we collect</h2>
  <p><strong>Quote requests:</strong> name, company, email, phone, card type, message and attached file, to prepare your quote and order.</p>
  <p><strong>EliteTap profile:</strong> the information you choose to display on your public profile (name, title, contact details, photo, links). It is visible to anyone who taps your card or opens your link.</p>
  <p><strong>Statistics:</strong> when a profile is opened, we record the date, the type of action (open, contact saved, link clicked) and an anonymous identifier that changes every day. We do not keep visitors' IP addresses.</p>
  <p><strong>Online payment:</strong> payments are processed by Stripe. EliteTap never sees or stores your card number.</p>

  <p><strong>Leads:</strong> when a visitor fills in a profile's "Leave your details" form and ticks the consent box, their name, contact details and message are passed on to the profile owner. EliteTap keeps them on behalf of that owner for at most <?= (int) config('lead_retention_days', 730) ?> days. The consent wording and its date are recorded. To have this information deleted, write to the profile owner or to <a href="mailto:info@elitetap.ca">info@elitetap.ca</a>.</p>

  <h2>Use</h2>
  <p>This information is only used to produce your cards, display your profile, provide your statistics and contact you about your order. It is never sold or rented.</p>

  <h2>Retention and hosting</h2>
  <p>Data is hosted in Canada [TO CONFIRM WITH THE HOST]. Quote requests that do not lead to an order are deleted after [DURATION]. Your profile is kept as long as your account is active.</p>

  <h2>Your rights</h2>
  <p>You can access, correct or delete your information, make your profile private from your client space, or ask us to close your account by writing to <a href="mailto:info@elitetap.ca">info@elitetap.ca</a>.</p>

  <h2>Cookies</h2>
  <p>The site uses only technical cookies: one to keep you signed in to your client space and one to remember your language. No advertising or third-party tracking cookies.</p>
</section>
