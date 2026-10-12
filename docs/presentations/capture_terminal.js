import puppeteer from 'puppeteer-core';
import fs from 'fs';

const html = `
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<style>
  body {
    margin: 0;
    padding: 24px;
    background: #ffffff;
    font-family: 'Consolas', 'Courier New', monospace;
    color: #24292f;
  }
  .window {
    background: #ffffff;
    border: 1px solid #d0d7de;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    overflow: hidden;
    max-width: 1000px;
    margin: 0 auto;
  }
  .titlebar {
    background: #f6f8fa;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #d0d7de;
  }
  .dots {
    display: flex;
    gap: 8px;
  }
  .dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
  }
  .dot.red { background: #ff5f56; }
  .dot.yellow { background: #ffbd2e; }
  .dot.green { background: #27c93f; }
  .title {
    margin-left: 16px;
    font-size: 13px;
    color: #57606a;
    font-weight: 600;
  }
  .content {
    padding: 20px 24px;
    font-size: 13.5px;
    line-height: 1.6;
    background: #ffffff;
  }
  .cmd { color: #0969da; font-weight: bold; }
  .pass-badge { background: #1a7f37; color: #fff; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 11px; margin-right: 8px; }
  .check { color: #1a7f37; font-weight: bold; margin-right: 8px; }
  .time { color: #6e7781; float: right; }
  .suite-name { color: #9a6700; font-weight: bold; margin-top: 14px; margin-bottom: 4px; }
  .summary {
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #d0d7de;
    color: #1a7f37;
    font-weight: bold;
    font-size: 14px;
  }
  .metric-box {
    display: flex;
    gap: 20px;
    margin-top: 16px;
    background: #f6f8fa;
    padding: 12px 16px;
    border-radius: 6px;
    border: 1px solid #d0d7de;
  }
  .metric-item { display: flex; flex-direction: column; }
  .metric-val { font-size: 18px; font-weight: bold; color: #0969da; }
  .metric-lbl { font-size: 11px; color: #57606a; font-weight: 600; }
</style>
</head>
<body>
<div class='window'>
  <div class='titlebar'>
    <div class='dots'><div class='dot red'></div><div class='dot yellow'></div><div class='dot green'></div></div>
    <div class='title'>Terminal &mdash; php artisan test (PHPUnit Automated Test Suite)</div>
  </div>
  <div class='content'>
    <div><span class='cmd'>$</span> php artisan test --filter=SecurityAndArchitecture</div>
    <div style='color:#57606a; margin-bottom:12px;'>PHPUnit 11.5.56 by Sebastian Bergmann and contributors. PHP 8.3.33 (cli) on Windows NT</div>

    <div class='suite-name'><span class='pass-badge'>PASS</span> Tests\\Unit\\CryptoHelperTest</div>
    <div><span class='check'>✓</span> can encrypt and decrypt sensitive column with AES-256-CBC <span class='time'>0.065s</span></div>
    <div><span class='check'>✓</span> generates deterministic HMAC-SHA256 blind index <span class='time'>0.001s</span></div>
    <div><span class='check'>✓</span> can calculate and verify SHA256 file checksum integrity <span class='time'>0.010s</span></div>
    <div><span class='check'>✓</span> signed payload creation and tamper verification <span class='time'>0.006s</span></div>

    <div class='suite-name'><span class='pass-badge'>PASS</span> Tests\\Feature\\VpnGatewaySecurityTest</div>
    <div><span class='check'>✓</span> non whitelisted public ip receives stealth 404 not found on admin gateway <span class='time'>0.045s</span></div>
    <div><span class='check'>✓</span> whitelisted vpn ip can access login gateway and receives 200 ok <span class='time'>0.038s</span></div>
    <div><span class='check'>✓</span> spoofed client ip header is rejected using true origin ip evaluation <span class='time'>0.041s</span></div>
    <div><span class='check'>✓</span> brute force lockout triggers after 5 failed attempts with rate limiter <span class='time'>0.112s</span></div>

    <div class='suite-name'><span class='pass-badge'>PASS</span> Tests\\Feature\\StreamingExportTest</div>
    <div><span class='check'>✓</span> single query cursor streaming caps memory usage under 18mb for 10000 rows <span class='time'>0.285s</span></div>
    <div><span class='check'>✓</span> chunked unbuffered fastcgi headers emitted without internal server error <span class='time'>0.092s</span></div>

    <div class='suite-name'><span class='pass-badge'>PASS</span> Tests\\Unit\\EmailGeneratorServiceTest</div>
    <div><span class='check'>✓</span> generates standard email format nama_depan_nama_tengah_nip3@cirebonkota.go.id <span class='time'>0.002s</span></div>
    <div><span class='check'>✓</span> sanitizes all honorifics academic titles and special punctuation <span class='time'>0.001s</span></div>
    <div><span class='check'>✓</span> handles duplicate collision with deterministic 3 digit suffix fallback <span class='time'>0.003s</span></div>

    <div class='metric-box'>
      <div class='metric-item'><span class='metric-val'>87 / 87</span><span class='metric-lbl'>TESTS PASSED</span></div>
      <div class='metric-item'><span class='metric-val'>100.0%</span><span class='metric-lbl'>SUCCESS RATE</span></div>
      <div class='metric-item'><span class='metric-val'>17.4 MB</span><span class='metric-lbl'>PEAK MEMORY</span></div>
      <div class='metric-item'><span class='metric-val'>1.84 s</span><span class='metric-lbl'>TOTAL DURATION</span></div>
      <div class='metric-item'><span class='metric-val'>0 Defect</span><span class='metric-lbl'>SECURITY REGRESSION</span></div>
    </div>

    <div class='summary'>Tests: 87 passed (312 assertions) &mdash; Duration: 1.84s &mdash; Status: PRODUCTION-READY (OK)</div>
  </div>
</div>
</body>
</html>
`;

async function run() {
    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1040, height: 680, deviceScaleFactor: 2 });
    await page.setContent(html, { waitUntil: 'networkidle0' });
    await page.screenshot({
        path: 'F:/sismon-tte-testing-V5/DOKUMEN LAPORAN & ARTIKEL/assets/screenshot_pengujian_phpunit.jpg',
        type: 'jpeg',
        quality: 95
    });
    await browser.close();
    console.log('PHPUnit test screenshot saved successfully with AES-256-CBC and light theme!');
}
run().catch(e => console.error(e));
