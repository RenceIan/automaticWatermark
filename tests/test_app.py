import io
import unittest
import zipfile

from PIL import Image

from app import app


class WatermarkFlowTests(unittest.TestCase):
    def setUp(self):
        self.client = app.test_client()

    def _make_png(self, color):
        img = Image.new("RGB", (40, 40), color=color)
        out = io.BytesIO()
        img.save(out, format="PNG")
        out.seek(0)
        return out

    def test_process_returns_zip_for_multiple_images(self):
        data = {
            "watermark_text": "Sample",
            "position": "bottom-right",
            "opacity": "140",
            "images": [
                (self._make_png("red"), "first.png"),
                (self._make_png("blue"), "second.png"),
            ],
        }

        response = self.client.post("/process", data=data, content_type="multipart/form-data")

        self.assertEqual(response.status_code, 200)
        self.assertIn("application/zip", response.content_type)

        archive = zipfile.ZipFile(io.BytesIO(response.data))
        self.assertCountEqual(
            archive.namelist(),
            ["first_watermarked.png", "second_watermarked.png"],
        )


if __name__ == "__main__":
    unittest.main()
