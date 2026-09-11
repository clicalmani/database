<?php
namespace Clicalmani\Database\Factory\Models\Scope;

use Clicalmani\Database\Factory\Models\Key;
use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Database\Factory\Models\ScopeInterface;
use Clicalmani\Database\QueryInterface;

class ScopeFilter implements ScopeInterface
{
    public function __construct(
        protected readonly array $exclude = [],
        protected readonly array $options = []
    )
    {}

    #[Override]
    public function apply(QueryInterface $query, ModelInterface $model): mixed
    {
        /**
         * |---------------------------------------------------
         * |              ***** Notice *****
         * |---------------------------------------------------
         * test_user_id and hash are two request parameters internally used by Tonka.
         * test_user_id holds the request user ID in test mode.
         * and hash is used for url encryption.
         */
        /** @var \Clicalmani\Database\Factory\Entity */
        $entity  = $model->getEntity();
        $hash    = \Clicalmani\Foundation\Auth\EncryptionServiceProvider::hashParameter();
        $request = request()->all();
        $attrs   = collect(array_keys($request))->filter(fn(string $attr) => !in_array($attr, array_merge($this->exclude, ['test_user_id', $hash])))
                        ->filter(fn(string $attr) => $entity->attributeExists($attr));
        $class   = $model::class;
        
        try {
            $obj = $class::where(
                $attrs->copy()->map(fn(string $attr) => "{$attr} = ?")->join(' AND '), 
                $attrs->map(fn(string $attr) => $request[$attr])->toArray()
            );
            
            if (isset($this->options['order_by'])) {
                $obj->orderBy($this->options['order_by']);
            }

            if (isset($this->options['limit'])) {
                $obj->limit($this->options['offset'] ?? 0, $this->options['limit']);
            }
            
            return $obj->get();

        } catch (\PDOException $e) {
            return collect();
        }
    }
}