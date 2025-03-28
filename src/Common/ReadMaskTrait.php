<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common;

use Google\Protobuf\FieldMask;

trait ReadMaskTrait
{
    /**
     * @return array<string>
     */
    public function getFields(): array
    {
        if ($this->object->getReadMask() === null) {
            return [];
        }

        /** @phpstan-var array<string> */
        return iterator_to_array($this->object->getReadMask()->getPaths());
    }

    public function withFields(string ...$fields): self
    {
        $fieldMask = new FieldMask();
        $fieldMask->setPaths($fields);

        $this->object->setReadMask($fieldMask);

        return $this;
    }
}
