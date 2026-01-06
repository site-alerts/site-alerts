<?php

namespace SiteAlerts\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Notice
 *
 * Represents a single admin notice with configuration options.
 *
 * @package SiteAlerts\Admin
 * @version 1.0.0
 */
class Notice
{
    /**
     * Notice types
     */
    public const TYPE_SUCCESS = 'success';
    public const TYPE_ERROR   = 'error';
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO    = 'info';

    /**
     * Unique notice identifier
     *
     * @var string
     */
    public string $id;

    /**
     * Notice message
     *
     * @var string
     */
    public string $message;

    /**
     * Notice type (success, error, warning, info)
     *
     * @var string
     */
    public string $type;

    /**
     * Whether the notice can be dismissed
     *
     * @var bool
     */
    public bool $dismissible;

    /**
     * Whether the notice persists across page loads
     *
     * @var bool
     */
    public bool $persistent;

    /**
     * Limit notice to specific admin screen
     *
     * @var string|null
     */
    public ?string $screen;

    /**
     * Limit notice to users with specific capability
     *
     * @var string|null
     */
    public ?string $capability;

    /**
     * Additional CSS classes
     *
     * @var array
     */
    public array $classes = [];

    /**
     * Notice constructor.
     *
     * @param string $message The notice message.
     * @param string $type The notice type.
     */
    public function __construct(string $message, string $type = self::TYPE_INFO)
    {
        $this->id          = md5($message . '|' . $type);
        $this->message     = $message;
        $this->type        = $type;
        $this->dismissible = true;
        $this->persistent  = false;
        $this->screen      = null;
        $this->capability  = null;
    }

    /**
     * Set the notice ID.
     *
     * @param string $id Unique identifier.
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set whether the notice is dismissible.
     *
     * @param bool $dismissible Whether dismissible.
     * @return self
     */
    public function setDismissible(bool $dismissible): self
    {
        $this->dismissible = $dismissible;
        return $this;
    }

    /**
     * Set whether the notice persists across page loads.
     *
     * @param bool $persistent Whether persistent.
     * @return self
     */
    public function setPersistent(bool $persistent): self
    {
        $this->persistent = $persistent;
        return $this;
    }

    /**
     * Limit notice to specific admin screen.
     *
     * @param string $screen Screen ID.
     * @return self
     */
    public function setScreen(string $screen): self
    {
        $this->screen = $screen;
        return $this;
    }

    /**
     * Limit notice to users with specific capability.
     *
     * @param string $capability Capability name.
     * @return self
     */
    public function setCapability(string $capability): self
    {
        $this->capability = $capability;
        return $this;
    }

    /**
     * Add CSS classes to the notice.
     *
     * @param array $classes CSS class names.
     * @return self
     */
    public function addClasses(array $classes): self
    {
        $this->classes = array_merge($this->classes, $classes);
        return $this;
    }

    /**
     * Check if the notice should be displayed.
     *
     * @return bool
     */
    public function shouldDisplay(): bool
    {
        // Check capability
        if ($this->capability && !current_user_can($this->capability)) {
            return false;
        }

        // Check screen
        if ($this->screen) {
            $currentScreen = get_current_screen();
            if (!$currentScreen || $currentScreen->id !== $this->screen) {
                return false;
            }
        }

        // Check if dismissed
        if ($this->dismissible && AdminNotices::isDismissed($this->id)) {
            return false;
        }

        return true;
    }

    /**
     * Render the notice HTML.
     *
     * @return string
     */
    public function render(): string
    {
        if (!$this->shouldDisplay()) {
            return '';
        }

        $classes = array_merge([
            'notice',
            'notice-' . $this->type,
            'sa-notice',
        ], $this->classes);

        if ($this->dismissible) {
            $classes[] = 'is-dismissible';
            $classes[] = 'sa-dismissible-notice';
        }

        $classStr = implode(' ', array_map('esc_attr', $classes));

        $html = sprintf(
            '<div id="%s" class="%s" data-sa-notice-id="%s">',
            esc_attr($this->id),
            $classStr,
            esc_attr($this->id)
        );

        $html .= '<p>' . wp_kses_post($this->message) . '</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Convert notice to array for storage.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'message'     => $this->message,
            'type'        => $this->type,
            'dismissible' => $this->dismissible,
            'persistent'  => $this->persistent,
            'screen'      => $this->screen,
            'capability'  => $this->capability,
            'classes'     => $this->classes,
        ];
    }

    /**
     * Create notice from array.
     *
     * @param array $data Notice data.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $notice = new self($data['message'] ?? '', $data['type'] ?? self::TYPE_INFO);

        if (isset($data['id'])) {
            $notice->setId($data['id']);
        }
        if (isset($data['dismissible'])) {
            $notice->setDismissible($data['dismissible']);
        }
        if (isset($data['persistent'])) {
            $notice->setPersistent($data['persistent']);
        }
        if (isset($data['screen'])) {
            $notice->setScreen($data['screen']);
        }
        if (isset($data['capability'])) {
            $notice->setCapability($data['capability']);
        }
        if (isset($data['classes'])) {
            $notice->addClasses($data['classes']);
        }

        return $notice;
    }
}
