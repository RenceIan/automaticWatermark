<?php
// Simple frontend for Automatic Watermark Generator
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Automatic Watermark Generator</title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;max-width:900px;margin:30px auto}
    .row{display:flex;gap:12px}
    .col{flex:1}
    .preview img{max-width:150px;margin:6px}
  </style>
</head>
<body>
  <h1>Automatic Watermark Generator</h1>
  <form id="uploadForm" action="process.php" method="post" enctype="multipart/form-data">
    <label>Choose images (multiple):</label><br>
    <input type="file" name="images[]" id="images" multiple accept="image/*"><br><br>

    <label>Watermark type:</label>
    <select name="wm_type" id="wm_type">
      <option value="text">Text</option>
      <option value="logo">Logo (PNG)</option>
    </select><br><br>

    <div id="textOptions">
      <label>Watermark text:</label><br>
      <input type="text" name="wm_text" placeholder="© My Brand" value="© My Brand"><br>
    </div>

    <div id="logoOptions" style="display:none">
      <label>Upload logo (PNG with transparency):</label><br>
      <input type="file" name="logo" accept="image/png"><br>
    </div>

    <label>Position:</label>
    <select name="position">
      <option>bottom-right</option>
      <option>bottom-left</option>
      <option>top-right</option>
      <option>top-left</option>
      <option>center</option>
    </select><br><br>

    <label>Opacity (10-100):</label>
    <input type="number" name="opacity" value="70" min="10" max="100"><br><br>

    <label>Scale (watermark size as percent of image width):</label>
    <input type="number" name="scale" value="20" min="1" max="100"> %<br><br>

    <label>Download as ZIP:</label>
    <input type="checkbox" name="as_zip" checked><br><br>

    <button type="submit">Process Images</button>
  </form>

  <h3>Selected files preview</h3>
  <div class="preview" id="preview"></div>

  <script>
    const imagesInput = document.getElementById('images');
    const preview = document.getElementById('preview');
    const wmType = document.getElementById('wm_type');
    const textOptions = document.getElementById('textOptions');
    const logoOptions = document.getElementById('logoOptions');

    wmType.addEventListener('change', ()=>{
      if(wmType.value==='logo'){ textOptions.style.display='none'; logoOptions.style.display='block'; }
      else { textOptions.style.display='block'; logoOptions.style.display='none'; }
    });

    imagesInput.addEventListener('change', ()=>{
      preview.innerHTML = '';
      Array.from(imagesInput.files).forEach(f=>{
        const img = document.createElement('img');
        img.src = URL.createObjectURL(f);
        img.onload = ()=> URL.revokeObjectURL(img.src);
        preview.appendChild(img);
      })
    });
  </script>
</body>
</html>
