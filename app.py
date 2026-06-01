from __future__ import annotations

import io
import os
import zipfile
from datetime import UTC, datetime
from typing import Iterable

from flask import Flask, Response, render_template, request
from PIL import Image, ImageDraw, ImageFont, UnidentifiedImageError


app = Flask(__name__)

ALLOWED_EXTENSIONS = {".jpg", ".jpeg", ".png", ".webp", ".bmp"}


def _safe_output_name(filename: str) -> str:
    base, ext = os.path.splitext(filename)
    ext = ext.lower()
    if ext not in ALLOWED_EXTENSIONS:
        ext = ".png"
    safe_base = base.strip().replace(" ", "_") or "image"
    return f"{safe_base}_watermarked{ext}"


def _apply_text_watermark(
    image: Image.Image,
    text: str,
    position: str,
    opacity: int,
) -> Image.Image:
    img = image.convert("RGBA")
    overlay = Image.new("RGBA", img.size, (255, 255, 255, 0))
    draw = ImageDraw.Draw(overlay)

    font = ImageFont.load_default()
    text_bbox = draw.textbbox((0, 0), text, font=font)
    text_width = text_bbox[2] - text_bbox[0]
    text_height = text_bbox[3] - text_bbox[1]

    padding = 16
    positions = {
        "top-left": (padding, padding),
        "top-right": (max(padding, img.width - text_width - padding), padding),
        "bottom-left": (padding, max(padding, img.height - text_height - padding)),
        "bottom-right": (
            max(padding, img.width - text_width - padding),
            max(padding, img.height - text_height - padding),
        ),
        "center": ((img.width - text_width) // 2, (img.height - text_height) // 2),
    }
    x, y = positions.get(position, positions["bottom-right"])

    draw.text((x, y), text, fill=(255, 255, 255, max(20, min(255, opacity))), font=font)
    return Image.alpha_composite(img, overlay)


def _process_files(files: Iterable, watermark_text: str, position: str, opacity: int) -> io.BytesIO:
    zip_buffer = io.BytesIO()
    with zipfile.ZipFile(zip_buffer, "w", zipfile.ZIP_DEFLATED) as zip_file:
        for file_storage in files:
            if not file_storage or not file_storage.filename:
                continue

            output_name = _safe_output_name(file_storage.filename)
            try:
                with Image.open(file_storage.stream) as original:
                    watermarked = _apply_text_watermark(original, watermark_text, position, opacity)

                    format_hint = "PNG"
                    if output_name.lower().endswith((".jpg", ".jpeg")):
                        format_hint = "JPEG"
                        watermarked = watermarked.convert("RGB")

                    output = io.BytesIO()
                    watermarked.save(output, format=format_hint)
                    output.seek(0)
                    zip_file.writestr(output_name, output.read())
            except UnidentifiedImageError:
                continue

    zip_buffer.seek(0)
    return zip_buffer


@app.get("/")
def index() -> str:
    return render_template("index.html")


@app.post("/process")
def process_images() -> Response:
    files = request.files.getlist("images")
    watermark_text = request.form.get("watermark_text", "").strip()
    position = request.form.get("position", "bottom-right").strip()

    try:
        opacity = int(request.form.get("opacity", "140"))
    except ValueError:
        opacity = 140

    if not files:
        return Response("Please upload at least one image.", status=400)
    if not watermark_text:
        return Response("Please provide a watermark text.", status=400)

    zip_data = _process_files(files, watermark_text, position, opacity)
    if zip_data.getbuffer().nbytes == 0:
        return Response("No valid image files were uploaded.", status=400)

    timestamp = datetime.now(UTC).strftime("%Y%m%d_%H%M%S")
    return Response(
        zip_data.getvalue(),
        mimetype="application/zip",
        headers={
            "Content-Disposition": f'attachment; filename="watermarked_images_{timestamp}.zip"'
        },
    )


if __name__ == "__main__":
    app.run()
