# automaticWatermark

A simple web app for bulk image watermarking:

- upload multiple pictures
- choose your watermark text (and placement)
- download all processed pictures in one ZIP file

## Run locally

```bash
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python app.py
```

Open `http://127.0.0.1:5000`, upload your images, enter watermark text, and click **Process and Download ZIP**.

## Test

```bash
python -m unittest -v
```
