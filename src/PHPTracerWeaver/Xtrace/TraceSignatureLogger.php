<?php namespace PHPTracerWeaver\Xtrace;

use PHPTracerWeaver\Exceptions\Exception;
use PHPTracerWeaver\Signature\Signatures;

class TraceSignatureLogger
{
    /** @var Signatures */
    protected $signatures;
    /** @var string[] */
    protected $typeMapping = [
        'TRUE'            => 'bool',
        'FALSE'           => 'false', // Falsable or tbd. bool
        'NULL'            => 'null',
        'void'            => 'void',
        '???'             => '???',
        '*uninitialized*' => '???',
    ];

    /**
     * @param Signatures $signatures
     */
    public function __construct(Signatures $signatures)
    {
        $this->signatures = $signatures;
    }

    /**
     * @param string[] $trace
     *
     * @return void
     */
    public function log(array $trace): void
    {
        $sig = $this->signatures->get($trace['function']);
        $sig->blend(
            $this->parseArguments($trace['arguments']),
            $this->parseType($trace['returnValue'])
        );
    }

    /**
     * @param string[] $arguments
     *
     * @return string[]
     */
    public function parseArguments(array $arguments): array
    {
        $types = [];
        foreach ($arguments as $type) {
            $types[] = $this->parseType($type);
        }

        return $types;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function parseType(string $type): string
    {
        if (isset($this->typeMapping[$type])) {
            return $this->typeMapping[$type];
        }

        $typeTransforms = ['~^(array) \(.*\)$~', '~^class (\S+)~', '~^(resource)\(\d+\)~'];
        foreach ($typeTransforms as $regex) {
            if (preg_match($regex, $type, $match)) {
                return $match[1];
            }
        }
        if (preg_match('~^-?\d+$~', $type) || preg_match('~^-?\'\d+\'$~', $type)) {
            return 'int';
        }
        if (preg_match('~^-?\d+\.\d+(?:E\d+)?$~', $type) || preg_match('~^-?\'\d+\.\d+(?:E\d+)?\'$~', $type)) {
            return 'float';
        }

        if ("'" === substr($type, 0, 1)) {
            return 'string';
        }

        throw new Exception('Unknown return type: ' . $type);
    }
}
