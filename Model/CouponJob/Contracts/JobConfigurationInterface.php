<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob\Contracts;

use Dotdigitalgroup\Email\Model\Validator\ValidatableStructInterface;

interface JobConfigurationInterface extends ValidatableStructInterface
{
    public const BATCH_SIZE = 'batch_size';
    public const FILTER_TYPE = 'filter_type';
    public const FILTER_ID = 'filter_id';
    public const CODE_FORMAT = 'code_format';
    public const CODE_LENGTH = 'code_length';
    public const CODE_DASH = 'code_dash';
    public const CODE_PREFIX = 'code_prefix';
    public const CODE_SUFFIX = 'code_suffix';
    public const DATA_FIELD = 'data_field';
    public const EXPIRES_AT = 'expires_at';
    public const VALIDATION_PATTERN = [
        self::BATCH_SIZE => 'required|isInt|min:1',
        self::FILTER_TYPE => 'required|isString|enum:LIST,SEGMENT',
        self::FILTER_ID => 'required|isInt',
        self::CODE_FORMAT => 'required|isString',
        self::CODE_LENGTH => 'isInt|min:1',
        self::CODE_DASH => 'isInt|min:0',
        self::CODE_PREFIX => 'isString',
        self::CODE_SUFFIX => 'isString',
        self::DATA_FIELD => 'required|isString',
        self::EXPIRES_AT => 'nullable|dateFormat:Y-m-d'
    ];
}
