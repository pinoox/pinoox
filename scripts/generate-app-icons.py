#!/usr/bin/env python3
"""Post-process generated app icons: square crop, transparency, no quality loss."""

from __future__ import annotations

import sys
from pathlib import Path

from PIL import Image

TARGET_SIZE = 512
BLACK_THRESHOLD = 45


def fix_icon(source: Path, dest: Path) -> None:
    img = Image.open(source).convert("RGBA")
    pixels = img.load()
    width, height = img.size

    for y in range(height):
        for x in range(width):
            r, g, b, a = pixels[x, y]
            if r <= BLACK_THRESHOLD and g <= BLACK_THRESHOLD and b <= BLACK_THRESHOLD:
                pixels[x, y] = (0, 0, 0, 0)

    bbox = img.getbbox()
    if not bbox:
        raise ValueError(f"No visible content in {source}")

    left, top, right, bottom = bbox
    bw, bh = right - left, bottom - top
    side = max(bw, bh)
    cx = (left + right) // 2
    cy = (top + bottom) // 2
    half = side // 2
    square = (cx - half, cy - half, cx - half + side, cy - half + side)
    img = img.crop(square)

    if img.size != (TARGET_SIZE, TARGET_SIZE):
        img = img.resize((TARGET_SIZE, TARGET_SIZE), Image.Resampling.LANCZOS)

    dest.parent.mkdir(parents=True, exist_ok=True)
    img.save(dest, format="PNG", compress_level=1)
    print(f"{dest} -> {img.size} {img.mode} {dest.stat().st_size} bytes alpha_corners={img.getpixel((0, 0))[3]}")


def main() -> None:
    root = Path(__file__).resolve().parents[1]
    assets = Path(r"C:\Users\yoose\.cursor\projects\c-MAMP-htdocs-pinoox\assets")
    mapping = [
        (assets / "icon-installer.png", root / "apps/com_pinoox_installer/icon.png"),
        (assets / "icon-manager.png", root / "apps/com_pinoox_manager/icon.png"),
        (assets / "icon-welcome.png", root / "apps/com_pinoox_welcome/icon.png"),
        (assets / "icon-comingsoon.png", root / "apps/com_pinoox_comingsoon/icon.png"),
        (assets / "icon-app-template.png", root / "packages/app/resource/icon.png"),
    ]
    for src, dest in mapping:
        if not src.exists():
            print(f"missing source: {src}", file=sys.stderr)
            sys.exit(1)
        fix_icon(src, dest)


if __name__ == "__main__":
    main()
