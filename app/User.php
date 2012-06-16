<?php
/**
 * @author nfx
 */

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\HttpFoundation\Request;


class User {
    public $id;
    public $firstName;
    public $lastName;
    public $email;

    public function bindRequest(Request $request)
    {
        $selfReflection = new ReflectionClass(__CLASS__);
        foreach($selfReflection->getProperties() as $property) {
            /** @var $property \ReflectionProperty */
            $property->setValue($this, $request->get($property->getName()));
        }
    }

    public function bindDocument(array $document)
    {
        $selfReflection = new ReflectionClass(__CLASS__);
        foreach($selfReflection->getProperties() as $property) {
            /** @var $property \ReflectionProperty */
            if (isset($document[$property->getName()])) {
                $property->setValue($this, $document[$property->getName()]);
            }
        }

        $this->id = $document['_id'].'';
    }

    public function toArray()
    {
        $arrayCopy = get_object_vars($this);
        unset($arrayCopy['id']);
        return array_filter($arrayCopy);
    }

    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('firstName', new Constraints\NotNull());
        $metadata->addPropertyConstraint('firstName', new Constraints\NotBlank());
        $metadata->addPropertyConstraint('lastName', new Constraints\NotNull());
        $metadata->addPropertyConstraint('lastName', new Constraints\NotBlank());
        $metadata->addPropertyConstraint('email', new Constraints\NotNull());
        $metadata->addPropertyConstraint('email', new Constraints\NotBlank());
        $metadata->addPropertyConstraint('email', new Constraints\Email());
    }
}