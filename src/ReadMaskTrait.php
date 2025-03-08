<?php

namespace Cbws\Sdk;

use Google\Protobuf\FieldMask;

trait ReadMaskTrait
{
    /**
     * @return ?string[]
     */
    public function getFields(): ?array
    {
        if (is_null($this->object->getReadMask())) {
            return null;
        }

        return iterator_to_array($this->object->getReadMask()->getPaths());
    }

    /**
     * @param string ...$fields
     */
    public function withFields(string ...$fields): self
    {
        $fieldMask = new FieldMask();
        $fieldMask->setPaths($fields);

        $this->object->setReadMask($fieldMask);

        return $this;
    }
}