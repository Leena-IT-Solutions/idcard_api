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
    public bool $showPrintGuides = true;
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
            if (!$this->template) {
                $this->template = Template::find($templateId);
                if ($this->template) $this->type = 'master';
            }
        } else {
            $this->template = Template::find($templateId);
            if (!$this->template) {
                $this->template = SchoolTemplate::find($templateId);
                if ($this->template) $this->type = 'school';
            }
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
            $idx = $update['idx'] ?? null;
            if ($idx === null || !isset($this->layers[$idx])) continue;
            $this->layers[$idx]['x'] = $update['x'];
            $this->layers[$idx]['y'] = $update['y'];
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
        $textTransform = $lastTextLayer ? ($lastTextLayer['text_transform'] ?? $lastTextLayer['text_case'] ?? 'none') : 'none';
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
            'text_transform' => $textTransform,
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
        $this->recordHistory();
        $idx = (int)($index ?? -1);
        if ($idx >= 0 && isset($this->layers[$idx])) {
            $this->layers[$idx]['x'] = max(0, (int)round((float)($x ?? 0)));
            $this->layers[$idx]['y'] = max(0, (int)round((float)($y ?? 0)));
        }
    }

    public function batchUpdateLayerCoordinates(array $updates)
    {
        $this->recordHistory();
        foreach ($updates as $up) {
            $idx = (int)($up['index'] ?? -1);
            if ($idx >= 0 && isset($this->layers[$idx])) {
                $this->layers[$idx]['x'] = max(0, (int)round((float)($up['x'] ?? 0)));
                $this->layers[$idx]['y'] = max(0, (int)round((float)($up['y'] ?? 0)));
            }
        }
    }

    public function updateLayerDimensions($index, $width = null, $height = null, $fontSize = null, $x = null, $y = null)
    {
        $this->recordHistory();
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

            $ext = strtolower($this->bgUpload->getClientOriginalExtension());
            if ($ext === 'svg') {
                // SVG backgrounds from design tools commonly rely on <filter>
                // (feColorMatrix-into-mask for opacity) which flutter_svg
                // cannot render, leaving the background blank in the mobile
                // app. Rasterize through Chromium (via Browsershot) instead,
                // which renders filters correctly, and store a PNG.
                $widthPx = $this->orientation === 'portrait' ? 638 : 1011;
                $heightPx = $this->orientation === 'portrait' ? 1011 : 638;
                try {
                    $svgContents = file_get_contents($this->bgUpload->getRealPath());
                    $png = (new \App\Services\CardRenderService())->rasterizeSvgToPng($svgContents, $widthPx, $heightPx);
                    $bgPath = 'templates/backgrounds/' . uniqid('bg_', true) . '.png';
                    Storage::disk('public')->put($bgPath, $png);
                } catch (\Throwable $e) {
                    report($e);
                    $bgPath = $this->bgUpload->store('templates/backgrounds', 'public');
                    session()->flash('error', 'Could not convert the SVG background for mobile compatibility, so it was saved as-is. It may not display correctly in the mobile app. (' . $e->getMessage() . ')');
                }
            } else {
                $bgPath = $this->bgUpload->store('templates/backgrounds', 'public');
            }
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
    $bgPath = $template->background_image ?? null;
    $bgUrl = $bgPath ? (str_starts_with($bgPath, 'http') ? $bgPath : asset('storage/' . $bgPath)) : null;

    // Printer's text-safe artwork area: 50mm x 80mm (portrait), inset from the
    // punch-size canvas edge (54mm x 85.6mm), converted via the canvas's own px/mm ratio.
    $safeWidthMm = $isPortrait ? 50 : 80;
    $safeHeightMm = $isPortrait ? 80 : 50;
    $pxPerMmX = $canvasW / $widthMm;
    $pxPerMmY = $canvasH / $heightMm;
    $safeInsetXPx = ($canvasW - $safeWidthMm * $pxPerMmX) / 2;
    $safeInsetYPx = ($canvasH - $safeHeightMm * $pxPerMmY) / 2;
@endphp

<div class="space-y-6 notranslate" translate="no" x-data="templateStudio({{ $canvasW }}, {{ $canvasH }})">

    @include('livewire.templates.partials.top-bar')

    <!-- Main Workspace Split Container (2 Columns: Workbench Left, Inspector Panel Right) -->
    <div class="flex flex-col lg:flex-row items-start gap-6 w-full">
        <!-- Left Column: Interactive Studio Workbench & Variable Tags (7 Cols / 58% Width) -->
        <div class="w-full lg:w-[58%] min-w-0 space-y-5">
            @include('livewire.templates.partials.canvas-workbench')
            @include('livewire.templates.partials.variable-inserter')
        </div>

        <!-- Right Column: Canva Studio Tabbed Inspector Panel (5 Cols / 42% Width) -->
        <div class="w-full lg:w-[42%] min-w-0 flex flex-col space-y-4">
            @include('livewire.templates.partials.inspector-panel')
        </div>
    </div>
</div>
<script>
    function templateStudio(canvasW, canvasH) {
        return {
            zoomLevel: 100,
            activeInspectorTab: 'layers',
            alignMode: 'page',
            activeTool: 'select',
            isSpacePressed: false,
            isPanning: false,
            panStartX: 0,
            panStartY: 0,
            panOffsetX: 0,
            panOffsetY: 0,
            isDragging: false,
            dragLayerIndex: null,
            startX: 0,
            startY: 0,
            origX: 0,
            origY: 0,
            curX: 0,
            curY: 0,
            curW: 150,
            curH: 30,
            curFontSize: 14,
            isResizing: false,
            resizeHandle: null,
            hasMoved: false,
            selectedIndices: [],
            isLayerSelected(idx) {
                if (this.selectedIndices && this.selectedIndices.includes(idx)) return true;
                if (this.$wire.selectedLayerIndices && this.$wire.selectedLayerIndices.includes(idx)) return true;
                return false;
            },
            selectLayer(idx, e) {
                const shiftKey = e ? e.shiftKey : false;
                if (idx === null || idx === undefined) {
                    this.selectedIndices = [];
                } else if (shiftKey) {
                    const pos = this.selectedIndices.indexOf(idx);
                    if (pos > -1) {
                        this.selectedIndices.splice(pos, 1);
                    } else {
                        this.selectedIndices.push(idx);
                    }
                } else {
                    this.selectedIndices = [idx];
                    this.activeInspectorTab = 'controls';
                }
                this.$wire.selectLayer(idx, shiftKey);
            },
            isSelectingBox: false,
            boxStartX: 0,
            boxStartY: 0,
            boxRect: { left: 0, top: 0, width: 0, height: 0 },
            snapLines: { x: null, y: null },
            nudgeTimeout: null,
            nudgeLayers(dx, dy) {
                let indices = [];
                if (this.selectedIndices && this.selectedIndices.length > 0) {
                    indices = [...this.selectedIndices];
                } else if (this.$wire.selectedLayerIndices && this.$wire.selectedLayerIndices.length > 0) {
                    indices = [...this.$wire.selectedLayerIndices];
                } else if (this.$wire.selectedLayerIndex !== null && this.$wire.selectedLayerIndex !== undefined) {
                    indices = [this.$wire.selectedLayerIndex];
                }

                if (indices.length === 0) return;

                const updates = [];
                indices.forEach(idx => {
                    const el = document.querySelector('[data-layer-index="' + idx + '"]');
                    const layer = (this.$wire.layers && this.$wire.layers[idx]) ? this.$wire.layers[idx] : {};
                    let curX = (el && el.style.left !== '') ? parseInt(el.style.left) : (parseInt(layer.x) || 0);
                    let curY = (el && el.style.top !== '') ? parseInt(el.style.top) : (parseInt(layer.y) || 0);

                    let newX = Math.max(0, curX + dx);
                    let newY = Math.max(0, curY + dy);

                    if (el) {
                        el.style.left = newX + 'px';
                        el.style.top = newY + 'px';
                    }
                    if (layer) {
                        layer.x = newX;
                        layer.y = newY;
                    }

                    updates.push({ index: idx, x: newX, y: newY });
                });

                clearTimeout(this.nudgeTimeout);
                this.nudgeTimeout = setTimeout(() => {
                    if (updates.length === 1) {
                        this.$wire.updateLayerCoordinates(updates[0].index, updates[0].x, updates[0].y);
                    } else if (updates.length > 1) {
                        this.$wire.batchUpdateLayerCoordinates(updates);
                    }
                }, 150);
            },
            setZoom(val) {
                this.zoomLevel = val;
                try {
                    localStorage.setItem('canva_studio_zoom', val);
                } catch (e) {}
            },
            toggleTool(tool) {
                this.activeTool = tool;
                if (tool === 'select') {
                    this.isPanning = false;
                }
            },
            resetPan() {
                this.panOffsetX = 0;
                this.panOffsetY = 0;
            },
            onViewportMouseDown(e) {
                if (this.activeTool === 'pan' || this.isSpacePressed || e.button === 1) {
                    this.isPanning = true;
                    this.panStartX = e.clientX - this.panOffsetX;
                    this.panStartY = e.clientY - this.panOffsetY;
                    e.preventDefault();
                    return;
                }

                // Layer elements stop propagation on their own mousedown, so reaching here
                // means the click landed on empty canvas space - deselect the current layer(s)
                // and arm the drag-to-select marquee box in case this turns into a drag.
                // Goes through the Alpine selectLayer() wrapper (not $wire directly) so the
                // local selectedIndices array clears immediately too - that's what actually
                // drives the selection ring/resize-handle visibility via isLayerSelected().
                if (e.button === 0) {
                    this.selectLayer(null);

                    const canvasEl = document.getElementById('canva-studio-canvas');
                    if (canvasEl) {
                        const rect = canvasEl.getBoundingClientRect();
                        const zoomFactor = (parseFloat(this.zoomLevel) || 100) / 100;
                        this.boxStartX = (e.clientX - rect.left) / zoomFactor;
                        this.boxStartY = (e.clientY - rect.top) / zoomFactor;
                        this.boxRect = { left: this.boxStartX, top: this.boxStartY, width: 0, height: 0 };
                        this.isSelectingBox = true;
                    }
                }
            },
            onViewportMouseMove(e) {
                if (this.isPanning) {
                    this.panOffsetX = e.clientX - this.panStartX;
                    this.panOffsetY = e.clientY - this.panStartY;
                }
            },
            onViewportMouseUp(e) {
                if (this.isPanning) {
                    this.isPanning = false;
                }
            },
            init() {
                try {
                    const savedZoom = localStorage.getItem('canva_studio_zoom');
                    if (savedZoom) this.zoomLevel = parseInt(savedZoom);
                } catch(e) {}

                window.addEventListener('keydown', (e) => {
                    const activeEl = document.activeElement;
                    const isEditing = activeEl && (['INPUT', 'TEXTAREA', 'SELECT'].includes(activeEl.tagName) || activeEl.isContentEditable);

                    if (e.code === 'Space' && !isEditing) {
                        this.isSpacePressed = true;
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !isEditing) {
                        e.preventDefault();
                        this.$wire.undo();
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key === 'y' && !isEditing) {
                        e.preventDefault();
                        this.$wire.redo();
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                        e.preventDefault();
                        this.$wire.saveStudioDesign();
                    }

                    // Duplicate Layer (Ctrl+D / Cmd+D)
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd' && !isEditing) {
                        e.preventDefault();
                        let hasSelected = (this.selectedIndices && this.selectedIndices.length > 0) || 
                                          (this.$wire.selectedLayerIndices && this.$wire.selectedLayerIndices.length > 0) || 
                                          (this.$wire.selectedLayerIndex !== null);
                        if (hasSelected) {
                            this.$wire.duplicateSelected();
                        }
                    }

                    // Copy / Paste Layer (Ctrl+C / Ctrl+V or Cmd+C / Cmd+V)
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c' && !isEditing) {
                        let hasSelected = (this.selectedIndices && this.selectedIndices.length > 0) || 
                                          (this.$wire.selectedLayerIndices && this.$wire.selectedLayerIndices.length > 0) || 
                                          (this.$wire.selectedLayerIndex !== null);
                        if (hasSelected) {
                            this.copiedIndices = this.selectedIndices.length > 0 ? [...this.selectedIndices] : (this.$wire.selectedLayerIndex !== null ? [this.$wire.selectedLayerIndex] : []);
                        }
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'v' && !isEditing) {
                        if (this.copiedIndices && this.copiedIndices.length > 0) {
                            e.preventDefault();
                            this.$wire.duplicateSelected();
                        }
                    }

                    // Arrow Key Movement (Nudge 1px or Shift+Arrow 10px)
                    if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key) && !isEditing) {
                        e.preventDefault();
                        const step = e.shiftKey ? 10 : 1;
                        let dx = 0;
                        let dy = 0;
                        if (e.key === 'ArrowLeft') dx = -step;
                        if (e.key === 'ArrowRight') dx = step;
                        if (e.key === 'ArrowUp') dy = -step;
                        if (e.key === 'ArrowDown') dy = step;

                        this.nudgeLayers(dx, dy);
                    }

                    // Delete / Backspace to remove selected layer(s)
                    if ((e.key === 'Delete' || e.key === 'Backspace') && !isEditing) {
                        let hasSelected = (this.selectedIndices && this.selectedIndices.length > 0) || 
                                          (this.$wire.selectedLayerIndices && this.$wire.selectedLayerIndices.length > 0) || 
                                          (this.$wire.selectedLayerIndex !== null);
                        if (hasSelected) {
                            e.preventDefault();
                            this.selectLayer(null);
                            this.$wire.removeLayer(-1);
                        }
                    }
                });
                window.addEventListener('keyup', (e) => {
                    if (e.code === 'Space') {
                        this.isSpacePressed = false;
                        this.isPanning = false;
                    }
                });
                window.addEventListener('mousemove', (e) => {
                    if (this.isDragging) {
                        const zoomFactor = (parseFloat(this.zoomLevel) || 100) / 100;
                        let dx = (e.clientX - this.startX) / zoomFactor;
                        let dy = (e.clientY - this.startY) / zoomFactor;

                        if (Math.abs(dx) > 2 || Math.abs(dy) > 2) {
                            this.hasMoved = true;
                        }

                        if (this.hasMoved) {
                            let targetX = Math.round(this.origX + dx);
                            let targetY = Math.round(this.origY + dy);

                            if (this.$wire.enableSnapping) {
                                const snapThreshold = 8;
                                let snappedX = null;
                                let snappedY = null;

                                const layerW = parseFloat(this.curW) || 150;
                                const layerH = parseFloat(this.curH) || 30;

                                const layerLeft = targetX;
                                const layerCenterX = targetX + layerW / 2;
                                const layerRight = targetX + layerW;

                                const layerTop = targetY;
                                const layerCenterY = targetY + layerH / 2;
                                const layerBottom = targetY + layerH;

                                // X-axis snap targets: Canvas center, margins & other layers
                                const snapTargetsX = [
                                    { target: 0, align: 'left', snapLine: 0 },
                                    { target: 20, align: 'left', snapLine: 20 },
                                    { target: canvasW / 2, align: 'center', snapLine: canvasW / 2 },
                                    { target: canvasW - 20, align: 'right', snapLine: canvasW - 20 },
                                    { target: canvasW, align: 'right', snapLine: canvasW },
                                ];

                                if (this.otherLayerBounds && this.otherLayerBounds.length) {
                                    for (const b of this.otherLayerBounds) {
                                        snapTargetsX.push({ target: b.left, align: 'left', snapLine: b.left });
                                        snapTargetsX.push({ target: b.centerX, align: 'center', snapLine: b.centerX });
                                        snapTargetsX.push({ target: b.right, align: 'right', snapLine: b.right });
                                        snapTargetsX.push({ target: b.right, align: 'left', snapLine: b.right });
                                        snapTargetsX.push({ target: b.left, align: 'right', snapLine: b.left });
                                    }
                                }

                                // Y-axis snap targets: Canvas center, margins & other layers
                                const snapTargetsY = [
                                    { target: 0, align: 'top', snapLine: 0 },
                                    { target: 20, align: 'top', snapLine: 20 },
                                    { target: canvasH / 2, align: 'center', snapLine: canvasH / 2 },
                                    { target: canvasH - 20, align: 'bottom', snapLine: canvasH - 20 },
                                    { target: canvasH, align: 'bottom', snapLine: canvasH },
                                ];

                                if (this.otherLayerBounds && this.otherLayerBounds.length) {
                                    for (const b of this.otherLayerBounds) {
                                        snapTargetsY.push({ target: b.top, align: 'top', snapLine: b.top });
                                        snapTargetsY.push({ target: b.centerY, align: 'center', snapLine: b.centerY });
                                        snapTargetsY.push({ target: b.bottom, align: 'bottom', snapLine: b.bottom });
                                        snapTargetsY.push({ target: b.bottom, align: 'top', snapLine: b.bottom });
                                        snapTargetsY.push({ target: b.top, align: 'bottom', snapLine: b.top });
                                    }
                                }

                                // Check horizontal snap
                                let minDiffX = snapThreshold;
                                for (const sp of snapTargetsX) {
                                    let diff = 0;
                                    let candidateX = targetX;
                                    if (sp.align === 'left') {
                                        diff = Math.abs(layerLeft - sp.target);
                                        candidateX = sp.target;
                                    } else if (sp.align === 'center') {
                                        diff = Math.abs(layerCenterX - sp.target);
                                        candidateX = Math.round(sp.target - layerW / 2);
                                    } else if (sp.align === 'right') {
                                        diff = Math.abs(layerRight - sp.target);
                                        candidateX = Math.round(sp.target - layerW);
                                    }

                                    if (diff < minDiffX) {
                                        minDiffX = diff;
                                        targetX = candidateX;
                                        snappedX = sp.snapLine;
                                    }
                                }

                                // Check vertical snap
                                let minDiffY = snapThreshold;
                                for (const sp of snapTargetsY) {
                                    let diff = 0;
                                    let candidateY = targetY;
                                    if (sp.align === 'top') {
                                        diff = Math.abs(layerTop - sp.target);
                                        candidateY = sp.target;
                                    } else if (sp.align === 'center') {
                                        diff = Math.abs(layerCenterY - sp.target);
                                        candidateY = Math.round(sp.target - layerH / 2);
                                    } else if (sp.align === 'bottom') {
                                        diff = Math.abs(layerBottom - sp.target);
                                        candidateY = Math.round(sp.target - layerH);
                                    }

                                    if (diff < minDiffY) {
                                        minDiffY = diff;
                                        targetY = candidateY;
                                        snappedY = sp.snapLine;
                                    }
                                }

                                this.snapLines = { x: snappedX, y: snappedY };
                            } else {
                                this.snapLines = { x: null, y: null };
                            }

                            this.curX = targetX;
                            this.curY = targetY;

                            const layerEl = document.querySelector('[data-layer-index="' + this.dragLayerIndex + '"]');
                            if (layerEl) {
                                layerEl.style.left = targetX + 'px';
                                layerEl.style.top = targetY + 'px';
                            }
                        }
                    } else if (this.isResizing) {
                        const zoomFactor = (parseFloat(this.zoomLevel) || 100) / 100;
                        let dx = (e.clientX - this.startX) / zoomFactor;
                        let dy = (e.clientY - this.startY) / zoomFactor;

                        if (Math.abs(dx) > 2 || Math.abs(dy) > 2) {
                            this.hasMoved = true;
                        }

                        if (this.hasMoved) {
                            let newW = this.origW;
                            let newH = this.origH;
                            let newX = this.origX;
                            let newY = this.origY;

                            const handle = this.resizeHandle;
                            if (handle.includes('e')) newW = Math.max(10, this.origW + dx);
                            if (handle.includes('s')) newH = Math.max(10, this.origH + dy);
                            if (handle.includes('w')) {
                                newW = Math.max(10, this.origW - dx);
                                newX = this.origX + (this.origW - newW);
                            }
                            if (handle.includes('n')) {
                                newH = Math.max(10, this.origH - dy);
                                newY = this.origY + (this.origH - newH);
                            }

                            newW = Math.round(newW);
                            newH = Math.round(newH);
                            newX = Math.round(newX);
                            newY = Math.round(newY);

                            this.curW = newW;
                            this.curH = newH;
                            this.curX = newX;
                            this.curY = newY;

                            const layerEl = document.querySelector('[data-layer-index="' + this.dragLayerIndex + '"]');
                            if (layerEl) {
                                layerEl.style.left = newX + 'px';
                                layerEl.style.top = newY + 'px';
                                const contentEl = layerEl.querySelector('[data-layer-content]');
                                if (contentEl) {
                                    contentEl.style.width = newW + 'px';
                                    contentEl.style.height = newH + 'px';
                                }
                            }
                        }
                    } else if (this.isSelectingBox) {
                        const canvasEl = document.getElementById('canva-studio-canvas');
                        if (canvasEl) {
                            const rect = canvasEl.getBoundingClientRect();
                            const zoomFactor = (parseFloat(this.zoomLevel) || 100) / 100;
                            const currX = (e.clientX - rect.left) / zoomFactor;
                            const currY = (e.clientY - rect.top) / zoomFactor;

                            const left = Math.min(this.boxStartX, currX);
                            const top = Math.min(this.boxStartY, currY);
                            const width = Math.abs(currX - this.boxStartX);
                            const height = Math.abs(currY - this.boxStartY);

                            this.boxRect = { left, top, width, height };
                        }
                    }
                });
                window.addEventListener('mouseup', (e) => {
                    if (this.isDragging) {
                        const idx = this.dragLayerIndex;
                        const moved = this.hasMoved;
                        this.isDragging = false;
                        this.snapLines = { x: null, y: null };

                        if (moved) {
                            this.$wire.updateLayerCoordinates(idx, this.curX, this.curY);
                        } else {
                            this.$wire.selectLayer(idx, e ? e.shiftKey : false);
                        }
                    }
                    if (this.isResizing) {
                        const idx = this.dragLayerIndex;
                        const moved = this.hasMoved;
                        this.isResizing = false;
                        this.snapLines = { x: null, y: null };

                        if (moved) {
                            this.$wire.updateLayerDimensions(idx, this.curW, this.curH, this.curFontSize, this.curX, this.curY);
                        } else {
                            this.$wire.selectLayer(idx, e ? e.shiftKey : false);
                        }
                    }
                    if (this.isSelectingBox) {
                        this.isSelectingBox = false;
                        const selRect = this.boxRect;
                        if (selRect.width > 5 && selRect.height > 5) {
                            const selectedIndices = [];
                            const layerElements = document.querySelectorAll('[data-layer-box]');
                            layerElements.forEach(el => {
                                const idx = parseInt(el.getAttribute('data-layer-index'));
                                const x = parseInt(el.style.left) || 0;
                                const y = parseInt(el.style.top) || 0;
                                const w = el.offsetWidth || 50;
                                const h = el.offsetHeight || 20;

                                if (x < selRect.left + selRect.width &&
                                    x + w > selRect.left &&
                                    y < selRect.top + selRect.height &&
                                    y + h > selRect.top) {
                                    selectedIndices.push(idx);
                                }
                            });
                            if (selectedIndices.length > 0) {
                                this.selectedIndices = selectedIndices;
                                this.$wire.selectLayerBatch(selectedIndices);
                            }
                        }
                    }
                });
            },
            startDrag(idx, e) {
                if (this.activeTool === 'pan' || this.isSpacePressed) return;
                this.selectLayer(idx, e);
                const el = document.querySelector('[data-layer-index="' + idx + '"]');
                if (!el) return;

                this.isDragging = true;
                this.dragLayerIndex = idx;
                this.startX = e.clientX;
                this.startY = e.clientY;
                this.hasMoved = false;

                const layer = (this.$wire.layers && this.$wire.layers[idx]) ? this.$wire.layers[idx] : {};
                this.origX = (el.style.left !== '') ? parseInt(el.style.left) : (parseInt(layer.x) || 0);
                this.origY = (el.style.top !== '') ? parseInt(el.style.top) : (parseInt(layer.y) || 0);
                this.curX = this.origX;
                this.curY = this.origY;

                const parsedW = parseFloat(layer.width);
                const parsedH = parseFloat(layer.height);
                this.curW = (!isNaN(parsedW) && parsedW > 0) ? parsedW : (el.offsetWidth || 150);
                this.curH = (!isNaN(parsedH) && parsedH > 0) ? parsedH : (el.offsetHeight || 30);
                this.curFontSize = layer.font_size || 14;

                // Cache other layer bounds for smart alignment snapping
                this.otherLayerBounds = [];
                const allBoxes = document.querySelectorAll('[data-layer-box]');
                allBoxes.forEach(box => {
                    const boxIdx = parseInt(box.getAttribute('data-layer-index'));
                    if (boxIdx !== idx) {
                        const bx = parseInt(box.style.left) || 0;
                        const by = parseInt(box.style.top) || 0;
                        const bw = box.offsetWidth || 150;
                        const bh = box.offsetHeight || 30;
                        this.otherLayerBounds.push({
                            left: bx,
                            centerX: bx + bw / 2,
                            right: bx + bw,
                            top: by,
                            centerY: by + bh / 2,
                            bottom: by + bh,
                        });
                    }
                });
            },
            startResize(idx, handle, e) {
                if (this.activeTool === 'pan' || this.isSpacePressed) return;
                this.selectLayer(idx, e);
                const el = document.querySelector('[data-layer-index="' + idx + '"]');
                if (!el) return;

                this.isResizing = true;
                this.dragLayerIndex = idx;
                this.resizeHandle = handle;
                this.startX = e.clientX;
                this.startY = e.clientY;
                this.hasMoved = false;

                const layer = (this.$wire.layers && this.$wire.layers[idx]) ? this.$wire.layers[idx] : {};
                this.origX = (el.style.left !== '') ? parseInt(el.style.left) : (parseInt(layer.x) || 0);
                this.origY = (el.style.top !== '') ? parseInt(el.style.top) : (parseInt(layer.y) || 0);
                
                const parsedW = parseFloat(layer.width);
                const parsedH = parseFloat(layer.height);
                this.origW = (!isNaN(parsedW) && parsedW > 0) ? parsedW : (el.offsetWidth || 150);
                this.origH = (!isNaN(parsedH) && parsedH > 0) ? parsedH : (el.offsetHeight || 30);

                this.curX = this.origX;
                this.curY = this.origY;
                this.curW = this.origW;
                this.curH = this.origH;
                this.curFontSize = layer.font_size || 14;
            },
            alignSelectedToPage(position) {
                if (!this.$wire.selectedLayerIndices || this.$wire.selectedLayerIndices.length === 0) return;
                const updates = [];

                this.$wire.selectedLayerIndices.forEach(idx => {
                    const layer = this.$wire.layers[idx];
                    if (!layer) return;
                    const el = document.querySelector('[data-layer-index="' + idx + '"]');
                    const w = el ? el.offsetWidth : (layer.width || 100);
                    const h = el ? el.offsetHeight : (layer.height || 30);

                    let newX = layer.x;
                    let newY = layer.y;

                    switch(position) {
                        case 'top': newY = 0; break;
                        case 'bottom': newY = canvasH - h; break;
                        case 'middle': newY = Math.round((canvasH - h) / 2); break;
                        case 'left': newX = 0; break;
                        case 'right': newX = canvasW - w; break;
                        case 'center': newX = Math.round((canvasW - w) / 2); break;
                    }

                    if (el) {
                        el.style.left = newX + 'px';
                        el.style.top = newY + 'px';
                    }
                    updates.push({ idx: idx, x: newX, y: newY });
                });

                this.$wire.updateMultipleLayersCoordinates(updates);
            },
            alignSelectedToSelection(position) {
                if (!this.$wire.selectedLayerIndices || this.$wire.selectedLayerIndices.length < 2) return;
                const items = [];
                let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

                this.$wire.selectedLayerIndices.forEach(idx => {
                    const layer = this.$wire.layers[idx];
                    if (!layer) return;
                    const el = document.querySelector('[data-layer-index="' + idx + '"]');
                    const x = layer.x || 0;
                    const y = layer.y || 0;
                    const w = el ? el.offsetWidth : (layer.width || 100);
                    const h = el ? el.offsetHeight : (layer.height || 30);

                    minX = Math.min(minX, x);
                    minY = Math.min(minY, y);
                    maxX = Math.max(maxX, x + w);
                    maxY = Math.max(maxY, y + h);

                    items.push({ idx: idx, el: el, x: x, y: y, w: w, h: h });
                });

                const groupW = maxX - minX;
                const groupH = maxY - minY;
                const updates = [];

                items.forEach(item => {
                    let finalX = item.x;
                    let finalY = item.y;

                    switch(position) {
                        case 'left': finalX = minX; break;
                        case 'center': finalX = minX + (groupW - item.w) / 2; break;
                        case 'right': finalX = maxX - item.w; break;
                        case 'top': finalY = minY; break;
                        case 'middle': finalY = minY + (groupH - item.h) / 2; break;
                        case 'bottom': finalY = maxY - item.h; break;
                    }

                    finalX = Math.round(finalX);
                    finalY = Math.round(finalY);

                    if (item.el) {
                        item.el.style.left = finalX + 'px';
                        item.el.style.top = finalY + 'px';
                    }
                    updates.push({ idx: item.idx, x: finalX, y: finalY });
                });

                this.$wire.updateMultipleLayersCoordinates(updates);
            },
            distributeSelected(direction) {
                let indices = [];
                if (this.$wire.selectedLayerIndices && this.$wire.selectedLayerIndices.length >= 2) {
                    indices = [...this.$wire.selectedLayerIndices];
                } else if (this.selectedIndices && this.selectedIndices.length >= 2) {
                    indices = [...this.selectedIndices];
                }

                if (indices.length < 2) return;

                const items = [];
                indices.forEach(idx => {
                    const layer = (this.$wire.layers && this.$wire.layers[idx]) ? this.$wire.layers[idx] : {};
                    const el = document.querySelector('[data-layer-index="' + idx + '"]');
                    const x = (el && el.style.left !== '') ? parseInt(el.style.left) : (parseInt(layer.x) || 0);
                    const y = (el && el.style.top !== '') ? parseInt(el.style.top) : (parseInt(layer.y) || 0);
                    const parsedW = parseFloat(layer.width);
                    const parsedH = parseFloat(layer.height);
                    const w = (el && el.offsetWidth) ? el.offsetWidth : ((!isNaN(parsedW) && parsedW > 0) ? parsedW : 100);
                    const h = (el && el.offsetHeight) ? el.offsetHeight : ((!isNaN(parsedH) && parsedH > 0) ? parsedH : 30);
                    items.push({ idx, el, layer, x, y, w, h });
                });

                const updates = [];

                if (direction === 'vertically') {
                    // Sort items top to bottom by current Y
                    items.sort((a, b) => a.y - b.y);

                    const firstItem = items[0];
                    const lastItem = items[items.length - 1];

                    const totalSpan = (lastItem.y + lastItem.h) - firstItem.y;
                    const totalItemsHeight = items.reduce((sum, it) => sum + it.h, 0);
                    const totalGaps = items.length - 1;
                    const gap = totalGaps > 0 ? (totalSpan - totalItemsHeight) / totalGaps : 0;

                    let currentY = firstItem.y;
                    items.forEach((item, i) => {
                        let finalY = Math.round(currentY);
                        if (item.el) {
                            item.el.style.top = finalY + 'px';
                        }
                        if (item.layer) {
                            item.layer.y = finalY;
                        }
                        updates.push({ idx: item.idx, x: item.x, y: finalY });
                        currentY += item.h + gap;
                    });
                } else if (direction === 'horizontally') {
                    // Sort items left to right by current X
                    items.sort((a, b) => a.x - b.x);

                    const firstItem = items[0];
                    const lastItem = items[items.length - 1];

                    const totalSpan = (lastItem.x + lastItem.w) - firstItem.x;
                    const totalItemsWidth = items.reduce((sum, it) => sum + it.w, 0);
                    const totalGaps = items.length - 1;
                    const gap = totalGaps > 0 ? (totalSpan - totalItemsWidth) / totalGaps : 0;

                    let currentX = firstItem.x;
                    items.forEach((item, i) => {
                        let finalX = Math.round(currentX);
                        if (item.el) {
                            item.el.style.left = finalX + 'px';
                        }
                        if (item.layer) {
                            item.layer.x = finalX;
                        }
                        updates.push({ idx: item.idx, x: finalX, y: item.y });
                        currentX += item.w + gap;
                    });
                }

                if (updates.length > 0) {
                    this.$wire.updateMultipleLayersCoordinates(updates);
                }
            }
        };
    }
</script>
