# Mirror Print Option — Export & Print Center (Final Plan)

Add a **Mirror Print (Horizontal Flip / Reverse Printing)** option to the **Export & Print Center** in the Laravel application (`/Users/sandeep/Projects/IdCard/api`). This feature allows print operators to produce horizontally mirrored ID card outputs required for printing on transparent PVC cards, acrylic sheets, or back-side thermal transfer films (so text and visuals read correctly once viewed from the physical front side).

---

## Technical Context & Scope

The Export & Print Center generates ID cards in three visual formats:
1. **Rendered Cards PNG (ZIP)**
2. **Single Card PDF (ID Printer)**
3. **Print Imposition PDF**
and one non-visual format (**Excel Roster + Photos ZIP**).

This plan adds a "Mirror Print" toggle to the export modal that flips rendered card content (`transform: scaleX(-1)`) across all three visual export pipelines, while leaving normal (non-mirrored) exports completely unaffected.

### Key Features & Design Decisions:
1. **Session Persistence**: User preference (`mirror_print`) is remembered across sessions from their latest export.
2. **Imposition Grid Column Reversal**: When imposition sheets are mirrored, column assignments are reversed left-to-right (`$col = $isMirrored ? ($cols - 1 - $rawCol) : $rawCol`) so front/back grid cells align perfectly during duplex printing.
3. **Format Gating**: The Mirror Print toggle control is automatically hidden when "Excel Roster + Photos ZIP" format is selected.
4. **Visual Warning & History Badging**: Active toggle shows an amber warning state to prevent unintended mirrored prints, and recent exports show a `MIRRORED` badge.

---

## Detailed File Changes

All paths relative to `/Users/sandeep/Projects/IdCard/api`.

### 1. `resources/views/livewire/students/index.blade.php` (Livewire Volt Component)

- **Add state property**:
  ```php
  public bool $exportMirrorPrint = false;
  ```

- **Restore persisted preference in `openExportModal()`**:
  ```php
  public function openExportModal()
  {
      $this->isExportModalOpen = true;

      $lastPdfExport = \App\Models\Export::where('school_id', session('active_school_id'))
          ->where('user_id', auth()->id())
          ->where('type', 'imposition_pdf')
          ->orderBy('id', 'desc')
          ->first();

      if ($lastPdfExport && is_array($lastPdfExport->params)) {
          $p = $lastPdfExport->params;
          if (!empty($p['page_size'])) $this->exportPageSize = $p['page_size'];
          if (isset($p['custom_width_mm'])) $this->exportCustomWidthMm = (float)$p['custom_width_mm'];
          if (isset($p['custom_height_mm'])) $this->exportCustomHeightMm = (float)$p['custom_height_mm'];
          if (isset($p['bleed_mm'])) $this->exportBleedMm = (float)$p['bleed_mm'];
          if (isset($p['margin_mm'])) $this->exportMarginMm = (float)$p['margin_mm'];
          if (isset($p['gutter_mm'])) $this->exportGutterMm = (float)$p['gutter_mm'];
      }

      $lastAnyExport = \App\Models\Export::where('school_id', session('active_school_id'))
          ->where('user_id', auth()->id())
          ->orderBy('id', 'desc')
          ->first();

      if ($lastAnyExport && is_array($lastAnyExport->params) && isset($lastAnyExport->params['mirror_print'])) {
          $this->exportMirrorPrint = (bool) $lastAnyExport->params['mirror_print'];
      }
  }
  ```

- **Pass `'mirror_print'` in `triggerExport()`**:
  ```php
  $params = [
      'campaign_id' => $this->filterCampaign ?: null,
      'student_ids' => $targetStudentIds,
      'page_size' => $this->exportPageSize,
      'custom_width_mm' => $this->exportCustomWidthMm,
      'custom_height_mm' => $this->exportCustomHeightMm,
      'bleed_mm' => $this->exportBleedMm,
      'margin_mm' => $this->exportMarginMm,
      'gutter_mm' => $this->exportGutterMm,
      'mirror_print' => $this->exportMirrorPrint,
  ];
  ```

