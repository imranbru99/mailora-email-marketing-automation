<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class SegmentEngine
{
    /**
     * @param  array<string,mixed>  $conditions
     * @return Builder<Contact>
     */
    public function query(array $conditions): Builder
    {
        $query = Contact::query();
        $this->apply($query, $conditions);

        return $query;
    }

    /**
     * @param  Builder<Contact>  $query
     * @param  array<string,mixed>  $group
     */
    private function apply(Builder $query, array $group, int $depth = 0): void
    {
        if ($depth > 5 || count($group['rules'] ?? []) > 30) {
            throw ValidationException::withMessages(['conditions' => 'Segment is too complex.']);
        }
        $mode = ($group['match'] ?? 'all') === 'any' ? 'orWhere' : 'where';
        $query->where(function (Builder $nested) use ($group, $mode, $depth) {
            foreach ($group['rules'] ?? [] as $rule) {
                if (isset($rule['rules'])) {
                    $nested->$mode(fn (Builder $q) => $this->apply($q, $rule, $depth + 1));

                    continue;
                }
                $field = $rule['field'] ?? '';
                $operator = $rule['operator'] ?? '=';
                $value = $rule['value'] ?? '';
                if (! in_array($field, ['email', 'first_name', 'last_name', 'company', 'status', 'locale', 'source', 'score', 'created_at'], true) || ! in_array($operator, ['=', '!=', '>', '>=', '<', '<=', 'contains'], true)) {
                    throw ValidationException::withMessages(['conditions' => 'Unsupported segment field or operator.']);
                }
                $nested->$mode($field, $operator === 'contains' ? 'like' : $operator, $operator === 'contains' ? '%'.addcslashes($value, '%_\\').'%' : $value);
            }
        });
    }
}
