<?php

namespace Kodhe\Framework\Exceptions\Database;

use Kodhe\Framework\Exceptions\Http\NotFoundException;

class RecordNotFoundException extends NotFoundException
{
    protected string $errorCode = 'RECORD_NOT_FOUND';
    protected int $httpStatusCode = 404;
    protected string $logLevel = 'info';

    /**
     * @var string|null Model/table name
     */
    protected ?string $model = null;

    /**
     * @var mixed Record identifier
     */
    protected $identifier = null;

    /**
     * @var array Query criteria
     */
    protected array $criteria = [];

    public function __construct(string $model = '', $identifier = null, array $criteria = [])
    {
        $message = 'Record not found';
        $data = [];
        
        if ($model) {
            $message = "{$model} record not found";
            $data['model'] = $model;
            $this->model = $model;
        }
        
        if ($identifier !== null) {
            $message .= " with identifier: {$identifier}";
            $data['identifier'] = $identifier;
            $this->identifier = $identifier;
        }
        
        if (!empty($criteria)) {
            $data['criteria'] = $criteria;
            $this->criteria = $criteria;
        }
        
        parent::__construct($message);
        $this->withData($data);
    }

    /**
     * Get model/table name
     *
     * @return string|null
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * Get record identifier
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Get query criteria
     *
     * @return array
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public static function byId(string $model, $id): self
    {
        return new self($model, $id, ['id' => $id]);
    }

    public static function byCriteria(string $model, array $criteria): self
    {
        return new self($model, null, $criteria);
    }
}