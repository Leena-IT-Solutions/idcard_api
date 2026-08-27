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

        $showCuttingMarks = isset($params['show_cutting_marks']) 
            ? filter_var($params['show_cutting_marks'], FILTER_VALIDATE_BOOLEAN) 
            : true;

        $showCenterMarks = isset($params['show_center_marks']) 
            ? filter_var($params['show_center_marks'], FILTER_VALIDATE_BOOLEAN) 
            : true;

        $centerMarks = [];
        if ($showCenterMarks) {
            // 1. Top and Bottom margin registration center marks (aligned at horizontal center of each card column)
            for ($c = 0; $c < $cols; $c++) {
                $colCenterX = $startLeftMm + ($c * ($cardOuterWidth + $horizontalGutterMm)) + ($cardOuterWidth / 2);

                // Top margin mark
                $topY = max(3.0, $startTopMm > 6.0 ? ($startTopMm / 2) : ($startTopMm - 2.8));
                $centerMarks[] = ['x' => round($colCenterX, 2), 'y' => round($topY, 2), 'type' => 'col_top'];

                // Bottom margin mark
                $botY = $pageHeightMm - max(3.0, $startTopMm > 6.0 ? ($startTopMm / 2) : ($startTopMm - 2.8));
                $centerMarks[] = ['x' => round($colCenterX, 2), 'y' => round($botY, 2), 'type' => 'col_bottom'];

                // Internal row gutters at column centers (if vertical gutter has room)
                if ($verticalGutterMm >= 4.0) {
                    for ($r = 1; $r < $rows; $r++) {
                        $gutterY = $startTopMm + ($r * $cardOuterHeight) + (($r - 0.5) * $verticalGutterMm);
                        $centerMarks[] = ['x' => round($colCenterX, 2), 'y' => round($gutterY, 2), 'type' => 'internal_v_gutter'];
                    }
                }
            }

            // 2. Left and Right margin registration center marks (aligned at vertical center of each card row)
            for ($r = 0; $r < $rows; $r++) {
                $rowCenterY = $startTopMm + ($r * ($cardOuterHeight + $verticalGutterMm)) + ($cardOuterHeight / 2);

                // Left margin mark
                $leftX = max(3.0, $startLeftMm > 6.0 ? ($startLeftMm / 2) : ($startLeftMm - 2.8));
                $centerMarks[] = ['x' => round($leftX, 2), 'y' => round($rowCenterY, 2), 'type' => 'row_left'];

                // Right margin mark
                $rightX = $pageWidthMm - max(3.0, $startLeftMm > 6.0 ? ($startLeftMm / 2) : ($startLeftMm - 2.8));
                $centerMarks[] = ['x' => round($rightX, 2), 'y' => round($rowCenterY, 2), 'type' => 'row_right'];

                // Internal column gutters at row centers (if horizontal gutter has room)
                if ($horizontalGutterMm >= 4.0) {
                    for ($c = 1; $c < $cols; $c++) {
                        $gutterX = $startLeftMm + ($c * $cardOuterWidth) + (($c - 0.5) * $horizontalGutterMm);
                        $centerMarks[] = ['x' => round($gutterX, 2), 'y' => round($rowCenterY, 2), 'type' => 'internal_h_gutter'];
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

