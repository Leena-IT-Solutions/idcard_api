<?php

namespace App\Services;

class ImpositionLayoutService
{
    public function calculateLayout(array $params, float $cardWidthMm = 90.0, float $cardHeightMm = 57.0): array
    {
        $pageSize = $params['page_size'] ?? 'A4';
        $bleedMm = (float) ($params['bleed_mm'] ?? 0.0);
        $marginMm = (float) ($params['margin_mm'] ?? 0.0);
        $gutterMm = (float) ($params['gutter_mm'] ?? 4.0);
        $trimMarkLen = (float) ($params['trim_mark_len'] ?? 4.0);

        [$pageWidthMm, $pageHeightMm] = match (strtoupper($pageSize)) {
            '297X210', 'A4_LANDSCAPE' => [297.0, 210.0],
            'LETTER' => [215.9, 279.4],
            'LETTER_LANDSCAPE' => [279.4, 215.9],
            'A3' => [297.0, 420.0],
            'A3_LANDSCAPE' => [420.0, 297.0],
            'CUSTOM' => [
                (float) ($params['custom_width_mm'] ?? 297.0),
                (float) ($params['custom_height_mm'] ?? 210.0),
            ],
            default => [210.0, 297.0], // A4 Portrait
        };

        $cardOuterWidth = $cardWidthMm + (2 * $bleedMm);
        $cardOuterHeight = $cardHeightMm + (2 * $bleedMm);

        $cols = (int) floor(($pageWidthMm - $gutterMm) / ($cardOuterWidth + $gutterMm));
        $rows = (int) floor(($pageHeightMm - $gutterMm) / ($cardOuterHeight + $gutterMm));
        $cardsPerPage = max(1, $cols * $rows);

        // Center grid on page
        $totalGridWidth = ($cols * $cardOuterWidth) + (($cols - 1) * $gutterMm);
        $totalGridHeight = ($rows * $cardOuterHeight) + (($rows - 1) * $gutterMm);

        $startLeftMm = max(0, ($pageWidthMm - $totalGridWidth) / 2);
        $startTopMm = max(0, ($pageHeightMm - $totalGridHeight) / 2);

        return [
            'page_width_mm' => $pageWidthMm,
            'page_height_mm' => $pageHeightMm,
            'bleed_mm' => $bleedMm,
            'margin_mm' => $marginMm,
            'gutter_mm' => $gutterMm,
            'trim_mark_len' => $trimMarkLen,
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
