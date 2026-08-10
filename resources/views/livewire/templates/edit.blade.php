<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Template;
use App\Models\SchoolTemplate;
use App\Models\School;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public $templateId;
    public string $type = 'master'; // 'master' or 'school'
    public $template = null;

    // Editable template properties
    public string $templateName = '';
    public string $orientation = 'landscape';
    public float $widthMm = 85.60;
    public float $heightMm = 54.00;
    public array $layers = [];
    public $bgUpload = null;
    public $uploadedImage = null;

    // Studio Canvas Settings
    public bool $showGrid = true;
    public bool $enableSnapping = true;
    public bool $livePreviewMode = true; // Show mock data vs placeholder text
    public ?int $selectedLayerIndex = null;
    public array $selectedLayerIndices = [];
    public array $undoStack = [];
    public array $redoStack = [];

    public function mount($templateId, $type = 'master')
    {
        $this->templateId = $templateId;
        $this->type = request()->query('type', $type);

        if ($this->type === 'school') {
            $this->template = SchoolTemplate::find($templateId);
        } else {
            $this->template = Template::find($templateId);
        }

        if (!$this->template) {
            session()->flash('error', 'Template not found.');
            return redirect()->route('templates');
        }

        $this->templateName = $this->template->name ?? ($this->type === 'school' && $this->template->masterTemplate ? $this->template->masterTemplate->name : '');
        $this->orientation = $this->template->orientation ?? ($this->type === 'school' && $this->template->masterTemplate ? $this->template->masterTemplate->orientation : 'landscape');
        $this->widthMm = (float)($this->template->width_mm ?? ($this->type === 'school' && $this->template->masterTemplate ? $this->template->masterTemplate->width_mm : 85.60));
        $this->heightMm = (float)($this->template->height_mm ?? ($this->type === 'school' && $this->template->masterTemplate ? $this->template->masterTemplate->height_mm : 54.00));

        $config = $this->template->layout_config;
        $this->layers = is_array($config) ? $config : (is_string($config) ? json_decode($config, true) : []);

        if (empty($this->layers)) {
            $this->layers = [
                [
                    'id' => 'student_name',
                    'type' => 'text',
                    'label' => 'Student Name',
                    'text' => '{First Name} {Middle Name} {Last Name}',
                    'x' => 130,
                    'y' => 82,
                    'font_size' => 16,
                    'font_weight' => 'bold',
                    'font_family' => 'Inter',
                    'color' => '#ffffff',
                    'align' => 'left',
                    'rotation' => 0,
                ]
            ];
        }

        foreach ($this->layers as $idx => &$l) {
            if (empty($l['id'])) {
                $l['id'] = 'layer_' . $idx . '_' . rand(1000, 9999);
            }
        }
        unset($l);
    }

    public function recordHistory()
    {
        if (!empty($this->undoStack) && end($this->undoStack) === $this->layers) {
            return;
        }

        $this->undoStack[] = $this->layers;

        if (count($this->undoStack) > 50) {
            array_shift($this->undoStack);
        }

        $this->redoStack = [];
    }

    public function undo()
    {
        if (empty($this->undoStack)) return;

        $this->redoStack[] = $this->layers;
        $this->layers = array_pop($this->undoStack);
        $this->selectLayer(null);
    }

    public function redo()
    {
        if (empty($this->redoStack)) return;

        $this->undoStack[] = $this->layers;
        $this->layers = array_pop($this->redoStack);
        $this->selectLayer(null);
    }

    public function updating($name, $value)
    {
        if (str_starts_with($name, 'layers')) {
            $this->recordHistory();
        }
    }

    public function selectLayer(?int $index = null, bool $shift = false)
    {

        if ($index === null) {
            $this->selectedLayerIndices = [];
            $this->selectedLayerIndex = null;
            return;
        }

        // Find the group of the clicked layer (if any)
        $targetGroupId = $this->layers[$index]['group_id'] ?? null;
        $indicesToToggle = [$index];

        if ($targetGroupId) {
            $indicesToToggle = [];
            foreach ($this->layers as $i => $layer) {
                if (($layer['group_id'] ?? null) === $targetGroupId) {
                    $indicesToToggle[] = $i;
                }
            }
        }

        if ($shift) {
            // If any element of the group is already selected, remove the whole group
            $hasIntersection = !empty(array_intersect($indicesToToggle, $this->selectedLayerIndices));
            if ($hasIntersection) {
                $this->selectedLayerIndices = array_diff($this->selectedLayerIndices, $indicesToToggle);
            } else {
                $this->selectedLayerIndices = array_merge($this->selectedLayerIndices, $indicesToToggle);
            }
            $this->selectedLayerIndices = array_values(array_unique($this->selectedLayerIndices));
        } else {
            $this->selectedLayerIndices = $indicesToToggle;
        }

        if (count($this->selectedLayerIndices) === 1) {
            $this->selectedLayerIndex = $this->selectedLayerIndices[0];
        } else {
            $this->selectedLayerIndex = null;
        }

    }

    public function selectLayerBatch(array $indices)
    {
        $this->selectedLayerIndices = array_values(array_unique(array_map('intval', $indices)));
        if (count($this->selectedLayerIndices) === 1) {
            $this->selectedLayerIndex = $this->selectedLayerIndices[0];
        } else {
            $this->selectedLayerIndex = null;
        }
    }

    public function setOrientation(string $newOrientation)
    {
        if (!in_array($newOrientation, ['landscape', 'portrait'])) return;
        $this->orientation = $newOrientation;
        if ($newOrientation === 'portrait') {
            $this->widthMm = 54.00;
            $this->heightMm = 85.60;
        } else {
            $this->widthMm = 85.60;
            $this->heightMm = 54.00;
        }
        $this->saveStudioDesign();
    }

    public function updateMultipleLayersCoordinates(array $updates)
    {
        $this->recordHistory();
        foreach ($updates as $update) {
            $idx = $update['index'];
            if (isset($this->layers[$idx])) {
                $this->layers[$idx]['x'] = $update['x'];
                $this->layers[$idx]['y'] = $update['y'];
            }
        }
    }

    public function duplicateSelected()
    {
        if (empty($this->selectedLayerIndices)) {
            if ($this->selectedLayerIndex !== null) {
                $this->selectedLayerIndices = [$this->selectedLayerIndex];
            } else {
                return;
            }
        }

        $this->recordHistory();
        $newIndices = [];
        $sortedIndices = $this->selectedLayerIndices;
        sort($sortedIndices);

        $groupMap = [];

        foreach ($sortedIndices as $idx) {
            if (!isset($this->layers[$idx])) continue;

            $layer = $this->layers[$idx];
            $duplicate = $layer;
            $duplicate['id'] = $layer['type'] . '_' . microtime(true) . '_' . rand(1000, 9999);
            $duplicate['x'] = ($layer['x'] ?? 50) + 15;
            $duplicate['y'] = ($layer['y'] ?? 50) + 15;
            
            if (isset($duplicate['label'])) {
                $duplicate['label'] .= ' (Copy)';
            }

            $oldGroupId = $layer['group_id'] ?? null;
            if ($oldGroupId) {
                if (!isset($groupMap[$oldGroupId])) {
                    $groupMap[$oldGroupId] = 'group_' . microtime(true) . '_' . rand(1000, 9999);
                }
                $duplicate['group_id'] = $groupMap[$oldGroupId];
            }

            $this->layers[] = $duplicate;
            $newIndices[] = count($this->layers) - 1;
        }

        $this->selectedLayerIndices = $newIndices;
        if (count($newIndices) === 1) {
            $this->selectedLayerIndex = $newIndices[0];
        } else {
            $this->selectedLayerIndex = null;
        }
    }

    public function groupSelected()
    {
        if (count($this->selectedLayerIndices) <= 1) return;

        $this->recordHistory();
        $groupId = 'group_' . microtime(true) . '_' . rand(1000, 9999);
        foreach ($this->selectedLayerIndices as $idx) {
            if (isset($this->layers[$idx])) {
                $this->layers[$idx]['group_id'] = $groupId;
            }
        }
    }

    public function ungroupSelected()
    {
        $groupIdsToClear = [];

        if ($this->selectedLayerIndex !== null && isset($this->layers[$this->selectedLayerIndex])) {
            if (!empty($this->layers[$this->selectedLayerIndex]['group_id'])) {
                $groupIdsToClear[] = $this->layers[$this->selectedLayerIndex]['group_id'];
            }
        }

        foreach ($this->selectedLayerIndices as $idx) {
            if (isset($this->layers[$idx]) && !empty($this->layers[$idx]['group_id'])) {
                $groupIdsToClear[] = $this->layers[$idx]['group_id'];
            }
        }

        $groupIdsToClear = array_unique($groupIdsToClear);
        if (empty($groupIdsToClear)) return;

        $this->recordHistory();

        foreach ($this->layers as $i => $layer) {
            if (isset($layer['group_id']) && in_array($layer['group_id'], $groupIdsToClear)) {
                $this->layers[$i]['group_id'] = null;
            }
        }
    }

    public function updateCommonProperty(string $property, $value)
    {
        if (empty($this->selectedLayerIndices)) return;

        foreach ($this->selectedLayerIndices as $idx) {
            if (!isset($this->layers[$idx])) continue;

            if ($property === 'width') {
                $this->layers[$idx]['width'] = round(floatval($value));
            } elseif ($property === 'height') {
                $this->layers[$idx]['height'] = round(floatval($value));
            } elseif ($property === 'x') {
                $this->layers[$idx]['x'] = round(floatval($value));
            } elseif ($property === 'y') {
                $this->layers[$idx]['y'] = round(floatval($value));
            } elseif ($property === 'rotation') {
                $this->layers[$idx]['rotation'] = round(floatval($value));
            }
        }
    }

    public function addTextLayer()
    {
        $this->recordHistory();
        $newIndex = count($this->layers);

        // Find last text layer to copy its styling settings
        $lastTextLayer = null;
        if ($this->selectedLayerIndex !== null && isset($this->layers[$this->selectedLayerIndex]) && $this->layers[$this->selectedLayerIndex]['type'] === 'text') {
            $lastTextLayer = $this->layers[$this->selectedLayerIndex];
        } else {
            for ($i = count($this->layers) - 1; $i >= 0; $i--) {
                if ($this->layers[$i]['type'] === 'text') {
                    $lastTextLayer = $this->layers[$i];
                    break;
                }
            }
        }

        $fontSize = $lastTextLayer ? ($lastTextLayer['font_size'] ?? 14) : 14;
        $fontWeight = $lastTextLayer ? ($lastTextLayer['font_weight'] ?? 'bold') : 'bold';
        $fontFamily = $lastTextLayer ? ($lastTextLayer['font_family'] ?? 'Inter') : 'Inter';
        $color = $lastTextLayer ? ($lastTextLayer['color'] ?? '#ffffff') : '#ffffff';
        $align = $lastTextLayer ? ($lastTextLayer['align'] ?? 'left') : 'left';
        $rotation = $lastTextLayer ? ($lastTextLayer['rotation'] ?? 0) : 0;
        $x = $lastTextLayer ? (($lastTextLayer['x'] ?? 100) + 15) : 100;
        $y = $lastTextLayer ? (($lastTextLayer['y'] ?? 100) + 15) : 100;

        $this->layers[] = [
            'id' => 'text_' . microtime(true) . '_' . rand(1000, 9999),
            'type' => 'text',
            'label' => 'Text Layer ' . ($newIndex + 1),
            'text' => 'Sample Text Layer',
            'x' => $x,
            'y' => $y,
            'font_size' => $fontSize,
            'font_weight' => $fontWeight,
            'font_family' => $fontFamily,
            'color' => $color,
            'align' => $align,
            'rotation' => $rotation,
        ];
        $this->selectLayer($newIndex, false);
    }

    public function addPhotoLayer()
    {
        $this->recordHistory();
        $newIndex = count($this->layers);

        // Find last photo layer to copy its styling settings
        $lastPhotoLayer = null;
        if ($this->selectedLayerIndex !== null && isset($this->layers[$this->selectedLayerIndex]) && $this->layers[$this->selectedLayerIndex]['type'] === 'photo') {
            $lastPhotoLayer = $this->layers[$this->selectedLayerIndex];
        } else {
            for ($i = count($this->layers) - 1; $i >= 0; $i--) {
                if ($this->layers[$i]['type'] === 'photo') {
                    $lastPhotoLayer = $this->layers[$i];
                    break;
                }
            }
        }

        $w = $lastPhotoLayer ? ($lastPhotoLayer['width'] ?? 90) : 90;
        $h = $lastPhotoLayer ? ($lastPhotoLayer['height'] ?? 110) : 110;
        $borderRadius = $lastPhotoLayer ? ($lastPhotoLayer['border_radius'] ?? 12) : 12;
        $borderColor = $lastPhotoLayer ? ($lastPhotoLayer['border_color'] ?? '#818cf8') : '#818cf8';
        $borderWidth = $lastPhotoLayer ? ($lastPhotoLayer['border_width'] ?? 2) : 2;
        $rotation = $lastPhotoLayer ? ($lastPhotoLayer['rotation'] ?? 0) : 0;
        $x = $lastPhotoLayer ? (($lastPhotoLayer['x'] ?? 24) + 15) : 24;
        $y = $lastPhotoLayer ? (($lastPhotoLayer['y'] ?? 80) + 15) : 80;

        $this->layers[] = [
            'id' => 'photo_' . microtime(true) . '_' . rand(1000, 9999),
            'type' => 'photo',
            'label' => 'Student Photo',
            'x' => $x,
            'y' => $y,
            'width' => $w,
            'height' => $h,
            'border_radius' => $borderRadius,
            'border_color' => $borderColor,
            'border_width' => $borderWidth,
            'rotation' => $rotation,
        ];
        $this->selectLayer($newIndex, false);
    }

    public function addLogoLayer()
    {
        $this->recordHistory();
        $newIndex = count($this->layers);

        // Find last logo layer to copy its styling settings
        $lastLogoLayer = null;
        if ($this->selectedLayerIndex !== null && isset($this->layers[$this->selectedLayerIndex]) && $this->layers[$this->selectedLayerIndex]['type'] === 'logo') {
            $lastLogoLayer = $this->layers[$this->selectedLayerIndex];
        } else {
            for ($i = count($this->layers) - 1; $i >= 0; $i--) {
                if ($this->layers[$i]['type'] === 'logo') {
                    $lastLogoLayer = $this->layers[$i];
                    break;
                }
            }
        }

        $w = $lastLogoLayer ? ($lastLogoLayer['width'] ?? 45) : 45;
        $h = $lastLogoLayer ? ($lastLogoLayer['height'] ?? 45) : 45;
        $borderRadius = $lastLogoLayer ? ($lastLogoLayer['border_radius'] ?? 8) : 8;
        $rotation = $lastLogoLayer ? ($lastLogoLayer['rotation'] ?? 0) : 0;
        $x = $lastLogoLayer ? (($lastLogoLayer['x'] ?? 24) + 15) : 24;
        $y = $lastLogoLayer ? (($lastLogoLayer['y'] ?? 20) + 15) : 20;

        $this->layers[] = [
            'id' => 'logo_' . microtime(true) . '_' . rand(1000, 9999),
            'type' => 'logo',
            'label' => 'School Logo',
            'x' => $x,
            'y' => $y,
            'width' => $w,
            'height' => $h,
            'border_radius' => $borderRadius,
            'rotation' => $rotation,
        ];
        $this->selectLayer($newIndex, false);
    }

    public function addQrLayer()
    {
        $this->recordHistory();
        $newIndex = count($this->layers);

        // Find last QR layer to copy its styling settings
        $lastQrLayer = null;
        if ($this->selectedLayerIndex !== null && isset($this->layers[$this->selectedLayerIndex]) && $this->layers[$this->selectedLayerIndex]['type'] === 'qr') {
            $lastQrLayer = $this->layers[$this->selectedLayerIndex];
        } else {
            for ($i = count($this->layers) - 1; $i >= 0; $i--) {
                if ($this->layers[$i]['type'] === 'qr') {
                    $lastQrLayer = $this->layers[$i];
                    break;
                }
            }
        }

        $w = $lastQrLayer ? ($lastQrLayer['width'] ?? 60) : 60;
        $h = $lastQrLayer ? ($lastQrLayer['height'] ?? 60) : 60;
        $x = $lastQrLayer ? (($lastQrLayer['x'] ?? 300) + 15) : 300;
        $y = $lastQrLayer ? (($lastQrLayer['y'] ?? 200) + 15) : 200;

        $this->layers[] = [
            'id' => 'qr_' . microtime(true) . '_' . rand(1000, 9999),
            'type' => 'qr',
            'label' => 'QR Code',
            'x' => $x,
            'y' => $y,
            'width' => $w,
            'height' => $h,
            'value' => '{Ref No}',
        ];
        $this->selectLayer($newIndex, false);
    }

    public function addBarcodeLayer()
    {
        $this->recordHistory();
        $newIndex = count($this->layers);

        // Find last barcode layer to copy its styling settings
        $lastBarcodeLayer = null;
        if ($this->selectedLayerIndex !== null && isset($this->layers[$this->selectedLayerIndex]) && $this->layers[$this->selectedLayerIndex]['type'] === 'barcode') {
            $lastBarcodeLayer = $this->layers[$this->selectedLayerIndex];
        } else {
            for ($i = count($this->layers) - 1; $i >= 0; $i--) {
                if ($this->layers[$i]['type'] === 'barcode') {
                    $lastBarcodeLayer = $this->layers[$i];
                    break;
                }
            }
        }

        $w = $lastBarcodeLayer ? ($lastBarcodeLayer['width'] ?? 160) : 160;
        $h = $lastBarcodeLayer ? ($lastBarcodeLayer['height'] ?? 45) : 45;
        $x = $lastBarcodeLayer ? (($lastBarcodeLayer['x'] ?? 100) + 15) : 100;
        $y = $lastBarcodeLayer ? (($lastBarcodeLayer['y'] ?? 240) + 15) : 240;

        $this->layers[] = [
            'id' => 'barcode_' . microtime(true) . '_' . rand(1000, 9999),
            'type' => 'barcode',
            'label' => 'Barcode',
            'x' => $x,
            'y' => $y,
            'width' => $w,
            'height' => $h,
            'value' => '{Ref No}',
            'show_text' => true,
        ];
        $this->selectLayer($newIndex, false);
    }

    public function getUploadedAssetsProperty()
    {
        try {
            $files = Storage::disk('public')->files('uploads');
            $images = array_filter($files, function ($file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg']);
            });

            usort($images, function ($a, $b) {
                return Storage::disk('public')->lastModified($b) <=> Storage::disk('public')->lastModified($a);
            });

            return array_values($images);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function updatedUploadedImage()
    {
        $this->validate([
            'uploadedImage' => 'image|max:10240', // 10MB max JPG, PNG, WEBP, SVG
        ]);

        $path = $this->uploadedImage->store('uploads', 'public');

        $this->recordHistory();

        if ($this->selectedLayerIndex !== null && isset($this->layers[$this->selectedLayerIndex]) && ($this->layers[$this->selectedLayerIndex]['type'] ?? '') === 'image') {
            $this->layers[$this->selectedLayerIndex]['image_path'] = $path;
        } else {
            $newIndex = count($this->layers);
            $this->layers[] = [
                'id' => 'image_' . microtime(true) . '_' . rand(1000, 9999),
                'type' => 'image',
                'label' => 'Uploaded Image',
                'image_path' => $path,
                'x' => 100,
                'y' => 80,
                'width' => 90,
                'height' => 90,
                'border_radius' => 8,
                'border_color' => '#818cf8',
                'border_width' => 0,
                'opacity' => 100,
                'rotation' => 0,
                'object_fit' => 'contain',
            ];
            $this->selectLayer($newIndex, false);
        }

        $this->uploadedImage = null;
    }

    public function addAssetToCanvas(string $assetPath)
    {
        $this->recordHistory();

        if ($this->selectedLayerIndex !== null && isset($this->layers[$this->selectedLayerIndex]) && ($this->layers[$this->selectedLayerIndex]['type'] ?? '') === 'image') {
            $this->layers[$this->selectedLayerIndex]['image_path'] = $assetPath;
            return;
        }

        $newIndex = count($this->layers);
        $this->layers[] = [
            'id' => 'image_' . microtime(true) . '_' . rand(1000, 9999),
            'type' => 'image',
            'label' => 'Uploaded Image',
            'image_path' => $assetPath,
            'x' => 100,
            'y' => 80,
            'width' => 90,
            'height' => 90,
            'border_radius' => 8,
            'border_color' => '#818cf8',
            'border_width' => 0,
            'opacity' => 100,
            'rotation' => 0,
            'object_fit' => 'contain',
        ];

        $this->selectLayer($newIndex, false);
    }

    public function deleteUploadedAsset(string $assetPath)
    {
        if (Storage::disk('public')->exists($assetPath)) {
            Storage::disk('public')->delete($assetPath);
        }
    }

    private function shapeDefaults(string $shapeType): array
    {
        $shapeType = in_array($shapeType, ['rectangle', 'circle', 'line']) ? $shapeType : 'rectangle';

        $shared = [
            'type' => 'shape',
            'shape_type' => $shapeType,
            'width' => 120,
            'height' => 60,
            'rotation' => 0,
            'fill_type' => 'solid',
            'fill_color' => '#4f46e5',
            'fill_opacity' => 100,
            'stroke_color' => '#312e81',
            'stroke_width' => 2,
            'stroke_style' => 'solid',
            'stroke_alignment' => 'center',
            'opacity' => 100,
            'group_id' => null,
        ];

        return match ($shapeType) {
            'circle' => array_merge($shared, [
                'label' => 'Circle Shape',
                'width' => 90,
                'height' => 90,
                'aspect_locked' => true,
            ]),
            'line' => array_merge($shared, [
                'label' => 'Line Divider',
                'width' => 140,
                'height' => 20,
                'stroke_width' => 3,
                'fill_type' => 'none',
            ]),
            default => array_merge($shared, [
                'label' => 'Rectangle Shape',
                'corner_radius' => 8,
                'corner_radius_pill' => false,
            ]),
        };
    }

    public function addShapeLayer(string $shapeType = 'rectangle')
    {
        $this->recordHistory();
        $newIndex = count($this->layers);

        // Find last shape layer of the same shape_type to copy its styling settings
        $lastShapeLayer = null;
        for ($i = count($this->layers) - 1; $i >= 0; $i--) {
            if (($this->layers[$i]['type'] ?? null) === 'shape' && ($this->layers[$i]['shape_type'] ?? null) === $shapeType) {
                $lastShapeLayer = $this->layers[$i];
                break;
            }
        }

        $defaults = $this->shapeDefaults($shapeType);

        if ($lastShapeLayer) {
            foreach (array_keys($defaults) as $prop) {
                if (in_array($prop, ['type', 'shape_type', 'label'], true)) continue;
                if (array_key_exists($prop, $lastShapeLayer)) {
                    $defaults[$prop] = $lastShapeLayer[$prop];
                }
            }
        }

        $x = $lastShapeLayer ? (($lastShapeLayer['x'] ?? 100) + 15) : 100;
        $y = $lastShapeLayer ? (($lastShapeLayer['y'] ?? 100) + 15) : 100;

        $this->layers[] = array_merge($defaults, [
            'id' => 'shape_' . microtime(true) . '_' . rand(1000, 9999),
            'x' => $x,
            'y' => $y,
        ]);
        $this->selectLayer($newIndex, false);
    }

    public function updateShapeProperty(string $property, $value)
    {
        if (empty($this->selectedLayerIndices)) return;
        $this->recordHistory();

        foreach ($this->selectedLayerIndices as $idx) {
            if (!isset($this->layers[$idx]) || ($this->layers[$idx]['type'] ?? null) !== 'shape') continue;
            $shapeType = $this->layers[$idx]['shape_type'] ?? 'rectangle';
            $this->layers[$idx][$property] = $this->sanitizeShapeProperty($property, $value, $shapeType, $this->layers[$idx]);
        }
    }

    private function sanitizeShapeProperty(string $property, $value, string $shapeType, array $layer)
    {
        $hexColor = function ($v, $default) {
            return is_string($v) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $v) ? $v : $default;
        };

        return match ($property) {
            'fill_color', 'stroke_color' => $hexColor($value, $layer[$property] ?? '#000000'),
            'fill_opacity', 'opacity' => max(0, min(100, (int)round((float)$value))),
            'stroke_width' => max(0, min(40, (int)round((float)$value))),
            'corner_radius' => max(0, min((int)round((float)$value), (int)(min($layer['width'] ?? 999, $layer['height'] ?? 999) / 2))),
            'fill_type' => in_array($value, ['solid', 'gradient', 'none'], true) ? $value : 'solid',
            'stroke_style' => in_array($value, ['solid', 'dashed', 'dotted'], true) ? $value : 'solid',
            'stroke_alignment' => 'center', // only 'center' is supported until Phase 3 (viewBox padding math for inside/outside)
            'corner_radius_pill', 'aspect_locked' => (bool)$value,
            default => $value,
        };
    }

    public function removeLayer(int $index)
    {
        $this->recordHistory();
        if ($index === -1) {
            $sorted = $this->selectedLayerIndices;
            rsort($sorted);
            foreach ($sorted as $idx) {
                if (isset($this->layers[$idx])) {
                    array_splice($this->layers, $idx, 1);
                }
            }
            $this->selectLayer(null);
            return;
        }

        if (isset($this->layers[$index])) {
            array_splice($this->layers, $index, 1);
            $this->selectLayer(null);
        }
    }

    public function moveLayer(?int $index, string $direction)
    {
        if ($index === null) {
            $index = $this->selectedLayerIndex;
        }
        if ($index === null || !isset($this->layers[$index])) return;

        $targetLayer = $this->layers[$index];
        $targetId = $targetLayer['id'] ?? null;

        $this->recordHistory();
        $total = count($this->layers);

        if ($direction === 'front' || $direction === 'top') {
            // Bring to Front (Move to end of array)
            array_splice($this->layers, $index, 1);
            $this->layers[] = $targetLayer;
        } elseif ($direction === 'back' || $direction === 'bottom') {
            // Send to Back (Move to start of array)
            array_splice($this->layers, $index, 1);
            array_unshift($this->layers, $targetLayer);
        } elseif (($direction === 'up' || $direction === 'forward') && $index < $total - 1) {
            // Move up in stack (+1 position)
            $temp = $this->layers[$index];
            $this->layers[$index] = $this->layers[$index + 1];
            $this->layers[$index + 1] = $temp;
        } elseif (($direction === 'down' || $direction === 'backward') && $index > 0) {
            // Move down in stack (-1 position)
            $temp = $this->layers[$index];
            $this->layers[$index] = $this->layers[$index - 1];
            $this->layers[$index - 1] = $temp;
        }

        // Re-locate target layer index by ID to guarantee selection stays locked on selected element
        $newIndex = null;
        if ($targetId) {
            foreach ($this->layers as $i => $l) {
                if (($l['id'] ?? null) === $targetId) {
                    $newIndex = $i;
                    break;
                }
            }
        }

        if ($newIndex !== null) {
            $this->selectedLayerIndex = $newIndex;
            $this->selectedLayerIndices = [$newIndex];
        }
    }

    public function alignSelectedLayer(string $alignment)
    {
        if ($this->selectedLayerIndex === null || !isset($this->layers[$this->selectedLayerIndex])) return;

        $canvasWidth = $this->orientation === 'portrait' ? 638 : 1011;
        $canvasHeight = $this->orientation === 'portrait' ? 1011 : 638;

        $layer = &$this->layers[$this->selectedLayerIndex];
        $layerW = $layer['width'] ?? 150;
        $layerH = $layer['height'] ?? 30;

        switch ($alignment) {
            case 'left':
                $layer['x'] = 20;
                break;
            case 'center_h':
                $layer['x'] = (int)(($canvasWidth - $layerW) / 2);
                break;
            case 'right':
                $layer['x'] = (int)($canvasWidth - $layerW - 20);
                break;
            case 'top':
                $layer['y'] = 20;
                break;
            case 'center_v':
                $layer['y'] = (int)(($canvasHeight - $layerH) / 2);
                break;
            case 'bottom':
                $layer['y'] = (int)($canvasHeight - $layerH - 20);
                break;
        }
    }

    public function appendVariableToSelected(string $tag)
    {
        if ($tag === '{School Logo}') {
            $this->addLogoLayer();
            return;
        }
        if ($tag === '{Student Photo}') {
            $this->addPhotoLayer();
            return;
        }
        if ($tag === '{QR Code}') {
            $this->addQrLayer();
            return;
        }
        if ($tag === '{Barcode}') {
            $this->addBarcodeLayer();
            return;
        }

        if ($this->selectedLayerIndex !== null && isset($this->layers[$this->selectedLayerIndex])) {
            $type = $this->layers[$this->selectedLayerIndex]['type'] ?? '';
            if ($type === 'text') {
                $current = trim($this->layers[$this->selectedLayerIndex]['text'] ?? '');
                $this->layers[$this->selectedLayerIndex]['text'] = $current !== '' ? ($current . ' ' . $tag) : $tag;
            } elseif ($type === 'qr' || $type === 'barcode') {
                $current = trim($this->layers[$this->selectedLayerIndex]['value'] ?? '');
                $this->layers[$this->selectedLayerIndex]['value'] = $current !== '' ? ($current . ' ' . $tag) : $tag;
            }
        }
    }

    public function updateLayerCoordinates($index, $x = 0, $y = 0)
    {
        $idx = (int)($index ?? -1);
        if ($idx >= 0 && isset($this->layers[$idx])) {
            $this->layers[$idx]['x'] = max(0, (int)round((float)($x ?? 0)));
            $this->layers[$idx]['y'] = max(0, (int)round((float)($y ?? 0)));
        }
    }

    public function updateLayerDimensions($index, $width = null, $height = null, $fontSize = null, $x = null, $y = null)
    {
        $idx = (int)($index ?? -1);
        if ($idx >= 0 && isset($this->layers[$idx])) {
            if ($x !== null) $this->layers[$idx]['x'] = max(0, (int)round((float)$x));
            if ($y !== null) $this->layers[$idx]['y'] = max(0, (int)round((float)$y));
            if ($width !== null) {
                $wVal = (int)round((float)$width);
                $this->layers[$idx]['width'] = $wVal > 0 ? max(10, $wVal) : 0;
            }
            if ($height !== null && (float)$height > 0) $this->layers[$idx]['height'] = max(10, (int)round((float)$height));
            if ($fontSize !== null && (float)$fontSize > 0 && ($this->layers[$idx]['type'] ?? '') === 'text') {
                $this->layers[$idx]['font_size'] = max(4, (int)round((float)$fontSize));
            }
        }
    }

    public function deleteBackgroundImage()
    {
        if (!$this->template) return;

        $bgPath = $this->template->background_image;
        if ($bgPath && !str_starts_with($bgPath, 'http') && Storage::disk('public')->exists($bgPath)) {
            Storage::disk('public')->delete($bgPath);
        }

        $this->template->update([
            'background_image' => null,
        ]);

        $this->bgUpload = null;
        session()->flash('message', 'Background image deleted successfully!');
    }

    public function saveStudioDesign()
    {
        if (!$this->template) return;

        $bgPath = $this->template->background_image;
        if ($this->bgUpload) {
            // Delete old background image file from storage if updating
            if ($bgPath && !str_starts_with($bgPath, 'http') && Storage::disk('public')->exists($bgPath)) {
                Storage::disk('public')->delete($bgPath);
            }
            $bgPath = $this->bgUpload->store('templates/backgrounds', 'public');
        }

        $this->template->update([
            'name' => $this->templateName,
            'orientation' => $this->orientation,
            'width_mm' => $this->widthMm,
            'height_mm' => $this->heightMm,
            'background_image' => $bgPath,
            'layout_config' => $this->layers,
        ]);

        $this->bgUpload = null;
        session()->flash('message', 'Canvas studio design saved successfully!');
    }

    public function with(): array
    {
        $activeSchoolId = session('active_school_id');
        $activeSchool = $activeSchoolId ? School::find($activeSchoolId) : null;

        $mockStudent = (object)[
            'first_name' => 'Aaditya',
            'middle_name' => 'Sonu',
            'last_name' => 'Thakur',
            'dob' => '2017-10-27',
            'contact_number' => '9730777244',
            'blood_group' => 'AB+',
            'gender' => 'Male',
            'address' => 'Sarvodhya Nagar Phase 3 Flat No 704',
            'pincode' => '400001',
            'photo_path' => '',
            'campaignStudents' => collect([
                (object)[
                    'grade' => (object)['name' => 'V'],
                    'division' => (object)['name' => 'B'],
                    'roll_no' => '202',
                    'serial_number' => '202',
                ]
            ])
        ];

        return [
            'activeSchool' => $activeSchool,
            'mockStudent' => $mockStudent,
        ];
    }
}; ?>

