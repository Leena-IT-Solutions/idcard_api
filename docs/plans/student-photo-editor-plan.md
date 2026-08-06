# Plan: Student Photo Editor (Crop / Background Removal / Background Color / Touch-up)

**Audience:** This document is written to be executed by an AI coding agent (Gemini) with no
prior context on this conversation. It contains the confirmed architecture decisions, exact
files to touch, and an ordered task list. Follow it in order; each phase should be a working,
testable checkpoint before moving to the next.

## 1. Goal

On the Students page (`resources/views/livewire/students/index.blade.php`, Part 4 of the
add/edit student wizard — "Student Photo & Final Review"), replace the plain file-input photo
upload with an in-browser **Photo Studio** editor that lets the operator, before saving:

1. **Crop** to a fixed aspect ratio — **1:1** or **3:4** (toggle, ID-photo standard ratios)
2. **Remove background** (isolate the subject, make background transparent)
3. **Change background color** (solid fill behind the subject — presets + custom picker)
4. **Touch up** — brightness / contrast / saturation / sharpness adjustments with 1-click presets
5. Save the final composited image as the student's `photo_path`, exactly as today.

This directly feeds `app/Services/CardRenderService.php`, which renders the ID card via an HTML
Blade component (`components.id-card-renderer`) + Browsershot. That component simply embeds
`asset('storage/' . $student->photo_path)` as an `<img>` — so **no changes are needed there**;
whatever final image we save is what appears on the card. Consistent crop ratios and clean
backgrounds mean the card layout no longer has to fight with mismatched photo framing.

## 2. Confirmed architecture decisions

