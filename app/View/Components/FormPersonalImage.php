<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormPersonalImage extends Component
{
    public $src;
    /**
     * Create a new component instance.
     */
    public function __construct( $src = NULL)
    {
        $this->src = $src;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('admin.components.form-personal-image');
    }
}
