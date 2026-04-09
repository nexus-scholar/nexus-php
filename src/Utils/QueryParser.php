<?php

namespace Nexus\Utils;

use Nexus\Models\QueryField;

class QueryParser
{
    private const FIELD_PATTERN = '/^(\w+):/';

    private const PHRASE_PATTERN = '/^"([^"]*)"/';

    private const OPERATOR_PATTERN = '/^(AND|OR|NOT)\b/i';

    public function normalizeText(string $text): string
    {
        if (! $text) {
            return '';
        }

        $text = str_replace("\u{2011}", '-', $text);
        $text = str_replace(["\u{201c}", "\u{201d}"], '"', $text);
        $text = str_replace(["\u{2018}", "\u{2019}"], "'", $text);

        return $text;
    }

    /**
     * @return QueryToken[]
     */
    public function parse(string $queryText): array
    {
        $queryText = $this->normalizeText($queryText);
        $tokens = [];
        $remaining = $queryText;
        $currentField = null;

        while ($remaining !== '' && $remaining !== false) {
            $remaining = ltrim($remaining);
            if ($remaining === '') {
                break;
            }

            if ($remaining[0] === '(' || $remaining[0] === ')') {
                $tokens[] = new QueryToken($remaining[0], isOperator: true);
                $remaining = substr($remaining, 1);

                continue;
            }

            if (preg_match(self::FIELD_PATTERN, $remaining, $matches)) {
                $fieldName = strtolower($matches[1]);
                $currentField = QueryField::tryFrom($fieldName) ?? QueryField::ANY;
                $remaining = substr($remaining, strlen($matches[0]));

                continue;
            }

            if (preg_match(self::PHRASE_PATTERN, $remaining, $matches)) {
                $phrase = $matches[1];
                $tokens[] = new QueryToken($phrase, $currentField ?? QueryField::ANY, isPhrase: true);
                $remaining = substr($remaining, strlen($matches[0]));
                $currentField = null;

                continue;
            }

            if (preg_match(self::OPERATOR_PATTERN, $remaining, $matches)) {
                $operator = strtoupper($matches[1]);
                $tokens[] = new QueryToken($operator, isOperator: true);
                $remaining = substr($remaining, strlen($matches[0]));

                continue;
            }

            if (preg_match('/^([^\s()]+)/', $remaining, $matches)) {
                $word = $matches[1];
                $tokens[] = new QueryToken($word, $currentField ?? QueryField::ANY, isPhrase: false);
                $remaining = substr($remaining, strlen($matches[0]));
                $currentField = null;

                continue;
            }

            break;
        }

        return $tokens;
    }

    /**
     * @param  QueryToken[]  $tokens
     */
    public function validate(array $tokens): bool
    {
        if (empty($tokens)) {
            return false;
        }

        $parenCount = 0;
        foreach ($tokens as $token) {
            if ($token->value === '(') {
                $parenCount++;
            } elseif ($token->value === ')') {
                $parenCount--;
            }
            if ($parenCount < 0) {
                return false;
            }
        }

        return $parenCount === 0;
    }
}
