import { test, expect, type Page } from '@playwright/test';

/*
  The head, as a browser actually ends up with it.

  Worth an e2e rather than a unit test: the failure this guards against is
  not a wrong string, it is a DUPLICATE tag. The head used to be built by
  teleporting a second <title> and a second og:* set next to the ones
  index.html ships, and a document's title is defined as the text of the
  FIRST <title> element — so the static default always won and no page
  ever changed the tab. Only a real DOM shows that.
*/

const SITE = 'https://evrst.hu';

const count = (page: Page, selector: string) => page.locator(selector).count();

const content = (page: Page, selector: string) =>
  page.locator(selector).first().getAttribute('content');

/** Every tag a crawler reads must appear exactly once in the document. */
const expectNoDuplicates = async (page: Page) => {
  for (const selector of [
    'head title',
    'head meta[name="description"]',
    'head meta[name="robots"]',
    'head meta[property="og:title"]',
    'head meta[property="og:description"]',
    'head meta[property="og:image"]',
    'head meta[property="og:url"]',
    'head meta[name="twitter:card"]',
    'head link[rel="canonical"]',
  ]) {
    expect(await count(page, selector), `${selector} should appear once`).toBe(1);
  }
};

test('the home page carries its own title, description and canonical', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('main')).not.toBeEmpty();

  await expectNoDuplicates(page);

  // Not the static index.html default — proof the app rewrote the tag
  // rather than appending a second one behind it.
  await expect(page).toHaveTitle(/rakétacsapat|rocketry team/i);
  await expect(page).toHaveTitle(/Escape Velocity Rocketry Student Team$/);

  expect(await page.locator('link[rel="canonical"]').getAttribute('href')).toBe(`${SITE}/`);
  expect(await content(page, 'meta[property="og:url"]')).toBe(`${SITE}/`);
  expect(await content(page, 'meta[property="og:image"]')).toBe(`${SITE}/share.jpg`);
  expect(await content(page, 'meta[name="twitter:card"]')).toBe('summary_large_image');
  expect(await content(page, 'meta[name="robots"]')).toContain('index');
  expect(await content(page, 'meta[name="robots"]')).not.toContain('noindex');
  expect((await content(page, 'meta[name="description"]'))?.length).toBeGreaterThan(50);
});

test('a localized route canonicalises to its own spelling and names the other', async ({ page }) => {
  await page.goto('/csatlakozz');
  await expect(page.locator('main')).not.toBeEmpty();

  await expectNoDuplicates(page);

  expect(await page.locator('link[rel="canonical"]').getAttribute('href')).toBe(
    `${SITE}/csatlakozz`,
  );
  expect(await page.locator('link[rel="alternate"][hreflang="hu"]').getAttribute('href')).toBe(
    `${SITE}/csatlakozz`,
  );
  expect(await page.locator('link[rel="alternate"][hreflang="en"]').getAttribute('href')).toBe(
    `${SITE}/join-us`,
  );
  expect(
    await page.locator('link[rel="alternate"][hreflang="x-default"]').getAttribute('href'),
  ).toBe(`${SITE}/csatlakozz`);

  // The path decides the language, so /csatlakozz is Hungarian whatever
  // the stored preference was — and the document has to say so.
  expect(await page.getAttribute('html', 'lang')).toBe('hu');
  expect(await content(page, 'meta[property="og:locale"]')).toBe('hu_HU');
});

test('the english spelling is the same page under a different canonical', async ({ page }) => {
  await page.goto('/join-us');
  await expect(page.locator('main')).not.toBeEmpty();

  expect(await page.locator('link[rel="canonical"]').getAttribute('href')).toBe(`${SITE}/join-us`);
  expect(await page.getAttribute('html', 'lang')).toBe('en');
  expect(await content(page, 'meta[property="og:locale"]')).toBe('en_GB');
});

test('the head follows a client-side navigation', async ({ page }) => {
  await page.goto('/csatlakozz');
  await expect(page.locator('main')).not.toBeEmpty();
  const joinTitle = await page.title();

  await page.goto('/');
  await expect(page.locator('main')).not.toBeEmpty();

  // Navigating away has to take the previous page's head with it — the
  // home page had no HtmlTitle of its own and used to inherit whatever
  // the last page set.
  expect(await page.title()).not.toBe(joinTitle);
  await expectNoDuplicates(page);
});

test('an unknown path is a soft 404 and says noindex', async ({ page }) => {
  // try_files answers index.html with a 200 for anything unmatched, so the
  // only way to keep this out of an index is the robots tag.
  await page.goto('/nincs-ilyen-oldal-123');
  await expect(page.locator('main')).not.toBeEmpty();

  expect(await content(page, 'meta[name="robots"]')).toContain('noindex');
  await expectNoDuplicates(page);
});

test('robots.txt and sitemap.xml are real files, not the SPA fallback', async ({ request }) => {
  const robots = await request.get('/robots.txt');
  expect(robots.status()).toBe(200);
  expect(await robots.text()).toContain('Sitemap: https://evrst.hu/sitemap.xml');

  const sitemap = await request.get('/sitemap.xml');
  expect(sitemap.status()).toBe(200);
  const xml = await sitemap.text();
  expect(xml).toContain('<loc>https://evrst.hu/</loc>');
  expect(xml).toContain('<loc>https://evrst.hu/csatlakozz</loc>');
});

test('the share image exists and is a real jpeg', async ({ request }) => {
  const res = await request.get('/share.jpg');
  expect(res.status()).toBe(200);
  expect(res.headers()['content-type']).toContain('image/jpeg');
  expect((await res.body()).length).toBeGreaterThan(5000);
});
