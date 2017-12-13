<?php namespace PHPTracerWeaver\Scanner;

/** Scans for function name + body */
class FunctionBodyScanner implements ScannerInterface
{
    protected $current_class_scope;
    protected $name;
    /** @var int */
    protected $state = 0;

    public function accept(Token $token)
    {
        if ($token->isA(T_FUNCTION)) {
            $this->current_class_scope = $token->getDepth();
            $this->state = 1;
        } elseif (1 === $this->state && $token->isA(T_STRING)) {
            $this->name = $token->getText();
            $this->state = 2;
        } elseif (2 === $this->state && $token->getDepth() > $this->current_class_scope) {
            $this->state = 3;
        } elseif (3 === $this->state && $token->getDepth() === $this->current_class_scope) {
            $this->state = 0;
        }
    }

    public function isActive()
    {
        return $this->state > 2;
    }

    public function getName()
    {
        return $this->name;
    }
}
