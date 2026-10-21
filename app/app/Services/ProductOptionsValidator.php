<?php

namespace App\Services;

/**
 * Validates the racikan option structure stored on a product.
 */
class ProductOptionsValidator
{
    /**
     * Check that the decoded option payload matches the expected shape.
     *
     * Each group needs a name and a non-empty list of options, and every
     * option needs a label plus a non-negative integer price.
     */
    public function isValid(mixed $options): bool
    {
        $groups = is_string($options) ? json_decode($options, true) : $options;

        if (! is_array($groups) || ! array_is_list($groups)) {
            return false;
        }

        foreach ($groups as $group) {
            if (! is_array($group)
                || ! is_string($group['name'] ?? null)
                || trim($group['name']) === ''
                || ! is_array($group['options'] ?? null)
                || ! array_is_list($group['options'])
                || $group['options'] === []) {
                return false;
            }

            foreach ($group['options'] as $option) {
                if (! is_array($option)
                    || ! is_string($option['label'] ?? null)
                    || trim($option['label']) === ''
                    || filter_var($option['price'] ?? null, FILTER_VALIDATE_INT) === false
                    || (int) $option['price'] < 0) {
                    return false;
                }
            }
        }

        return true;
    }
}
