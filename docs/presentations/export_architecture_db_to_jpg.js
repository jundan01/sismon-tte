import puppeteer from 'puppeteer-core';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function exportArchitectureDbDiagrams() {
    console.log('🚀 Memulai ekspor diagram Topologi & Basis Data ke format JPG...');

    const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
    const htmlFilePath = 'file:///' + path.resolve(__dirname, 'render_architecture_db.html').replace(/\\/g, '/');

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
            height: 3400,
            deviceScaleFactor: 2 // Ultra-high DPI crisp output
        }
    });

    const page = await browser.newPage();
    console.log(`📄 Memuat berkas HTML: ${htmlFilePath}`);
    await page.goto(htmlFilePath, { waitUntil: 'networkidle0' });

    // Tunggu Mermaid SVG selesai dirender
    console.log('⏳ Menunggu Mermaid SVG selesai dirender...');
    await page.waitForSelector('.diagram-container svg', { timeout: 15000 });
    await new Promise(r => setTimeout(r, 1500)); // Buffer stabilisasi layout

    // 1. Ekspor Full Poster Terintegrasi
    console.log('📸 Mengambil screenshot: diagram_lengkap_arsitektur_db.jpg...');
    const fullPosterElement = await page.$('#poster-root');
    await fullPosterElement.screenshot({
        path: path.resolve(__dirname, 'diagram_lengkap_arsitektur_db.jpg'),
        type: 'jpeg',
        quality: 95
    });

    // 2. Ekspor Diagram Topologi Arsitektur
    console.log('📸 Mengambil screenshot: diagram_topologi_arsitektur.jpg...');
    const topologiElement = await page.$('#section-topologi');
    await topologiElement.screenshot({
        path: path.resolve(__dirname, 'diagram_topologi_arsitektur.jpg'),
        type: 'jpeg',
        quality: 95
    });

    // 3. Ekspor Diagram Skema Basis Data (ERD)
    console.log('📸 Mengambil screenshot: diagram_skema_database.jpg...');
    const databaseElement = await page.$('#section-database');
    await databaseElement.screenshot({
        path: path.resolve(__dirname, 'diagram_skema_database.jpg'),
        type: 'jpeg',
        quality: 95
    });

    await browser.close();
    console.log('✅ SELURUH DIAGRAM TOPOLOGI & DATABASE BERHASIL DIEKSPOR KE JPG DENGAN RESOLUSI TINGGI!');
}

exportArchitectureDbDiagrams().catch(err => {
    console.error('❌ Gagal mengekspor diagram:', err);
    process.exit(1);
});
