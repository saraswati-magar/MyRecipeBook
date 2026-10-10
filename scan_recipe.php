<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Recipe Scanner - MyRecipeBook</title>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tab-btn { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 10px 20px; font-weight: 600; cursor: pointer; border-radius: 6px 6px 0 0; transition: all 0.2s; }
        .tab-btn.active { background: #ffffff; color: #4f46e5; border-bottom: 2px solid #ffffff; border-color: #cbd5e1 #cbd5e1 transparent #cbd5e1; }
        .tab-content { display: none; background: #fff; padding: 25px; border: 1px solid #cbd5e1; border-radius: 0 0 8px 8px; border-top: none; }
        .tab-content.active { display: block; }
        .region-btn { background: #e2e8f0; border: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 0.85rem; color: #334155; margin-right: 5px; }
        .region-btn.active { background: #4f46e5; color: white; }
    </style>
</head>
<body>

<div class="form-container" style="max-width: 750px; margin: 40px auto; padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #1e293b;">Smart Recipe Scanner</h2>
        <a href="dashboard.php" style="background: #f1f5f9; color: #334155; padding: 0.5rem 1rem; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 0.9rem; border: 1px solid #cbd5e1;">&larr; Back to Dashboard</a>
    </div>

    <!-- Tabs Header -->
    <div style="display: flex; gap: 5px;">
        <button type="button" class="tab-btn active" onclick="switchTab('scan')">📷 Column-Focused OCR</button>
        <button type="button" class="tab-btn" onclick="switchTab('paste')">✍️ Direct Text Paste</button>
    </div>

    <!-- Tab 1: Column-Focused OCR -->
    <div id="tab-scan" class="tab-content active">
        <p style="color: #64748b; margin-bottom: 15px; font-size: 0.95rem;">Upload a cookbook page and choose a column region to keep ingredients and steps separate during the scan!</p>
        
        <div style="border: 2px dashed #cbd5e1; padding: 20px; text-align: center; border-radius: 8px; background: #f8fafc; margin-bottom: 20px;">
            <input type="file" id="recipe-image-input" accept="image/*" style="display: none;">
            <button type="button" onclick="document.getElementById('recipe-image-input').click()" style="background: #4f46e5; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Choose Cookbook Image</button>
            <p id="file-name-display" style="margin-top: 8px; color: #475569; font-size: 0.85rem;">No image selected</p>
        </div>

        <!-- Preview & Column Focus Controls -->
        <div id="preview-section" style="display: none; margin-bottom: 20px; text-align: center;">
            <div style="margin-bottom: 12px;">
                <span style="font-size: 0.9rem; font-weight: 600; color: #334155; margin-right: 8px;">Scan Region:</span>
                <button type="button" class="region-btn active" onclick="setRegion('full', this)">Full Page</button>
                <button type="button" class="region-btn" onclick="setRegion('left', this)">Left Column</button>
                <button type="button" class="region-btn" onclick="setRegion('right', this)">Right Column</button>
            </div>

            <canvas id="image-canvas" style="max-width: 100%; max-height: 280px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;"></canvas>
            
            <div style="background: #e2e8f0; border-radius: 4px; overflow: hidden; height: 8px; width: 100%; margin-bottom: 8px;">
                <div id="progress-bar" style="background: #4f46e5; width: 0%; height: 100%; transition: width 0.2s;"></div>
            </div>
            <p id="status-text" style="font-weight: 500; color: #334155; font-size: 0.9rem;"></p>
        </div>

        <div id="output-section" style="display: none;">
            <h3 style="color: #1e293b; font-size: 1rem; margin-bottom: 6px;">Scanned Text (Review & Edit if needed)</h3>
            <textarea id="raw-ocr-text" style="width: 100%; height: 160px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: monospace; font-size: 0.9rem; box-sizing: border-box; margin-bottom: 15px;"></textarea>
            <button type="button" id="transfer-btn" style="background: #059669; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%;">Import into Recipe Wizard &rarr;</button>
        </div>
    </div>

    <!-- Tab 2: Direct Text Paste -->
    <div id="tab-paste" class="tab-content">
        <p style="color: #64748b; margin-bottom: 15px; font-size: 0.95rem;">Paste text directly from digital sources for instant, flawless importing.</p>
        <textarea id="direct-paste-text" placeholder="Paste recipe text here..." style="width: 100%; height: 200px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: monospace; font-size: 0.9rem; box-sizing: border-box; margin-bottom: 15px;"></textarea>
        <button type="button" id="import-paste-btn" style="background: #059669; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%;">Import into Recipe Wizard &rarr;</button>
    </div>
</div>

<script>
    let loadedImage = null;
    let currentRegion = 'full';

    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        if (tabName === 'scan') {
            document.querySelectorAll('.tab-btn')[0].classList.add('active');
            document.getElementById('tab-scan').classList.add('active');
        } else {
            document.querySelectorAll('.tab-btn')[1].classList.add('active');
            document.getElementById('tab-paste').classList.add('active');
        }
    }

    const imageInput = document.getElementById('recipe-image-input');
    const fileNameDisplay = document.getElementById('file-name-display');
    const previewSection = document.getElementById('preview-section');
    const canvas = document.getElementById('image-canvas');
    const ctx = canvas.getContext('2d');
    const progressBar = document.getElementById('progress-bar');
    const statusText = document.getElementById('status-text');
    const outputSection = document.getElementById('output-section');
    const rawOcrText = document.getElementById('raw-ocr-text');

    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        fileNameDisplay.textContent = file.name;
        previewSection.style.display = 'block';
        outputSection.style.display = 'none';

        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                loadedImage = img;
                renderAndRunOCR();
            }
            img.src = event.target.result;
        }
        reader.readAsDataURL(file);
    });

    function setRegion(region, btn) {
        document.querySelectorAll('.region-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentRegion = region;
        if (loadedImage) {
            renderAndRunOCR();
        }
    }

    function renderAndRunOCR() {
        if (!loadedImage) return;

        let sx = 0, sy = 0, sw = loadedImage.width, sh = loadedImage.height;

        // Crop coordinates based on column selection
        if (currentRegion === 'left') {
            sw = loadedImage.width * 0.52; 
        } else if (currentRegion === 'right') {
            sx = loadedImage.width * 0.48; 
            sw = loadedImage.width * 0.52;
        }

        canvas.width = sw;
        canvas.height = sh;

        // Draw the image normally onto the canvas without distortion or filters
        ctx.drawImage(loadedImage, sx, sy, sw, sh, 0, 0, sw, sh);

        runTesseractOCR(canvas.toDataURL());
    }

    function runTesseractOCR(imageSource) {
        statusText.textContent = "Scanning region...";
        progressBar.style.width = "20%";

        Tesseract.recognize(
            imageSource,
            'eng',
            {
                logger: m => {
                    if (m.status === 'recognizing text') {
                        let progressPercent = Math.round(m.progress * 80);
                        progressBar.style.width = (20 + progressPercent) + '%';
                        statusText.textContent = `Processing... ${Math.round(m.progress * 100)}%`;
                    }
                }
            }
        ).then(({ data: { text } }) => {
            progressBar.style.width = "100%";
            statusText.textContent = "Scan Completed Successfully!";
            rawOcrText.value = text.trim();
            outputSection.style.display = 'block';
        }).catch(err => {
            console.error(err);
            statusText.textContent = "OCR Error. Please try another view.";
            progressBar.style.background = "#dc2626";
        });
    }

    document.getElementById('transfer-btn').addEventListener('click', function() {
        const text = rawOcrText.value;
        if (!text.trim()) {
            alert("No text to transfer!");
            return;
        }
        sessionStorage.setItem('ocr_scanned_text', text);
        window.location.href = 'add_recipe.php?from_ocr=1';
    });

    document.getElementById('import-paste-btn').addEventListener('click', function() {
        const text = document.getElementById('direct-paste-text').value;
        if (!text.trim()) {
            alert("Please paste some recipe text first!");
            return;
        }
        sessionStorage.setItem('ocr_scanned_text', text);
        window.location.href = 'add_recipe.php?from_ocr=1';
    });
</script>

</body>
</html>