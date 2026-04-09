<?php

namespace Nexus\Utils;

use Nexus\Models\Query;
use Nexus\Models\QueryField;

class BooleanQueryTranslator
{
    private QueryParser $parser;

    public function __construct(
        private array $fieldMap,
        private array $operatorMap = [
            'AND' => 'AND',
            'OR' => 'OR',
            'NOT' => 'NOT',
        ],
        private string $specialChars = ''
    ) {
        $this->parser = new QueryParser;
    }

    public function translate(Query $query): array
    {
        $tokens = $this->parser->parse($query->text);

        if (! $this->parser->validate($tokens)) {
            return ['q' => $query->text];
        }

        $queryParts = [];
        foreach ($tokens as $token) {
            if ($token->value === '(' || $token->value === ')') {
                $queryParts[] = $token->value;
            } elseif ($token->isOperator) {
                $queryParts[] = $this->operatorMap[$token->value] ?? $token->value;
            } else {
                $field = $this->translateField($token->field);
                $term = $this->escapeSpecialChars($token->value);
                $queryParts[] = $this->formatFieldTerm($field, $term, $token->isPhrase);
            }
        }

        $translatedQuery = implode(' ', $queryParts);
        $translatedQuery = str_replace(['( ', ' )'], ['(', ')'], $translatedQuery);

        return ['q' => $translatedQuery];
    }

    private function translateField(QueryField $field): string
    {
        return $this->fieldMap[$field->value] ?? $field->value;
    }

    private function escapeSpecialChars(string $text): string
    {
        if ($this->specialChars === '') {
            return $text;
        }

        $escaped = $text;
        for ($i = 0; $i < strlen($this->specialChars); $i++) {
            $char = $this->specialChars[$i];
            $escaped = str_replace($char, "\\$char", $escaped);
        }

        return $escaped;
    }

    private function formatFieldTerm(string $field, string $term, bool $isPhrase): string
    {
        if ($field === '' || $field === 'any') {
            return $isPhrase ? "\"$term\"" : $term;
        }

        if ($isPhrase) {
            return "$field:\"$term\"";
        }

        return "$field:$term";
    }
}
