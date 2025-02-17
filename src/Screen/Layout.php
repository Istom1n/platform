<?php

declare(strict_types=1);

namespace Orchid\Screen;

use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Layouts\Tabs;
use Orchid\Screen\Layouts\View;
use Orchid\Screen\Layouts\Blank;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Layouts\Columns;
use Orchid\Screen\Layouts\Wrapper;
use Orchid\Screen\Layouts\Collapse;
use Orchid\Screen\Layouts\Accordion;
use Illuminate\Support\Traits\Macroable;

/**
 * Class Layout.
 */
class Layout
{
    use Macroable;

    /**
     * @var array
     */
    public $layouts = [];

    /**
     * @param string                                        $view
     * @param \Illuminate\Contracts\Support\Arrayable|array $data
     *
     * @return View
     */
    public static function view(string $view, $data = []): View
    {
        return new class($view, $data) extends View {
        };
    }

    /**
     * @param array $fields
     *
     * @return Rows
     */
    public static function rows(array $fields): Rows
    {
        return new class($fields) extends Rows {
            /**
             * @return array
             */
            public function fields(): array
            {
                return $this->layouts;
            }
        };
    }

    /**
     * @param array $layouts
     *
     * @return Columns
     */
    public static function columns(array $layouts): Columns
    {
        return new class($layouts) extends Columns {
        };
    }

    /**
     * @param array $layouts
     *
     * @return Tabs
     */
    public static function tabs(array $layouts): Tabs
    {
        return new class($layouts) extends Tabs {
        };
    }

    /**
     * @param string $key
     * @param array  $layouts
     *
     * @return Modal
     */
    public static function modal(string $key, array $layouts): Modal
    {
        return new class($key, $layouts) extends Modal {
        };
    }

    /**
     * @param array $layouts
     *
     * @return Blank
     */
    public static function blank(array $layouts): Blank
    {
        return new class($layouts) extends Blank {
        };
    }

    /**
     * @param array $fields
     *
     * @return Collapse
     */
    public static function collapse(array $fields): Collapse
    {
        return new class($fields) extends Collapse {
            /**
             * @return array
             */
            public function fields(): array
            {
                return $this->layouts;
            }
        };
    }

    /**
     * @param string $template
     * @param array  $layouts
     *
     * @return Wrapper
     */
    public static function wrapper(string $template, array $layouts): Wrapper
    {
        return new class($template, $layouts) extends Wrapper {
        };
    }

    /**
     * @param array $layouts
     *
     * @return Accordion
     */
    public static function accordion(array $layouts): Accordion
    {
        return new class($layouts) extends Accordion {
        };
    }
}
