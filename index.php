<?php
// ============================================================
//  CLOAKER INTELIGENTE — O Manuscrito Sagrado (Lar da Fé)
//  Detecta bots/revisores do Facebook Ads e serve a White Page limpa.
//  Clientes legítimos vindos de anúncios visualizam o Quiz/Oferta.
// ============================================================

// --- CONFIGURAÇÕES BÁSICAS ---
define('SALES_PAGE',    'index.html');    // Página de destino real (Black Page: Quiz)
define('CLEAN_PAGE',    'clean.html');    // Página de aprovação (White Page Editorial)
define('SECRET_BYPASS', 'fiel2026');     // URL com ?bypass=fiel2026 abre a black sempre
define('LOG_ENABLED',   false);           // true = salva log para depuração em cloaker_log.txt

// --- 1. REGRA SUPREMA: BYPASS MANUAL VIA URL ---
if (isset($_GET['bypass']) && $_GET['bypass'] === SECRET_BYPASS) {
    setcookie('_fiel_ok', '1', time() + 86400 * 7, '/');
    serve_sales();
    exit;
}

// --- 2. REGRA SUPREMA: COOKIE ATIVO (Usuário já validado anteriormente) ---
if (!empty($_COOKIE['_fiel_ok'])) {
    serve_sales();
    exit;
}

// --- 3. FILTRO POR PARÂMETROS DE ANÚNCIO (Facebook / Google / UTMs) ---
// Bots e revisores manuais sem parâmetros de campanha caem direto na White Page
$has_ad_tracking = isset($_GET['fbclid']) ||
                   isset($_GET['gclid']) ||
                   isset($_GET['ttclid']) ||
                   isset($_GET['utm_source']) ||
                   isset($_GET['utm_campaign']) ||
                   isset($_GET['src']) ||
                   isset($_GET['sck']);

if (!$has_ad_tracking) {
    log_visit(get_real_ip(), $_SERVER['HTTP_USER_AGENT'] ?? '', 'NO_TRACKING_PARAMS');
    serve_clean();
    exit;
}

$ua   = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$ip   = get_real_ip();
$ref  = strtolower($_SERVER['HTTP_REFERER'] ?? '');
$lang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');

// ============================================================
//  CAMADA 1 — User-Agent de Bots, Revisores e Crawlers
// ============================================================
$bot_agents = [
    // Meta / Facebook
    'facebookexternalhit', 'facebot', 'facebookcatalog', 'meta-externalagent',
    'facebookplatform', 'meta-externalhit',
    // Google
    'googlebot', 'adsbot-google', 'mediapartners-google', 'apis-google', 'feedfetcher',
    // Outros buscadores e bots
    'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot', 'applebot',
    // Crawlers e ferramentas de auditoria/espionagem
    'semrushbot', 'ahrefsbot', 'mj12bot', 'dotbot', 'petalbot', 'screaming frog',
    'rogerbot', 'exabot', 'ia_archiver', 'archive.org_bot',
    // Bibliotecas de script e automação
    'wget', 'curl', 'python-requests', 'go-http-client', 'java/', 'libwww',
    'scrapy', 'httpclient', 'guzzle', 'okhttp', 'apache-httpclient', 'axios',
    'node-fetch', 'undici',
    // Monitoramento e headless browsers
    'adbeat', 'brand-checker', 'check_http', 'pingdom', 'uptimerobot',
    'headlesschrome', 'phantomjs', 'selenium', 'webdriver', 'puppeteer',
    'playwright', 'nightmarejs', 'slimerjs', 'zgrab', 'masscan', 'nmap'
];

foreach ($bot_agents as $bot) {
    if (strpos($ua, $bot) !== false) {
        log_visit($ip, $ua, 'BOT_UA:' . $bot);
        serve_clean();
        exit;
    }
}

// User-Agent ausente ou muito curto
if (empty($ua) || strlen($ua) < 20) {
    log_visit($ip, $ua, 'EMPTY_OR_SHORT_UA');
    serve_clean();
    exit;
}

