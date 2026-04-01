<?php

namespace App\Traits;

use InvalidArgumentException;

/**
 * Provides state-machine behaviour for any Eloquent model that has a `status` column.
 *
 * Models using this trait MUST define:
 *   - A static `statusTransitions()` method returning an array mapping
 *     each status to the statuses it may transition to.
 *   - A static `statusOptions()` method returning all valid statuses.
 *
 * Optional overrides:
 *   - statusField(): string — override if the column is not called `status`.
 */
trait HasStateMachine
{
    /*
    |----------------------------------------------------------------------
    | Public API
    |----------------------------------------------------------------------
    */

    /**
     * Check whether a transition from the current status to $target is allowed.
     */
    public function canTransitionTo(string $target): bool
    {
        $field   = $this->statusField();
        $current = $this->{$field};

        $allowed = static::statusTransitions()[$current] ?? [];

        return in_array($target, $allowed, true);
    }

    /**
     * Transition to a new status. Throws if the transition is not allowed.
     */
    public function transitionTo(string $target): static
    {
        $field   = $this->statusField();
        $current = $this->{$field};

        if (! $this->canTransitionTo($target)) {
            throw new InvalidArgumentException(
                "Cannot transition from '{$current}' to '{$target}' on " . class_basename($this) . " #{$this->getKey()}."
            );
        }

        $this->{$field} = $target;

        return $this;
    }

    /**
     * Transition and persist in one call.
     */
    public function transitionToAndSave(string $target): static
    {
        $this->transitionTo($target);
        $this->save();

        return $this;
    }

    /*
    |----------------------------------------------------------------------
    | Guard helpers (for controllers)
    |----------------------------------------------------------------------
    */

    /**
     * Assert the model is currently in one of the given statuses.
     * Returns true if OK, or an error message string if not.
     */
    public function requireStatus(string|array $expected): true|string
    {
        $expected = (array) $expected;
        $field    = $this->statusField();
        $current  = $this->{$field};

        if (in_array($current, $expected, true)) {
            return true;
        }

        $allowed = implode(', ', $expected);
        return "This action requires status: {$allowed}. Current status is '{$current}'.";
    }

    /**
     * Assert that a transition to $target is allowed.
     * Returns true if OK, or an error message string if not.
     */
    public function requireTransition(string $target): true|string
    {
        if ($this->canTransitionTo($target)) {
            return true;
        }

        $field   = $this->statusField();
        $current = $this->{$field};
        return "Cannot transition from '{$current}' to '{$target}'.";
    }

    /*
    |----------------------------------------------------------------------
    | Convenience query helpers
    |----------------------------------------------------------------------
    */

    public function scopeInStatus($query, string|array $statuses)
    {
        $statuses = (array) $statuses;
        $field    = $this->statusField();

        return count($statuses) === 1
            ? $query->where($field, $statuses[0])
            : $query->whereIn($field, $statuses);
    }

    public function scopeNotInStatus($query, string|array $statuses)
    {
        $statuses = (array) $statuses;
        $field    = $this->statusField();

        return count($statuses) === 1
            ? $query->where($field, '!=', $statuses[0])
            : $query->whereNotIn($field, $statuses);
    }

    /*
    |----------------------------------------------------------------------
    | Configuration (override in model if needed)
    |----------------------------------------------------------------------
    */

    protected function statusField(): string
    {
        return 'status';
    }
}