- **UI Toggle Card in Modal**:
  ```blade
  @if ($exportType !== 'excel_photo_zip')
      <div class="p-4 border rounded-2xl flex items-start justify-between gap-4 transition {{ $exportMirrorPrint ? 'border-amber-400 bg-amber-50/60 dark:bg-amber-950/20' : 'border-gray-200 dark:border-gray-700' }}">
          <div>
              <span class="font-bold text-xs text-gray-800 dark:text-gray-200 flex items-center gap-2">
                  {{ __('Mirror Print (Horizontal Flip)') }}
                  @if ($exportMirrorPrint)
                      <span class="px-2 py-0.5 bg-amber-400 text-amber-950 rounded-md text-[9px] font-extrabold uppercase tracking-wider">{{ __('Active') }}</span>
                  @endif
              </span>
              <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                  {{ __('Horizontally flips all rendered cards for printing on transparent PVC, acrylic, or back-side thermal transfer film. Text and QR codes will appear reversed — this is expected and corrects itself once viewed from the intended physical side.') }}
              </p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer shrink-0">
              <input type="checkbox" wire:model.live="exportMirrorPrint" class="sr-only peer" />
              <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:bg-amber-500 transition-colors"></div>
              <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
          </label>
      </div>
  @endif
  ```

- **History List Badge**:
  ```blade
  <span class="font-bold text-gray-800 dark:text-gray-200 uppercase text-[11px] block flex items-center gap-1.5">
      {{ str_replace('_', ' ', $exp->type) }}
      @if (is_array($exp->params) && !empty($exp->params['mirror_print']))
          <span class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 rounded text-[8px] font-bold">MIRRORED</span>
      @endif
  </span>
  ```

---

### 2. `resources/views/components/id-card-renderer.blade.php`

- Add `'isMirrored' => false` prop.
- Combine scale and mirror transform into a single `transform` string to prevent CSS property override:
  ```php
  $mirrorTransform = $isMirrored ? ' scaleX(-1)' : '';

  $cardStyle = $forExport 
      ? "position: relative; overflow: hidden; width: {$widthPx}px; height: {$heightPx}px; transform: scale({$scale}){$mirrorTransform}; transform-origin: top left; background-color: #ffffff;" 
      : "position: relative; overflow: hidden; border-radius: 12px; width: {$widthPx}px; height: {$heightPx}px; transform: scale({$scale}){$mirrorTransform}; transform-origin: top left; background-color: #ffffff;";
  ```

---

### 3. `app/Services/CardRenderService.php`

- Pass `$isMirrored` into `renderFrontHtml()`:
  ```php
  public function renderFrontHtml($template, Student $student, School $school, bool $isMirrored = false): string
  {
      $cardHtml = view('components.id-card-renderer', [
          'template' => $template,
          'student' => $student,
          'school' => $school,
          'scale' => 1.0,
          'previewMode' => false,
          'forExport' => true,
          'isMirrored' => $isMirrored,
      ])->render();
      ...
  }
  ```

---

### 4. `app/Jobs/ExportPngZipJob.php`

- Extract `$isMirrored = (bool)($export->params['mirror_print'] ?? false);` and pass to `renderFrontHtml()`.

---

### 5. `app/Jobs/ExportSingleCardPdfJob.php`

- Extract `$isMirrored = (bool)($export->params['mirror_print'] ?? false);` and pass `'isMirrored' => $isMirrored` to `exports.single-card-pdf` view.

---

### 6. `app/Jobs/ExportImpositionPdfJob.php`

- Extract `$isMirrored = (bool)($export->params['mirror_print'] ?? false);`.
- Reverse grid column assignment when mirrored:
  ```php
  $cardIndexInPage = count($currentPageCards);
  $row = (int) floor($cardIndexInPage / $cols);
  $rawCol = $cardIndexInPage % $cols;
  $col = $isMirrored ? ($cols - 1 - $rawCol) : $rawCol;
  ```
- Pass `'isMirrored' => $isMirrored` to `exports.imposition-sheet` view.

---

### 7. PDF Views (`exports/single-card-pdf.blade.php` & `exports/imposition-sheet.blade.php`)

- Pass `:isMirrored="$isMirrored"` to `<x-id-card-renderer>`.

---

## Verification & Testing Plan

1. **Syntax & Static Checks**: `php -l` on modified files; verify clean build with `npm run build`.
2. **UI & State Verification**:
   - Open Export & Print Center modal on Students page.
   - Confirm toggle appears for visual formats and hides for Excel format.
   - Confirm amber warning indicator shows when active.
   - Confirm toggle preference restores across modal re-opens.
3. **Export Output Verification**:
   - **PNG ZIP**: Download ZIP and confirm cards are horizontally flipped.
   - **Single Card PDF**: Open PDF and confirm single cards are horizontally flipped.
   - **Imposition Sheet PDF**: Open PDF and confirm cards are flipped AND column positions are reversed left-to-right for duplex registration.
   - **Recent Exports List**: Confirm `MIRRORED` badge appears on mirrored export entries.
