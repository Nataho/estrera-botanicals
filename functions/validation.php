<?php

function validateEmailFormat(string $value): ?string {
	return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Please enter a valid email address.';
}

function validateIntRange(string $value, string $label, int $min, int $max): ?string
{
    $ok = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max],
    ]);
    return $ok !== false ? null : "$label must be a whole number between $min and $max.";
}

?>