| Decision | Choice | Why |
|---|---|---|
| Background removal | **Client-side, in-browser**, via `@imgly/background-removal` (WASM/ONNX) | Zero per-image cost, no API key, no new server/queue infra. Runs entirely in the operator's browser. |
| Touch-up scope | **Basic adjustments only** — brightness, contrast, saturation, sharpness + presets | No AI auto-enhance in v1 (that would require the same paid-API path we're explicitly avoiding for background removal). |
| Editing scope | **Single-photo editor only** (one student at a time, inside the existing add/edit modal) | Bulk/batch apply across a grade/class is out of scope for v1 — see "Future work" (§8). |
| Where edits happen | Entirely **client-side in the browser** (canvas), producing one final `Blob`/`File` that is uploaded once | Avoids multiple round-trips to the server per edit step; server only ever receives the finished image, so `StudentController`/the Volt component's existing validation and storage code barely changes. |
| Non-destructive editing | Kept only **during the current editing session** (in memory), not persisted across saves | Keeps this v1 schema-free — no new DB column. See §8 for the future upgrade path if "re-edit from original later" is wanted. |

## 3. New dependencies

Add to `api/package.json` (`dependencies`):

```
npm install cropperjs @imgly/background-removal
```

- **`cropperjs`** — battle-tested, framework-agnostic cropping UI (drag, zoom, fixed aspect
  ratio, rotate). Do not hand-roll crop/drag/zoom math — this is exactly the kind of subtle,
  fiddly interaction code a dedicated library has already solved.
- **`@imgly/background-removal`** — runs a segmentation model fully client-side via WASM. By
  default it fetches its model files from a jsDelivr CDN at runtime. **Self-host the model
  assets instead**: copy the package's `dist/` model files into `api/public/models/bg-removal/`
  and pass `publicPath: '/models/bg-removal/'` in the library's config. This avoids a runtime
  dependency on a third-party CDN and keeps the feature working if the school's network blocks
  external CDNs. Document this asset-copy step as a `postinstall` note (or a small npm script)
  since the model files must be copied again after every `npm install`/version bump.

No new PHP/Composer packages are required. `spatie/image` (already installed) is optionally
used server-side only for a final normalize/optimize pass (see §5, step 7) — not for cropping
or background removal.

## 4. UX flow

Replace the current "Choose Photo" file input (index.blade.php, the `@if ($activeStep === 4)`
block, roughly lines 1959–1986) with:

1. Operator clicks "Choose Photo" → native file picker (unchanged).
2. On file selection, **do not** bind directly to `wire:model="photo"` anymore. Instead open a
   full-screen/modal **Photo Studio** (new Alpine component, client-side only) pre-loaded with
   the selected image.
3. **Step tabs inside the studio, applied in this order** (order matters — see rationale below):
   - **Crop** — Cropper.js canvas, aspect-ratio toggle `1:1` / `3:4`, drag to reposition, pinch/scroll to zoom, rotate 90° buttons, flip horizontal.
   - **Background** — toggle "Remove background" (runs `@imgly/background-removal` on the *cropped* image, shows a progress/spinner state — first run may take a few seconds while the WASM model initializes). Once removed, show a background-color swatch row (presets: White, Light Grey, Sky Blue, Navy, Red — common ID-card backdrop colors) plus a custom color picker. Selecting a color fills the transparent area.
   - **Touch-up** — sliders for Brightness / Contrast / Saturation / Sharpness (live preview via canvas `ctx.filter`), plus one-click presets ("ID Photo Enhance" = mild contrast + saturation boost). Applied last, on the final composited (cropped + background-filled) image, so adjustments affect the whole photo uniformly.
   - **Reset to original** button, available at any point in the session — discards all in-memory edits and restarts from the untouched selected file (kept as a `Blob` in Alpine state, never mutated).
4. **Preview & Confirm** — shows the final thumbnail. If practical, also render it inside a
   miniature version of the currently-selected ID card template so the operator sees exactly
   how it will look on the printed card before committing (nice-to-have, not a hard requirement
   — skip if it adds significant complexity, see §7 task 6).
5. "Save Photo" — canvas → `toBlob()` → wrap as a `File` named e.g. `student-photo.png` → call
   Livewire's JS upload API: `$wire.upload('photo', file, successCallback, errorCallback, progressCallback)`.
   This reuses the **existing** `public $photo` Livewire property, existing validation rule
   (`'photo' => ['nullable', 'image', 'max:2048']`), and existing save logic in the Volt
   component almost untouched (see §5 step 6 for the one required tweak).
6. Studio closes, the Part-4 preview thumbnail updates from `$photo->temporaryUrl()` exactly as
   it does today.

**Why this order (crop → background → touch-up)**: background removal works best on a
tightly-cropped, face-centered image (less background noise for the segmentation model to
misjudge), and touch-up filters should be the last step so brightness/contrast/saturation are
applied evenly across the final composited image — including the newly-filled background color
— rather than being undone or looking inconsistent if reordered.

## 5. Files to create / modify

1. **`api/package.json`** — add `cropperjs`, `@imgly/background-removal` to `dependencies`.
2. **`api/public/models/bg-removal/`** (new, gitignored or committed per team preference) —
   self-hosted model assets copied from `node_modules/@imgly/background-removal/dist/`.
3. **`api/resources/js/photo-studio.js`** (new) — Alpine component `photoStudio()`:
   - Holds state: `originalFile`, `croppedCanvas`, `bgRemovedCanvas`, `backgroundColor`,
     `touchup: { brightness, contrast, saturation, sharpness }`, `aspectRatio` (`1` or `0.75`),
     `step` (`'crop' | 'background' | 'touchup' | 'preview'`), `isProcessingBg` (bool).
   - Wraps Cropper.js lifecycle (init on file load, destroy on modal close, `setAspectRatio()`
     on toggle).
   - `removeBackground()` — calls `@imgly/background-removal`'s `removeBackground()` against the
     cropped-canvas image, with `publicPath` pointed at `/models/bg-removal/`. Handles/display
     errors gracefully (e.g. unsupported browser) with a message: "Background removal isn't
     available on this device — you can still crop and adjust the photo."
   - `compositeFinalImage()` — draws background color (if set) → draws the (possibly
     bg-removed) subject on top → applies `ctx.filter` string built from the touch-up sliders →
     returns a final `<canvas>`.
   - `save()` — `finalCanvas.toBlob(blob => { const file = new File([blob], 'photo.png', {type: 'image/png'}); this.$wire.upload('photo', file, ...) })`.
4. **`api/resources/js/app.js`** — register the component:
   ```js
   document.addEventListener('alpine:init', () => {
       Alpine.data('photoStudio', photoStudio);
   });
   ```
   (import from `./photo-studio.js`).
5. **`api/resources/views/livewire/students/index.blade.php`**:
   - Replace the plain `<input type="file" wire:model="photo" ...>` block (~lines 1959–1986)
     with a "Choose Photo" trigger that opens the Photo Studio modal, plus a new modal partial
     (can live inline in this file, following the existing pattern of other modals in this
     component, e.g. the bulk-upload modal) using `x-data="photoStudio()"`.
   - No changes needed to the PHP class portion of this Volt component **except** the note in
     step 6 below.
6. **PHP class inside `index.blade.php`** (the `new class extends Component { ... }` block) —
   the existing `save()` method (~line 764 onward) already does everything needed once `$photo`
   is populated (`'photo' => ['nullable', 'image', 'max:2048']`, then
   `$this->photo->store('photos', 'public')`). **One tweak required**: raise the `max:2048` (2MB)
   validation limit modestly (e.g. `max:4096`) since a PNG with an alpha channel (from background
   removal) compresses less efficiently than a JPEG — verify actual output sizes during testing
   (§6) and adjust.
7. **Optional server-side normalize pass** (recommended, keeps storage predictable): in the same
   `save()` method, after `$this->photo->store('photos', 'public')`, use `spatie/image` (already
   a dependency) to re-encode the stored file — resize so the longest edge is capped (e.g.
   1600px, matching typical ID-card print resolution needs, not more), and re-save. This is a
   safety net in case a browser produces an oversized canvas export; it does not change the
   crop/background/touch-up the operator already applied.
8. **`api/resources/css/app.css`** — add styles for the new Photo Studio modal if Tailwind
   utility classes (already used throughout `index.blade.php`) aren't sufficient (they should
   be, for a modal + tabs + sliders — match the existing modal styling patterns already in this
   file rather than introducing a new visual language).

