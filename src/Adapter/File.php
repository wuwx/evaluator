<?php namespace Elepunk\Evaluator\Adapter;

use Illuminate\Support\Fluent;
use Illuminate\Support\Arr as A;
use Elepunk\Evaluator\Contracts\Adapter;
use Illuminate\Contracts\Cache\Repository as Cache;
use Elepunk\Evaluator\Traits\ExpressionCheckerTrait;
use Elepunk\Evaluator\Exceptions\MissingExpressionException;

class File implements Adapter
{
    use ExpressionCheckerTrait;

    /**
     * Cache repository
     * 
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create new file adapter instance
     * 
     * @param \Illuminate\Contracts\Cache\Repository $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        $this->expressions = $this->cache->get('elepunk.evaluator', []);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function reload()
    {
        $this->cache->forever('elepunk.evaluator', $this->expressions());
    }

    /**
     * {@inheritdoc}
     */
    public function add($key, $expressions)
    {
        if ( ! is_array($expressions)) {
            $this->expressions = A::add($this->expressions, $key, $expressions);

            return $this;
        }

        $expression = new Fluent($expressions);

        if ($this->verifyExpression($expression)) {
            $this->expressions = A::add($this->expressions, $key, $expression);    
        }

        $this->reload();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $expression = A::get($this->expressions, $key, null);

        if (is_null($expression)) {
            throw new MissingExpressionException("Expression {{$key}} is not defined");
        }

        return $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        A::forget($this->expressions, $key);

        $this->reload();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expressions()
    {
        return $this->expressions;
    }
}