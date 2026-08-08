# Mirror Print Option Implementation Plan (Export & Print Center)

Add a **Mirror Print (Horizontal Flip / Reverse Printing)** option to the **Export & Print Center** in the Laravel application (`/Users/sandeep/Projects/IdCard/api`). This feature allows print operators to produce horizontally mirrored ID card outputs required for printing on transparent PVC cards, acrylic sheets, or back-side thermal transfer films.

---

## Overview & Technical Scope

### Objective
Provide a clean UI toggle control in the Export & Print Center modal that applies horizontal mirroring (`transform: scaleX(-1)`) to rendered ID card outputs across supported export formats:
1. **Single Card PDF (ID Printer)**
2. **Print Imposition PDF**
3. **Rendered Cards PNG (ZIP)**

---

## User Review Required & Design Decisions

- **Supported Formats**: Applies to all visual card export formats (PNG ZIP, Single Card PDF, Imposition PDF).
- **Default State**: Disabled by default (`false`) so standard printing remains unaffected.
- **Persistence**: User preference (`mirror_print`) is remembered across sessions from their latest export params.

---

## Proposed Technical Changes

### 1. Livewire Student Management & Export Modal
#### [MODIFY] [index.blade.php](file:///Users/sandeep/Projects/IdCard/api/resources/views/livewire/students/index.blade.php)
- Add `public bool $exportMirrorPrint = false;` state property to the Livewire Volt component.
- In `mount()`, restore `$this->exportMirrorPrint` from the user's latest export record parameters.
- In the **Export & Print Center** modal UI:
  - Add a dedicated **Mirror Print** toggle card under the Export Format selector.
  - Display helpful context for print operators (*"Horizontal Flip for Transparent PVC, Acrylic & Back-side Transfer Printing"*).
- In `triggerExport()`, include `'mirror_print' => $this->exportMirrorPrint` inside the `$params` payload.

---

### 2. Blade Component & Rendering Service
#### [MODIFY] [id-card-renderer.blade.php](file:///Users/sandeep/Projects/IdCard/api/resources/views/components/id-card-renderer.blade.php)
- Add `'isMirrored' => false` to `@props`.
- When `$isMirrored` is true, append `transform: scaleX(-1); transform-origin: center center;` to the inner card style container so all background, text, photo, logo, and QR code layers flip horizontally.

#### [MODIFY] [CardRenderService.php](file:///Users/sandeep/Projects/IdCard/api/app/Services/CardRenderService.php)
- Update `renderFrontHtml($template, Student $student, School $school, bool $isMirrored = false)` signature and pass `:isMirrored="$isMirrored"` to `<x-id-card-renderer>`.

---

### 3. Background Export Jobs & PDF Templates
#### [MODIFY] [ExportPngZipJob.php](file:///Users/sandeep/Projects/IdCard/api/app/Jobs/ExportPngZipJob.php)
- Read `$isMirrored = (bool)($export->params['mirror_print'] ?? false);`.
- Pass `$isMirrored` into `CardRenderService::renderFrontHtml()`.

#### [MODIFY] [ExportSingleCardPdfJob.php](file:///Users/sandeep/Projects/IdCard/api/app/Jobs/ExportSingleCardPdfJob.php)
- Read `$isMirrored = (bool)($export->params['mirror_print'] ?? false);`.
- Pass `'isMirrored' => $isMirrored` to `exports.single-card-pdf` view data.

#### [MODIFY] [ExportImpositionPdfJob.php](file:///Users/sandeep/Projects/IdCard/api/app/Jobs/ExportImpositionPdfJob.php)
- Read `$isMirrored = (bool)($export->params['mirror_print'] ?? false);`.
- Pass `'isMirrored' => $isMirrored` to `exports.imposition-sheet` view data.

#### [MODIFY] [single-card-pdf.blade.php](file:///Users/sandeep/Projects/IdCard/api/resources/views/exports/single-card-pdf.blade.php)
- Pass `:isMirrored="$isMirrored"` to `<x-id-card-renderer>`.

#### [MODIFY] [imposition-sheet.blade.php](file:///Users/sandeep/Projects/IdCard/api/resources/views/exports/imposition-sheet.blade.php)
- Pass `:isMirrored="$isMirrored"` to `<x-id-card-renderer>`.

---

## Verification & Testing Plan

### 1. Build & Syntax Verification
- Execute `npm run build` to verify frontend assets compile cleanly.
- Run syntax check on modified PHP files.

### 2. Manual Export Verification
- Open **Export & Print Center** modal on Students management page.
- Verify **Mirror Print** toggle control renders clearly with responsive styling.
- Trigger **Single Card PDF**, **Print Imposition PDF**, and **Rendered Cards PNG (ZIP)** exports with Mirror Print **ON**.
- Verify generated cards are horizontally flipped (`scaleX(-1)`).
- Trigger export with Mirror Print **OFF** and confirm standard rendering remains intact.
