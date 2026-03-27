<?php

namespace Nexus\Utils;

use Nexus\Models\QueryField;

class QueryToken
{
    public function __construct(
        public string $value,
        public QueryField $field = QueryField::ANY,
        public bool $isPhrase = false,
        public bool $isOperator = false
    ) {}

    public function __toString(): string
    {
        return sprintf(
            'QueryToken(%s, field=%s, phrase=%s, operator=%s)',
            $this->value,
            $this->field->value,
            $this->isPhrase ? 'true' : 'false',
            $this->isOperator ? 'true' : 'false'
        );
    }
}
