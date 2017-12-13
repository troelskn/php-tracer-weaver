<?php namespace PHPTracerWeaver\Transform;

use PHPTracerWeaver\Scanner\FunctionBodyScanner;
use PHPTracerWeaver\Scanner\FunctionParametersScanner;
use PHPTracerWeaver\Scanner\ModifiersScanner;
use PHPTracerWeaver\Scanner\Token;
use PHPTracerWeaver\Scanner\TokenBuffer;

class DocCommentEditorTransformer implements TransformerInterface
{
    protected $function_body_scanner;
    protected $modifiers_scanner;
    protected $parameters_scanner;
    protected $editor;
    protected $state = 0;
    protected $buffer;

    public function __construct(FunctionBodyScanner $function_body_scanner, ModifiersScanner $modifiers_scanner, FunctionParametersScanner $parameters_scanner, BufferEditorInterface $editor)
    {
        $this->function_body_scanner = $function_body_scanner;
        $this->modifiers_scanner = $modifiers_scanner;
        $this->parameters_scanner = $parameters_scanner;
        $this->editor = $editor;
        $this->buffer = new TokenBuffer();
    }

    public function accept(Token $token)
    {
        if ($token->isA(T_DOC_COMMENT)) {
            $this->state = 1;
            $this->raiseBuffer();
        } elseif (0 === $this->state && ($this->modifiers_scanner->isActive() || $token->isA(T_FUNCTION))) {
            $this->state = 1;
            $this->raiseBuffer();
        } elseif ($this->state > 0 && $this->function_body_scanner->isActive()) {
            $this->editor->editBuffer($this->buffer);
            $this->state = 0;
            $this->flushBuffers();
        } elseif ($token->isA(T_INTERFACE) || $token->isA(T_CLASS) || ($token->isA(T_VARIABLE) && !$this->parameters_scanner->isActive())) {
            $this->state = 0;
            $this->flushBuffers();
        }
        $this->buffer->append($token);
    }

    public function raiseBuffer()
    {
        $this->flushBuffers();
        $this->buffer = $this->buffer->raise();
    }

    public function flushBuffers()
    {
        while ($this->buffer->hasSuper()) {
            $this->buffer = $this->buffer->flush();
        }
    }

    public function getOutput()
    {
        $this->flushBuffers();

        return $this->buffer->toText();
    }
}