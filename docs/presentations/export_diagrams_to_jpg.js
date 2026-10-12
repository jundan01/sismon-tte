import puppeteer from 'puppeteer-core';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function exportDiagrams() {
    console.log('🚀 Memulai ekspor diagram ke format JPG...');

    const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
    const htmlFilePath = 'file:///' + path.resolve(__dirname, 'render_diagram.html').replace(/\\/g, '/');

    const browser = await puppeteer.launch({
        executablePath: chromePath,
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-gpu',
            '--disable-web-security',
            '--allow-file-access-from-files'
        ],
        defaultViewport: {
            width: 2400,
            height: 3200,
            deviceScaleFactor: 2 // Ultra-high DPI crisp output
        }
    });

    const page = await browser.newPage();
    console.log(`📄 Memuat berkas HTML: ${htmlFilePath}`);
    await page.goto(htmlFilePath, { waitUntil: 'networkidle0' });

    // Tunggu Mermaid SVG selesai dirender
    console.log('⏳ Menunggu Mermaid SVG selesai dirender...');
    await page.waitForSelector('.diagram-container svg', { timeout: 15000 });
    await new Promise(r => setTimeout(r, 1500)); // Buffer stabilisasi rendering font & layout

    // 1. Ekspor Full Poster Terintegrasi
    console.log('📸 Mengambil screenshot: diagram_lengkap_sistem_tte.jpg...');
    const fullPosterElement = await page.$('#poster-root');
    await fullPosterElement.screenshot({
        path: path.resolve(__dirname, 'diagram_lengkap_sistem_tte.jpg'),
        type: 'jpeg',
        quality: 95
    });

    // 2. Ekspor Diagram Alur Bisnis Utama
    console.log('📸 Mengambil screenshot: diagram_alur_bisnis_tte.jpg...');
    const alurBisnisElement = await page.$('#section-alur-bisnis');
    await alurBisnisElement.screenshot({
        path: path.resolve(__dirname, 'diagram_alur_bisnis_tte.jpg'),
        type: 'jpeg',
        quality: 95
    });

    // 3. Ekspor Diagram Siklus Hidup Status
    console.log('📸 Mengambil screenshot: diagram_siklus_status_tte.jpg...');
    const siklusStatusElement = await page.$('#section-siklus-status');
    await siklusStatusElement.screenshot({
        path: path.resolve(__dirname, 'diagram_siklus_status_tte.jpg'),
        type: 'jpeg',
        quality: 95
    });

    await browser.close();
    console.log('✅ SELURUH DIAGRAM BERHASIL DIEKSPOR KE JPG DENGAN RESOLUSI TINGGI!');
}

exportDiagrams().catch(err => {
    console.error('❌ Gagal mengekspor diagram:', err);
    process.exit(1);
});