// ============================================================
//  CAMADA 2 — Faixas de IP de Datacenters e Redes de Monitoramento
// ============================================================
$dc_ranges = [
    // Facebook / Meta Ranges
    '31.13.', '66.220.', '69.63.', '69.171.', '74.119.', '102.132.',
    '103.4.96.', '129.134.', '157.240.', '163.70.', '173.252.', '179.60.',
    '185.60.', '204.15.',
    // Google Cloud / Crawlers
    '34.64.', '34.65.', '34.80.', '34.96.', '34.100.', '34.102.',
    '34.104.', '34.116.', '34.140.', '34.142.', '34.143.', '34.144.',
    '35.184.', '35.185.', '35.186.', '35.187.', '35.188.', '35.189.',
    '35.190.', '35.191.', '35.192.', '35.193.', '35.194.', '35.195.',
    '35.196.', '35.197.', '35.198.', '35.199.', '35.200.', '35.201.',
    '35.202.', '35.203.', '35.204.', '35.205.', '35.206.', '35.207.',
    '64.18.', '64.233.', '66.102.', '66.249.', '72.14.', '74.125.',
    '108.177.', '142.250.', '172.217.', '173.194.', '209.85.', '216.58.', '216.239.',
    // Amazon AWS
    '3.', '13.', '18.', '34.', '35.', '44.', '52.', '54.',
    // Microsoft Azure
    '13.64.', '13.65.', '13.66.', '13.67.', '13.68.', '13.69.',
    '20.', '40.', '51.', '52.', '65.52.',
    // Cloudflare Datacenter ranges
    '1.1.1.', '1.0.0.', '104.16.', '104.17.', '104.18.', '104.19.',
    '104.20.', '104.21.', '104.22.', '104.23.', '104.24.', '104.25.',
    '104.26.', '104.27.', '104.28.', '172.64.', '172.65.', '172.66.',
    '172.67.', '172.68.', '172.69.', '172.70.', '188.114.',
    // DigitalOcean / Hetzner / OVH / Linode
    '104.131.', '104.236.', '107.170.', '128.199.', '138.197.',
    '139.59.', '142.93.', '143.110.', '159.65.', '159.203.',
    '162.243.', '165.227.', '167.99.', '174.138.', '178.62.',
    '192.241.', '198.199.', '206.189.', '45.33.', '45.56.', '45.79.',
    '96.126.', '172.104.', '136.243.', '144.76.', '148.251.', '168.119.'
];

foreach ($dc_ranges as $range) {
    if (strpos($ip, $range) === 0) {
        log_visit($ip, $ua, 'DC_IP:' . $range);
        serve_clean();
        exit;
    }
}

// ============================================================
//  CAMADA 3 — Idiomas Incompatíveis com o Público-Alvo
// ============================================================
$suspicious_langs = ['zh-cn', 'zh-tw', 'ko-kr', 'ja-jp', 'ar-sa', 'ru-ru', 'uk-ua'];
foreach ($suspicious_langs as $sl) {
    if (strpos($lang, $sl) === 0) {
        log_visit($ip, $ua, 'SUSP_LANG:' . $sl);
        serve_clean();
        exit;
    }
}

// ============================================================
//  CAMADA 4 — Referers Suspeitos (Painéis de Análise e Auditoria)
// ============================================================
$suspicious_refs = [
    'facebook.com/ads', 'business.facebook.com', 'ads.google.com',
    'adspector', 'adbeat', 'moat.com', 'ad-score', 'whotracked',
    'builtwith', 'similarweb', 'semrush', 'ahrefs', 'moz.com',
    'adplexity', 'anstrex'
];

foreach ($suspicious_refs as $sr) {
    if (strpos($ref, $sr) !== false) {
        log_visit($ip, $ua, 'SUSP_REF:' . $sr);
        serve_clean();
        exit;
    }
}

// ============================================================
//  PASSOU EM TODOS OS FILTROS — USUÁRIO HUMANO REAL VALIDADO
// ============================================================
setcookie('_fiel_ok', '1', time() + 86400 * 3, '/');
log_visit($ip, $ua, 'HUMAN_PASSED');
serve_sales();
exit;

// ============================================================
//  FUNÇÕES DE ENTREGA DE CONTEÚDO
// ============================================================

function serve_sales() {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    $file = __DIR__ . '/' . SALES_PAGE;
    if (file_exists($file)) {
        readfile($file);
    } else {
        echo 'Página não encontrada.';
    }
}

function serve_clean() {
    header('Cache-Control: public, max-age=86400');
    $file = __DIR__ . '/' . CLEAN_PAGE;
    if (file_exists($file)) {
        readfile($file);
    } else {
        readfile(__DIR__ . '/clean.html');
    }
}

function get_real_ip() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
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

function log_visit($ip, $ua, $reason) {
    if (!LOG_ENABLED) return;
    $line = date('Y-m-d H:i:s') . " | {$reason} | {$ip} | {$ua}\n";
    file_put_contents(__DIR__ . '/cloaker_log.txt', $line, FILE_APPEND | LOCK_EX);
}