@php
    $isPortrait = $orientation === 'portrait';
    $canvasW = $isPortrait ? 638 : 1011;
    $canvasH = $isPortrait ? 1011 : 638;
    $bgPath = $template->background_image;
    $bgUrl = $bgPath ? (str_starts_with($bgPath, 'http') ? $bgPath : asset('storage/' . $bgPath)) : null;
@endphp

<div class="space-y-6 notranslate" translate="no" x-data="{
    zoomLevel: (function() {
        try {
            const savedZoom = localStorage.getItem('canva_studio_zoom');
            if (savedZoom !== null) {
                const parsed = parseInt(savedZoom);
                if (!isNaN(parsed) && parsed >= 25 && parsed <= 300) {
                    return parsed;
                }
            }
        } catch(e) {}
        return 100;
    })(),
    draggingIndex: null,
    draggingEl: null,
    resizingIndex: null,
    resizeHandle: null,
    resizeEl: null,
    startX: 0,
    startY: 0,
    origX: 0,
    origY: 0,
    startW: 0,
    startH: 0,
    startFontSize: 0,
    curX: 0,
    curY: 0,
    curW: 0,
    curH: 0,
    curFontSize: 0,
    hasMoved: false,
    isText: false,
    snapLines: { x: null, y: null },
    isShiftPressed: false,
    draggedLayers: [],
    alignMode: 'page',

    // Studio Tool Mode & Viewport Panning State
    activeTool: (function() {
        try {
            const savedTool = localStorage.getItem('canva_studio_tool');
            if (savedTool === 'select' || savedTool === 'pan') return savedTool;
        } catch(e) {}
        return 'select';
    })(),
    isSpacePressed: false,
    isPanning: false,
    panStartX: 0,
    panStartY: 0,
    panOffsetX: 0,
    panOffsetY: 0,
    panStartOffsetX: 0,
    panStartOffsetY: 0,

    // Marquee Drag-to-Select State
    isSelectingBox: false,
    boxStartCanvasX: 0,
    boxStartCanvasY: 0,
    boxRect: { left: 0, top: 0, width: 0, height: 0 },

    // Studio Inspector Active Tab ('layers', 'controls', 'background')
    activeInspectorTab: 'layers',

    setZoom(val) {
        const num = parseInt(val);
        if (!isNaN(num) && num >= 25 && num <= 300) {
            this.zoomLevel = num;
            try {
                localStorage.setItem('canva_studio_zoom', num.toString());
            } catch (e) {}
        }
    },

    init() {
        // Restore canvas settings from browser localStorage
        try {
            const savedZoom = localStorage.getItem('canva_studio_zoom');
            if (savedZoom !== null) {
                const parsedZoom = parseInt(savedZoom);
                if (!isNaN(parsedZoom) && parsedZoom >= 25 && parsedZoom <= 300) {
                    this.zoomLevel = parsedZoom;
                }
            }

            const savedShowGrid = localStorage.getItem('canva_studio_show_grid');
            if (savedShowGrid !== null) {
                this.$wire.showGrid = (savedShowGrid === 'true');
            }

            const savedSnapping = localStorage.getItem('canva_studio_enable_snapping');
            if (savedSnapping !== null) {
                this.$wire.enableSnapping = (savedSnapping === 'true');
            }

            const savedPreviewMode = localStorage.getItem('canva_studio_preview_mode');
            if (savedPreviewMode !== null) {
                this.$wire.livePreviewMode = (savedPreviewMode === 'true');
            }
        } catch (e) {
            console.warn('LocalStorage error reading canvas settings:', e);
        }

        // Set up watchers for non-zoom state changes
        this.$watch('activeTool', (val) => {
            try { localStorage.setItem('canva_studio_tool', val); } catch (e) {}
        });

        this.$watch('$wire.showGrid', (val) => {
            try { localStorage.setItem('canva_studio_show_grid', val); } catch (e) {}
        });

        this.$watch('$wire.enableSnapping', (val) => {
            try { localStorage.setItem('canva_studio_enable_snapping', val); } catch (e) {}
        });

        this.$watch('$wire.livePreviewMode', (val) => {
            try { localStorage.setItem('canva_studio_preview_mode', val); } catch (e) {}
        });
    },

    toggleTool(tool) {
        this.activeTool = tool;
    },

    resetPan() {
        this.panOffsetX = 0;
        this.panOffsetY = 0;
    },

    getSelectedIndices() {
        const val = this.$wire.selectedLayerIndices;
        if (!val) return [];
        const arr = Array.isArray(val) ? val : Object.values(val);
        return arr.map(v => parseInt(v));
    },

    getClientCoords(e) {
        if (e.touches && e.touches.length > 0) {
            return { x: e.touches[0].clientX, y: e.touches[0].clientY };
        }
        if (e.changedTouches && e.changedTouches.length > 0) {
            return { x: e.changedTouches[0].clientX, y: e.changedTouches[0].clientY };
        }
        return { x: e.clientX, y: e.clientY };
    },

    syncSelectedLayerData() {
        const idx = this.$wire.selectedLayerIndex;
        if (idx !== null && this.$wire.layers && this.$wire.layers[idx]) {
            const layer = this.$wire.layers[idx];
            this.curX = Math.round(parseFloat(layer.x) || 0);
            this.curY = Math.round(parseFloat(layer.y) || 0);
            this.curW = Math.round(parseFloat(layer.width) || 0);
            this.curH = Math.round(parseFloat(layer.height) || 0);
            this.curFontSize = Math.round(parseFloat(layer.font_size) || 14);
        }
    },

    onViewportMouseDown(event) {
        // Pan Mode / Spacebar / Middle Click Panning
        if (this.activeTool === 'pan' || this.isSpacePressed || event.button === 1) {
            this.isPanning = true;
            this.panStartX = event.clientX;
            this.panStartY = event.clientY;
            this.panStartOffsetX = this.panOffsetX;
            this.panStartOffsetY = this.panOffsetY;
            if (event.cancelable) event.preventDefault();
            return;
        }

        // Clicked on a layer or resize handle
        if (event.target.closest('[data-layer-box]')) {
            return;
        }

        // Start Drag-to-Select Marquee Rectangle Box
        const canvasEl = document.getElementById('canva-studio-canvas');
        if (!canvasEl) return;
        const rect = canvasEl.getBoundingClientRect();
        const scale = (parseFloat(this.zoomLevel) || 100) / 100;

        const startX = (event.clientX - rect.left) / scale;
        const startY = (event.clientY - rect.top) / scale;

        this.isSelectingBox = true;
        this.boxStartCanvasX = startX;
        this.boxStartCanvasY = startY;
        this.boxRect = { left: startX, top: startY, width: 0, height: 0 };

        if (!event.shiftKey) {
            this.$wire.selectLayer(null);
        }
    },

    onViewportMouseMove(event) {
        if (this.isPanning) {
            const dx = event.clientX - this.panStartX;
            const dy = event.clientY - this.panStartY;
            this.panOffsetX = Math.round(this.panStartOffsetX + dx);
            this.panOffsetY = Math.round(this.panStartOffsetY + dy);
            return;
        }

        if (this.isSelectingBox) {
            const canvasEl = document.getElementById('canva-studio-canvas');
            if (!canvasEl) return;
            const rect = canvasEl.getBoundingClientRect();
            const scale = (parseFloat(this.zoomLevel) || 100) / 100;

            const curX = (event.clientX - rect.left) / scale;
            const curY = (event.clientY - rect.top) / scale;

            const left = Math.min(this.boxStartCanvasX, curX);
            const top = Math.min(this.boxStartCanvasY, curY);
            const width = Math.abs(curX - this.boxStartCanvasX);
            const height = Math.abs(curY - this.boxStartCanvasY);

            this.boxRect = { left: Math.round(left), top: Math.round(top), width: Math.round(width), height: Math.round(height) };
        }
    },

    onViewportMouseUp(event) {
        if (this.isPanning) {
            this.isPanning = false;
        }

        if (this.isSelectingBox) {
            this.isSelectingBox = false;

            if (this.boxRect.width > 4 && this.boxRect.height > 4) {
                const boxL = this.boxRect.left;
                const boxR = this.boxRect.left + this.boxRect.width;
                const boxT = this.boxRect.top;
                const boxB = this.boxRect.top + this.boxRect.height;

                const intersected = [];
                document.querySelectorAll('#canva-studio-canvas [data-layer-box]').forEach(el => {
                    const idxStr = el.getAttribute('data-layer-index');
                    if (idxStr !== null && idxStr !== undefined) {
                        const idx = parseInt(idxStr);
                        const lX = parseFloat(el.style.left) || 0;
                        const lY = parseFloat(el.style.top) || 0;
                        const lW = el.offsetWidth || 0;
                        const lH = el.offsetHeight || 0;
                        const lR = lX + lW;
                        const lB = lY + lH;

                        // Check box overlap
                        if (lX < boxR && lR > boxL && lY < boxB && lB > boxT) {
                            intersected.push(idx);
                        }
                    }
                });

                if (intersected.length > 0) {
                    if (event.shiftKey) {
                        const current = this.getSelectedIndices();
                        const merged = Array.from(new Set([...current, ...intersected]));
                        this.$wire.selectLayerBatch(merged);
                    } else {
                        this.$wire.selectLayerBatch(intersected);
                    }
                }
            }

            this.boxRect = { left: 0, top: 0, width: 0, height: 0 };
        }
    },

    alignSelectedToPage(alignment) {
        const indices = this.getSelectedIndices();
        const count = indices.length;
        if (count === 0) return;

        const canvasW = {{ $canvasW }};
        const canvasH = {{ $canvasH }};

        const items = [];
        indices.forEach((idx) => {
            const el = document.querySelector('[data-layer-index=\'' + idx + '\']');
            if (el) {
                items.push({
                    idx: idx,
                    el: el,
                    x: parseFloat(el.style.left) || 0,
                    y: parseFloat(el.style.top) || 0,
                    w: el.offsetWidth || 0,
                    h: el.offsetHeight || 0
                });
            }
        });

        const updates = [];

        items.forEach((item) => {
            let finalX = item.x;
            let finalY = item.y;

            switch (alignment) {
                case 'left':
                    finalX = 0;
                    break;
                case 'center':
                    finalX = (canvasW - item.w) / 2;
                    break;
                case 'right':
                    finalX = canvasW - item.w;
                    break;
                case 'top':
                    finalY = 0;
                    break;
                case 'middle':
                    finalY = (canvasH - item.h) / 2;
                    break;
                case 'bottom':
                    finalY = canvasH - item.h;
                    break;
            }

            finalX = Math.round(finalX);
            finalY = Math.round(finalY);

            item.el.style.left = finalX + 'px';
            item.el.style.top = finalY + 'px';

            if (this.$wire.layers && this.$wire.layers[item.idx]) {
                this.$wire.layers[item.idx].x = finalX;
                this.$wire.layers[item.idx].y = finalY;
            }

            updates.push({
                index: item.idx,
                x: finalX,
                y: finalY
            });
        });

        this.$wire.updateMultipleLayersCoordinates(updates);
    },

    alignSelectedToSelection(alignment) {
        const indices = this.getSelectedIndices();
        const count = indices.length;
        if (count <= 1) return;

        const items = [];
        indices.forEach((idx) => {
            const el = document.querySelector('[data-layer-index=\'' + idx + '\']');
            if (el) {
                items.push({
                    idx: idx,
                    el: el,
                    x: parseFloat(el.style.left) || 0,
                    y: parseFloat(el.style.top) || 0,
                    w: el.offsetWidth || 0,
                    h: el.offsetHeight || 0
                });
            }
        });

        if (items.length <= 1) return;

        let minX = 999999;
        let maxX = -999999;
        let minY = 999999;
        let maxY = -999999;

        items.forEach(item => {
            if (item.x < minX) minX = item.x;
            if (item.x + item.w > maxX) maxX = item.x + item.w;
            if (item.y < minY) minY = item.y;
            if (item.y + item.h > maxY) maxY = item.y + item.h;
        });

        const boxW = maxX - minX;
        const boxH = maxY - minY;
        const centerX = minX + boxW / 2;
        const centerY = minY + boxH / 2;

        const updates = [];

        items.forEach((item) => {
            let finalX = item.x;
            let finalY = item.y;

            switch (alignment) {
                case 'left':
                    finalX = minX;
                    break;
                case 'center':
                    finalX = centerX - item.w / 2;
                    break;
                case 'right':
                    finalX = maxX - item.w;
                    break;
                case 'top':
                    finalY = minY;
                    break;
                case 'middle':
                    finalY = centerY - item.h / 2;
                    break;
                case 'bottom':
                    finalY = maxY - item.h;
                    break;
            }

            finalX = Math.round(finalX);
            finalY = Math.round(finalY);

            item.el.style.left = finalX + 'px';
            item.el.style.top = finalY + 'px';

            if (this.$wire.layers && this.$wire.layers[item.idx]) {
                this.$wire.layers[item.idx].x = finalX;
                this.$wire.layers[item.idx].y = finalY;
            }

            updates.push({
                index: item.idx,
                x: finalX,
                y: finalY
            });
        });

        this.$wire.updateMultipleLayersCoordinates(updates);
    },

    spaceSelectedEvenly(direction) {
        const indices = this.getSelectedIndices();
        const count = indices.length;
        if (count <= 2) return;

        // Fetch all elements with their actual dimensions
        const items = [];
        indices.forEach((idx) => {
            const el = document.querySelector('[data-layer-index=\'' + idx + '\']');
            if (el) {
                items.push({
                    idx: idx,
                    el: el,
                    x: parseFloat(el.style.left) || 0,
                    y: parseFloat(el.style.top) || 0,
                    w: el.offsetWidth || 0,
                    h: el.offsetHeight || 0
                });
            }
        });

        if (items.length <= 2) return;

        const updates = [];

        if (direction === 'vertical') {
            // Sort items by Y coordinate
            items.sort((a, b) => a.y - b.y);

            const firstY = items[0].y;
            const lastY = items[count - 1].y;
            const lastH = items[count - 1].h;

            let totalHeights = 0;
            items.forEach(item => {
                totalHeights += item.h;
            });

            const span = (lastY + lastH) - firstY;
            const totalGaps = span - totalHeights;
            const gap = totalGaps / (count - 1);

            let currentY = firstY;
            items.forEach((item, index) => {
                const finalY = Math.round(currentY);
                item.el.style.top = finalY + 'px';
                
                // Keep local client state updated
                if (this.$wire.layers && this.$wire.layers[item.idx]) {
                    this.$wire.layers[item.idx].y = finalY;
                }

                updates.push({
                    index: item.idx,
                    x: Math.round(item.x),
                    y: finalY
                });

                currentY += item.h + gap;
            });
        } else if (direction === 'horizontal') {
            // Sort items by X coordinate
            items.sort((a, b) => a.x - b.x);

            const firstX = items[0].x;
            const lastX = items[count - 1].x;
            const lastW = items[count - 1].w;

            let totalWidths = 0;
            items.forEach(item => {
                totalWidths += item.w;
            });

            const span = (lastX + lastW) - firstX;
            const totalGaps = span - totalWidths;
            const gap = totalGaps / (count - 1);

            let currentX = firstX;
            items.forEach((item, index) => {
                const finalX = Math.round(currentX);
                item.el.style.left = finalX + 'px';
                
                // Keep local client state updated
                if (this.$wire.layers && this.$wire.layers[item.idx]) {
                    this.$wire.layers[item.idx].x = finalX;
                }

                updates.push({
                    index: item.idx,
                    x: finalX,
                    y: Math.round(item.y)
                });

                currentX += item.w + gap;
            });
        }

        // Commit coordinates to Livewire
        this.$wire.updateMultipleLayersCoordinates(updates);
    },

    tidyUpSelected() {
        const indices = this.getSelectedIndices();
        const count = indices.length;
        if (count <= 1) return;

        // Fetch elements with actual dimensions
        const items = [];
        indices.forEach((idx) => {
            const el = document.querySelector('[data-layer-index=\'' + idx + '\']');
            if (el) {
                items.push({
                    idx: idx,
                    el: el,
                    x: parseFloat(el.style.left) || 0,
                    y: parseFloat(el.style.top) || 0,
                    w: el.offsetWidth || 0,
                    h: el.offsetHeight || 0
                });
            }
        });

        if (items.length <= 1) return;

        // Calculate bounding box bounds
        let minX = 999999;
        let maxX = -999999;
        let minY = 999999;
        let maxY = -999999;

        items.forEach(item => {
            if (item.x < minX) minX = item.x;
            if (item.x + item.w > maxX) maxX = item.x + item.w;
            if (item.y < minY) minY = item.y;
            if (item.y + item.h > maxY) maxY = item.y + item.h;
        });

        const boxW = maxX - minX;
        const boxH = maxY - minY;

        if (boxH >= boxW) {
            // Vertical Layout: align left edges to minX
            items.forEach(item => {
                item.el.style.left = Math.round(minX) + 'px';
                if (this.$wire.layers && this.$wire.layers[item.idx]) {
                    this.$wire.layers[item.idx].x = Math.round(minX);
                }
            });

            // Distribute vertically if 3 or more elements
            if (count >= 3) {
                this.spaceSelectedEvenly('vertical');
            } else {
                // If just 2 elements, commit left alignment
                const updates = items.map(item => ({
                    index: item.idx,
                    x: Math.round(minX),
                    y: Math.round(item.y)
                }));
                this.$wire.updateMultipleLayersCoordinates(updates);
            }
        } else {
            // Horizontal Layout: align top edges to minY
            items.forEach(item => {
                item.el.style.top = Math.round(minY) + 'px';
                if (this.$wire.layers && this.$wire.layers[item.idx]) {
                    this.$wire.layers[item.idx].y = Math.round(minY);
                }
            });

            // Distribute horizontally if 3 or more elements
            if (count >= 3) {
                this.spaceSelectedEvenly('horizontal');
            } else {
                // If just 2 elements, commit top alignment
                const updates = items.map(item => ({
                    index: item.idx,
                    x: Math.round(item.x),
                    y: Math.round(minY)
                }));
                this.$wire.updateMultipleLayersCoordinates(updates);
            }
        }
    },

    startDrag(idx, event) {
        if (this.resizingIndex !== null) return;
        if (event && event.cancelable) event.preventDefault();

        this.draggingIndex = idx;
        this.draggingEl = event.currentTarget ? (event.currentTarget.closest('[data-layer-box]') || event.currentTarget) : null;
        if (!this.draggingEl) return;

        this.isShiftPressed = event.shiftKey;

        const coords = this.getClientCoords(event);
        this.startX = coords.x;
        this.startY = coords.y;
        this.hasMoved = false;

        const layers = this.$wire.get('layers');
        const layer = (layers && layers[idx]) ? layers[idx] : {};
        const layerType = (layer && layer.type) ? layer.type : (this.draggingEl.getAttribute('data-layer-type') || 'text');
        this.isText = (layerType === 'text');

        let parseX = parseFloat(layer.x);
        let parseY = parseFloat(layer.y);
        if (isNaN(parseX)) parseX = parseFloat(this.draggingEl.style.left) || 0;
        if (isNaN(parseY)) parseY = parseFloat(this.draggingEl.style.top) || 0;

        this.origX = parseX;
        this.origY = parseY;
        this.curX = this.origX;
        this.curY = this.origY;

        this.draggedLayers = [];
        const indices = this.getSelectedIndices();
        const isMulti = indices.includes(idx);
        const indicesToDrag = isMulti ? indices : [idx];

        indicesToDrag.forEach((dIdx) => {
            const el = document.querySelector('[data-layer-index=\'' + dIdx + '\']');
            if (el) {
                const px = parseFloat(el.style.left) || 0;
                const py = parseFloat(el.style.top) || 0;
                this.draggedLayers.push({
                    idx: dIdx,
                    origX: px,
                    origY: py,
                    el: el
                });
            }
        });
    },

    onDrag(event) {
        if (this.draggingIndex === null || !this.draggingEl || this.draggedLayers.length === 0) return;
        const scale = (parseFloat(this.zoomLevel) || 100) / 100;
        const coords = this.getClientCoords(event);
        const dx = (coords.x - this.startX) / scale;
        const dy = (coords.y - this.startY) / scale;

        if (Math.abs(dx) > 1 || Math.abs(dy) > 1) {
            this.hasMoved = true;
        }

        let newX = Math.max(0, Math.round(this.origX + dx));
        let newY = Math.max(0, Math.round(this.origY + dy));

        let snapX = null;
        let snapY = null;

        if (this.$wire.enableSnapping) {
            const threshold = 6;
            const curW = this.draggingEl.offsetWidth;
            const curH = this.draggingEl.offsetHeight;

            const left = newX;
            const centerX = newX + curW / 2;
            const right = newX + curW;

            const top = newY;
            const centerY = newY + curH / 2;
            const bottom = newY + curH;

            const canvasW = {{ $canvasW }};
            const canvasH = {{ $canvasH }};

            const xTargets = [
                { val: 0, guide: 0 },
                { val: canvasW / 2, guide: canvasW / 2 },
                { val: canvasW, guide: canvasW }
            ];

            const yTargets = [
                { val: 0, guide: 0 },
                { val: canvasH / 2, guide: canvasH / 2 },
                { val: canvasH, guide: canvasH }
            ];

            const layerEls = document.querySelectorAll('[data-layer-box]');
            layerEls.forEach((el) => {
                if (el === this.draggingEl) return;

                const lx = parseFloat(el.style.left) || 0;
                const ly = parseFloat(el.style.top) || 0;
                const lw = el.offsetWidth || 0;
                const lh = el.offsetHeight || 0;

                xTargets.push({ val: lx, guide: lx });
                xTargets.push({ val: lx + lw / 2, guide: lx + lw / 2 });
                xTargets.push({ val: lx + lw, guide: lx + lw });

                yTargets.push({ val: ly, guide: ly });
                yTargets.push({ val: ly + lh / 2, guide: ly + lh / 2 });
                yTargets.push({ val: ly + lh, guide: ly + lh });
            });

            for (let t of xTargets) {
                if (Math.abs(left - t.val) < threshold) {
                    newX = t.val;
                    snapX = t.guide;
                    break;
                }
                if (Math.abs(centerX - t.val) < threshold) {
                    newX = t.val - curW / 2;
                    snapX = t.guide;
                    break;
                }
                if (Math.abs(right - t.val) < threshold) {
                    newX = t.val - curW;
                    snapX = t.guide;
                    break;
                }
            }

            for (let t of yTargets) {
                if (Math.abs(top - t.val) < threshold) {
                    newY = t.val;
                    snapY = t.guide;
                    break;
                }
                if (Math.abs(centerY - t.val) < threshold) {
                    newY = t.val - curH / 2;
                    snapY = t.guide;
                    break;
                }
                if (Math.abs(bottom - t.val) < threshold) {
                    newY = t.val - curH;
                    snapY = t.guide;
                    break;
                }
            }

            if (snapX === null) {
                newX = Math.round(newX / 10) * 10;
            }
            if (snapY === null) {
                newY = Math.round(newY / 10) * 10;
            }
        }

        this.snapLines.x = snapX !== null ? Math.round(snapX) : null;
        this.snapLines.y = snapY !== null ? Math.round(snapY) : null;

        const deltaX = newX - this.origX;
        const deltaY = newY - this.origY;

        this.draggedLayers.forEach((layer) => {
            const lx = Math.round(layer.origX + deltaX);
            const ly = Math.round(layer.origY + deltaY);
            layer.el.style.left = lx + 'px';
            layer.el.style.top = ly + 'px';

            if (layer.idx === this.draggingIndex) {
                this.curX = lx;
                this.curY = ly;
            }
        });
    },

    stopDrag() {
        this.snapLines = { x: null, y: null };
        if (this.draggingIndex !== null) {
            const idx = this.draggingIndex;
            const moved = this.hasMoved;

            this.draggingIndex = null;
            this.draggingEl = null;
            this.hasMoved = false;

            if (moved) {
                const updates = [];
                this.draggedLayers.forEach((layer) => {
                    const lx = parseInt(layer.el.style.left) || 0;
                    const ly = parseInt(layer.el.style.top) || 0;
                    updates.push({
                        index: layer.idx,
                        x: lx,
                        y: ly
                    });
                    if (this.$wire.layers && this.$wire.layers[layer.idx]) {
                        this.$wire.layers[layer.idx].x = lx;
                        this.$wire.layers[layer.idx].y = ly;
                    }
                });
                this.$wire.updateMultipleLayersCoordinates(updates);
            } else {
                this.$wire.selectLayer(idx, this.isShiftPressed);
            }
            this.draggedLayers = [];
        }
    },

    startResize(idx, handle, event) {
        if (event) {
            if (event.cancelable) event.preventDefault();
            event.stopPropagation();
        }
        this.resizingIndex = idx;
        this.resizeHandle = handle;
        this.resizeEl = event.currentTarget ? event.currentTarget.closest('[data-layer-box]') : null;
        if (!this.resizeEl) return;

        const coords = this.getClientCoords(event);
        this.startX = coords.x;
        this.startY = coords.y;

        const layers = this.$wire.get('layers');
        const layer = (layers && layers[idx]) ? layers[idx] : {};
        const layerType = (layer && layer.type) ? layer.type : (this.resizeEl.getAttribute('data-layer-type') || 'text');
        this.isText = (layerType === 'text');

        let parseX = parseFloat(layer.x);
        let parseY = parseFloat(layer.y);
        if (isNaN(parseX)) parseX = parseFloat(this.resizeEl.style.left) || 0;
        if (isNaN(parseY)) parseY = parseFloat(this.resizeEl.style.top) || 0;

        this.origX = parseX;
        this.origY = parseY;

        const contentEl = this.resizeEl.querySelector('[data-layer-content]');
        const realW = contentEl ? contentEl.offsetWidth : this.resizeEl.offsetWidth;
        const realH = contentEl ? contentEl.offsetHeight : this.resizeEl.offsetHeight;

        if (this.isText) {
            this.startW = realW || 50;
            this.startH = realH || 20;
        } else {
            this.startW = parseFloat(layer.width) || realW || 100;
            this.startH = parseFloat(layer.height) || realH || 30;
        }

        this.startFontSize = parseFloat(layer.font_size) || 14;

        this.curX = this.origX;
        this.curY = this.origY;
        this.curW = this.startW;
        this.curH = this.startH;
        this.curFontSize = this.startFontSize;
    },

    onResize(event) {
        if (this.resizingIndex === null || !this.resizeEl) return;
        const scale = (parseFloat(this.zoomLevel) || 100) / 100;
        const coords = this.getClientCoords(event);
        const dx = (coords.x - this.startX) / scale;
        const dy = (coords.y - this.startY) / scale;

        const isText = this.isText;
        const h = this.resizeHandle;

        let newW = this.startW;
        let newH = this.startH;
        let newX = this.origX;
        let newY = this.origY;
        let newFontSize = this.startFontSize;

        if (h.includes('e')) newW = Math.max(15, Math.round(this.startW + dx));
        if (h.includes('w')) {
            newW = Math.max(15, Math.round(this.startW - dx));
            newX = Math.round(this.origX + dx);
        }
        if (h.includes('s')) newH = Math.max(10, Math.round(this.startH + dy));
        if (h.includes('n')) {
            newH = Math.max(10, Math.round(this.startH - dy));
            newY = Math.round(this.origY + dy);
        }

        let snapX = null;
        let snapY = null;

        if (this.$wire.enableSnapping) {
            const threshold = 6;
            const canvasW = {{ $canvasW }};
            const canvasH = {{ $canvasH }};

            const xTargets = [
                { val: 0, guide: 0 },
                { val: canvasW / 2, guide: canvasW / 2 },
                { val: canvasW, guide: canvasW }
            ];

            const yTargets = [
                { val: 0, guide: 0 },
                { val: canvasH / 2, guide: canvasH / 2 },
                { val: canvasH, guide: canvasH }
            ];

            const layerEls = document.querySelectorAll('[data-layer-box]');
            layerEls.forEach((el) => {
                if (el === this.resizeEl) return;

                const lx = parseFloat(el.style.left) || 0;
                const ly = parseFloat(el.style.top) || 0;
                const lw = el.offsetWidth || 0;
                const lh = el.offsetHeight || 0;

                xTargets.push({ val: lx, guide: lx });
                xTargets.push({ val: lx + lw / 2, guide: lx + lw / 2 });
                xTargets.push({ val: lx + lw, guide: lx + lw });

                yTargets.push({ val: ly, guide: ly });
                yTargets.push({ val: ly + lh / 2, guide: ly + lh / 2 });
                yTargets.push({ val: ly + lh, guide: ly + lh });
            });

            if (h.includes('w')) {
                for (let t of xTargets) {
                    if (Math.abs(newX - t.val) < threshold) {
                        const diff = newX - t.val;
                        newX = t.val;
                        newW = Math.max(15, newW + diff);
                        snapX = t.guide;
                        break;
                    }
                }
            } else if (h.includes('e')) {
                for (let t of xTargets) {
                    const right = newX + newW;
                    if (Math.abs(right - t.val) < threshold) {
                        newW = Math.max(15, t.val - newX);
                        snapX = t.guide;
                        break;
                    }
                }
            }

            if (h.includes('n')) {
                for (let t of yTargets) {
                    if (Math.abs(newY - t.val) < threshold) {
                        const diff = newY - t.val;
                        newY = t.val;
                        newH = Math.max(10, newH + diff);
                        snapY = t.guide;
                        break;
                    }
                }
            } else if (h.includes('s')) {
                for (let t of yTargets) {
                    const bottom = newY + newH;
                    if (Math.abs(bottom - t.val) < threshold) {
                        newH = Math.max(10, t.val - newY);
                        snapY = t.guide;
                        break;
                    }
                }
            }

            if (snapX === null) {
                if (h.includes('w')) {
                    const snappedX = Math.round(newX / 10) * 10;
                    const diff = newX - snappedX;
                    newX = snappedX;
                    newW = Math.max(15, newW + diff);
                } else if (h.includes('e')) {
                    const right = newX + newW;
                    const snappedRight = Math.round(right / 10) * 10;
                    newW = Math.max(15, snappedRight - newX);
                }
            }
            if (snapY === null) {
                if (h.includes('n')) {
                    const snappedY = Math.round(newY / 10) * 10;
                    const diff = newY - snappedY;
                    newY = snappedY;
                    newH = Math.max(10, newH + diff);
                } else if (h.includes('s')) {
                    const bottom = newY + newH;
                    const snappedBottom = Math.round(bottom / 10) * 10;
                    newH = Math.max(10, snappedBottom - newY);
                }
            }
        }

        this.snapLines.x = snapX !== null ? Math.round(snapX) : null;
        this.snapLines.y = snapY !== null ? Math.round(snapY) : null;

        if (isText) {
            if (h === 'se' || h === 'sw' || h === 'ne' || h === 'nw') {
                const ratio = newW / (this.startW || 1);
                newFontSize = Math.max(6, Math.min(140, Math.round(this.startFontSize * ratio)));
            }
        } else {
            if (h === 'se' || h === 'sw' || h === 'ne' || h === 'nw') {
                const aspect = (this.startW || 1) / (this.startH || 1);
                newH = Math.round(newW / aspect);
            }
        }

        this.curW = newW;
        this.curH = newH;
        this.curX = newX;
        this.curY = newY;
        this.curFontSize = newFontSize;

        // Direct DOM manipulation for zero latency 60fps resizing
        this.resizeEl.style.left = newX + 'px';
        this.resizeEl.style.top = newY + 'px';

        const innerContent = this.resizeEl.querySelector('[data-layer-content]');
        if (innerContent) {
            if (!isText) {
                innerContent.style.width = newW + 'px';
                innerContent.style.height = newH + 'px';
            } else {
                const textDiv = innerContent.querySelector('div');
                if (textDiv) {
                    textDiv.style.fontSize = newFontSize + 'pt';
                    if (h === 'e' || h === 'w') {
                        textDiv.style.whiteSpace = 'normal';
                        textDiv.style.width = newW + 'px';
                    }
                }
            }
        }
    },

    stopResize() {
        this.snapLines = { x: null, y: null };
        if (this.resizingIndex !== null) {
            const idx = this.resizingIndex;
            let finalW = parseInt(this.curW) || 0;
            let finalH = parseInt(this.curH) || 0;
            const finalFontSize = parseInt(this.curFontSize) || 14;
            const finalX = parseInt(this.curX) || 0;
            const finalY = parseInt(this.curY) || 0;

            const isText = this.isText;
            const contentEl = this.resizeEl ? this.resizeEl.querySelector('[data-layer-content]') : null;
            if (contentEl) {
                if (!isText) {
                    finalW = contentEl.offsetWidth || finalW;
                    finalH = contentEl.offsetHeight || finalH;
                } else {
                    if (this.resizeHandle === 'e' || this.resizeHandle === 'w') {
                        finalW = parseInt(this.curW) || 0;
                    } else {
                        const layer = this.$wire.layers[idx];
                        finalW = layer ? (layer.width || 0) : 0;
                    }
                    finalH = 0;
                }
            }

            this.resizingIndex = null;
            this.resizeHandle = null;
            this.resizeEl = null;

            this.$wire.updateLayerDimensions(idx, finalW, finalH, finalFontSize, finalX, finalY);
        }
    },

    init() {
        this.syncSelectedLayerData();
        this.$watch('$wire.selectedLayerIndex', (val) => {
            this.syncSelectedLayerData();
            if (val !== null) {
                this.activeInspectorTab = 'controls';
            }
        });
        this.$watch('$wire.selectedLayerIndices', (val) => {
            if (val && val.length > 0) {
                this.activeInspectorTab = 'controls';
            }
        });
        this.$watch('$wire.layers', (val) => {
            this.syncSelectedLayerData();
        });

        // Mouse event listeners
        window.addEventListener('mousemove', (e) => {
            if (this.isPanning || this.isSelectingBox) {
                this.onViewportMouseMove(e);
            } else if (this.resizingIndex !== null) {
                this.onResize(e);
            } else if (this.draggingIndex !== null) {
                this.onDrag(e);
            }
        });
        window.addEventListener('mouseup', (e) => {
            if (this.isPanning || this.isSelectingBox) {
                this.onViewportMouseUp(e);
            }
            if (this.resizingIndex !== null) {
                this.stopResize();
            }
            if (this.draggingIndex !== null) {
                this.stopDrag();
            }
        });

        // Touch event listeners
        window.addEventListener('touchmove', (e) => {
            const coords = (e.touches && e.touches.length > 0) ? e.touches[0] : (e.changedTouches ? e.changedTouches[0] : null);

            if (this.resizingIndex !== null) {
                this.onResize(e);
            } else if (this.draggingIndex !== null) {
                this.onDrag(e);
            }
        }, { passive: false });
        window.addEventListener('touchend', (e) => {
            if (this.resizingIndex !== null) {
                this.stopResize();
            }
            if (this.draggingIndex !== null) {
                this.stopDrag();
            }
        });
        window.addEventListener('keydown', (e) => {
            const indices = this.getSelectedIndices();
            const singleIdx = this.$wire.selectedLayerIndex;

            const tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            if (['input', 'textarea', 'select'].includes(tag) || (document.activeElement && document.activeElement.isContentEditable)) {
                return;
            }

            // Spacebar: Pan Mode
            if (e.code === 'Space') {
                e.preventDefault();
                this.isSpacePressed = true;
                return;
            }

            // Ctrl + Z / Cmd + Z: Undo
            if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key.toLowerCase() === 'z') {
                e.preventDefault();
                this.$wire.undo();
                return;
            }

            // Ctrl + Y / Cmd + Y / Ctrl + Shift + Z: Redo
            if (
                ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') ||
                ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'z')
            ) {
                e.preventDefault();
                this.$wire.redo();
                return;
            }

            // Ctrl + D / Cmd + D duplication shortcut
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd') {
                if (indices.length > 0 || singleIdx !== null) {
                    e.preventDefault();
                    this.$wire.duplicateSelected();
                    return;
                }
            }

            // Ctrl + Shift + G / Cmd + Shift + G ungroup shortcut
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'g') {
                if (indices.length > 0 || singleIdx !== null) {
                    e.preventDefault();
                    this.$wire.ungroupSelected();
                    return;
                }
            }

            // Ctrl + G / Cmd + G group shortcut
            if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key.toLowerCase() === 'g') {
                if (indices.length > 1) {
                    e.preventDefault();
                    this.$wire.groupSelected();
                    return;
                }
            }

            if (indices.length === 0) return;

            const keys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
            if (!keys.includes(e.key)) return;

            e.preventDefault();

            const step = e.shiftKey ? 10 : 1;

            indices.forEach((idx) => {
                const el = document.querySelector('[data-layer-index=\'' + idx + '\']');
                if (!el) return;

                let curX = parseFloat(el.style.left) || 0;
                let curY = parseFloat(el.style.top) || 0;

                if (e.key === 'ArrowLeft') curX = Math.max(0, curX - step);
                if (e.key === 'ArrowRight') curX = curX + step;
                if (e.key === 'ArrowUp') curY = Math.max(0, curY - step);
                if (e.key === 'ArrowDown') curY = curY + step;

                el.style.left = curX + 'px';
                el.style.top = curY + 'px';

                if (this.$wire.layers && this.$wire.layers[idx]) {
                    this.$wire.layers[idx].x = curX;
                    this.$wire.layers[idx].y = curY;
                }

                // Sync the local binding variables for single selection sidebar if selected
                if (idx === this.$wire.selectedLayerIndex) {
                    this.curX = curX;
                    this.curY = curY;
                }
            });
        });

        window.addEventListener('keyup', (e) => {
            if (e.code === 'Space') {
                this.isSpacePressed = false;
            }

            const indices = this.getSelectedIndices();
            if (indices.length === 0) return;

            const keys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
            if (!keys.includes(e.key)) return;

            const tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            if (['input', 'textarea', 'select'].includes(tag)) return;

            const updates = [];
            indices.forEach((idx) => {
                const el = document.querySelector('[data-layer-index=\'' + idx + '\']');
                if (el) {
                    updates.push({
                        index: idx,
                        x: parseInt(el.style.left) || 0,
                        y: parseInt(el.style.top) || 0
                    });
                }
            });
            this.$wire.updateMultipleLayersCoordinates(updates);
        });
    }
}">
    @if(session()->has('message'))
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl flex items-center justify-between text-sm font-semibold">
            <span>{{ session('message') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white">&times;</button>
        </div>
    @endif

    <!-- Top Action Bar (Pro Studio Header) -->
    <div class="bg-white/90 backdrop-blur-xl border border-slate-200/80 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4 shadow-sm">
        <div class="flex items-center space-x-4">
            <a href="{{ route('templates') }}" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 transition flex items-center justify-center border border-slate-200/60" title="Back to Templates">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center space-x-2.5">
                    <input type="text" wire:model.live="templateName" class="bg-transparent border-b border-dashed border-slate-300 hover:border-indigo-500 text-lg font-black text-slate-900 focus:outline-none focus:border-indigo-600 transition px-1 py-0.5" placeholder="Untitled Template">
                    <span class="text-[10px] font-black text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                        {{ strtoupper($type) }} • CR-80
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5 font-medium flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                    <span>Canva Studio • Drag layers, snap to guides, edit text & shapes</span>
                </p>
            </div>
        </div>

        <!-- Studio Quick Tools Bar -->
        <div class="flex items-center space-x-2.5">
            <!-- Orientation Switcher -->
            <div class="flex items-center bg-slate-100 border border-slate-200/80 p-1 rounded-xl">
                <button type="button" wire:click="setOrientation('landscape')" title="Landscape Orientation (85.6mm x 54mm)" class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center {{ $orientation === 'landscape' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-600 hover:text-slate-900' }}">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 18h16"/></svg>
                    Landscape
                </button>
                <button type="button" wire:click="setOrientation('portrait')" title="Portrait Orientation (54mm x 85.6mm)" class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center {{ $orientation === 'portrait' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-600 hover:text-slate-900' }}">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4v16M18 4v16"/></svg>
                    Portrait
                </button>
            </div>

            <button type="button" wire:click="$toggle('showGrid')" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center border {{ $showGrid ? 'bg-indigo-50 border-indigo-200 text-indigo-700 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                Grid: {{ $showGrid ? 'ON' : 'OFF' }}
            </button>

            <button type="button" wire:click="$toggle('enableSnapping')" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center border {{ $enableSnapping ? 'bg-indigo-50 border-indigo-200 text-indigo-700 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 022 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Snap: {{ $enableSnapping ? 'ON' : 'OFF' }}
            </button>

            <button type="button" wire:click="$toggle('livePreviewMode')" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center border {{ $livePreviewMode ? 'bg-emerald-50 border-emerald-200 text-emerald-700 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Preview: {{ $livePreviewMode ? 'Live Data' : 'Tags' }}
            </button>

            <button type="button" wire:click="saveStudioDesign" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white rounded-xl text-xs font-black transition shadow-lg shadow-indigo-600/25 flex items-center transform hover:-translate-y-0.5">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Save Design
            </button>
        </div>
    </div>

    <!-- Main Workspace Split -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-stretch w-full">
        <!-- Left: Interactive Canva Canvas Studio Workbench & Variable Tags (7 Cols) -->
        <div class="md:col-span-6 lg:col-span-7 min-w-0 space-y-5">
            <!-- Studio Canvas Viewport Box -->
            <div class="bg-[#0f172a] border border-slate-800 rounded-3xl p-5 flex flex-col items-center justify-center min-h-[620px] shadow-2xl relative overflow-hidden">
            <!-- Studio Canvas Header Info -->
            <div class="w-full flex items-center justify-between mb-3 px-1">
                <div class="flex items-center space-x-2">
                    <span class="text-[10px] font-black uppercase tracking-widest text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1 rounded-lg">
                        Interactive Studio (CR-80 Scale)
                    </span>
                    <!-- Undo / Redo Buttons -->
                    <button type="button" 
                        wire:click="undo" 
                        title="Undo (Ctrl+Z)"
                        @if(empty($undoStack)) disabled @endif
                        class="p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white disabled:opacity-30 disabled:hover:bg-slate-900 disabled:hover:text-slate-300 transition"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"></path>
                        </svg>
                    </button>
                    <button type="button" 
                        wire:click="redo" 
                        title="Redo (Ctrl+Y)"
                        @if(empty($redoStack)) disabled @endif
                        class="p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white disabled:opacity-30 disabled:hover:bg-slate-900 disabled:hover:text-slate-300 transition"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 000 12h3"></path>
                        </svg>
                    </button>

                    <!-- 4 Direct Layer Order Buttons (Bigger & High Contrast) -->
                    <div class="flex items-center space-x-1.5 pl-2 border-l border-slate-800">
                        <button type="button" 
                            wire:click="moveLayer(null, 'front')" 
                            title="Bring to Front (Top Layer)"
                            @if($selectedLayerIndex === null) disabled @endif
                            class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-indigo-600 border border-slate-700/80 text-white disabled:opacity-25 disabled:hover:bg-slate-800 transition flex items-center justify-center shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 11l7-7 7 7M5 19l7-7 7 7"></path>
                            </svg>
                        </button>
                        <button type="button" 
                            wire:click="moveLayer(null, 'up')" 
                            title="Bring Forward (1 Step Up)"
                            @if($selectedLayerIndex === null) disabled @endif
                            class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-indigo-600 border border-slate-700/80 text-white disabled:opacity-25 disabled:hover:bg-slate-800 transition flex items-center justify-center shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"></path>
                            </svg>
                        </button>
                        <button type="button" 
                            wire:click="moveLayer(null, 'down')" 
                            title="Send Backward (1 Step Down)"
                            @if($selectedLayerIndex === null) disabled @endif
                            class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-indigo-600 border border-slate-700/80 text-white disabled:opacity-25 disabled:hover:bg-slate-800 transition flex items-center justify-center shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <button type="button" 
                            wire:click="moveLayer(null, 'back')" 
                            title="Send to Back (Bottom Layer)"
                            @if($selectedLayerIndex === null) disabled @endif
                            class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-indigo-600 border border-slate-700/80 text-white disabled:opacity-25 disabled:hover:bg-slate-800 transition flex items-center justify-center shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 13l-7 7-7-7M19 5l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <span class="text-xs text-slate-400 font-mono bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-800">85.6mm × 54.0mm</span>
            </div>

            <!-- Canvas Container with Drag & Snap Capabilities -->

            <!-- Canvas Outer Interactive Container with Zoom & Resize State -->
            <div class="w-full space-y-4">

                <!-- Scrollable Canvas Viewport Container -->
                <div 
                    id="canvas-viewport-container"
                    @mousedown="onViewportMouseDown($event)"
                    @mousemove="onViewportMouseMove($event)"
                    @mouseup="onViewportMouseUp($event)"
                    :class="(activeTool === 'pan' || isSpacePressed || isPanning) ? (isPanning ? 'cursor-grabbing' : 'cursor-grab') : ''"
                    class="w-full flex items-center overflow-auto p-4 min-h-[460px] max-h-[700px] bg-slate-950/40 rounded-2xl border border-slate-800/60 shadow-inner select-none relative"
                >
                    <div 
                        id="canva-studio-canvas"
                        class="relative mx-auto select-none shadow-2xl bg-white overflow-hidden shrink-0 my-auto transform"
                        :class="$wire.showGrid ? 'canvas-grid-bg' : ''"
                        :style="'width: {{ $canvasW }}px; height: {{ $canvasH }}px; transform: translate(' + panOffsetX + 'px, ' + panOffsetY + 'px) scale(' + ((parseFloat(zoomLevel) || 100) / 100) + '); transform-origin: center center;'"
                    >
                        <!-- Drag-to-Select Marquee Rectangle Overlay -->
                        <div 
                            x-show="isSelectingBox"
                            class="absolute border border-indigo-500 bg-indigo-500/15 rounded shadow-sm z-50 pointer-events-none transition-none"
                            :style="'left: ' + boxRect.left + 'px; top: ' + boxRect.top + 'px; width: ' + boxRect.width + 'px; height: ' + boxRect.height + 'px;'"
                        ></div>
                        @if($bgUrl)
                            <img src="{{ $bgUrl }}" class="absolute inset-0 w-full h-full object-fill pointer-events-none z-0" alt="Background Graphic" />
                        @endif

                        <!-- Center Snap Line (Visual Indicator when Selected) -->
                        @if($selectedLayerIndex !== null)
                            <div class="absolute top-0 bottom-0 left-1/2 w-[1px] bg-indigo-500/40 pointer-events-none border-r border-dashed border-indigo-400"></div>
                        @endif

                        <!-- Dynamic Snapping Alignment Guide Lines -->
                        <template x-if="snapLines.x !== null">
                            <div class="absolute top-0 bottom-0 pointer-events-none z-40 border-r border-dashed border-indigo-400" :style="'left: ' + snapLines.x + 'px; width: 1px;'"></div>
                        </template>
                        <template x-if="snapLines.y !== null">
                            <div class="absolute left-0 right-0 pointer-events-none z-40 border-b border-dashed border-indigo-400" :style="'top: ' + snapLines.y + 'px; height: 1px;'"></div>
                        </template>

                        <!-- Render Interactive Canvas Layers -->
                        @foreach($layers as $idx => $layer)
                            @php
                                $type = $layer['type'] ?? 'text';
                                $x = $layer['x'] ?? 0;
                                $y = $layer['y'] ?? 0;
                                $w = $layer['width'] ?? 150;
                                $h = $layer['height'] ?? 30;
                                $rot = $layer['rotation'] ?? 0;
                                $isSelected = in_array($idx, $selectedLayerIndices);
                            @endphp

                            <div 
                                wire:key="canvas-layer-{{ $layer['id'] ?? $idx }}"
                                @mousedown.prevent="startDrag({{ $idx }}, $event)"
                                @touchstart.prevent="startDrag({{ $idx }}, $event)"
                                @dragstart.prevent
                                @selectstart.prevent
                                data-layer-box
                                data-layer-index="{{ $idx }}"
                                data-layer-type="{{ $type }}"
                                class="absolute cursor-move select-none transition-shadow group {{ $isSelected ? 'ring-2 ring-indigo-500 ring-offset-1 ring-offset-slate-900' : 'hover:ring-1 hover:ring-indigo-400/50' }}"
                                style="left: {{ $x }}px; top: {{ $y }}px; transform: rotate({{ $rot }}deg); transform-origin: center center; z-index: {{ $idx + 10 }};"
                                @php
                                    $layerOpacity = max(0, min(100, (float)($layer['opacity'] ?? 100))) / 100;
                                    $layerFadeMode = $layer['fade_mode'] ?? 'none';
                                    
                                    $transStyle = "opacity: {$layerOpacity};";
                                    
                                    $maskGrad = match($layerFadeMode) {
                                        'fade_bottom' => 'linear-gradient(to bottom, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 100%)',
                                        'fade_top'    => 'linear-gradient(to top, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 100%)',
                                        'fade_right'  => 'linear-gradient(to right, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 100%)',
                                        'fade_left'   => 'linear-gradient(to left, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 100%)',
                                        'radial'      => 'radial-gradient(circle, rgba(0,0,0,1) 20%, rgba(0,0,0,0) 100%)',
                                        default       => null,
                                    };
                                    if ($maskGrad) {
                                        $transStyle .= " -webkit-mask-image: {$maskGrad}; mask-image: {$maskGrad};";
                                    }
                                @endphp
                                <div data-layer-content style="width: {{ ($type === 'text') ? (!empty($layer['width']) ? ($layer['width'] . 'px') : 'max-content') : ($w . 'px') }}; height: {{ ($type === 'text') ? 'max-content' : ($h . 'px') }}; max-width: 100%; {{ $transStyle }}">
                                    @if($type === 'text')
                                        @php
                                            $rawText = $layer['text'] ?? '';
                                            $displayText = $livePreviewMode 
                                                ? strtr($rawText, [
                                                    '{first_name}' => 'Aaditya', '{middle_name}' => 'Sonu', '{last_name}' => 'Thakur',
                                                    '{First Name}' => 'Aaditya', '{Middle Name}' => 'Sonu', '{Last Name}' => 'Thakur',
                                                    '{dob}' => '2017-10-27', '{DOB}' => '2017-10-27',
                                                    '{blood_group}' => 'AB+', '{Blood Group}' => 'AB+',
                                                    '{gender}' => 'Male', '{Gender}' => 'Male',
                                                    '{contact_number}' => '9730777244', '{Contact Number}' => '9730777244',
                                                    '{address}' => 'Sarvodhya Nagar Flat 704', '{Address}' => 'Sarvodhya Nagar Flat 704',
                                                    '{pincode}' => '400001', '{Pincode}' => '400001',
                                                    '{grade}' => 'V', '{Grade}' => 'V', '{Standard}' => 'V',
                                                    '{division}' => 'B', '{Division}' => 'B', '{Div}' => 'B',
                                                    '{roll_no}' => '202', '{Roll No}' => '202', '{serial_number}' => '202', '{Ref No}' => '202',
                                                    '{Campaign}' => 'iCard 2026-27',
                                                    '{School Name}' => ($activeSchool->name ?? 'Sarvodya Vidyalay'),
                                                    '{School Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                                    '{Registration Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                                    '{Principal Name}' => ($activeSchool->principal_name ?? 'Dr. R. K. Sharma'),
                                                    '{School Contact}' => ($activeSchool->contact_number ?? '9820198201'),
                                                    '{School Email}' => ($activeSchool->email ?? 'info@sarvodya.edu.in'),
                                                    '{School Website}' => ($activeSchool->website ?? 'www.sarvodya.edu.in'),
                                                    '{School Address}' => ($activeSchool->address ?? 'Station Road, Mumbai'),
                                                  ])
                                                : $rawText;

                                            $fontSize = $layer['font_size'] ?? 14;
                                            $fontWeight = $layer['font_weight'] ?? 'normal';
                                            $fontFamily = $layer['font_family'] ?? 'Inter';
                                            $color = $layer['color'] ?? '#ffffff';
                                            $align = $layer['align'] ?? 'left';
                                        @endphp
                                        <div style="font-size: {{ $fontSize }}pt; font-weight: {{ $fontWeight }}; font-family: {{ $fontFamily }}, sans-serif; color: {{ $color }}; text-align: {{ $align }}; {{ !empty($layer['width']) ? ('width: ' . $layer['width'] . 'px; max-width: ' . $layer['width'] . 'px; white-space: normal; word-break: break-word;') : 'width: max-content; white-space: nowrap;' }} padding: 2px 4px; border-radius: 4px; box-sizing: border-box; background: {{ $isSelected ? 'rgba(99, 102, 241, 0.15)' : 'transparent' }};">
                                            {{ $displayText }}
                                        </div>

                                    @elseif($type === 'photo')
                                         @php
                                             $borderRadius = $layer['border_radius'] ?? 12;
                                             $borderColor = $layer['border_color'] ?? '#818cf8';
                                             $borderWidth = $layer['border_width'] ?? 2;
                                             $shape = $layer['shape'] ?? (($borderRadius >= 999) ? 'round' : 'square');
                                             $radiusStyle = ($borderRadius >= 999 || $shape === 'round') ? '50%' : ($borderRadius . 'px');
                                         @endphp
                                         <div style="width: 100%; height: 100%; border-radius: {{ $radiusStyle }}; border: {{ $borderWidth }}px solid {{ $borderColor }}; overflow: hidden; background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; box-sizing: border-box;">
                                             <svg viewBox="0 0 24 24" style="width: 40%; height: 40%; color: #818cf8;" fill="currentColor">
                                                 <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                             </svg>
                                             <span style="font-size: 8px; font-weight: 800; color: #a5b4fc; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">STUDENT PHOTO</span>
                                         </div>

                                    @elseif($type === 'logo')
                                        @if($activeSchool && $activeSchool->logo_path)
                                            <div style="width: 100%; height: 100%; border-radius: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center; box-sizing: border-box; background: {{ $isSelected ? 'rgba(99, 102, 241, 0.15)' : 'transparent' }};">
                                                <img src="{{ asset('storage/' . $activeSchool->logo_path) }}" alt="{{ $activeSchool->name }}" style="max-width: 100%; max-height: 100%; object-fit: contain;" />
                                            </div>
                                        @else
                                            <div style="width: 100%; height: 100%; border-radius: 10px; background: linear-gradient(135deg, #312e81 0%, #4338ca 100%); border: 1.5px dashed #818cf8; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #ffffff; padding: 2px; box-sizing: border-box;">
                                                <svg viewBox="0 0 24 24" style="width: 40%; height: 40%; color: #fbbf24;" fill="currentColor">
                                                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM3.82 9L12 4.54 20.18 9 12 13.46 3.82 9zM5 14.45v3.55l7 3.82 7-3.82v-3.55l-7 3.81-7-3.81z"/>
                                                </svg>
                                                <span style="font-size: 7px; font-weight: 800; color: #fbbf24; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; text-align: center;">SCHOOL LOGO</span>
                                            </div>
                                        @endif

                                    @elseif($type === 'qr')
                                         @php
                                             $rawQrValue = !empty($layer['value']) ? $layer['value'] : '{Ref No}';
                                             $qrData = strtr($rawQrValue, [
                                                 '{Student Photo}' => 'PHOTO',
                                                 '{QR Code}' => 'QR',
                                                 '{First Name}' => 'Aaditya',
                                                 '{Middle Name}' => 'Sunil',
                                                 '{Last Name}' => 'Thakur',
                                                 '{Roll No}' => '102',
                                                 '{Ref No}' => 'REF-2026-0891',
                                                 '{Campaign}' => 'iCard 2026-27',
                                                 '{Standard}' => 'Grade V',
                                                 '{Division}' => 'Div A',
                                                 'Grade ({grade}) Div ({division})' => 'Grade V - A',
                                                 '{Blood Group}' => 'B+',
                                                 '{Gender}' => 'Male',
                                                 '{DOB}' => '2017-05-12',
                                                 '{Contact Number}' => '9876543210',
                                                 '{Address}' => 'Samarth Nagar, Pune',
                                                 '{Pincode}' => '411001',
                                                 '{School Name}' => ($activeSchool->name ?? 'Sarvodya Vidyalay'),
                                                 '{School Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                                 '{Registration Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                                 '{Principal Name}' => ($activeSchool->principal_name ?? 'Dr. R. K. Sharma'),
                                                 '{School Contact}' => ($activeSchool->contact_number ?? '9820198201'),
                                                 '{School Email}' => ($activeSchool->email ?? 'info@sarvodya.edu.in'),
                                                 '{School Website}' => ($activeSchool->website ?? 'www.sarvodya.edu.in'),
                                                 '{School Address}' => ($activeSchool->address ?? 'Station Road, Mumbai'),
                                             ]);
                                             if (empty(trim($qrData))) {
                                                 $qrData = 'REF-2026-0891';
                                             }
                                             $qrW = max(20, (int)($layer['width'] ?? 60));
                                             $qrH = max(20, (int)($layer['height'] ?? 60));
                                             $qrSize = min($qrW, $qrH);
                                             try {
                                                 $qrSvg = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', (string)\SimpleSoftwareIO\QrCode\Facades\QrCode::size($qrSize)->margin(1)->generate($qrData));
                                             } catch (\Throwable $e) {
                                                 $qrSvg = '<svg viewBox="0 0 24 24" style="width: 100%; height: 100%;" fill="#0f172a"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM18 18h3v3h-3zM14 18h3v3h-3z"/></svg>';
                                             }
                                         @endphp
                                         <div style="width: 100%; height: 100%; background: white; padding: 3px; border-radius: 6px; display: flex; align-items: center; justify-content: center; box-sizing: border-box; overflow: hidden;">
                                             {!! $qrSvg !!}
                                         </div>

                                    @elseif($type === 'barcode')
                                         @php
                                             $rawBarcodeValue = !empty($layer['value']) ? $layer['value'] : '{Ref No}';
                                             $barcodeData = strtr($rawBarcodeValue, [
                                                 '{Student Photo}' => 'PHOTO',
                                                 '{QR Code}' => 'QR',
                                                 '{Barcode}' => 'BARCODE',
                                                 '{First Name}' => 'Aaditya',
                                                 '{Middle Name}' => 'Sunil',
                                                 '{Last Name}' => 'Thakur',
                                                 '{Roll No}' => '102',
                                                 '{Ref No}' => 'REF-2026-0891',
                                                 '{Campaign}' => 'iCard 2026-27',
                                                 '{Standard}' => 'Grade V',
                                                 '{Division}' => 'Div A',
                                                 'Grade ({grade}) Div ({division})' => 'Grade V - A',
                                                 '{Blood Group}' => 'B+',
                                                 '{Gender}' => 'Male',
                                                 '{DOB}' => '2017-05-12',
                                                 '{Contact Number}' => '9876543210',
                                                 '{Address}' => 'Samarth Nagar, Pune',
                                                 '{Pincode}' => '411001',
                                                 '{School Name}' => ($activeSchool->name ?? 'Sarvodya Vidyalay'),
                                                 '{School Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                                 '{Registration Code}' => ($activeSchool->school_code ?? 'SV-2026'),
                                                 '{Principal Name}' => ($activeSchool->principal_name ?? 'Dr. R. K. Sharma'),
                                                 '{School Contact}' => ($activeSchool->contact_number ?? '9820198201'),
                                                 '{School Email}' => ($activeSchool->email ?? 'info@sarvodya.edu.in'),
                                                 '{School Website}' => ($activeSchool->website ?? 'www.sarvodya.edu.in'),
                                                 '{School Address}' => ($activeSchool->address ?? 'Station Road, Mumbai'),
                                             ]);
                                             if (empty(trim($barcodeData))) {
                                                 $barcodeData = 'REF-2026-0891';
                                             }
                                             $showText = !isset($layer['show_text']) || $layer['show_text'];

                                             try {
                                                 $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
                                                 $rawSvg = $generator->getBarcode($barcodeData, $generator::TYPE_CODE_128);
                                                 $barcodeSvg = preg_replace('/<\?xml.*?\?>/i', '', $rawSvg);
                                                 $barcodeSvg = preg_replace('/<!DOCTYPE.*?>/i', '', $barcodeSvg);
                                                 $barcodeSvg = str_replace('<svg ', '<svg preserveAspectRatio="none" style="width: 100%; height: 100%;" ', $barcodeSvg);
                                             } catch (\Throwable $e) {
                                                 $barcodeSvg = '<div style="font-size: 10px; font-weight: bold; color: #ef4444;">[Barcode Error]</div>';
                                             }
                                         @endphp
                                         <div style="width: 100%; height: 100%; background: white; padding: 4px 6px; border-radius: 6px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box; overflow: hidden;">
                                             <div style="flex: 1; width: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                                 {!! $barcodeSvg !!}
                                             </div>
                                             @if($showText)
                                                 <div style="font-size: 9px; font-weight: 800; font-family: monospace; color: #0f172a; letter-spacing: 1px; margin-top: 1px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">
                                                     {{ $barcodeData }}
                                                 </div>
                                             @endif
                                         </div>

                                     @elseif($type === 'image')
                                         @php
                                             $imgBw = (float)($layer['border_width'] ?? 0);
                                             $imgBc = $layer['border_color'] ?? '#818cf8';
                                             $imgBr = (float)($layer['border_radius'] ?? 0);
                                             $imgFit = $layer['object_fit'] ?? 'contain';
                                             $imgOpacity = max(0, min(100, (float)($layer['opacity'] ?? 100))) / 100;
                                             $imgRadiusStyle = ($imgBr >= 999) ? '50%' : ($imgBr . 'px');
                                             $imgSrc = !empty($layer['image_path']) ? asset('storage/' . $layer['image_path']) : null;
                                         @endphp
                                         <div style="width: 100%; height: 100%; border-radius: {{ $imgRadiusStyle }}; border: {{ $imgBw }}px solid {{ $imgBc }}; opacity: {{ $imgOpacity }}; overflow: hidden; display: flex; align-items: center; justify-content: center; box-sizing: border-box; background: {{ $isSelected ? 'rgba(99, 102, 241, 0.15)' : 'transparent' }};">
                                             @if($imgSrc)
                                                 <img src="{{ $imgSrc }}" alt="{{ $layer['label'] ?? 'Custom Image' }}" style="width: 100%; height: 100%; object-fit: {{ $imgFit }};" />
                                             @else
                                                 <div style="font-size: 10px; font-weight: bold; color: #94a3b8;">[No Image]</div>
                                             @endif
                                         </div>

                                    @elseif($type === 'shape')
                                        @php
                                            $shapeOpacity = max(0, min(100, (float)($layer['opacity'] ?? 100))) / 100;
                                        @endphp
                                        <div style="width: 100%; height: 100%; opacity: {{ $shapeOpacity }};">
                                            @include('components.shape-svg', ['layer' => $layer])
                                        </div>
                                    @endif
                                </div>

                                <!-- Canva 8 Interactive Resize Handles (Rendered on Selection) -->
                                @if($isSelected)
                                    <!-- 4 Corner Handles -->
                                    <div @mousedown.stop.prevent="startResize({{ $idx }}, 'nw', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'nw', $event)" title="Resize Top-Left" class="absolute w-3.5 h-3.5 bg-white border-2 border-indigo-600 rounded-full shadow-lg hover:scale-125 cursor-nwse-resize z-50 transition-transform" style="top: 0; left: 0; transform: translate(-50%, -50%);"></div>
                                    <div @mousedown.stop.prevent="startResize({{ $idx }}, 'ne', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'ne', $event)" title="Resize Top-Right" class="absolute w-3.5 h-3.5 bg-white border-2 border-indigo-600 rounded-full shadow-lg hover:scale-125 cursor-nesw-resize z-50 transition-transform" style="top: 0; left: 100%; transform: translate(-50%, -50%);"></div>
                                    <div @mousedown.stop.prevent="startResize({{ $idx }}, 'sw', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'sw', $event)" title="Resize Bottom-Left" class="absolute w-3.5 h-3.5 bg-white border-2 border-indigo-600 rounded-full shadow-lg hover:scale-125 cursor-nesw-resize z-50 transition-transform" style="top: 100%; left: 0; transform: translate(-50%, -50%);"></div>
                                    <div @mousedown.stop.prevent="startResize({{ $idx }}, 'se', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'se', $event)" title="Resize Bottom-Right" class="absolute w-3.5 h-3.5 bg-white border-2 border-indigo-600 rounded-full shadow-lg hover:scale-125 cursor-nwse-resize z-50 transition-transform" style="top: 100%; left: 100%; transform: translate(-50%, -50%);"></div>

                                    <!-- 4 Side Handles -->
                                    <div @mousedown.stop.prevent="startResize({{ $idx }}, 'n', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'n', $event)" title="Stretch Top" class="absolute w-3 h-2.5 bg-indigo-500 border border-white rounded-sm shadow-md hover:scale-125 cursor-ns-resize z-50 transition-transform" style="top: 0; left: 50%; transform: translate(-50%, -50%);"></div>
                                    <div @mousedown.stop.prevent="startResize({{ $idx }}, 's', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 's', $event)" title="Stretch Bottom" class="absolute w-3 h-2.5 bg-indigo-500 border border-white rounded-sm shadow-md hover:scale-125 cursor-ns-resize z-50 transition-transform" style="top: 100%; left: 50%; transform: translate(-50%, -50%);"></div>
                                    <div @mousedown.stop.prevent="startResize({{ $idx }}, 'w', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'w', $event)" title="Stretch Left" class="absolute w-2.5 h-3 bg-indigo-500 border border-white rounded-sm shadow-md hover:scale-125 cursor-ew-resize z-50 transition-transform" style="top: 50%; left: 0; transform: translate(-50%, -50%);"></div>
                    <div @mousedown.stop.prevent="startResize({{ $idx }}, 'e', $event)" @touchstart.stop.prevent="startResize({{ $idx }}, 'e', $event)" title="Stretch Right" class="absolute w-2.5 h-3 bg-indigo-500 border border-white rounded-sm shadow-md hover:scale-125 cursor-ew-resize z-50 transition-transform" style="top: 50%; left: 100%; transform: translate(-50%, -50%);"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Canvas Bottom Toolbar: Zoom Controls & Presets Floating Bar -->
                <div class="w-full bg-slate-900/90 backdrop-blur-md border border-slate-800 rounded-2xl px-4 py-3 flex flex-wrap items-center justify-between gap-3 shadow-xl">
                    <div class="flex items-center space-x-3">
                        <!-- Mode Selector (Select vs Pan Tool) -->
                        <div class="flex items-center bg-slate-950 border border-slate-800 p-1 rounded-xl">
                            <button 
                                type="button" 
                                @click="toggleTool('select')"
                                :class="activeTool === 'select' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:text-white'"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center"
                                title="Select Tool (V)"
                            >
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/></svg>
                                Select
                            </button>
                            <button 
                                type="button" 
                                @click="toggleTool('pan')"
                                :class="(activeTool === 'pan' || isSpacePressed) ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'text-slate-400 hover:text-white'"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center"
                                title="Pan Tool (Hold Spacebar)"
                            >
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0 0v-2.5m0 2.5l3.5 3.5m0 0l3.5-3.5m-3.5 3.5V6a2 2 0 012-2h0a2 2 0 012 2v6.5"/></svg>
                                Pan
                            </button>
                            <button 
                                type="button" 
                                x-show="panOffsetX !== 0 || panOffsetY !== 0"
                                @click="resetPan()"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 hover:bg-indigo-500/20 transition flex items-center"
                                title="Reset View Offset"
                            >
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Center
                            </button>
                        </div>

                        <div class="h-4 w-[1px] bg-slate-800"></div>

                        <div class="flex items-center space-x-3">
                            <span class="text-xs font-bold text-slate-300 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path>
                                </svg>
                                Zoom:
                            </span>
                            <div class="flex items-center space-x-2">
                                <button type="button" @click="setZoom(Math.max(30, parseInt(zoomLevel) - 10))" title="Zoom Out" class="w-7 h-7 rounded-lg bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white text-xs font-bold flex items-center justify-center transition">
                                    &minus;
                                </button>
                                <input type="range" min="30" max="200" step="5" :value="zoomLevel" @input="setZoom($event.target.value)" class="w-24 sm:w-32 h-1.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-indigo-500">
                                <button type="button" @click="setZoom(Math.min(200, parseInt(zoomLevel) + 10))" title="Zoom In" class="w-7 h-7 rounded-lg bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white text-xs font-bold flex items-center justify-center transition">
                                    &#43;
                                </button>
                            </div>
                            <span class="text-xs font-black text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-1 rounded-md font-mono" x-text="zoomLevel + '%'">
                                100%
                            </span>
                        </div>
                    </div>

                    <!-- Quick Zoom Preset Buttons & LocalStorage Badge -->
                    <div class="flex items-center space-x-1.5">
                        <button type="button" @click="setZoom(50)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition" :class="zoomLevel == 50 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'">50%</button>
                        <button type="button" @click="setZoom(75)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition" :class="zoomLevel == 75 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'">75%</button>
                        <button type="button" @click="setZoom(100)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition" :class="zoomLevel == 100 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'">100%</button>
                        <button type="button" @click="setZoom(125)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition" :class="zoomLevel == 125 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'">125%</button>
                        <button type="button" @click="setZoom(150)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition" :class="zoomLevel == 150 ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'">150%</button>

                        <span title="Preferences are saved automatically in your browser" class="text-[10px] text-emerald-400 font-extrabold bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-md flex items-center ml-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1 animate-pulse"></span>
                            Saved ⚡
                        </span>
                    </div>
                </div>
            </div>
            </div>

            <!-- Clickable Variable Inserter Toolbar Pills -->
            <div class="w-full mt-5 space-y-3 bg-white/90 backdrop-blur-xl border border-slate-200/80 rounded-2xl p-4 shadow-md">
                <div>
                    <span class="text-[11px] font-extrabold text-indigo-700 uppercase tracking-wider block mb-2">🏫 School Variable Tags (Click to Insert):</span>
                    <div class="flex flex-wrap gap-1.5">
                        @php
                            $schoolVars = [
                                '{School Logo}', '{School Name}', '{Registration Code}', '{Principal Name}',
                                '{School Contact}', '{School Email}', '{School Website}', '{School Address}'
                            ];
                        @endphp
                        @foreach($schoolVars as $v)
                            <button type="button" wire:click="appendVariableToSelected('{{ $v }}')" class="px-2.5 py-1 {{ $v === '{School Logo}' ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm shadow-indigo-600/25' : 'bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200/80' }} rounded-lg text-xs font-bold transition">
                                + {{ $v }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <span class="text-[11px] font-extrabold text-amber-700 uppercase tracking-wider block mb-2">🎓 Student Variable Tags (Click to Insert):</span>
                    <div class="flex flex-wrap gap-1.5">
                        @php
                            $studentVars = [
                                '{Student Photo}', '{QR Code}', '{Barcode}', '{First Name}', '{Middle Name}', '{Last Name}',
                                '{Roll No}', '{Ref No}', '{Campaign}', '{Standard}', '{Division}',
                                'Grade ({grade}) Div ({division})', '{Blood Group}', '{Gender}',
                                '{DOB}', '{Contact Number}', '{Address}', '{Pincode}'
                            ];
                        @endphp
                        @foreach($studentVars as $v)
                            @php
                                $btnStyle = 'bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200/80';
                                if ($v === '{Student Photo}') {
                                    $btnStyle = 'bg-amber-600 text-white hover:bg-amber-700 shadow-sm shadow-amber-600/25';
                                } elseif ($v === '{QR Code}') {
                                    $btnStyle = 'bg-violet-600 text-white hover:bg-violet-700 shadow-sm shadow-violet-600/25';
                                } elseif ($v === '{Barcode}') {
                                    $btnStyle = 'bg-cyan-600 text-white hover:bg-cyan-700 shadow-sm shadow-cyan-600/25';
                                }
                            @endphp
                            <button type="button" wire:click="appendVariableToSelected('{{ $v }}')" class="px-2.5 py-1 {{ $btnStyle }} rounded-lg text-xs font-bold transition">
                                + {{ $v }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Canva Studio Tabbed Inspector Panel (5 Cols) -->
        <div class="md:col-span-6 lg:col-span-5 min-w-0 flex flex-col h-full space-y-4">
            <div class="bg-white border border-slate-200/80 rounded-3xl shadow-xl shadow-slate-200/40 p-5 space-y-4 flex-1 flex flex-col min-h-[620px]">
                
                <!-- Tab Navigation Segmented Bar (4 Tabs) -->
                <div class="flex items-center bg-slate-100/90 p-1.5 rounded-2xl border border-slate-200/80 gap-1">
                    <button type="button" 
                        @click="activeInspectorTab = 'layers'" 
                        :class="activeInspectorTab === 'layers' ? 'bg-white text-indigo-700 shadow-sm font-black border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 font-bold'"
                        class="flex-1 py-2 text-center text-xs rounded-xl transition flex items-center justify-center space-x-1"
                    >
                        <span>📑</span>
                        <span class="truncate">Layers</span>
                    </button>

                    <button type="button" 
                        @click="activeInspectorTab = 'controls'" 
                        :class="activeInspectorTab === 'controls' ? 'bg-white text-indigo-700 shadow-sm font-black border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 font-bold'"
                        class="flex-1 py-2 text-center text-xs rounded-xl transition flex items-center justify-center space-x-1 relative"
                    >
                        <span>🎛️</span>
                        <span class="truncate max-w-[100px]">
                            @if($selectedLayerIndex !== null && isset($layers[$selectedLayerIndex]))
                                {{ $layers[$selectedLayerIndex]['label'] ?? 'Controls' }}
                            @else
                                Controls
                            @endif
                        </span>
                        @if($selectedLayerIndex !== null || count($selectedLayerIndices) > 0)
                            <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse shrink-0"></span>
                        @endif
                    </button>

                    <button type="button" 
                        @click="activeInspectorTab = 'background'" 
                        :class="activeInspectorTab === 'background' ? 'bg-white text-indigo-700 shadow-sm font-black border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 font-bold'"
                        class="flex-1 py-2 text-center text-xs rounded-xl transition flex items-center justify-center space-x-1"
                    >
                        <span>🖼️</span>
                        <span class="truncate">Background</span>
                    </button>

                    <button type="button" 
                        @click="activeInspectorTab = 'uploads'" 
                        :class="activeInspectorTab === 'uploads' ? 'bg-white text-indigo-700 shadow-sm font-black border border-slate-200/60' : 'text-slate-600 hover:text-slate-900 font-bold'"
                        class="flex-1 py-2 text-center text-xs rounded-xl transition flex items-center justify-center space-x-1"
                    >
                        <span>📁</span>
                        <span class="truncate">Uploads</span>
                    </button>
                </div>

                <!-- TAB 1: Templates Layers & Assets -->
                <div x-show="activeInspectorTab === 'layers'" x-transition:enter="transition ease-out duration-150 transform opacity-0 scale-95" class="space-y-4 flex-1 flex flex-col">
                    <div class="border-b border-slate-100 pb-3 space-y-2.5">
                        <div>
                            <h3 class="text-sm font-black text-slate-900">Template Layers & Assets</h3>
                            <p class="text-[11px] text-slate-500 font-medium">Add, reorder, or select elements on your ID card</p>
                        </div>

                        <!-- Dedicated Quick Add Buttons Row -->
                        <div class="flex items-center flex-wrap gap-2 pt-1">
                            <button type="button" wire:click="addTextLayer" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-indigo-600/20">
                                + Text
                            </button>
                            <label class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-amber-600/20 cursor-pointer">
                                <span>+ Image</span>
                                <input type="file" wire:model.live="uploadedImage" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="hidden">
                            </label>
                            <button type="button" wire:click="addQrLayer" class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-violet-600/20">
                                + QR Code
                            </button>
                            <button type="button" wire:click="addBarcodeLayer" class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-cyan-600/20">
                                + Barcode
                            </button>
                            <div class="relative" x-data="{ shapeMenuOpen: false }" @click.outside="shapeMenuOpen = false">
                                <button type="button" @click="shapeMenuOpen = !shapeMenuOpen" class="px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-emerald-600/20">
                                    + Shape ▾
                                </button>
                                <div x-show="shapeMenuOpen" @click="shapeMenuOpen = false" class="absolute left-0 mt-1.5 w-40 bg-white border border-slate-200 rounded-2xl shadow-2xl z-50 overflow-hidden py-1">
                                    <button type="button" wire:click="addShapeLayer('rectangle')" class="w-full text-left px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center space-x-2.5">
                                        <span class="w-3.5 h-3.5 rounded bg-indigo-500 inline-block shadow-sm"></span>
                                        <span>Rectangle Box</span>
                                    </button>
                                    <button type="button" wire:click="addShapeLayer('circle')" class="w-full text-left px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center space-x-2.5">
                                        <span class="w-3.5 h-3.5 rounded-full bg-indigo-500 inline-block shadow-sm"></span>
                                        <span>Circle Shape</span>
                                    </button>
                                    <button type="button" wire:click="addShapeLayer('line')" class="w-full text-left px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center space-x-2.5">
                                        <span class="w-4 h-1 bg-indigo-500 rounded-full inline-block"></span>
                                        <span>Line Divider</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Layer List Cards -->
                    <div class="space-y-2 flex-1 overflow-y-auto pr-1 min-h-0">
                        @foreach($layers as $idx => $layer)
                            @php
                                $isSelectedInList = in_array($idx, $selectedLayerIndices);
                                $layerTypeDisplay = ($layer['type'] ?? 'layer') === 'shape' ? ($layer['shape_type'] ?? 'shape') : ($layer['type'] ?? 'layer');
                            @endphp
                            <div 
                                wire:key="list-layer-{{ $layer['id'] ?? $idx }}"
                                @click.prevent="$wire.selectLayer({{ $idx }}, $event.shiftKey)"
                                class="p-3 rounded-2xl border transition flex items-center justify-between cursor-pointer {{ $isSelectedInList ? 'bg-indigo-50/90 border-indigo-300 text-indigo-950 shadow-sm' : 'bg-slate-50/70 border-slate-200/80 text-slate-700 hover:bg-slate-100 hover:border-slate-300' }}"
                            >
                                <div class="flex items-center space-x-3">
                                    <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md border {{ $isSelectedInList ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-indigo-50 text-indigo-700 border-indigo-200' }}">
                                        {{ $layerTypeDisplay }}
                                    </span>
                                    <div>
                                        <span class="text-xs font-bold block text-slate-900">{{ $layer['label'] ?? 'Layer #' . ($idx + 1) }}</span>
                                        <span class="text-[10px] text-slate-500 font-mono">X: {{ $layer['x'] ?? 0 }}px, Y: {{ $layer['y'] ?? 0 }}px</span>
                                    </div>
                                </div>
                                <span class="text-xs font-bold {{ $isSelectedInList ? 'text-indigo-600' : 'text-slate-400' }}">&rarr;</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- TAB 2: Element Controls -->
                <div x-show="activeInspectorTab === 'controls'" x-transition:enter="transition ease-out duration-150 transform opacity-0 scale-95" class="space-y-4 flex-1 flex flex-col min-h-0">
                    <!-- Alignment & Layer Tools for Multiple Selections -->
                    @if(count($selectedLayerIndices) > 1)
                        <div wire:key="controls-panel-multi" class="space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 animate-ping"></span>
                                    <h3 class="text-sm font-black text-slate-900 flex items-center">
                                        Multiple Selection
                                        <span class="ml-2 text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-bold">
                                            {{ count($selectedLayerIndices) }} elements
                                        </span>
                                    </h3>
                                </div>
                                <button type="button" wire:click="selectLayer(null)" class="text-xs font-bold text-slate-500 hover:text-slate-900">Deselect</button>
                            </div>

                            <!-- Alignment Section -->
                            <div class="space-y-3">
                                <!-- Align Target Selector Segment -->
                                <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
                                    <button type="button" @click="alignMode = 'page'" class="flex-1 py-1.5 text-center text-xs font-bold rounded-lg transition" :class="alignMode === 'page' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                                        Align to page
                                    </button>
                                    <button type="button" @click="alignMode = 'selection'" class="flex-1 py-1.5 text-center text-xs font-bold rounded-lg transition" :class="alignMode === 'selection' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                                        Align selection
                                    </button>
                                </div>

                                <div class="grid grid-cols-2 gap-2.5">
                                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('top') : alignSelectedToSelection('top')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                                        <span>Top</span>
                                    </button>
                                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('left') : alignSelectedToSelection('left')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                                        <span>Left</span>
                                    </button>
                                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('middle') : alignSelectedToSelection('middle')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                                        <span>Middle</span>
                                    </button>
                                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('center') : alignSelectedToSelection('center')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                                        <span>Center</span>
                                    </button>
                                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('bottom') : alignSelectedToSelection('bottom')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                                        <span>Bottom</span>
                                    </button>
                                    <button type="button" @click="alignMode === 'page' ? alignSelectedToPage('right') : alignSelectedToSelection('right')" class="flex items-center justify-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold transition">
                                        <span>Right</span>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                                @php
                                    $hasGroup = false;
                                    foreach ($selectedLayerIndices as $idx) {
                                        if (!empty($layers[$idx]['group_id'])) {
                                            $hasGroup = true;
                                            break;
                                        }
                                    }
                                @endphp
                                <span class="text-[10px] text-slate-500 font-medium">Bulk operations affect selection.</span>
                                <div class="flex items-center space-x-2">
                                    @if($hasGroup)
                                        <button type="button" 
                                            wire:click="ungroupSelected" 
                                            class="px-3.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-xs font-bold transition"
                                        >
                                            Ungroup
                                        </button>
                                    @else
                                        <button type="button" 
                                            wire:click="groupSelected" 
                                            class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm"
                                        >
                                            Group
                                        </button>
                                    @endif
                                    <button type="button" 
                                        wire:click="duplicateSelected" 
                                        class="px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition"
                                    >
                                        Duplicate
                                    </button>
                                    <button type="button" 
                                        wire:click="removeLayer(-1)" 
                                        class="px-3.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl text-xs font-bold transition"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    @elseif($selectedLayerIndex !== null && isset($layers[$selectedLayerIndex]))
                        @php $selectedLayer = $layers[$selectedLayerIndex]; @endphp
                        <div wire:key="controls-panel-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? 'layer' }}" class="space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center space-x-2">
                                    <span class="w-3 h-3 rounded-full bg-indigo-600 inline-block shadow-sm"></span>
                                    <h3 class="text-sm font-black text-slate-900">Element Controls ({{ $selectedLayer['label'] ?? 'Layer' }})</h3>
                                </div>
                                <button type="button" wire:click="$set('selectedLayerIndex', null)" class="text-xs font-bold text-slate-500 hover:text-slate-900">Deselect</button>
                            </div>

                            <!-- Alignment Actions Bar -->
                            <div class="space-y-2">
                                <span class="text-[11px] font-bold text-slate-500 block">Quick Align Canvas:</span>
                                <div class="grid grid-cols-6 gap-1.5">
                                    <button type="button" wire:click="alignSelectedLayer('left')" title="Align Left" class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold flex justify-center">Left</button>
                                    <button type="button" wire:click="alignSelectedLayer('center_h')" title="Center Horizontally" class="p-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold flex justify-center">Center H</button>
                                    <button type="button" wire:click="alignSelectedLayer('right')" title="Align Right" class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold flex justify-center">Right</button>
                                    <button type="button" wire:click="alignSelectedLayer('top')" title="Align Top" class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold flex justify-center">Top</button>
                                    <button type="button" wire:click="alignSelectedLayer('center_v')" title="Center Vertically" class="p-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold flex justify-center">Center V</button>
                                    <button type="button" wire:click="alignSelectedLayer('bottom')" title="Align Bottom" class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold flex justify-center">Bottom</button>
                            <!-- Universal Transparency & Fade Effects Controls (ALL Layer Types) -->
                            <div class="space-y-3 pt-2 border-t border-slate-100">
                                <span class="text-xs font-black text-indigo-700 uppercase tracking-wider block">✨ Transparency & Fade Effects:</span>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="text-[11px] font-bold text-slate-500">Overall Opacity</label>
                                            <span class="text-xs font-mono font-bold text-indigo-600">{{ $selectedLayer['opacity'] ?? 100 }}%</span>
                                        </div>
                                        <input type="range" min="0" max="100" wire:key="universal-opacity-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.opacity" class="w-full accent-indigo-600">
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Fade Gradient Mask</label>
                                        <select wire:key="universal-fademode-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.fade_mode" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                            <option value="none">Solid (No Fade)</option>
                                            <option value="fade_bottom">Linear Fade ⬇️ (Top to Bottom)</option>
                                            <option value="fade_top">Linear Fade ⬆️ (Bottom to Top)</option>
                                            <option value="fade_right">Linear Fade ➡️ (Left to Right)</option>
                                            <option value="fade_left">Linear Fade ⬅️ (Right to Left)</option>
                                            <option value="radial">Radial Center Fade ⭕</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Layer Name / Label & Millimeter Position Controls -->
                            <div class="space-y-3 pt-1">
                                <div>
                                    <label class="block text-[11px] font-bold text-indigo-700 mb-1">Layer Label Name</label>
                                    <input type="text" wire:key="input-label-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.label" placeholder="e.g. Header Title, Student Roll Tag" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 transition">
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Position X (mm)</label>
                                        <div class="relative">
                                            <input type="number" step="0.1" wire:key="input-x-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(curX / 11.8128 * 10) / 10" @input="curX = Math.round((parseFloat($event.target.value) || 0) * 11.8128); $wire.layers[{{ $selectedLayerIndex }}].x = curX; $wire.updateLayerCoordinates({{ $selectedLayerIndex }}, curX, curY);" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                            <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-mono" x-text="curX + 'px'"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Position Y (mm)</label>
                                        <div class="relative">
                                            <input type="number" step="0.1" wire:key="input-y-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(curY / 11.8128 * 10) / 10" @input="curY = Math.round((parseFloat($event.target.value) || 0) * 11.8128); $wire.layers[{{ $selectedLayerIndex }}].y = curY; $wire.updateLayerCoordinates({{ $selectedLayerIndex }}, curX, curY);" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                            <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-mono" x-text="curY + 'px'"></span>
                                        </div>
                                    </div>
                                </div>

                                @if(in_array($selectedLayer['type'] ?? '', ['photo', 'logo', 'qr', 'text', 'shape']))
                                    <div class="grid grid-cols-2 gap-3 pt-1">
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                                {{ ($selectedLayer['type'] ?? '') === 'text' ? 'Max Width (mm)' : 'Width (mm)' }}
                                            </label>
                                            <div class="relative">
                                                <input type="number" step="0.1" wire:key="input-w-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(curW / 11.8128 * 10) / 10" @input="curW = Math.round((parseFloat($event.target.value) || 0) * 11.8128); $wire.layers[{{ $selectedLayerIndex }}].width = curW; $wire.updateLayerDimensions({{ $selectedLayerIndex }}, curW, curH, curFontSize, curX, curY);" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                                <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-mono" x-text="curW + 'px'"></span>
                                            </div>
                                        </div>
                                        @if(($selectedLayer['type'] ?? '') !== 'text')
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Height (mm)</label>
                                                <div class="relative">
                                                    <input type="number" step="0.1" wire:key="input-h-mm-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" :value="Math.round(curH / 11.8128 * 10) / 10" @input="curH = Math.round((parseFloat($event.target.value) || 0) * 11.8128); $wire.layers[{{ $selectedLayerIndex }}].height = curH; $wire.updateLayerDimensions({{ $selectedLayerIndex }}, curW, curH, curFontSize, curX, curY);" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                                    <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-mono" x-text="curH + 'px'"></span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Text Specific Formatting Controls -->
                            @if(($selectedLayer['type'] ?? '') === 'text')
                                <div class="space-y-3 pt-2">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Text Content / Template Code</label>
                                        <input type="text" wire:key="input-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Font Family</label>
                                            <select wire:key="select-font-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.font_family" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                                <optgroup label="Sans-Serif (Modern & Clean)">
                                                    <option value="Inter">Inter</option>
                                                    <option value="Poppins">Poppins</option>
                                                    <option value="Roboto">Roboto</option>
                                                    <option value="Outfit">Outfit</option>
                                                    <option value="Montserrat">Montserrat</option>
                                                    <option value="Lato">Lato</option>
                                                    <option value="Open Sans">Open Sans</option>
                                                    <option value="Raleway">Raleway</option>
                                                    <option value="Nunito">Nunito</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Font Size (pt)</label>
                                            <input type="number" min="6" max="140" wire:key="input-fsize-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.font_size" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Font Weight</label>
                                            <select wire:key="select-weight-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.font_weight" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                                <option value="normal">Normal</option>
                                                <option value="semibold">Semi Bold</option>
                                                <option value="bold">Bold</option>
                                                <option value="extrabold">Extra Bold</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Text Alignment</label>
                                            <select wire:key="select-align-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.align" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                                <option value="left">Left Align</option>
                                                <option value="center">Center Align</option>
                                                <option value="right">Right Align</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Text Color (Hex)</label>
                                        <div class="flex items-center space-x-2">
                                            <input type="color" wire:key="input-color-picker-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.color" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer shadow-sm">
                                            <input type="text" wire:key="input-color-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.color" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 uppercase">
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- QR Code Specific Controls -->
                            @if(($selectedLayer['type'] ?? '') === 'qr')
                                <div class="space-y-3 pt-2 border-t border-slate-100">
                                    <span class="text-xs font-extrabold text-violet-700 block">QR Code Data Settings:</span>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">QR Encoded Value / Variable Tag</label>
                                        <input type="text" wire:key="input-qr-value-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.value" placeholder="e.g. {Ref No}, {Roll No}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                        
                                        <span class="text-[10px] text-slate-500 mt-1 block">Specify variable tag (click any tag below to insert) or static text encoded in the QR code.</span>
                                    </div>
                                </div>
                            @endif

                            <!-- Barcode Specific Controls -->
                            @if(($selectedLayer['type'] ?? '') === 'barcode')
                                <div class="space-y-3 pt-2 border-t border-slate-100">
                                    <span class="text-xs font-extrabold text-cyan-700 block">1D Barcode Data Settings:</span>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Barcode Encoded Value / Variable Tag</label>
                                        <input type="text" wire:key="input-barcode-value-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.value" placeholder="e.g. {Ref No}, {Roll No}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                        <span class="text-[10px] text-slate-500 mt-1 block">Specify variable tag (click any tag below to insert) or static text encoded in Code 128 barcode.</span>
                                    </div>
                                    <div class="flex items-center justify-between pt-1">
                                        <label class="text-xs font-bold text-slate-700">Display Text Value below Barcode</label>
                                        <input type="checkbox" wire:key="check-barcode-showtext-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.show_text" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                    </div>
                                </div>
                            @endif

                            <!-- Image / Custom Upload Specific Controls -->
                            @if(($selectedLayer['type'] ?? '') === 'image')
                                <div class="space-y-4 pt-2 border-t border-slate-100">
                                    <span class="text-xs font-extrabold text-amber-700 block">Custom Uploaded Image Settings:</span>

                                    <!-- Replace Image File Button -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Replace Image File (JPG, PNG, WEBP, SVG)</label>
                                        <input type="file" wire:key="input-img-file-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="uploadedImage" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100 cursor-pointer">
                                        <div wire:loading wire:target="uploadedImage" class="text-[10px] text-amber-600 font-bold mt-1">Uploading image...</div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Object Fit Mode</label>
                                            <select wire:key="select-fit-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.object_fit" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                                <option value="contain">Contain (Keep Aspect)</option>
                                                <option value="cover">Cover (Fill & Crop)</option>
                                                <option value="fill">Fill (Stretch)</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Corner Radius (px)</label>
                                            <input type="number" min="0" max="999" wire:key="input-img-bradius-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_radius" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Border Width (px)</label>
                                            <input type="number" min="0" max="30" wire:key="input-img-bwidth-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_width" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Border Color</label>
                                            <div class="flex items-center space-x-2">
                                                <input type="color" wire:key="input-img-bcolor-picker-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_color" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer shadow-sm">
                                                <input type="text" wire:key="input-img-bcolor-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_color" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 uppercase">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="text-[11px] font-bold text-slate-500">Opacity (%)</label>
                                            <span class="text-xs font-mono font-bold text-indigo-600">{{ $selectedLayer['opacity'] ?? 100 }}%</span>
                                        </div>
                                        <input type="range" min="0" max="100" wire:key="input-img-opacity-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.opacity" class="w-full accent-indigo-600">
                                    </div>
                                </div>
                            @endif

                            <!-- Shape Specific Controls -->
                            @if(($selectedLayer['type'] ?? '') === 'shape')
                                @php
                                    $shapeType = $selectedLayer['shape_type'] ?? 'rectangle';
                                @endphp
                                <div class="space-y-3 pt-2 border-t border-slate-100">
                                    <span class="text-xs font-extrabold text-indigo-700 block">Shape Formatting ({{ ucfirst($shapeType) }}):</span>

                                    @if($shapeType !== 'line')
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Fill Mode</label>
                                                <select wire:key="select-fill-type-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:change="updateShapeProperty('fill_type', $event.target.value)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                                    <option value="solid" {{ ($selectedLayer['fill_type'] ?? 'solid') === 'solid' ? 'selected' : '' }}>Solid</option>
                                                    <option value="none" {{ ($selectedLayer['fill_type'] ?? 'solid') === 'none' ? 'selected' : '' }}>None (Outline only)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Opacity (%)</label>
                                                <input type="range" min="0" max="100" wire:key="range-fill-opacity-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['fill_opacity'] ?? 100 }}" @change="$wire.updateShapeProperty('fill_opacity', parseInt($event.target.value))" class="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600 mt-2.5">
                                            </div>
                                        </div>
                                        @if(($selectedLayer['fill_type'] ?? 'solid') !== 'none')
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Fill Color (Hex)</label>
                                                <div class="flex items-center space-x-2">
                                                    <input type="color" wire:key="input-fill-picker-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['fill_color'] ?? '#4f46e5' }}" @change="$wire.updateShapeProperty('fill_color', $event.target.value)" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer shadow-sm">
                                                    <input type="text" wire:key="input-fill-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['fill_color'] ?? '#4f46e5' }}" @change="$wire.updateShapeProperty('fill_color', $event.target.value)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 uppercase">
                                                </div>
                                            </div>
                                        @endif
                                    @endif

                                    <div>
                                        <label class="block text-[11px] font-bold text-indigo-700 mb-1">Stroke Color (Hex)</label>
                                        <div class="flex items-center space-x-2">
                                            <input type="color" wire:key="input-stroke-picker-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['stroke_color'] ?? '#312e81' }}" @change="$wire.updateShapeProperty('stroke_color', $event.target.value)" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer shadow-sm">
                                            <input type="text" wire:key="input-stroke-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['stroke_color'] ?? '#312e81' }}" @change="$wire.updateShapeProperty('stroke_color', $event.target.value)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 uppercase">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Stroke Width (px)</label>
                                            <input type="number" min="0" max="40" wire:key="input-stroke-width-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['stroke_width'] ?? 0 }}" @change="$wire.updateShapeProperty('stroke_width', parseInt($event.target.value) || 0)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Stroke Style</label>
                                            <select wire:key="select-stroke-style-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:change="updateShapeProperty('stroke_style', $event.target.value)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                                <option value="solid" {{ ($selectedLayer['stroke_style'] ?? 'solid') === 'solid' ? 'selected' : '' }}>Solid</option>
                                                <option value="dashed" {{ ($selectedLayer['stroke_style'] ?? 'solid') === 'dashed' ? 'selected' : '' }}>Dashed</option>
                                                <option value="dotted" {{ ($selectedLayer['stroke_style'] ?? 'solid') === 'dotted' ? 'selected' : '' }}>Dotted</option>
                                            </select>
                                        </div>
                                    </div>

                                    @if($shapeType === 'rectangle')
                                        <div class="grid grid-cols-2 gap-3 items-end">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Corner Radius (px)</label>
                                                <input type="number" min="0" max="500" wire:key="input-corner-radius-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['corner_radius'] ?? 0 }}" @change="$wire.updateShapeProperty('corner_radius', parseInt($event.target.value) || 0)" {{ !empty($selectedLayer['corner_radius_pill']) ? 'disabled' : '' }} class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 disabled:opacity-40">
                                            </div>
                                            <label class="flex items-center space-x-2 pb-2 cursor-pointer">
                                                <input type="checkbox" wire:key="check-pill-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" {{ !empty($selectedLayer['corner_radius_pill']) ? 'checked' : '' }} @change="$wire.updateShapeProperty('corner_radius_pill', $event.target.checked)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="text-[11px] font-bold text-slate-700">Pill Shape</span>
                                            </label>
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Layer Opacity (%)</label>
                                        <input type="range" min="0" max="100" wire:key="range-opacity-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" value="{{ $selectedLayer['opacity'] ?? 100 }}" @change="$wire.updateShapeProperty('opacity', parseInt($event.target.value))" class="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                                    </div>
                                </div>
                            @endif

                            <!-- Photo Specific Shape & Frame Formatting Controls -->
                            @if(($selectedLayer['type'] ?? '') === 'photo')
                                <div class="space-y-3 pt-2 border-t border-slate-100">
                                    <div>
                                        <label class="block text-[11px] font-bold text-indigo-700 mb-1.5">Photo Frame Shape & Aspect Ratio</label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <!-- 1:1 Square Option -->
                                            <button 
                                                type="button" 
                                                @click="
                                                    curH = curW;
                                                    $wire.layers[{{ $selectedLayerIndex }}].shape = 'square';
                                                    $wire.layers[{{ $selectedLayerIndex }}].height = curW;
                                                    $wire.layers[{{ $selectedLayerIndex }}].border_radius = 12;
                                                    $wire.updateLayerDimensions({{ $selectedLayerIndex }}, curW, curW, curFontSize, curX, curY);
                                                "
                                                class="py-2.5 px-2 rounded-xl border text-xs font-extrabold flex flex-col items-center justify-center space-y-1 transition active:scale-95"
                                                :class="($wire.layers[{{ $selectedLayerIndex }}].shape === 'square' || ($wire.layers[{{ $selectedLayerIndex }}].border_radius < 999 && Math.abs(curW - curH) < 5)) ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30' : 'bg-slate-50 text-slate-700 border-slate-200 hover:border-slate-300'"
                                            >
                                                <span class="w-4 h-4 rounded-md border-2 border-current block"></span>
                                                <span>1:1 Square</span>
                                            </button>

                                            <!-- 3:4 Portrait Option -->
                                            <button 
                                                type="button" 
                                                @click="
                                                    curH = Math.round(curW * 4 / 3);
                                                    $wire.layers[{{ $selectedLayerIndex }}].shape = 'portrait';
                                                    $wire.layers[{{ $selectedLayerIndex }}].height = curH;
                                                    $wire.layers[{{ $selectedLayerIndex }}].border_radius = 12;
                                                    $wire.updateLayerDimensions({{ $selectedLayerIndex }}, curW, curH, curFontSize, curX, curY);
                                                "
                                                class="py-2.5 px-2 rounded-xl border text-xs font-extrabold flex flex-col items-center justify-center space-y-1 transition active:scale-95"
                                                :class="($wire.layers[{{ $selectedLayerIndex }}].shape === 'portrait' || ($wire.layers[{{ $selectedLayerIndex }}].border_radius < 999 && curH > curW)) ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30' : 'bg-slate-50 text-slate-700 border-slate-200 hover:border-slate-300'"
                                            >
                                                <span class="w-3.5 h-4.5 rounded-md border-2 border-current block"></span>
                                                <span>3:4 Portrait</span>
                                            </button>

                                            <!-- Round / Circle Option -->
                                            <button 
                                                type="button" 
                                                @click="
                                                    curH = curW;
                                                    $wire.layers[{{ $selectedLayerIndex }}].shape = 'round';
                                                    $wire.layers[{{ $selectedLayerIndex }}].height = curW;
                                                    $wire.layers[{{ $selectedLayerIndex }}].border_radius = 9999;
                                                    $wire.updateLayerDimensions({{ $selectedLayerIndex }}, curW, curW, curFontSize, curX, curY);
                                                "
                                                class="py-2.5 px-2 rounded-xl border text-xs font-extrabold flex flex-col items-center justify-center space-y-1 transition active:scale-95"
                                                :class="($wire.layers[{{ $selectedLayerIndex }}].shape === 'round' || $wire.layers[{{ $selectedLayerIndex }}].border_radius >= 999) ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30' : 'bg-slate-50 text-slate-700 border-slate-200 hover:border-slate-300'"
                                            >
                                                <span class="w-4 h-4 rounded-full border-2 border-current block"></span>
                                                <span>Round ⭕</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Border Radius (px)</label>
                                            <input type="number" min="0" max="9999" wire:key="input-radius-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_radius" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Border Width (px)</label>
                                            <input type="number" min="0" max="20" wire:key="input-bwidth-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_width" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1">Border Color (Hex)</label>
                                        <div class="flex items-center space-x-2">
                                            <input type="color" wire:key="input-bcolor-picker-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_color" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer shadow-sm">
                                            <input type="text" wire:key="input-bcolor-text-{{ $selectedLayerIndex }}-{{ $selectedLayer['id'] ?? '' }}" wire:model.live="layers.{{ $selectedLayerIndex }}.border_color" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600 uppercase">
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Layer Action Buttons -->
                            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                                <div class="flex space-x-1.5">
                                    <button type="button" wire:click="moveLayer({{ $selectedLayerIndex }}, 'up')" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition">Up</button>
                                    <button type="button" wire:click="moveLayer({{ $selectedLayerIndex }}, 'down')" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition">Down</button>
                                    <button type="button" wire:click="duplicateSelected" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition">Duplicate</button>
                                    @if(!empty($selectedLayer['group_id']))
                                        <button type="button" wire:click="ungroupSelected" class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-xs font-bold transition">Ungroup</button>
                                    @endif
                                </div>
                                <button type="button" wire:click="removeLayer({{ $selectedLayerIndex }})" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl text-xs font-bold transition">Delete</button>
                            </div>
                        </div>
                    @else
                        <!-- Empty State when no element is selected -->
                        <div class="text-center py-10 px-4 bg-slate-50 border border-dashed border-slate-200 rounded-2xl space-y-3">
                            <div class="w-12 h-12 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center mx-auto text-indigo-600 text-xl shadow-sm">
                                🎛️
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-900">No Element Selected</h4>
                                <p class="text-[11px] text-slate-500 mt-1 max-w-xs mx-auto">Click on any element on the canvas or select a layer from the Layers tab to customize formatting & properties.</p>
                            </div>
                            <button type="button" @click="activeInspectorTab = 'layers'" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-indigo-600/20">
                                Open Layers Directory
                            </button>
                        </div>
                    @endif
                </div>

                <!-- TAB 3: Background Graphic -->
                <div x-show="activeInspectorTab === 'background'" x-transition:enter="transition ease-out duration-150 transform opacity-0 scale-95" class="space-y-4 flex-1 flex flex-col min-h-0">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-sm font-black text-slate-900">Background Graphic</h3>
                            <p class="text-[11px] text-slate-500 font-medium">Upload or update the master card background graphic</p>
                        </div>
                        @if($template && $template->background_image)
                            <button type="button" wire:click="deleteBackgroundImage" wire:confirm="Are you sure you want to delete the current background image?" class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                <span>Delete</span>
                            </button>
                        @endif
                    </div>

                    @if($template && $template->background_image)
                        <div class="relative group rounded-2xl overflow-hidden border border-slate-200/80 bg-slate-100/70 p-3 space-y-2">
                            @php
                                $bgImgUrl = str_starts_with($template->background_image, 'http') 
                                    ? $template->background_image 
                                    : asset('storage/' . $template->background_image);
                            @endphp
                            <div class="w-full rounded-xl overflow-hidden bg-slate-900/90 border border-slate-300/80 p-2 flex items-center justify-center shadow-inner">
                                <img src="{{ $bgImgUrl }}" class="w-full h-auto max-h-[380px] object-contain rounded-lg shadow-md" alt="Full Master Background Graphic" />
                            </div>
                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 px-1 pt-1">
                                <span class="text-emerald-700 font-bold flex items-center">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block mr-1.5 animate-pulse"></span>
                                    Active Background Graphic (Full View)
                                </span>
                                <a href="{{ $bgImgUrl }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-extrabold flex items-center space-x-1">
                                    <span>🔍 Open Original</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">
                            {{ ($template && $template->background_image) ? 'Update / Replace Background Graphic' : 'Upload New Background Graphic' }}
                        </label>
                        <input type="file" wire:model="bgUpload" accept="image/*" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-700 focus:outline-none focus:border-indigo-600 transition">
                        @if($bgUpload)
                            <span class="text-[11px] font-bold text-indigo-700 mt-1 block">New background file selected. Click 'Save Design' to apply update!</span>
                        @else
                            <span class="text-[10px] text-slate-500 mt-1 block">Upload custom background graphic (CR-80 85.6mm x 54mm equivalent ratio)</span>
                        @endif
                    </div>
                </div>

                <!-- TAB 4: Uploads Media Library -->
                <div x-show="activeInspectorTab === 'uploads'" x-transition:enter="transition ease-out duration-150 transform opacity-0 scale-95" class="space-y-4 flex-1 flex flex-col min-h-0">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-sm font-black text-slate-900">Uploads Media Library</h3>
                            <p class="text-[11px] text-slate-500 font-medium">Dump JPG, PNG, WEBP & SVG images to use on your cards</p>
                        </div>
                        <label class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow-md shadow-amber-600/20 cursor-pointer space-x-1">
                            <span>+ Upload Image</span>
                            <input type="file" wire:model.live="uploadedImage" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="hidden">
                        </label>
                    </div>

                    <!-- Drag & Drop Upload Zone -->
                    <div class="relative border-2 border-dashed border-slate-200 hover:border-amber-500 rounded-2xl p-4 bg-slate-50/70 hover:bg-amber-50/30 transition text-center group cursor-pointer">
                        <input type="file" wire:model.live="uploadedImage" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div class="space-y-1.5">
                            <div class="w-10 h-10 mx-auto rounded-2xl bg-amber-100/80 text-amber-700 flex items-center justify-center text-lg group-hover:scale-110 transition shadow-sm">
                                ☁️
                            </div>
                            <p class="text-xs font-bold text-slate-800">Click or drop images here to upload</p>
                            <p class="text-[10px] text-slate-400 font-medium">Supports JPG, PNG, WEBP & SVG (Max 10MB)</p>
                            <div wire:loading wire:target="uploadedImage" class="text-xs text-amber-600 font-bold">Uploading file to library...</div>
                        </div>
                    </div>

                    <!-- Uploaded Files Grid -->
                    <div class="flex-1 flex flex-col space-y-2.5 min-h-0">
                        <div class="flex items-center justify-between px-1">
                            <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Uploaded Images</span>
                            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full text-[10px] font-bold">
                                {{ count($this->uploadedAssets) }} images
                            </span>
                        </div>

                        @if(count($this->uploadedAssets) > 0)
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5 overflow-y-auto pr-1 flex-1 max-h-[420px]">
                                @foreach($this->uploadedAssets as $assetPath)
                                    @php
                                        $assetUrl = asset('storage/' . $assetPath);
                                        $assetName = basename($assetPath);
                                    @endphp
                                    <div wire:key="tab4-asset-thumb-{{ md5($assetPath) }}" class="relative group aspect-square rounded-2xl bg-white border border-slate-200/80 shadow-sm overflow-hidden hover:border-amber-500 hover:shadow-lg transition">
                                        <img src="{{ $assetUrl }}" 
                                            wire:click="addAssetToCanvas('{{ $assetPath }}')" 
                                            title="Click to insert on ID Card" 
                                            class="w-full h-full object-contain p-1.5 cursor-pointer transition transform group-hover:scale-105" 
                                        />
                                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-900/80 to-transparent p-1.5 opacity-0 group-hover:opacity-100 transition flex items-center justify-between pointer-events-none">
                                            <span class="text-[9px] font-bold text-white truncate max-w-[80%]">{{ $assetName }}</span>
                                        </div>
                                        <button type="button" 
                                            wire:click="deleteUploadedAsset('{{ $assetPath }}')" 
                                            wire:confirm="Delete this image from uploads library?" 
                                            title="Delete image asset" 
                                            class="absolute top-1.5 right-1.5 bg-red-600/90 text-white p-1 rounded-lg text-[9px] opacity-0 group-hover:opacity-100 transition shadow-md hover:bg-red-700 z-10"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex-1 flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50 p-6 text-center">
                                <span class="text-3xl mb-2">📁</span>
                                <p class="text-xs font-bold text-slate-600">Your uploads folder is empty</p>
                                <p class="text-[11px] text-slate-400 mt-1 max-w-[220px]">Upload logos, icons, stamps or photos to easily place them on any ID card template.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
                <style>
                .canvas-grid-bg {
                    background-image: 
                        linear-gradient(to right, rgba(99, 102, 241, 0.09) 1px, transparent 1px),
                        linear-gradient(to bottom, rgba(99, 102, 241, 0.09) 1px, transparent 1px);
                    background-size: 10px 10px;
                }
                </style>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>
