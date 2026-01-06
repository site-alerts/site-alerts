<?php

namespace SiteAlerts\Database\Seeders;

use SiteAlerts\Abstracts\AbstractSeeder;
use SiteAlerts\Abstracts\AbstractSingleton;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SeederManager
 *
 * Manages and orchestrates database seeders.
 *
 * @package SiteAlerts\Database\Seeders
 * @version 1.0.0
 */
class SeederManager extends AbstractSingleton
{
    /**
     * Registered seeder classes.
     *
     * @var array<class-string<AbstractSeeder>>
     */
    private array $seeders = [
        DailyStatsSeeder::class,
        AlertSeeder::class,
    ];

    /**
     * Current seeding options.
     *
     * @var array
     */
    private array $options = [
        'days'    => 30,
        'pattern' => 'realistic',
    ];

    /**
     * Register a seeder class.
     *
     * @param string $seederClass Seeder class name.
     * @return self
     */
    public function addSeeder(string $seederClass): self
    {
        if (!in_array($seederClass, $this->seeders, true)) {
            $this->seeders[] = $seederClass;
        }

        return $this;
    }

    /**
     * Remove a seeder class.
     *
     * @param string $seederClass Seeder class name.
     * @return self
     */
    public function removeSeeder(string $seederClass): self
    {
        $this->seeders = array_filter($this->seeders, static function ($class) use ($seederClass) {
            return $class !== $seederClass;
        });

        return $this;
    }

    /**
     * Get all registered seeders.
     *
     * @return array
     */
    public function getSeeders(): array
    {
        /**
         * Filter the registered seeders.
         *
         * @param array $seeders Array of seeder class names.
         */
        return apply_filters('site_alerts_seeders', $this->seeders);
    }

    /**
     * Set seeding options.
     *
     * @param array $options Options array.
     * @return self
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Get current options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Run all registered seeders.
     *
     * @param array $options Seeding options.
     * @return array Results keyed by seeder class.
     */
    public function runAll(array $options = []): array
    {
        $this->setOptions($options);

        $seeders = $this->getSortedSeeders();
        $results = [];

        foreach ($seeders as $seeder) {
            $class           = get_class($seeder);
            $results[$class] = $seeder->run();
        }

        return $results;
    }

    /**
     * Run a specific seeder by short name.
     *
     * @param string $shortName Short seeder name (e.g., 'DailyStats' or 'Alert').
     * @param array $options Seeding options.
     * @return int|null Number of records created, or null if seeder not found.
     */
    public function run(string $shortName, array $options = []): ?int
    {
        $this->setOptions($options);

        $seeder = $this->findSeederByShortName($shortName);

        if ($seeder === null) {
            return null;
        }

        $seeder->setPattern($this->options['pattern']);
        $seeder->setDays($this->options['days']);

        return $seeder->run();
    }

    /**
     * Clean all tables before seeding.
     *
     * @return array Results keyed by seeder class.
     */
    public function cleanAll(): array
    {
        $seeders = $this->getSortedSeeders();
        $results = [];

        // Clean in reverse order (alerts before stats)
        $seeders = array_reverse($seeders);

        foreach ($seeders as $seeder) {
            $class           = get_class($seeder);
            $results[$class] = $seeder->clean();
        }

        return $results;
    }

    /**
     * Get sorted seeders by priority.
     *
     * @return AbstractSeeder[]
     */
    private function getSortedSeeders(): array
    {
        $seederClasses = $this->getSeeders();
        $seeders       = [];

        foreach ($seederClasses as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $seeder = new $class();

            if (!$seeder instanceof AbstractSeeder) {
                continue;
            }

            $seeder->setPattern($this->options['pattern']);
            $seeder->setDays($this->options['days']);

            $seeders[] = $seeder;
        }

        usort($seeders, static function (AbstractSeeder $a, AbstractSeeder $b) {
            return $a->getPriority() - $b->getPriority();
        });

        return $seeders;
    }

    /**
     * Find a seeder by short name.
     *
     * @param string $shortName Short name (e.g., 'DailyStats').
     * @return AbstractSeeder|null
     */
    private function findSeederByShortName(string $shortName): ?AbstractSeeder
    {
        $seederClasses  = $this->getSeeders();
        $normalizedName = strtolower($shortName);

        foreach ($seederClasses as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $className      = (new \ReflectionClass($class))->getShortName();
            $classShortName = str_replace('Seeder', '', $className);

            if (strtolower($classShortName) === $normalizedName) {
                $seeder = new $class();

                if ($seeder instanceof AbstractSeeder) {
                    return $seeder;
                }
            }
        }

        return null;
    }

    /**
     * Get list of available seeder short names.
     *
     * @return array
     */
    public function getAvailableSeederNames(): array
    {
        $names = [];

        foreach ($this->getSeeders() as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $className = (new \ReflectionClass($class))->getShortName();
            $names[]   = str_replace('Seeder', '', $className);
        }

        return $names;
    }
}
