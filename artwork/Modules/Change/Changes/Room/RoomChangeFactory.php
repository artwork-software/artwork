<?php

namespace Artwork\Modules\Change\Changes\Room;

use Artwork\Modules\Change\Interfaces\RoomChange;

class RoomChangeFactory
{
    public function __construct(
        private readonly MemberChange $memberChange,
        private readonly DescriptionChange $descriptionChange,
        private readonly AdjoiningRoomChange $adjoiningRoomChange,
        private readonly AttributeChange $attributeChange,
        private readonly NameChange $nameChange,
        private readonly CategoryChange $categoryChange,
        private readonly TemporaryChange $temporaryChange
    ) {
    }

    /**
     * @return RoomChange[]
     */
    public function getRoomChangesAll(): array
    {
        return [
            $this->memberChange,
            $this->descriptionChange,
            $this->adjoiningRoomChange,
            $this->attributeChange,
            $this->nameChange,
            $this->categoryChange,
            $this->temporaryChange
        ];
    }
}
