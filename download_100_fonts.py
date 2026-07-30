import os
import re
import urllib.request
import urllib.parse

FONTS_CATALOG = {
    "Sans-Serif": [
        "Inter", "Poppins", "Roboto", "Outfit", "Montserrat", "Lato", "Open Sans",
        "Raleway", "Nunito", "Work Sans", "Rubik", "DM Sans", "Plus Jakarta Sans",
        "Urbanist", "Kanit", "Quicksand", "Barlow", "Manrope", "Jost", "Mulish",
        "Cabin", "Noto Sans", "Syne", "Space Grotesk", "Lexend", "Figtree"
    ],
    "Serif": [
        "Playfair Display", "Lora", "Merriweather", "Cinzel", "Cormorant Garamond",
        "EB Garamond", "PT Serif", "Libre Baskerville", "Bodoni Moda", "Spectral",
        "Prata", "Marcellus", "Noto Serif", "Volkhov", "Bitter", "Cardo", "Arvo",
        "Crimson Text", "Domine", "Sorts Mill Goudy"
    ],
    "Script & Handwriting": [
        "Dancing Script", "Pacifico", "Great Vibes", "Alex Brush", "Sacramento",
        "Caveat", "Satisfy", "Kalam", "Yellowtail", "Shadows Into Light", "Allura",
        "Parisienne", "Cookie", "Kaushan Script", "Marck Script", "Courgette",
        "Tangerine", "Bad Script", "Damion", "Reenie Beanie"
    ],
    "Display & Impact": [
        "Oswald", "Bebas Neue", "Anton", "Lobster", "Abril Fatface", "Righteous",
        "Play", "Changa One", "Permanent Marker", "Bungee", "Monoton",
        "Press Start 2P", "Creepster", "Special Elite", "Titan One", "Bangers",
        "Shrikhand", "Ultra", "UnifrakturMaguntia", "Rubik Mono One"
    ],
    "Monospace & Tech": [
        "Fira Code", "JetBrains Mono", "Source Code Pro", "Space Mono", "Inconsolata",
        "Roboto Mono", "IBM Plex Mono", "VT323", "Share Tech Mono", "Cousine"
    ]
}

PUBLIC_DIR = os.path.dirname(os.path.abspath(__file__)) + "/public"
FONTS_DIR = PUBLIC_DIR + "/fonts"
CSS_FILE = PUBLIC_DIR + "/css/fonts.css"

os.makedirs(FONTS_DIR, exist_ok=True)
os.makedirs(PUBLIC_DIR + "/css", exist_ok=True)

USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"

css_rules = []
downloaded_count = 0
failed_count = 0

print("Starting Google Fonts Batch Downloader for 100+ Fonts...")

for category, font_list in FONTS_CATALOG.items():
    print(f"\nProcessing Category: {category} ({len(font_list)} fonts)")
    for font_name in font_list:
        try:
            encoded_name = urllib.parse.quote(font_name)
            url = f"https://fonts.googleapis.com/css2?family={encoded_name}:ital,wght@0,400;0,700;1,400;1,700&display=swap"
            req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
            
            try:
                with urllib.request.urlopen(req) as response:
                    css_text = response.read().decode('utf-8')
            except Exception as e:
                # Fallback without weights specified if family format differs
                url = f"https://fonts.googleapis.com/css2?family={encoded_name}&display=swap"
                req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
                with urllib.request.urlopen(req) as response:
                    css_text = response.read().decode('utf-8')

            # Parse @font-face blocks
            blocks = re.findall(r'@font-face\s*\{([^}]+)\}', css_text)
            
            for idx, block in enumerate(blocks):
                font_family_match = re.search(r"font-family:\s*['\"]?([^'\";]+)['\"]?", block)
                font_style_match = re.search(r"font-style:\s*([^;]+);", block)
                font_weight_match = re.search(r"font-weight:\s*([^;]+);", block)
                src_match = re.search(r"src:\s*url\((https://[^\)]+)\)", block)
                
                if font_family_match and src_match:
                    ff = font_family_match.group(1).strip()
                    fs = font_style_match.group(1).strip() if font_style_match else "normal"
                    fw = font_weight_match.group(1).strip() if font_weight_match else "400"
                    remote_woff2_url = src_match.group(1).strip()
                    
                    # Create safe filename
                    slug_name = font_name.lower().replace(" ", "-")
                    filename = f"{slug_name}-{fw}-{fs}.woff2"
                    filepath = os.path.join(FONTS_DIR, filename)
                    
                    if not os.path.exists(filepath):
                        # Download woff2 file
                        img_req = urllib.request.Request(remote_woff2_url, headers={"User-Agent": USER_AGENT})
                        with urllib.request.urlopen(img_req) as img_resp:
                            with open(filepath, "wb") as f:
                                f.write(img_resp.read())
                    
                    downloaded_count += 1
                    
                    # Generate local @font-face rule
                    rule = f"""@font-face {{
  font-family: '{ff}';
  font-style: {fs};
  font-weight: {fw};
  font-display: swap;
  src: url('/fonts/{filename}') format('woff2');
}}"""
                    css_rules.append(rule)
            
            print(f"  ✓ {font_name}")
        except Exception as err:
            print(f"  ✗ Failed {font_name}: {err}")
            failed_count += 1

# Write public/css/fonts.css
with open(CSS_FILE, "w", encoding="utf-8") as f:
    f.write("/* 100+ Self-Hosted Google Fonts Catalog for ID Card Builder */\n\n")
    f.write("\n\n".join(css_rules))

print(f"\n==========================================")
print(f"Done! Downloaded {downloaded_count} font files across {len(FONTS_CATALOG)} categories.")
print(f"Generated CSS at: {CSS_FILE}")
if failed_count > 0:
    print(f"Failed fonts count: {failed_count}")
