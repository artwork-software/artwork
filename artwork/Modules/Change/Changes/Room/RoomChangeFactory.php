<?php

namespace Artwork\Modules\Change\Changes\Room;

use Artwork\Modules\Change\Interfaces\RoomChange;

readonly class RoomChangeFactory
{
    public function __construct(
        private MemberChange $memberChange,
        private DescriptionChange $descriptionChange,
        private AdjoiningRoomChange $adjoiningRoomChange,
        private AttributeChange $attributeChange,
        private NameChange $nameChange,
        private CategoryChange $categoryChange,
        private TemporaryChange $temporaryChange
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
