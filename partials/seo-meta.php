<?php
declare(strict_types=1);

/**
 * Overrides opcionais por página (setar ANTES de incluir este partial):
 * $pageTitle, $pageDescription, $pageCanonical, $pageBreadcrumbName,
 * $pageServiceSchema (array de Schema.org Service, ou null),
 * $pageVideoSchema (array de Schema.org VideoObject, ou null),
 * $pageProductSchema (array de Schema.org Product, ou null),
 * $pagePostSchema (array de Schema.org BlogPosting, ou null),
 * $pageBreadcrumbTrail (array de ['name' => ..., 'url' => ...], pra
 * breadcrumbs com 2+ níveis — ex: Home > Blog > Post. Tem prioridade sobre
 * $pageBreadcrumbName quando definido),
 * $pageFaqItems (array de ['question' => ..., 'answer' => ...], ou null —
 * vira Schema.org FAQPage automaticamente).
 */
$pageTitle          = $pageTitle ?? SITE_TITLE;
$pageDescription    = $pageDescription ?? SITE_DESCRIPTION;
$pageCanonical      = $pageCanonical ?? (SITE_URL . '/');
$pageBreadcrumbName  = $pageBreadcrumbName ?? null;
$pageBreadcrumbTrail = $pageBreadcrumbTrail ?? null;
$pageServiceSchema  = $pageServiceSchema ?? null;
$pageVideoSchema    = $pageVideoSchema ?? null;
$pageProductSchema  = $pageProductSchema ?? null;
$pagePostSchema     = $pagePostSchema ?? null;
$pageFaqItems       = $pageFaqItems ?? null;

$faqSchema = null;
if ($pageFaqItems !== null) {
    $faqSchema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(
            static fn (array $item): array => [
                '@type'          => 'Question',
                'name'           => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $item['answer'],
                ],
            ],
            $pageFaqItems
        ),
    ];
}

$organizationSchema = [
    '@context'     => 'https://schema.org',
    '@type'        => 'Organization',
    '@id'          => SITE_URL . '/#organization',
    'name'         => SITE_NAME,
    'url'          => SITE_URL,
    'description'  => SITE_DESCRIPTION,
    'foundingDate' => (string) FOUNDING_YEAR,
    'contactPoint' => [
        '@type'             => 'ContactPoint',
        'telephone'         => CONTACT_PHONE_TEL,
        'email'             => CONTACT_EMAIL,
        'contactType'       => 'customer service',
        'areaServed'        => 'BR',
        'availableLanguage' => ['pt-BR'],
    ],
    'sameAs' => array_values(array_filter(SOCIAL_LINKS, static fn (string $url): bool => $url !== '#')),
];

$localBusinessSchema = [
    '@context'   => 'https://schema.org',
    '@type'      => 'LocalBusiness',
    '@id'        => SITE_URL . '/#local-business',
    'name'       => SITE_NAME,
    'image'      => SITE_URL . '/og-cover.jpg',
    'telephone'  => CONTACT_PHONE_TEL,
    'email'      => CONTACT_EMAIL,
    'address'    => [
        '@type'           => 'PostalAddress',
        'addressLocality' => CONTACT_CITY,
        'addressRegion'   => CONTACT_REGION,
        'addressCountry'  => CONTACT_COUNTRY,
    ],
    'priceRange' => '$$',
];

$breadcrumbItems = [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
];
if ($pageBreadcrumbTrail !== null) {
    foreach ($pageBreadcrumbTrail as $i => $crumb) {
        $breadcrumbItems[] = [
            '@type'    => 'ListItem',
            'position' => $i + 2,
            'name'     => $crumb['name'],
            'item'     => $crumb['url'],
        ];
    }
} elseif ($pageBreadcrumbName !== null) {
    $breadcrumbItems[] = [
        '@type'    => 'ListItem',
        'position' => 2,
        'name'     => $pageBreadcrumbName,
        'item'     => $pageCanonical,
    ];
}
$breadcrumbSchema = [
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
];
?>
<meta charset="UTF-8">
<!-- Google Tag Manager + GA4 — o dataLayer/gtag() ficam prontos na hora
     (qualquer evento chamado antes do script físico carregar só fica na
     fila), mas o carregamento de verdade dos scripts do GTM/GA (que
     brigam por banda/conexão com o CSS bem na hora crítica da primeira
     pintura) é adiado pro fim do load da página, ou pra primeira
     interação do usuário — o que vier primeiro. -->
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-69W8DMGG3B');

(function () {
  var loaded = false;
  function loadAnalytics() {
    if (loaded) return;
    loaded = true;
    (function (w, d, s, l, i) {
      w[l] = w[l] || [];
      w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
      var f = d.getElementsByTagName(s)[0], j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
      j.async = true;
      j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
      f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-WQJVN5JZ');

    var gtagScript = document.createElement('script');
    gtagScript.async = true;
    gtagScript.src = 'https://www.googletagmanager.com/gtag/js?id=G-69W8DMGG3B';
    document.head.appendChild(gtagScript);

    (function (c, l, a, r, i, t, y) {
      c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); };
      t = l.createElement(r); t.async = 1; t.src = 'https://www.clarity.ms/tag/' + i;
      y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y);
    })(window, document, 'clarity', 'script', 'y3vf44nmhf');
  }

  if (document.readyState === 'complete') {
    loadAnalytics();
  } else {
    window.addEventListener('load', loadAnalytics);
  }
  ['scroll', 'keydown', 'mousemove', 'touchstart'].forEach(function (evt) {
    window.addEventListener(evt, loadAnalytics, { once: true, passive: true });
  });
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
<meta name="theme-color" content="<?= SITE_THEME_COLOR ?>">
<meta name="robots" content="index, follow, max-image-preview:large">
<link rel="canonical" href="<?= htmlspecialchars($pageCanonical, ENT_QUOTES, 'UTF-8') ?>">
<link rel="sitemap" type="application/xml" href="/sitemap.xml">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:locale" content="<?= SITE_LOCALE ?>">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:url" content="<?= htmlspecialchars($pageCanonical, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image" content="<?= htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8') ?>/og-cover.jpg">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:image" content="<?= htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8') ?>/og-cover.jpg">

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon-16x16.png" type="image/png" sizes="16x16">
<link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32">
<link rel="icon" href="/favicon.png" type="image/png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">

<script type="application/ld+json"><?= json_encode($organizationSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/ld+json"><?= json_encode($localBusinessSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/ld+json"><?= json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php if ($pageServiceSchema !== null): ?>
<script type="application/ld+json"><?= json_encode($pageServiceSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<?php if ($pageVideoSchema !== null): ?>
<script type="application/ld+json"><?= json_encode($pageVideoSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<?php if ($pageProductSchema !== null): ?>
<script type="application/ld+json"><?= json_encode($pageProductSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<?php if ($pagePostSchema !== null): ?>
<script type="application/ld+json"><?= json_encode($pagePostSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<?php if ($faqSchema !== null): ?>
<script type="application/ld+json"><?= json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
