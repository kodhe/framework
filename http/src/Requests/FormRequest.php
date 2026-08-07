<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Requests;

use Kodhe\Framework\Http\Contracts\RequestInterface;

/**
 * Form Request - Base class for form validation requests
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class FormRequest
{
    /**
     * The request instance
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * The application instance
     *
     * @var mixed
     */
    protected $app;

    /**
     * Validation rules
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Validation messages
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Validation attributes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The validator instance
     *
     * @var mixed
     */
    protected $validator;

    /**
     * Create a new FormRequest instance
     *
     * @param RequestInterface $request
     * @param mixed $app
     */
    public function __construct(RequestInterface $request, $app = null)
    {
        $this->request = $request;
        $this->app = $app;
    }

    /**
     * Get the request instance
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Set the request instance
     *
     * @param RequestInterface $request
     * @return $this
     */
    public function setRequest(RequestInterface $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get the application instance
     *
     * @return mixed
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Get validation rules
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Get validation messages
     *
     * @return array
     */
    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * Get custom attributes for variable names
     *
     * @return array
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the request
     *
     * @return array
     * @throws \Exception
     */
    public function validate(): array
    {
        // Check authorization
        if (!$this->authorize()) {
            throw new \Exception('Unauthorized action.', 403);
        }

        // Get validation service
        $validator = $this->getValidator();

        if (!$validator) {
            // If no validator, return all input
            return $this->request->all();
        }

        // Perform validation
        $validation = $validator->validate(
            $this->request->all(),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );

        return $validation;
    }

    /**
     * Get the validator instance
     *
     * @return mixed|null
     */
    protected function getValidator()
    {
        if ($this->validator) {
            return $this->validator;
        }

        if ($this->app && method_exists($this->app, 'get')) {
            if ($this->app->has('validation')) {
                $this->validator = $this->app->get('validation');
                return $this->validator;
            }
        }

        return null;
    }

    /**
     * Get all input data
     *
     * @return array
     */
    public function all(): array
    {
        return $this->request->all();
    }

    /**
     * Get a specific input value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        return $this->request->input($key, $default);
    }

    /**
     * Check if the request has a specific input
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->request->has($key);
    }

    /**
     * Get only specified input
     *
     * @param array $keys
     * @return array
     */
    public function only(array $keys): array
    {
        return $this->request->only($keys);
    }

    /**
     * Get all input except specified keys
     *
     * @param array $keys
     * @return array
     */
    public function except(array $keys): array
    {
        return $this->request->except($keys);
    }

    /**
     * Flash the input to the session
     *
     * @return void
     */
    public function flash(): void
    {
        if ($this->app && method_exists($this->app, 'get')) {
            if ($this->app->has('session')) {
                $session = $this->app->get('session');
                $session->setFlash('_old_input', $this->request->all());
            }
        }
    }

    /**
     * Flash only specified keys to the session
     *
     * @param array $keys
     * @return void
     */
    public function flashOnly(array $keys): void
    {
        $this->flashExcept(array_diff(array_keys($this->all()), $keys));
    }

    /**
     * Flash all keys except specified to the session
     *
     * @param array $keys
     * @return void
     */
    public function flashExcept(array $keys): void
    {
        if ($this->app && method_exists($this->app, 'get')) {
            if ($this->app->has('session')) {
                $session = $this->app->get('session');
                $session->setFlash('_old_input', $this->except($keys));
            }
        }
    }
}