## 6. Testing checklist

- [ ] Upload a portrait photo, crop 1:1, save — verify `photo_path` updates and thumbnail /
      student list / ID card preview all show the cropped image.
- [ ] Same, with 3:4 — verify aspect ratio is exactly 3:4 in the stored file.
- [ ] Remove background on a photo with a busy background — verify transparent PNG composites
      correctly against a chosen background color (no white/black fringing around edges).
- [ ] Skip background removal entirely (crop + touch-up only) — verify original background is
      preserved untouched.
- [ ] Adjust each touch-up slider independently and in combination — verify final saved image
      reflects the adjustments (not just the live preview).
- [ ] "Reset to original" mid-edit — verify it fully discards crop/background/touch-up state.
- [ ] Edit an **existing** student's photo (not just new enrollment) — verify the old file is
      still deleted from storage (existing `Storage::disk('public')->delete(...)` logic) and
      replaced correctly.
- [ ] Test on a lower-end / mobile device — confirm the background-removal step's loading state
      is clear and the graceful-failure message appears if the browser can't run it.
- [ ] Confirm self-hosted model assets load from `/models/bg-removal/` (check browser network
      tab — no requests to jsDelivr or any external CDN).
- [ ] Re-check the `max:2048` validation limit against real exported file sizes (step 5.6) and
      raise it if PNG exports are being rejected.
- [ ] Full regression of the existing bulk CSV+ZIP photo import flow — confirm it is
      **untouched** by this change (it does not go through the new Photo Studio).

## 7. Suggested additions worth including in v1 (low cost, high value, no new infra)

These reuse the same canvas/editor work already being built, so they're cheap to add now:

1. **Rotate (90° steps) and flip horizontal** — trivial with Cropper.js, commonly needed for
   phone-camera photos taken sideways.
2. **Zoom & reposition within the crop frame** — needed anyway for good crop UX; make sure it's
   exposed as an explicit pinch/scroll + drag interaction, not just a fixed auto-crop.
3. **"Reset to original" / non-destructive session editing** — already specified above; call
   out because it's easy to accidentally build destructively (mutating the same canvas at each
   step) if not deliberate about keeping the original `Blob` untouched.
4. **Minimum-resolution / quality guardrail** — if the selected source photo is too small for a
   sharp print (e.g. under ~400px on the short edge after crop), show a warning before saving
   ("This photo may look blurry when printed — consider using a higher-resolution source").
   Simple to compute from the image's natural dimensions, and directly prevents a common
   ID-card complaint (blurry printed photos).
5. **Preset background colors matching common ID-card conventions** (white, light grey, school
   brand color if available from the `School`/template model, red, blue) rather than only a
   raw color picker — faster for operators doing many students in a row.

## 8. Explicitly out of scope for v1 (future work)

- **Bulk/batch photo editing** (e.g. apply the same background color, or auto-crop + auto-remove
  background, to an entire grade/class in one action). The existing bulk CSV+ZIP import
  (`ExportExcelPhotoZipJob` / the bulk upload modal in `index.blade.php`) is unaffected by this
  plan and continues to accept photos as-is; batch editing of already-imported photos is a
  natural v2 feature once the single-photo editor is validated in production.
- **AI auto-enhance / skin smoothing** — would require a paid API (Photoroom/remove.bg-style),
  which was explicitly ruled out for background removal too, for the same cost/infra reasons.
- **Webcam capture** ("take photo" directly instead of file upload) — a nice addition but
  orthogonal to editing; can be added later as just another way to populate `originalFile` in
  the Photo Studio, with no changes to the editor itself.
- **Face-detection-assisted auto-crop** (auto-center/zoom to a detected face before the operator
  manually adjusts) — worth revisiting once the manual crop flow is in production and if
  operators report the manual step is too slow for high-volume enrollment days.
- **Preserving the pristine original photo after save** (a `original_photo_path` column) so an
  operator can return days later and fully re-edit from scratch (e.g. undo a background removal
  applied last week). v1 only keeps the original in-memory during the active edit session, per
  the confirmed decision in §2. Flagging this explicitly so it's a conscious trade-off, not an
  oversight: if it turns out operators frequently need to re-edit already-saved photos, add one
  nullable string column via migration and mirror the existing `photo_path` storage pattern.

## 9. Rollout notes

- No database migration required for v1.
- No new environment variables or server-side services required.
- `npm install` picks up the two new packages; remember to (re-)copy the `@imgly/background-removal`
  model assets into `public/models/bg-removal/` after install (see §3) — this step is easy to
  forget and will silently fall back to (or fail against) the CDN default if skipped.
- Run `npm run build` (Vite) before deploying so the new `resources/js/photo-studio.js` module
  and its imports are bundled.
