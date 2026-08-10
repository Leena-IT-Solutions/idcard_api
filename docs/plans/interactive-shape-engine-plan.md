# Implementation Plan: Interactive Shape Engine for Canva Studio

This plan outlines the architecture and implementation strategy for adding **Interactive Shapes** to the Canva Studio ID Card Editor (`resources/views/livewire/templates/edit.blade.php`) and the output Card Renderer (`resources/views/components/id-card-renderer.blade.php`).

---

## Technical Overview & Proposed Architecture

Currently, Canva Studio supports 4 layer types (`text`, `photo`, `logo`, `qr`). We will introduce a new dynamic `shape` layer type (`shape_type`: `circle`, `rectangle`, `line`, `badge`, `star`, `polygon`) with live canvas rendering, interactive resize/rotation handles, and shape-specific property inspectors.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                             CANVA STUDIO TOOLBAR                            │
│  [+ Add Text]  [+ Add Photo]  [+ Add Logo]  [+ Add Shape ▾ (Circle/Rect/Line)]│
└─────────────────────────────────────────────────────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────┬───────────────────────────────────────┐
│         INTERACTIVE CANVAS          │          PROPERTY INSPECTOR           │
│                                     │                                       │
│  ┌───────────────────────────────┐  │  • Shape Type: Circle                 │
│  │   🔵 Circle (Radius: 35px)   │  │  • Radius: [ 35px ] (Slider)         │
│  │   Stroke: 2px Dashed          │  │  • Fill: Solid / Gradient            │
│  │   Fill: #4f46e5 (Indigo)      │  │  • Stroke Color & Thickness (2px)     │
│  └───────────────────────────────┘  │  • Stroke Style: Solid/Dashed/Dotted  │
│                                     │  • Drop Shadow & Opacity              │
└─────────────────────────────────────┴───────────────────────────────────────┘
```

---

## 1. Supported Shapes & Shape-Specific Properties

| Shape Type | Primary Geometry & Specific Controls | Use Cases in ID Cards |
| :--- | :--- | :--- |
| **Circle / Ellipse (`circle`)** | `radius` / `diameter`, equal aspect lock toggle | Student photo background halo, security seal, badge icon |
| **Rectangle & Rounded Box (`rectangle`)** | `width`, `height`, `corner_radius` (0px to 50px / 50% pill) | Card headers, footer banners, data highlight blocks |
| **Line & Divider (`line`)** | `length` / `width`, `stroke_width` (thickness), `stroke_style` (`solid`, `dashed`, `dotted`) | Section dividers, header underlines |
| **Ribbon / Header Badge (`badge`)** | `width`, `height`, `corner_radius`, `fill_color`, `stroke_color` | "STAFF", "STUDENT ID", "BUS PASS" header bars |
| **Star / Shield Accent (`star`)** | `points` (4 to 8), `inner_radius_ratio`, `fill_color` | School excellence badges, security emblems |

---

## 2. Advanced Features & Suggestions (What Else We Can Do)

Beyond basic Fill, Stroke, and Radius, we recommend implementing the following high-value design features:

1. **Dual Fill Modes (Solid & Gradient Fill)**:
   - **Solid Color**: Color picker with opacity/alpha slider (`fill_color`).
   - **Linear & Radial Gradients**: 2-color gradient support (`gradient_start`, `gradient_end`, `gradient_angle`).
   - **Transparent Fill**: Zero opacity fill for outline-only shapes.
2. **Advanced Stroke & Border Controls**:
   - **Stroke Styles**: Solid, Dashed, and Dotted lines (`stroke_style`).
   - **Stroke Alignment**: Inside, Center, or Outside border stroke.
3. **Drop Shadow & Glow Effects**:
   - Toggle drop shadow (`shadow_enabled`, `shadow_color`, `shadow_blur`, `shadow_offset_x`, `shadow_offset_y`).
4. **Preset Shape Library & Quick-Insert Templates**:
   - 1-click insertion of popular ID card design components (e.g., *Divider Line*, *Header Banner*, *Photo Backdrop Ring*, *Footer Bar*).
5. **Seamless Canvas Integration**:
   - 8-point interactive resize handles on canvas.
   - Aspect ratio locking (important for perfect circles & squares).
   - Rotation handle & degree input.
   - Layer Opacity (0% - 100%).
   - Layer ordering (Bring to Front, Send to Back) & Grouping compatibility.

---

## 3. Detailed Proposed File Changes

### A. `resources/views/livewire/templates/edit.blade.php` (Canva Studio Editor)
- **Livewire Volt Component Methods**:
  - Add `addShapeLayer($shapeType = 'rectangle')` method initializing default geometry and colors.
  - Update `updateCommonProperty` and layer property mutation methods to handle `radius`, `corner_radius`, `fill_color`, `stroke_color`, `stroke_width`, `stroke_style`, `shadow_*`, and `gradient_*`.
- **Toolbar & Dropdown UI**:
  - Add a **Shape Picker Menu** in the studio toolbar with icons for Rectangle, Circle, Line, Badge, Star.
- **Interactive Canvas Rendering**:
  - Render shape layers using CSS/SVG with interactive bounding boxes, resize points, and rotation handles.
- **Property Inspector Sidebar**:
  - Render shape-specific controls dynamically when a shape layer is selected (Radius slider for Circles, Corner Radius for Rectangles, Line Thickness & Dash pattern for Lines, Fill/Stroke color pickers).

### B. `resources/views/components/id-card-renderer.blade.php` (Card Engine Renderer)
- Add `@elseif($type === 'shape' || in_array($type, ['rectangle', 'circle', 'line', 'badge', 'star']))` branch to render pure CSS/SVG shapes identically during PDF exports (Chromium/Browsershot) and PNG exports.

### C. Serialization & JSON Compatibility
- Ensure new shape properties cleanly serialize into template JSON exports and import back into the studio without data loss.

---

## User Review & Decisions Required

> [!IMPORTANT]
> **Shape Architecture**: All shape properties will be saved in the existing `layout_config` JSON field without requiring database schema migrations.

---

## Verification Plan

### Automated & Technical Checks
- Run `php -l` on modified Blade & Livewire component files.
- Run `npm run build` to compile Vite production assets.

### Manual Verification Steps
1. Open Canva Studio (`/school-admin/templates/{id}/edit`).
2. Add a **Circle**: Adjust radius slider, change fill color & stroke color, verify live canvas updates.
3. Add a **Rectangle**: Change corner radius (rounded box), resize with handles, rotate.
4. Add a **Line**: Change thickness, set stroke style to `dashed`.
5. Export template to **PDF & PNG** to verify shapes render razor-sharp in print outputs.
