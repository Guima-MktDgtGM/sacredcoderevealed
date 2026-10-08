<?php
// ============================================================
//  CLOAKER INTELIGENTE — Página da Oferta (Mini VSL)
//  Detecta bots/revisores e serve a White Page limpa.
//  Clientes legítimos vindos de anúncios visualizam a Oferta/VSL.
// ============================================================

define('SALES_PAGE',    'offer.html');    // Página de destino real (Black Page: Oferta)
define('CLEAN_PAGE',    'clean.html');    // Página de aprovação (White Page Editorial)
define('SECRET_BYPASS', 'fiel2026');     // URL com ?bypass=fiel2026 abre a black sempre
define('LOG_ENABLED',   false);

// 1. Bypass Manual
if (isset($_GET['bypass']) && $_GET['bypass'] === SECRET_BYPASS) {
    setcookie('_fiel_ok', '1', time() + 86400 * 7, '/');
    serve_sales();
    exit;
}

// 2. Cookie de humano já validado
if (!empty($_COOKIE['_fiel_ok'])) {
    serve_sales();
    exit;
}

// 3. Parâmetros de anúncio
$has_ad_tracking = isset($_GET['fbclid']) ||
                   isset($_GET['gclid']) ||
                   isset($_GET['ttclid']) ||
                   isset($_GET['utm_source']) ||
                   isset($_GET['utm_campaign']) ||
                   isset($_GET['src']) ||
                   isset($_GET['sck']);

if (!$has_ad_tracking) {
    serve_clean();
    exit;
}

$ua   = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$ip   = get_real_ip();
$ref  = strtolower($_SERVER['HTTP_REFERER'] ?? '');
$lang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');

// Camada 1: User-Agent
$bot_agents = [
    'facebookexternalhit', 'facebot', 'facebookcatalog', 'meta-externalagent',
    'facebookplatform', 'meta-externalhit', 'googlebot', 'adsbot-google',
    'mediapartners-google', 'semrushbot', 'ahrefsbot', 'headlesschrome',
    'phantomjs', 'selenium', 'webdriver', 'puppeteer', 'playwright',
    'python-requests', 'wget', 'curl'
];

foreach ($bot_agents as $bot) {
    if (strpos($ua, $bot) !== false) {
        serve_clean();
        exit;
    }
}

if (empty($ua) || strlen($ua) < 20) {
    serve_clean();
    exit;
}

// Camada 2: Datacenter IPs
$dc_ranges = [
    '31.13.', '66.220.', '69.63.', '69.171.', '74.119.', '102.132.',
    '103.4.96.', '129.134.', '157.240.', '163.70.', '173.252.', '179.60.',
    '185.60.', '204.15.', '34.', '35.', '64.18.', '64.233.', '66.102.',
    '66.249.', '72.14.', '74.125.', '108.177.', '142.250.', '172.217.',
    '173.194.', '209.85.', '216.58.', '3.', '13.', '18.', '44.', '52.',
    '54.', '20.', '40.', '51.', '65.52.', '104.16.', '104.17.', '104.18.',
    '104.19.', '104.20.', '104.21.', '104.22.', '104.23.', '104.24.',
    '104.25.', '104.26.', '104.27.', '104.28.', '172.64.', '172.65.',
    '172.66.', '172.67.', '172.68.', '172.69.', '172.70.', '188.114.'
];

foreach ($dc_ranges as $range) {
    if (strpos($ip, $range) === 0) {
        serve_clean();
        exit;
    }
}

// Camada 3: Idiomas
$suspicious_langs = ['zh-cn', 'zh-tw', 'ko-kr', 'ja-jp', 'ar-sa', 'ru-ru', 'uk-ua'];
foreach ($suspicious_langs as $sl) {
    if (strpos($lang, $sl) === 0) {
        serve_clean();
        exit;
    }
}

// Camada 4: Referers
$suspicious_refs = ['facebook.com/ads', 'business.facebook.com', 'ads.google.com', 'semrush', 'ahrefs', 'adbeat', 'adplexity'];
foreach ($suspicious_refs as $sr) {
    if (strpos($ref, $sr) !== false) {
        serve_clean();
        exit;
    }
}

// Validado
setcookie('_fiel_ok', '1', time() + 86400 * 3, '/');
serve_sales();
exit;

function serve_sales() {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    $file = __DIR__ . '/' . SALES_PAGE;
    if (file_exists($file)) readfile($file);
    else echo 'Página não encontrada.';
}

function serve_clean() {
    header('Cache-Control: public, max-age=86400');
    $file = __DIR__ . '/' . CLEAN_PAGE;
    if (file_exists($file)) readfile($file);
    else readfile(__DIR__ . '/clean.html');
}

function get_real_ip() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
