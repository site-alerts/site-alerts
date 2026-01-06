<?php

namespace SiteAlerts\Abstracts;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AbstractFactory
 *
 * Base class for model factories providing fake data generation.
 *
 * @package SiteAlerts\Abstracts
 * @version 1.0.0
 */
abstract class AbstractFactory
{
    /**
     * The model class this factory creates.
     *
     * @var string
     */
    protected string $model = '';

    /**
     * Current pattern context.
     *
     * @var string
     */
    protected string $pattern = 'realistic';

    /**
     * Set the pattern context.
     *
     * @param string $pattern Pattern name ('realistic' or 'alerts').
     * @return self
     */
    public function setPattern(string $pattern): self
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Get the current pattern.
     *
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Define default attributes for the model.
     *
     * @return array
     */
    abstract protected function definition(): array;

    /**
     * Make a model instance without saving.
     *
     * @param array $attributes Override attributes.
     * @return object
     */
    public function make(array $attributes = []): object
    {
        $modelClass = $this->model;
        $data       = array_merge($this->definition(), $attributes);

        return $modelClass::make($data);
    }

    /**
     * Create and save a model instance.
     *
     * @param array $attributes Override attributes.
     * @return object|null
     */
    public function create(array $attributes = []): ?object
    {
        $modelClass = $this->model;
        $data       = array_merge($this->definition(), $attributes);

        return $modelClass::create($data);
    }

    /**
     * Create multiple model instances.
     *
     * @param int $count Number of instances.
     * @param array $attributes Override attributes.
     * @return array
     */
    public function createMany(int $count, array $attributes = []): array
    {
        $models = [];

        for ($i = 0; $i < $count; $i++) {
            $model = $this->create($attributes);

            if ($model !== null) {
                $models[] = $model;
            }
        }

        return $models;
    }

    /**
     * Generate a random integer within range.
     *
     * @param int $min Minimum value.
     * @param int $max Maximum value.
     * @return int
     */
    protected function randomInt(int $min, int $max): int
    {
        return wp_rand($min, $max);
    }

    /**
     * Generate a random float within range.
     *
     * @param float $min Minimum value.
     * @param float $max Maximum value.
     * @param int $decimals Decimal places.
     * @return float
     */
    protected function randomFloat(float $min, float $max, int $decimals = 2): float
    {
        $scale = 10 ** $decimals;
        $value = wp_rand((int)($min * $scale), (int)($max * $scale)) / $scale;

        return round($value, $decimals);
    }

    /**
     * Pick a random element from an array.
     *
     * @param array $items Items to choose from.
     * @return mixed
     */
    protected function randomElement(array $items)
    {
        if (empty($items)) {
            return null;
        }

        $index = wp_rand(0, count($items) - 1);

        return $items[$index];
    }

    /**
     * Pick multiple random elements from an array.
     *
     * @param array $items Items to choose from.
     * @param int $count Number of elements to pick.
     * @return array
     */
    protected function randomElements(array $items, int $count): array
    {
        if (empty($items) || $count <= 0) {
            return [];
        }

        $shuffled = $items;
        shuffle($shuffled);

        return array_slice($shuffled, 0, min($count, count($items)));
    }

    /**
     * Generate a random date within range.
     *
     * @param string $start Start date (Y-m-d).
     * @param string $end End date (Y-m-d).
     * @return string Date in Y-m-d format.
     */
    protected function randomDate(string $start, string $end): string
    {
        $startTs = strtotime($start);
        $endTs   = strtotime($end);

        $randomTs = wp_rand($startTs, $endTs);

        return gmdate('Y-m-d', $randomTs);
    }

    /**
     * Generate a date range array.
     *
     * @param int $days Number of days.
     * @param string $endDate End date (Y-m-d). Defaults to today.
     * @return array Array of dates in Y-m-d format.
     */
    protected function dateRange(int $days, string $endDate = ''): array
    {
        if (empty($endDate)) {
            $endDate = (string)current_time('Y-m-d');
        }

        $dates = [];
        $endTs = strtotime($endDate);

        for ($i = $days - 1; $i >= 0; $i--) {
            $dates[] = gmdate('Y-m-d', strtotime("-{$i} days", $endTs));
        }

        return $dates;
    }

    /**
     * Check if a date falls on a weekend.
     *
     * @param string $date Date in Y-m-d format.
     * @return bool
     */
    protected function isWeekend(string $date): bool
    {
        $dayOfWeek = (int)gmdate('N', strtotime($date));

        return $dayOfWeek >= 6;
    }
}
