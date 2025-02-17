<?php

declare(strict_types=1);

namespace Orchid\Screen;

use Throwable;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Orchid\Screen\Layouts\Base;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Routing\UrlRoutable;
use Orchid\Platform\Http\Controllers\Controller;

/**
 * Class Screen.
 */
abstract class Screen extends Controller
{
    use Commander;

    /**
     * Display header name.
     *
     * @var string
     */
    public $name;

    /**
     * Display header description.
     *
     * @var string
     */
    public $description;

    /**
     * @var Request
     */
    public $request;

    /**
     * Permission.
     *
     * @var string|array
     */
    public $permission;

    /**
     * @var Repository
     */
    private $source;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Screen constructor.
     *
     * @param Request|null $request
     */
    public function __construct(Request $request = null)
    {
        $this->request = $request ?? request();
    }

    /**
     * Button commands.
     *
     * @return Action[]
     */
    abstract public function commandBar(): array;

    /**
     * Views.
     *
     * @return Layout[]
     */
    abstract public function layout(): array;

    /**
     *@throws Throwable
     *
     * @return View
     */
    public function build()
    {
        $layout = Layout::blank([
            $this->layout(),
        ]);

        return $layout->build($this->source);
    }

    /**
     * @param mixed $method
     * @param mixed $slugLayouts
     *
     * @throws Throwable
     *
     * @return View
     */
    protected function asyncBuild($method, $slugLayouts)
    {
        $this->arguments = $this->request->json()->all();

        $this->reflectionParams($method);
        $query = call_user_func_array([$this, $method], $this->arguments);
        $source = new Repository($query);

        foreach ($this->layout() as $layout) {

            /** @var Base|string $layout */
            $layout = is_object($layout) ? $layout : app()->make($layout);

            if ($layout->getSlug() === $slugLayouts) {
                return $layout->currentAsync()->build($source);
            }
        }

        abort(404, "Async method: {$method} not found");
    }

    /**
     * @throws Throwable
     *
     * @return Factory|\Illuminate\View\View
     */
    public function view()
    {
        $this->reflectionParams('query');
        $query = call_user_func_array([$this, 'query'], $this->arguments);
        $this->source = new Repository($query);
        $commandBar = $this->buildCommandBar($this->source);

        return view('platform::layouts.base', [
            'screen'     => $this,
            'commandBar' => $commandBar,
        ]);
    }

    /**
     * @param mixed ...$parameters
     *
     * @throws ReflectionException
     * @throws Throwable
     *
     * @return Factory|View|\Illuminate\View\View|mixed
     */
    public function handle(...$parameters)
    {
        abort_if(! $this->checkAccess(), 403);

        if ($this->request->method() === 'GET' || (! count($parameters))) {
            $this->arguments = $parameters;

            return $this->view();
        }

        $method = array_pop($parameters);
        $this->arguments = $parameters;

        if (Str::startsWith($method, 'async')) {
            return $this->asyncBuild($method, array_pop($this->arguments));
        }

        $this->reflectionParams($method);

        return call_user_func_array([$this, $method], $this->arguments);
    }

    /**
     * @param string $method
     *
     * @throws ReflectionException
     */
    private function reflectionParams(string $method)
    {
        $class = new ReflectionClass($this);

        if (! is_string($method)) {
            return;
        }

        if (! $class->hasMethod($method)) {
            return;
        }

        $parameters = $class->getMethod($method)->getParameters();

        $this->arguments = collect($parameters)
            ->map(function ($parameter, $key) {
                return $this->bind($key, $parameter);
            })->all();
    }

    /**
     * @param int|string               $key
     * @param ReflectionParameter|null $parameter
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return mixed
     */
    private function bind($key, $parameter)
    {
        if (is_null($parameter->getClass())) {
            return $this->arguments[$key] ?? null;
        }

        $class = $parameter->getClass()->name;

        $object = Arr::first($this->arguments, function ($value) use ($class) {
            return is_subclass_of($value, $class) || is_a($value, $class);
        });

        if (! is_null($object)) {
            return $object;
        }

        $object = app()->make($class);

        if (is_a($object, UrlRoutable::class) && isset($this->arguments[$key])) {
            $object = $object->resolveRouteBinding($this->arguments[$key]);
        }

        return $object;
    }

    /**
     * @return bool
     */
    private function checkAccess(): bool
    {
        if (empty($this->permission)) {
            return true;
        }

        return collect($this->permission)
            ->map(function ($item) {
                return Auth::user()->hasAccess($item);
            })->contains(true);
    }

    /**
     * @return string
     */
    public function formValidateMessage(): string
    {
        return __('Please check the entered data, it may be necessary to specify in other languages.');
    }
}
