<?php

namespace App\Services;

class ImpositionLayoutService
{
    public function calculateLayout(array $params, float $cardWidthMm = 90.0, float $cardHeightMm = 57.0): array
    {
        $pageSize = $params['page_size'] ?? 'A4';
        $bleedMm = (float) ($params['bleed_mm'] ?? 0.0);
        $marginMm = (float) ($params['margin_mm'] ?? 0.0);
        
        $horizontalGutterMm = (float) ($params['horizontal_gutter_mm'] ?? $params['gutter_x_mm'] ?? $params['gutter_mm'] ?? 4.0);
        $verticalGutterMm = (float) ($params['vertical_gutter_mm'] ?? $params['gutter_y_mm'] ?? $params['gutter_mm'] ?? 4.0);
        $trimMarkLen = (float) ($params['trim_mark_len'] ?? 4.0);

        [$pageWidthMm, $pageHeightMm] = match (strtoupper($pageSize)) {
            '297X210', 'A4_LANDSCAPE' => [297.0, 210.0],
            'LETTER' => [215.9, 279.4],
            'LETTER_LANDSCAPE' => [279.4, 215.9],
            'A3' => [297.0, 420.0],
            'A3_LANDSCAPE' => [420.0, 297.0],
            '304.8X457.2', '12X18' => [304.8, 457.2],
            '330.2X482.6', '13X19' => [330.2, 482.6],
            'CUSTOM' => [
                (float) ($params['custom_width_mm'] ?? 297.0),
                (float) ($params['custom_height_mm'] ?? 210.0),
            ],
            default => [210.0, 297.0], // A4 Portrait
        };

        $cardOuterWidth = $cardWidthMm + (2 * $bleedMm);
        $cardOuterHeight = $cardHeightMm + (2 * $bleedMm);

        $cols = (int) floor(($pageWidthMm - $horizontalGutterMm) / ($cardOuterWidth + $horizontalGutterMm));
        $rows = (int) floor(($pageHeightMm - $verticalGutterMm) / ($cardOuterHeight + $verticalGutterMm));
        $cols = max(1, $cols);
        $rows = max(1, $rows);
        $cardsPerPage = max(1, $cols * $rows);

        // Center grid on page
        $totalGridWidth = ($cols * $cardOuterWidth) + (($cols - 1) * $horizontalGutterMm);
        $totalGridHeight = ($rows * $cardOuterHeight) + (($rows - 1) * $verticalGutterMm);

        $startLeftMm = max(0, ($pageWidthMm - $totalGridWidth) / 2);
        $startTopMm = max(0, ($pageHeightMm - $totalGridHeight) / 2);

        $showCuttingMarks = (bool) ($params['show_cutting_marks'] ?? true);
        $showCenterMarks = (bool) ($params['show_center_marks'] ?? true);

        $centerMarks = [];
        if ($showCenterMarks) {
            // 1. Top and Bottom margin registration center marks
            for ($c = 0; $c <= $cols; $c++) {
                if ($c === 0) {
                    $x = $startLeftMm;
                } elseif ($c === $cols) {
                    $x = $startLeftMm + ($cols * $cardOuterWidth) + (($cols - 1) * $horizontalGutterMm);
                } else {
                    $x = $startLeftMm + ($c * $cardOuterWidth) + (($c - 0.5) * $horizontalGutterMm);
                }

                // Top margin center mark
                $topY = max(3.0, $startTopMm / 2);
                $centerMarks[] = ['x' => round($x, 2), 'y' => round($topY, 2), 'type' => 'col_top'];

                // Bottom margin center mark
                $botY = $pageHeightMm - max(3.0, $startTopMm / 2);
                $centerMarks[] = ['x' => round($x, 2), 'y' => round($botY, 2), 'type' => 'col_bottom'];
            }

            // 2. Left and Right margin registration center marks
            for ($r = 0; $r <= $rows; $r++) {
                if ($r === 0) {
                    $y = $startTopMm;
                } elseif ($r === $rows) {
                    $y = $startTopMm + ($rows * $cardOuterHeight) + (($rows - 1) * $verticalGutterMm);
                } else {
                    $y = $startTopMm + ($r * $cardOuterHeight) + (($r - 0.5) * $verticalGutterMm);
                }

                // Left margin center mark
                $leftX = max(3.0, $startLeftMm / 2);
                $centerMarks[] = ['x' => round($leftX, 2), 'y' => round($y, 2), 'type' => 'row_left'];

                // Right margin center mark
                $rightX = $pageWidthMm - max(3.0, $startLeftMm / 2);
                $centerMarks[] = ['x' => round($rightX, 2), 'y' => round($y, 2), 'type' => 'row_right'];
            }

            // 3. Internal gutter intersection center marks (if gutters allow space)
            if ($horizontalGutterMm >= 4.0 || $verticalGutterMm >= 4.0) {
                for ($c = 1; $c < $cols; $c++) {
                    for ($r = 1; $r < $rows; $r++) {
                        $cx = $startLeftMm + ($c * $cardOuterWidth) + (($c - 0.5) * $horizontalGutterMm);
                        $cy = $startTopMm + ($r * $cardOuterHeight) + (($r - 0.5) * $verticalGutterMm);
                        $centerMarks[] = ['x' => round($cx, 2), 'y' => round($cy, 2), 'type' => 'internal_intersection'];
                    }
                }
            }
        }

        return [
            'page_width_mm' => $pageWidthMm,
            'page_height_mm' => $pageHeightMm,
            'bleed_mm' => $bleedMm,
            'margin_mm' => $marginMm,
            'horizontal_gutter_mm' => $horizontalGutterMm,
            'vertical_gutter_mm' => $verticalGutterMm,
            'gutter_mm' => $horizontalGutterMm,
            'trim_mark_len' => $trimMarkLen,
            'show_cutting_marks' => $showCuttingMarks,
            'show_center_marks' => $showCenterMarks,
            'center_marks' => $centerMarks,
            'card_width_mm' => $cardWidthMm,
            'card_height_mm' => $cardHeightMm,
            'card_outer_width' => $cardOuterWidth,
            'card_outer_height' => $cardOuterHeight,
            'cols' => $cols,
            'rows' => $rows,
            'cards_per_page' => $cardsPerPage,
            'start_left_mm' => $startLeftMm,
            'start_top_mm' => $startTopMm,
        ];
    }
}

