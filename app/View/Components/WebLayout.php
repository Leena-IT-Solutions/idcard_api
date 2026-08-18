<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class WebLayout extends Component
{
    public ?string $title;
    public ?string $description;

    public function __construct(?string $title = null, ?string $description = null)
    {
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.web');
    }
}
